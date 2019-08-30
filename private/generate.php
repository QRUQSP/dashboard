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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'dashboard', 'private', 'loadCell');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

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
    $dt = new DateTime('now', new DateTimezone($intl_timezone));
        
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
    // Check if this is a panel update request
    //
    $action = 'load';
    if( isset($_GET['update']) ) {
        $panel_ids = explode(',', $_GET['update']);    
        $action = 'update';
    }
    elseif( isset($args['action']) && $args['action'] == 'editui' ) {
        $action = 'editui';
        if( isset($args['panel_id']) && $args['panel_id'] != '' ) {
            $panel_ids = array($args['panel_id']);
        }
    }

    //
    // Load the panels and cells
    //
    $strsql = "SELECT panels.id, "
        . "panels.title, "
        . "panels.sequence, "
        . "panels.rows, "
        . "panels.cols, "
        . "panels.settings, "
        . "cells.id AS cell_id, "
        . "cells.row, "
        . "cells.col, "
        . "cells.rowspan, "
        . "cells.colspan, "
        . "cells.widget_ref, "
        . "cells.settings AS cell_settings "
        . "FROM qruqsp_dashboard_panels AS panels "
        . "LEFT JOIN qruqsp_dashboard_cells AS cells ON ("
            . "panels.id = cells.panel_id "
            . "AND cells.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE panels.dashboard_id = '" . ciniki_core_dbQuote($ciniki, $dashboard['id']) . "' "
        . "AND panels.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( ($action == 'update' || $action == 'editui') && count($panel_ids) > 0 ) {
        $strsql .= "AND panels.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $panel_ids) . ") ";
    }
    $strsql .= "ORDER BY panels.sequence, cells.row, cells.col "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.dashboard', array(
        array('container'=>'panels', 'fname'=>'id', 'fields'=>array('id', 'title', 'sequence', 'rows', 'cols', 'settings')),
        array('container'=>'cells', 'fname'=>'cell_id', 'fields'=>array('id'=>'cell_id', 'row', 'col', 'rowspan', 'colspan', 'widget_ref', 'settings'=>'cell_settings')),
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
        $dashboard['panels'][$pid]['data'] = array();
        if( isset($panel['cells']) ) {
            foreach($panel['cells'] as $cid => $cell) {
                $dashboard['panels'][$pid]['cells'][$cid]['settings'] = unserialize($cell['settings']);
                $dashboard['panels'][$pid]['cells'][$cid]['content'] = '';
                $dashboard['panels'][$pid]['cells'][$cid]['css'] = '';
                $dashboard['panels'][$pid]['cells'][$cid]['js'] = '';
                $dashboard['panels'][$pid]['cells'][$cid]['data'] = array();
            }
        }
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
    // Load the cell widget data
    //
    foreach($dashboard['panels'] as $pid => $panel) {
        if( isset($panel['cells']) ) {
            foreach($panel['cells'] as $cid => $cell) {
                if( isset($cell['widget_ref']) ) {
                    $rc = qruqsp_dashboard_loadCell($ciniki, $tnid, $action, $cell);
                    if( $rc['stat'] != 'ok' ) {
                        //
                        // If error, return dummy information so dashboard doesn't break
                        //
                        $dashboard['panels'][$pid]['cells'][$cid] = array(
                            'id' => $cell['id'],
                            'content' => '',
                            'css' => '',
                            'js' => '',
                            'data' => array(),
                            );
                    }
                    if( isset($rc['cell']) ) {
                        $dashboard['panels'][$pid]['cells'][$cid] = $rc['cell'];
                    }
                }
            }
        }
    }
    
    //
    // Load the panel content
    //
/*    foreach($dashboard['panels'] as $pid => $panel) {
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
                    return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.52', 'msg'=>'Panel', 'err'=>$rc['err']));
                }
                $dashboard['panels'][$pid] = $rc['panel'];
            }
        }
    }
*/
    //
    // Check if just the data should be sent back
    //
    if( $action == 'update' ) {
        $data = array();
        foreach($dashboard['panels'] as $pid => $panel) {
            if( isset($panel['cells']) ) {
                foreach($panel['cells'] as $cid => $cell) {
                    $data[$panel['id']][$cell['id']] = $cell['data'];
                }
            }
//            $data[$panel['id']] = $panel['data'];
        }
        return array('stat'=>'ok', 'data'=>$data, 'lastupdated'=>$dt->format('M d, Y H:i:s'));
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
//    $content .= '<link href="' . $dashboard['theme-url'] . '/fa5/css/fontawesome.png" />';
//    $content .= '<link href="' . $dashboard['theme-url'] . '/fa5/css/solid.png" />';
    $content .= '<title>' . $dashboard['name'] . '</title>';

    //
    // Include the stylesheets and javascript for the dashboard.
    // Currently included directly into script so less calls to pi and less caching issues.
    // In cloud instance this may need to change to be links to content so browser can cache.
    //
    if( file_exists($dashboard['theme-dir'] . '/style.css') ) {
//        $content .= '<link rel="stylesheet" type="text/css" href="' . $dashboard['theme-url'] . '/style.css" />';
        $content .= '<style>' . file_get_contents($dashboard['theme-dir'] . '/style.css') . '</style>';
    }
    if( file_exists($dashboard['theme-dir'] . '/theme.js') ) {
        $content .= '<script type="text/javascript">' . file_get_contents($dashboard['theme-dir'] . '/theme.js') . '</script>';
    }
   
    //
    // Setup sections of document
    //
    $css = '<style>';
    $js = '';
    $js_panels = array();
    $js_cells = array();
    $js_panel_sequence = "var db_panel_order = [";
    $htmlstart = '</head>';
    $html = '';
    $htmlend = '';
    if( count($dashboard['panels']) > 1 ) {
        $htmlstart .= '<body onresize="db_resize(); setTimeout(db_resize,250);"><div id="dbc" class="container" onclick="db_advance();">';
    } else {
        $htmlstart .= '<body onresize="db_resize(); setTimeout(db_resize,250);"><div id="dbc" class="container">';
    }

    //
    // Check for no panels
    //
    if( count($dashboard['panels']) < 1 ) {
        $htmlstart .= "<div class='nopanels'>This dashboard has not be setup</div>";
    }

    //
    // Add the panels
    //
    $display='block';
    foreach($dashboard['panels'] as $pid => $panel) {
        $js_panel_sequence .= $panel['id'] . ',';
        $update_cell_ids = array();
        //
        // Setup the grid that will form the html table
        //
        $grid = array();
        for($row = 1; $row <= $panel['rows']; $row++) {
            $grid[$row] = array();
            for($col = 1; $col <= $panel['cols']; $col++) {
                $grid[$row][$col] = array(
                    'type' => 'empty',
                    );
            }
        }
        //
        // Add the cells to the grid
        //
        if( isset($panel['cells']) ) {
            foreach($panel['cells'] as $cid => $cell) {
                if( !isset($cell['row']) || !isset($cell['col']) || !isset($grid[$cell['row']][$cell['col']]) ) {
                    continue;    
                }
                if( isset($cell['css']) && $cell['css'] != '' ) {
                    $css .= $cell['css'] . "\n"; 
                }
                if( isset($grid[$cell['row']][$cell['col']]['type']) && $grid[$cell['row']][$cell['col']]['type'] == 'hidden' ) {
                    continue;
                }
                $grid[$cell['row']][$cell['col']]['type'] = 'widget';
                $grid[$cell['row']][$cell['col']]['cid'] = $cid;
                $js_cells[$cell['id']] = array(
                    'id' => $cell['id'],
                    'row' => $cell['row'],
                    'col' => $cell['col'],
                    'rowspan' => $cell['rowspan'],
                    'colspan' => $cell['colspan'],
                    );
                $panel['data'][$cell['id']] = (isset($cell['data']) ? $cell['data'] : array());

                // 
                // Mark empty cells as result of col/rowspans
                // 
                if( $cell['rowspan'] > 1 || $cell['colspan'] > 1 ) {
                    for($row = $cell['row']; $row < $cell['row'] + $cell['rowspan']; $row++) {
                        for($col = $cell['col']; $col < $cell['col'] + $cell['colspan']; $col++) {
                            // Skip first cell as it's the actual cell
                            if( $col == $cell['col'] && $row == $cell['row'] ) {
                                continue;
                            }
                            if( isset($grid[$row][$col]['type']) ) {
                                $grid[$row][$col]['type'] = 'hidden';
                            }
                        }
                    }
                }

                //
                // Add javascript
                //
                if( isset($cell['js']) ) {
                    foreach($cell['js'] as $name => $func) {
                        $js .= "db_cells[{$cell['id']}]['{$name}'] = " . $func;
                        if( $name == 'update' ) { 
                            $update_cell_ids[] = $cell['id'];
                        }
                    }
                }
            }
        }
        //
        // Setup the html for the panel and table inside
        //
        $html .= "<div id='dbpanel-{$panel['id']}' class='dbpanel' display='{$display};'>"
            . "<table class='dbpanel dbpanel-{$panel['id']}'><tbody>";
        for($row = 1; $row <= $panel['rows']; $row++) {
            // Add spacer row
            if( $row == 1 ) {   
                $html .= "<tr class='spacing'><td></td>";
                for($col = 1; $col <= $panel['cols']; $col++) {
                    $html .= "<td></td>";
                }
                $html .= "</tr>";
            }
            //
            // $rowstart is used once, then reset to blank on each row
            // row is only added if one or more cells are visible
            //
            $rowstart = "<tr><td class='spacing'></td>";
            for($col = 1; $col <= $panel['cols']; $col++) {
                if( $grid[$row][$col]['type'] == 'hidden' ) {
                    continue;
                }
                if( isset($grid[$row][$col]['cid']) ) {
                    $cell = $panel['cells'][$grid[$row][$col]['cid']];
                    $class = '';
                    if( $cell['colspan'] > 1 ) {
                        $class = 'w' . $cell['colspan'];
                    }
                    if( $cell['rowspan'] > 1 ) {
                        $class .= ($class != '' ? ' ' : '') . 'h' . $cell['rowspan'];
                    }
                    $html .= $rowstart . "<td"
                        . ($class != '' ? " class='" . $class . "'" : '')
                        . ($cell['rowspan'] > 1 ? " rowspan='" . $cell['rowspan'] . "'" : '')
                        . ($cell['colspan'] > 1 ? " colspan='" . $cell['colspan'] . "'" : '')
                        . ">";
                    $rowstart = '';
                    if( isset($action) && $action == 'editui' ) {
                        $html .= "<div draggable='true' id='widget-{$cell['id']}' class='widget' "
                            . "ondragstart='M.qruqsp_dashboard_main.panel.dragStart(event,{$cell['id']});' "
                            . "onclick='M.qruqsp_dashboard_main.panel.editCell({$cell['id']});'>";
                        $html .= print_r($cell['content'], true);
                        $html .= "</div>";
                    } elseif( isset($cell['content']) ) {
                        $html .= "<div id='widget-{$cell['id']}' class='widget'>";
                        $html .= $cell['content'];
                        $html .= "</div>";
                    }
                    $html .= "</td>";
                } else {
                    if( isset($action) && $action == 'editui' ) {
                        $html .= $rowstart . "<td ondrop='M.qruqsp_dashboard_main.panel.dropCell(event,{$row},{$col});' "
                            . "ondragover='this.classList.add(\"over\");' "
                            . "ondragleave='this.classList.remove(\"over\");' "
                            . "onclick='M.qruqsp_dashboard_main.panel.addCell({$row},{$col});'><div class='empty'></div></td>";
                    } else {
                        $html .= $rowstart . "<td></td>";
                    }
                    $rowstart = '';
                }
            }
            if( $rowstart == '' ) {
                $html .= "</tr>";
            } else {
                $html .= $rowstart . "</tr>";
            }
        }
        $html .= '</tbody></table></div>';
        //
        // Add the panel to the array of panels
        //
        $js_panels[$panel['id']] = array(
            'id' => $panel['id'],
            'title' => $panel['title'],
            'sequence' => $panel['sequence'],
            'rows' => $panel['rows'],
            'cols' => $panel['cols'],
            'settings' => $panel['settings'],
            'data' => $panel['data'],
            );
        if( count($update_cell_ids) > 0 ) {
            $js .= "db_panels[{$panel['id']}]['update'] = function(data) {";
            foreach($update_cell_ids as $cell_id) {
                $js .= "db_cells[{$cell_id}].update(data[{$cell_id}]);";
            }
            $js .= "}; ";
        }
        $display = 'none';
    }

    $css .= "</style>\n";
    $css .= "<style id='sizing'></style>";
    $js_panel_sequence .= '];';
    $js = "<script type='text/javascript'>"
        . "var url='/dashboard" . ($permalink != '' ? '/' . $permalink : '') . "'; "
        . "var db_panels = " . json_encode($js_panels) . ";" 
        . "var db_cells = " . json_encode($js_cells) . ";" 
        . "var db_settings = " . json_encode($dashboard['settings']) . ";"
        . $js_panel_sequence 
        . $js 
        . "</script>";
    //
    // Dashboard must be included after db_panel array is setup
    //
    if( file_exists($dashboard['core-dir'] . '/dashboard.js') ) {
        $js .= '<script type="text/javascript">' . file_get_contents($dashboard['core-dir'] . '/dashboard.js') . '</script>';
    }
    $htmlend .= '</div><div id="lastupdated">' . $dt->format('M d, Y H:i:s') . '</div></body>'
        . '</html>';

    if( isset($action) && $action == 'editui' ) {
        return array('stat'=>'ok', 'panel' => $panel, 'html'=>$html);
    }

    return array('stat'=>'ok', 'html'=>$content . $css . $js . $htmlstart . $html . $htmlend);
}
?>
