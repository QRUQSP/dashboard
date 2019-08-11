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
function qruqsp_dashboard_dashboardUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'dashboard_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Dashboards'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'permalink'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Permalink'),
        'theme'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Theme'),
        'password'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Password'),
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
    $rc = qruqsp_dashboard_checkAccess($ciniki, $args['tnid'], 'qruqsp.dashboard.dashboardUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($args['name']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, name, permalink "
            . "FROM qruqsp_dashboards "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['dashboard_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.dashboard', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.28', 'msg'=>'You already have an dashboards with this name, please choose another.'));
        }
    }

    //
    // Check for settings
    //
    $strsql = "SELECT id, settings "
        . "FROM qruqsp_dashboards "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['dashboard_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.dashboard', 'dashboard');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.25', 'msg'=>'Unable to load dashboard', 'err'=>$rc['err']));
    }
    if( !isset($rc['dashboard']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.26', 'msg'=>'Unable to find requested dashboard'));
    }
    $dashboard = $rc['dashboard'];
    
    $settings = unserialize($dashboard['settings']);
    // Array of allowed settings and default values
    $new_settings = $settings;
    $allowed_settings = array(
        'slideshow-mode' => 'auto',
        'slideshow-delay-seconds' => 60,
        'slideshow-reset-seconds' => 60,
        );
    $update_settings = 'no';
    foreach($allowed_settings as $setting => $default) {
        // Set with new value
        if( isset($ciniki['request']['args'][$setting]) ) {
            $new_settings[$setting] = $ciniki['request']['args'][$setting];
            $update_settings = 'yes';
        }
        // Add default if missing
        if( !isset($new_settings[$setting]) ) {
            $new_settings[$setting] = $default;
            $update_settings = 'yes';
        }
    }

    if( $update_settings == 'yes' ) {
        $args['settings'] = serialize($new_settings);
    }

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
    // Update the Dashboards in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'qruqsp.dashboard.dashboard', $args['dashboard_id'], $args, 0x04);
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'qruqsp.dashboard.dashboard', 'object_id'=>$args['dashboard_id']));

    return array('stat'=>'ok');
}
?>
