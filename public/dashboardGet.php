<?php
//
// Description
// ===========
// This method will return all the information about an dashboards.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the dashboards is attached to.
// dashboard_id:          The ID of the dashboards to get the details for.
//
// Returns
// -------
//
function qruqsp_dashboard_dashboardGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'dashboard_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Dashboards'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'dashboard', 'private', 'checkAccess');
    $rc = qruqsp_dashboard_checkAccess($ciniki, $args['tnid'], 'qruqsp.dashboard.dashboardGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Dashboards
    //
    if( $args['dashboard_id'] == 0 ) {
        $dashboard = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'theme'=>'default',
            'password'=>'',
            'slideshow-mode'=>'auto',
            'slideshow-delay-seconds'=>'60',
            'slideshow-reset-seconds'=>'60',
        );
    }

    //
    // Get the details for an existing Dashboards
    //
    else {
        $strsql = "SELECT qruqsp_dashboards.id, "
            . "qruqsp_dashboards.name, "
            . "qruqsp_dashboards.permalink, "
            . "qruqsp_dashboards.theme, "
            . "qruqsp_dashboards.password, "
            . "qruqsp_dashboards.settings "
            . "FROM qruqsp_dashboards "
            . "WHERE qruqsp_dashboards.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND qruqsp_dashboards.id = '" . ciniki_core_dbQuote($ciniki, $args['dashboard_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.dashboard', array(
            array('container'=>'dashboards', 'fname'=>'id', 
                'fields'=>array('name', 'permalink', 'theme', 'password', 'settings'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.14', 'msg'=>'Dashboards not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['dashboards'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.15', 'msg'=>'Unable to find Dashboards'));
        }
        $dashboard = $rc['dashboards'][0];
        if( $rc['dashboards'][0]['settings'] != '' ) {
            $settings = unserialize($rc['dashboards'][0]['settings']);
            foreach($settings as $k => $v) {    
                $dashboard[$k] = $v;
            }
        }
        unset($dashboard['settings']);

        //
        // Get the list of panels
        //
        $strsql = "SELECT qruqsp_dashboard_panels.id, "
            . "qruqsp_dashboard_panels.title, "
            . "qruqsp_dashboard_panels.sequence, "
            . "qruqsp_dashboard_panels.numrows, "
            . "qruqsp_dashboard_panels.numcols, "
            . "qruqsp_dashboard_panels.settings "
            . "FROM qruqsp_dashboard_panels "
            . "WHERE qruqsp_dashboard_panels.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND dashboard_id = '" . ciniki_core_dbQuote($ciniki, $args['dashboard_id']) . "' "
            . "ORDER BY sequence "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.dashboard', array(
            array('container'=>'panels', 'fname'=>'id', 
                'fields'=>array('id', 'title', 'sequence', 'numrows', 'numcols', 'settings')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['panels']) ) {
            $dashboard['panels'] = $rc['panels'];
            $dashboard['panel_ids'] = array();
            foreach($dashboard['panels'] as $iid => $panel) {
                $dashboard['panel_ids'][] = $panel['id'];
            }
        } else {
            $dashboard['panels'] = array();
            $dashboard['panel_ids'] = array();
        }
    }

    return array('stat'=>'ok', 'dashboard'=>$dashboard);
}
?>
