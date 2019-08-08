<?php
//
// Description
// -----------
// This method will return the list of Dashboardss for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Dashboards for.
//
// Returns
// -------
//
function qruqsp_dashboard_dashboardList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'dashboard', 'private', 'checkAccess');
    $rc = qruqsp_dashboard_checkAccess($ciniki, $args['tnid'], 'qruqsp.dashboard.dashboardList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of dashboards
    //
    $strsql = "SELECT qruqsp_dashboards.id, "
        . "qruqsp_dashboards.name, "
        . "qruqsp_dashboards.permalink, "
        . "qruqsp_dashboards.theme, "
        . "qruqsp_dashboards.password "
        . "FROM qruqsp_dashboards "
        . "WHERE qruqsp_dashboards.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.dashboard', array(
        array('container'=>'dashboards', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'theme', 'password')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['dashboards']) ) {
        $dashboards = $rc['dashboards'];
        $dashboard_ids = array();
        foreach($dashboards as $iid => $dashboard) {
            $dashboard_ids[] = $dashboard['id'];
        }
    } else {
        $dashboards = array();
        $dashboard_ids = array();
    }

    return array('stat'=>'ok', 'dashboards'=>$dashboards, 'nplist'=>$dashboard_ids);
}
?>
