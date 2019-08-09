<?php
//
// Description
// -----------
// This method will add a new panel for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Panel to.
//
// Returns
// -------
//
function qruqsp_dashboard_panelAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'dashboard_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Dashboard'),
        'title'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Title'),
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
        'panel_ref'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Panel'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'dashboard', 'private', 'checkAccess');
    $rc = qruqsp_dashboard_checkAccess($ciniki, $args['tnid'], 'qruqsp.dashboard.panelAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the panel
    //
    list($package, $module, $panel) = explode('.', $args['panel_ref']);
    $rc = ciniki_core_loadMethod($ciniki, $package, $module, 'hooks', 'dashboardPanels');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.10', 'msg'=>'Unable to load panel', 'err'=>$rc['err']));
    }
    $fn = $rc['function_call'];
    $rc = $fn($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.16', 'msg'=>'Unable to load panel', 'err'=>$rc['err']));
    }
    if( !isset($rc['panels'][$args['panel_ref']]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.16', 'msg'=>'Invalid panel'));
    }
    $panel = $rc['panels'][$args['panel_ref']];

    //
    // Setup the settings
    //
    $settings = array();
    if( isset($panel['options']) ) {
        foreach($panel['options'] as $oid => $option) {
            if( isset($ciniki['request']['args'][$oid]) ) {
                $settings[$oid] = $ciniki['request']['args'][$oid];
            } else {
            }
        }
    }
    $args['settings'] = serialize($settings); 

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'qruqsp.dashboard');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the panel to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'qruqsp.dashboard.panel', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.dashboard');
        return $rc;
    }
    $panel_id = $rc['id'];

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'qruqsp.dashboard');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'qruqsp', 'dashboard');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'qruqsp.dashboard.panel', 'object_id'=>$panel_id));

    return array('stat'=>'ok', 'id'=>$panel_id);
}
?>
