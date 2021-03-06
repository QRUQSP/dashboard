<?php
//
// Description
// -----------
// This method searchs for a Panels for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Panel for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function qruqsp_dashboard_panelSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'dashboard', 'private', 'checkAccess');
    $rc = qruqsp_dashboard_checkAccess($ciniki, $args['tnid'], 'qruqsp.dashboard.panelSearch');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of panels
    //
    $strsql = "SELECT qruqsp_dashboard_panels.id, "
        . "qruqsp_dashboard_panels.title, "
        . "qruqsp_dashboard_panels.sequence "
        . "FROM qruqsp_dashboard_panels "
        . "WHERE qruqsp_dashboard_panels.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ("
            . "name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.dashboard', array(
        array('container'=>'panels', 'fname'=>'id', 
            'fields'=>array('id', 'title', 'sequence')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['panels']) ) {
        $panels = $rc['panels'];
        $panel_ids = array();
        foreach($panels as $iid => $panel) {
            $panel_ids[] = $panel['id'];
        }
    } else {
        $panels = array();
        $panel_ids = array();
    }

    return array('stat'=>'ok', 'panels'=>$panels, 'nplist'=>$panel_ids);
}
?>
