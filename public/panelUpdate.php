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
function qruqsp_dashboard_panelUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'panel_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Panel'),
        'panel_title'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Title'),
        'panel_sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'),
        'panel_ref'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Panel'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    if( isset($args['panel_title']) ) {
        $args['title'] = $args['panel_title'];
    }
    if( isset($args['panel_sequence']) ) {
        $args['title'] = $args['panel_sequence'];
    }

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'dashboard', 'private', 'checkAccess');
    $rc = qruqsp_dashboard_checkAccess($ciniki, $args['tnid'], 'qruqsp.dashboard.panelUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the current panel
    //
    $strsql = "SELECT id, title, sequence, panel_ref, settings "
        . "FROM qruqsp_dashboard_panels "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['panel_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.dashboard', 'panel');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.23', 'msg'=>'Unable to load panel', 'err'=>$rc['err']));
    }
    if( !isset($rc['panel']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.24', 'msg'=>'Unable to find requested panel'));
    }
    $existing_panel = $rc['panel'];
    $existing_settings = unserialize($rc['panel']['settings']);

    //
    // Load the panel
    //
    $panel_ref = isset($args['panel_ref']) ? $args['panel_ref'] : $existing_panel['panel_ref'];
    list($package, $module, $panel) = explode('.', $panel_ref);
    $rc = ciniki_core_loadMethod($ciniki, $package, $module, 'hooks', 'dashboardPanels');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.10', 'msg'=>'Unable to load panel', 'err'=>$rc['err']));
    }
    $fn = $rc['function_call'];
    $rc = $fn($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.16', 'msg'=>'Unable to load panel', 'err'=>$rc['err']));
    }
    if( !isset($rc['panels'][$panel_ref]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.16', 'msg'=>'Invalid panel'));
    }
    $panel = $rc['panels'][$panel_ref];

    //
    // Setup the settings
    //
    $settings = array();
    if( isset($panel['options']) ) {
        foreach($panel['options'] as $oid => $option) {
            if( isset($ciniki['request']['args'][$oid]) ) {
                $settings[$oid] = $ciniki['request']['args'][$oid];
            } elseif( $existing_settings[$oid] ) {
                $settings[$oid] = $existing_settings[$oid];
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
    // Update the Panel in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'qruqsp.dashboard.panel', $args['panel_id'], $args, 0x04);
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'qruqsp.dashboard.panel', 'object_id'=>$args['panel_id']));

    return array('stat'=>'ok');
}
?>
