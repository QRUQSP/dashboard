<?php
//
// Description
// -----------
// This function will generate and output the dashboard for a tenant
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function qruqsp_dashboard_generate(&$ciniki, $tnid, $args) {

    //
    // Load the tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'tenantDetails');
    $rc = ciniki_tenants_hooks_tenantDetails($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.4', 'msg'=>'Unable to get tenant details', 'err'=>$rc['err']));
    }

    //
    // Check if a dashboard is specified
    //
    $permalink = '';
    if( isset($args['path'][0]) && $args['path'][0] != '' ) {
        $permalink = $args['path'][0]; 
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
        
    //
    // Load the dashboard
    //
    $strsql = "SELECT dashboards.id, "
        . "dashboards.name, "
        . "dashboards.permalink, "
        . "dashboards.theme, "
        . "dashboards.settings AS db_settings "
        . "FROM qruqsp_dashboards AS dashboards "
        . "WHERE dashboards.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( isset($args['dashboard_id']) && $args['dashboard_id'] > 0 ) {
        $strsql .= "AND dashboards.id = '" . ciniki_core_dbQuote($ciniki, $args['dashboard_id']) . "' ";
    } elseif( $permalink != '' ) {
        $strsql .= "AND dashboards.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' ";
    }
    $strsql .= "ORDER BY dashboards.id ";
    if( !isset($args['dashboard_id']) || $permalink == '' ) {
        $strsql .= "LIMIT 1 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.dashboard', array(
        array('container'=>'dashboards', 'fname'=>'id', 'fields'=>array('id', 'name', 'permalink', 'theme', 'settings'=>'db_settings')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.29', 'msg'=>'Unable to load dashboard', 'err'=>$rc['err']));
    }
    if( !isset($rc['dashboards'][0]) ) {
        if( !isset($args['dashboard_id']) && $permalink == 'default' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.7', 'msg'=>'No dashboards configured.'));
        }
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.27', 'msg'=>'Unable to find requested dashboard'));
    }
    $dashboard = $rc['dashboards'][0]; 
    if( $rc['dashboards'][0]['settings'] != '' ) {
        $dashboard['settings'] = unserialize($rc['dashboards'][0]['settings']);
    } else {    
        $dashboard['settings'] = array();
    }

    //
    // Load the panels
    //
    $strsql = "SELECT panels.id, "
        . "panels.title, "
        . "panels.sequence, "
        . "panels.panel_ref, "
        . "panels.settings "
        . "FROM qruqsp_dashboard_panels AS panels "
        . "WHERE panels.dashboard_id = '" . ciniki_core_dbQuote($ciniki, $dashboard['id']) . "' "
        . "AND panels.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY panels.sequence "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.dashboard', array(
        array('container'=>'panels', 'fname'=>'id', 'fields'=>array('id', 'title', 'sequence', 'panel_ref', 'settings')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.8', 'msg'=>'Unable to load dashboard panels', 'err'=>$rc['err']));
    }
    if( !isset($rc['panels']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.6', 'msg'=>'Unable to find dashboard panels'));
    }
    $dashboard['panels'] = $rc['panels'];

    //
    // Setup the panels
    //
    foreach($dashboard['panels'] as $pid => $panel) {
        $dashboard['panels'][$pid]['settings'] = unserialize($panel['settings']);
        $dashboard['panels'][$pid]['content'] = '';
        $dashboard['panels'][$pid]['css'] = '';
        $dashboard['panels'][$pid]['js'] = '';
        $dashboard['panels'][$pid]['data'] = array();
    }

    //
    // Setup databoard settings
    //
    if( $dashboard['theme'] == '' ) {
        $dashboard['theme'] = 'default';
    }
    $dashboard['cache-url'] = '/qruqsp-dashboard-cache';
    $dashboard['cache-dir'] = $ciniki['config']['qruqsp.core']['modules_dir'] . '/dashboard/cache';
    $dashboard['theme-url'] = '/qruqsp-dashboard-themes';
    $dashboard['theme-dir'] = $ciniki['config']['qruqsp.core']['modules_dir'] . '/dashboard/themes';

    //
    // Setup core dir for basic required files, assests, js
    //
    $dashboard['core-url'] = $dashboard['theme-url'] . '/_core';
    $dashboard['core-dir'] = $dashboard['theme-dir'] . '/_core';

    //
    // Check the theme exists, otherwise set to default theme
    //
    if( file_exists($dashboard['theme-dir'] . '/' . $dashboard['theme']) ) {
        $dashboard['theme-url'] .= '/' . $dashboard['theme'];
        $dashboard['theme-dir'] .= '/' . $dashboard['theme'];
    } else {
        $dashboard['theme-url'] .= '/default';
        $dashboard['theme-dir'] .= '/default';
    }

    //
    // Check if this is a panel update request
    //
    $action = 'load';
    if( isset($_GET['update']) ) {
        $panel_ids = explode(',', $_GET['update']);    
        $action = 'update';
    }

    //
    // Load the panel content
    //
    foreach($dashboard['panels'] as $pid => $panel) {
        $p = explode('.', $panel['panel_ref']);
        if( isset($p[1]) && $p[1] != '' && (!isset($panel_ids) || in_array($panel['id'], $panel_ids)) ) {
            $rc = ciniki_core_loadMethod($ciniki, $p[0], $p[1], 'hooks', 'dashboardPanel');
            if( $rc['stat'] == 'ok' ) {
                $fn = $rc['function_call'];
                $rc = $fn($ciniki, $tnid, array(
                    'action'=>$action, 
                    'panel'=>$panel,
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.9', 'msg'=>'Panel', 'err'=>$rc['err']));
                }
                $dashboard['panels'][$pid] = $rc['panel'];
            }
        }
    }

    //
    // Check if just the data should be sent back
    //
    if( $action == 'update' ) {
        $data = array();
        foreach($dashboard['panels'] as $pid => $panel) {
            $data[$panel['id']] = $panel['data'];
        }
        return array('stat'=>'ok', 'data'=>$data);
    }

    //
    // Generate the page content
    // 
    $content = '<!DOCTYPE html>'
        . '<html>'
        . '<head>';
    $content .= '<meta content="text/html:charset=UTF-8" http-equiv="Content-Type" />';
    $content .= '<meta content="UTF-8" http-equiv="encoding" />';
    $content .= '<meta name="apple-mobile-web-app-capable" content="yes" />';
    $content .= '<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />';
    $content .= '<link rel="apple-touch-icon" href="' . $dashboard['theme-url'] . '/icon.png" />';
    $content .= '<link href="' . $dashboard['theme-url'] . '/fa5/css/fontawesome.png" />';
    $content .= '<link href="' . $dashboard['theme-url'] . '/fa5/css/solid.png" />';
    $content .= '<title>' . $dashboard['name'] . '</title>';

    //
    // Include the stylesheets and javascript for the dashboard
    //
    if( file_exists($dashboard['theme-dir'] . '/style.css') ) {
        $content .= '<link rel="stylesheet" type="text/css" href="' . $dashboard['theme-url'] . '/style.css" />';
    }
    if( file_exists($dashboard['theme-dir'] . '/theme.js') ) {
        $content .= '<script type="text/javascript" src="' . $dashboard['theme-url'] . '/theme.js"></script>';
    }
   
    //
    // Setup sections of document
    //
    $css = '<style>';
    $js = '';
    $js_panels = array();
    $js_panel_sequence = "var db_panel_order = [";
    $html = '</head>';
    if( isset($dashboard['settings']['slideshow-mode']) && $dashboard['settings']['slideshow-mode'] == 'manual' 
        && count($dashboard['panels']) > 1 
        ) {
        $html .= '<body><div class="container" onclick="db_advance();">';
    } else {
        $html .= '<body><div class="container">';
    }

    $dt = new DateTime('now', new DateTimezone($intl_timezone));

    //
    // Check for no panels
    //
    if( count($dashboard['panels']) < 1 ) {
        $html .= "<div class='nopanels'>This dashboard has not be setup</div>";
    }

    //
    // Add the panels
    //
    $display='block';
    foreach($dashboard['panels'] as $pid => $panel) {
        $js_panel_sequence .= $panel['id'] . ',';
        if( isset($panel['css']) && $panel['css'] != '' ) {
            $css .= $panel['css'] . "\n"; 
        }
        // Add javascript function
        if( isset($panel['js']) ) {
            foreach($panel['js'] as $name => $func) {
                $js .= "db_panels[{$panel['id']}]['{$name}'] = " . $func;
            }
        }
        if( isset($panel['content']) && $panel['content'] != '' ) {
            $html .= "<div id='panel-{$panel['id']}' class='panel' display='{$display};'>";
            $html .= $panel['content'];
            $html .= '</div>';
        }
        //
        // Add the panel to the array of panels
        //
        $js_panels[$panel['id']] = array(
            'id' => $panel['id'],
            'title' => $panel['title'],
            'sequence' => $panel['sequence'],
            'panel_ref' => $panel['panel_ref'],
            'settings' => $panel['settings'],
            'data' => $panel['data'],
            );
        $display = 'none';
    }

    $css .= "</style>\n";
    $js_panel_sequence .= '];';
    $js = "<script type='text/javascript'>"
        . "var url='/dashboard" . ($permalink != '' ? '/' . $permalink : '') . "'; "
        . "var db_panels = " . json_encode($js_panels) . ";" 
        . "var db_settings = " . json_encode($dashboard['settings']) . ";"
        . $js_panel_sequence 
        . $js 
        . "</script>";
    //
    // Dashboard must be included after db_panel array is setup
    //
    if( file_exists($dashboard['core-dir'] . '/dashboard.js') ) {
        $js .= '<script type="text/javascript" src="' . $dashboard['core-url'] . '/dashboard.js"></script>';
    }
    $html .= '</div></body>'
        . '</html>';

    return array('stat'=>'ok', 'html'=>$content . $css . $js . $html);
}
?>
