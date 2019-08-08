<?php
//
// Description
// ===========
// This method will return all the information about an panel.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the panel is attached to.
// panel_id:          The ID of the panel to get the details for.
//
// Returns
// -------
//
function qruqsp_dashboard_panelGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'panel_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Panel'),
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
    $rc = qruqsp_dashboard_checkAccess($ciniki, $args['tnid'], 'qruqsp.dashboard.panelGet');
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
    // Return default for new Panel
    //
    if( $args['panel_id'] == 0 ) {
        $panel = array('id'=>0,
            'title'=>'',
            'sequence'=>'',
            'panel_ref'=>'',
            'settings'=>'',
        );
    }

    //
    // Get the details for an existing Panel
    //
    else {
        $strsql = "SELECT qruqsp_dashboard_panels.id, "
            . "qruqsp_dashboard_panels.title, "
            . "qruqsp_dashboard_panels.sequence, "
            . "qruqsp_dashboard_panels.panel_ref, "
            . "qruqsp_dashboard_panels.settings "
            . "FROM qruqsp_dashboard_panels "
            . "WHERE qruqsp_dashboard_panels.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND qruqsp_dashboard_panels.id = '" . ciniki_core_dbQuote($ciniki, $args['panel_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.dashboard', array(
            array('container'=>'panels', 'fname'=>'id', 
                'fields'=>array('panel_title'=>'title', 'panel_sequence'=>'sequence', 'panel_ref', 'settings'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.20', 'msg'=>'Panel not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['panels'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.21', 'msg'=>'Unable to find Panel'));
        }
        $panel = $rc['panels'][0];
        $panel['settings'] = unserialize($panel['settings']);
    }

    $rsp = array('stat'=>'ok', 'panel'=>$panel, 'panels'=>array());

    //
    // Get the list of available panels
    //
    foreach($ciniki['tenant']['modules'] as $module => $m) {
        list($pkg, $mod) = explode('.', $module);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'dashboardPanels');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $args['tnid'], array());
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.5', 'msg'=>'Error retrieving panels', 'err'=>$rc['err']));
            }
            if( isset($rc['panels']) ) {
                $rsp['panels'] = array_merge($rsp['panels'], $rc['panels']);
            }
        }
    }


    return $rsp;
}
?>
