<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_dashboard_cellUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'cell_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Cell'),
        'panel_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Panel'),
        'row'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Row'),
        'col'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Column'),
        'rowspan'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Row Span'),
        'colspan'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Column Span'),
        'widget_ref'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Widget Reference'),
        'settings'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Settings'),
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
    $rc = qruqsp_dashboard_checkAccess($ciniki, $args['tnid'], 'qruqsp.dashboard.cellUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the current cell
    //
    $strsql = "SELECT id, row, col, widget_ref, settings, cache "
        . "FROM qruqsp_dashboard_cells "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['cell_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.dashboard', 'cell');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.41', 'msg'=>'Unable to load cell', 'err'=>$rc['err']));
    }
    if( !isset($rc['cell']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.24', 'msg'=>'Unable to find requested cell'));
    }
    $existing_cell = $rc['cell'];
    $existing_settings = unserialize($rc['cell']['settings']);

    //
    // Load the widget
    //
    $widget_ref = isset($args['widget_ref']) ? $args['widget_ref'] : $existing_cell['widget_ref'];
    list($package, $module, $widget) = explode('.', $widget_ref);
    $rc = ciniki_core_loadMethod($ciniki, $package, $module, 'hooks', 'dashboardWidgets');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.40', 'msg'=>'Unable to load widget', 'err'=>$rc['err']));
    }
    $fn = $rc['function_call'];
    $rc = $fn($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.46', 'msg'=>'Unable to load widget', 'err'=>$rc['err']));
    }
    if( !isset($rc['widgets'][$widget_ref]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.45', 'msg'=>'Invalid widget'));
    }
    $widget = $rc['widgets'][$widget_ref];

    //
    // Setup the settings
    //
    $settings = array();
    if( isset($widget['options']) ) {
        foreach($widget['options'] as $oid => $option) {
            if( isset($ciniki['request']['args'][$oid]) ) {
                $settings[$oid] = $ciniki['request']['args'][$oid];
            } elseif( isset($existing_settings[$oid]) ) {
                $settings[$oid] = $existing_settings[$oid];
            }
        }
    }
    $args['settings'] = serialize($settings); 

    //
    // Clear the cache whenever settings are changed so the calendar files are reloaded
    //
    $args['cache'] = '';

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
    // Update the Cell in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'qruqsp.dashboard.cell', $args['cell_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'qruqsp.dashboard');
        return $rc;
    }

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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'qruqsp.dashboard.cell', 'object_id'=>$args['cell_id']));

    return array('stat'=>'ok');
}
?>
