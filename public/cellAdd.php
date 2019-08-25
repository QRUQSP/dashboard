<?php
//
// Description
// -----------
// This method will add a new cell for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Cell to.
//
// Returns
// -------
//
function qruqsp_dashboard_cellAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'panel_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Panel'),
        'row'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Row'),
        'col'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Column'),
        'rowspan'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Row Span'),
        'colspan'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Column Span'),
        'widget_ref'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Widget Reference'),
        'settings'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Settings'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'dashboard', 'private', 'checkAccess');
    $rc = qruqsp_dashboard_checkAccess($ciniki, $args['tnid'], 'qruqsp.dashboard.cellAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the widget
    //
    list($package, $module, $widget) = explode('.', $args['widget_ref']);
    $rc = ciniki_core_loadMethod($ciniki, $package, $module, 'hooks', 'dashboardWidgets');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.30', 'msg'=>'Unable to load widget', 'err'=>$rc['err']));
    }
    $fn = $rc['function_call'];
    $rc = $fn($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.49', 'msg'=>'Unable to load widget', 'err'=>$rc['err']));
    }
    if( !isset($rc['widgets'][$args['widget_ref']]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.48', 'msg'=>'Invalid widget'));
    }
    $widget = $rc['widgets'][$args['widget_ref']];

    //
    // Setup the settings
    //
    $settings = array();
    if( isset($widget['options']) ) {
        foreach($widget['options'] as $oid => $option) {
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
    // Add the cell to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'qruqsp.dashboard.cell', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.dashboard');
        return $rc;
    }
    $cell_id = $rc['id'];

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

    return array('stat'=>'ok', 'id'=>$cell_id);
}
?>
