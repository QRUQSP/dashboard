<?php
//
// Description
// ===========
// This method will return all the information about an cell.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the cell is attached to.
// cell_id:          The ID of the cell to get the details for.
//
// Returns
// -------
//
function qruqsp_dashboard_cellGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'cell_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Cell'),
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
    $rc = qruqsp_dashboard_checkAccess($ciniki, $args['tnid'], 'qruqsp.dashboard.cellGet');
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
    // Return default for new Cell
    //
    if( $args['cell_id'] == 0 ) {
        //
        // FIXME: Get next available cell based on rows and cols in panel
        //
        $cell = array('id'=>0,
            'panel_id'=>'',
            'row'=>'1',
            'col'=>'1',
            'rowspan'=>'1',
            'colspan'=>'1',
            'widget_ref'=>'',
            'settings'=>'',
        );
    }

    //
    // Get the details for an existing Cell
    //
    else {
        $strsql = "SELECT qruqsp_dashboard_cells.id, "
            . "qruqsp_dashboard_cells.panel_id, "
            . "qruqsp_dashboard_cells.row, "
            . "qruqsp_dashboard_cells.col, "
            . "qruqsp_dashboard_cells.rowspan, "
            . "qruqsp_dashboard_cells.colspan, "
            . "qruqsp_dashboard_cells.widget_ref, "
            . "qruqsp_dashboard_cells.settings "
            . "FROM qruqsp_dashboard_cells "
            . "WHERE qruqsp_dashboard_cells.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND qruqsp_dashboard_cells.id = '" . ciniki_core_dbQuote($ciniki, $args['cell_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.dashboard', array(
            array('container'=>'cells', 'fname'=>'id', 
                'fields'=>array('panel_id', 'row', 'col', 'rowspan', 'colspan', 'widget_ref', 'settings'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.50', 'msg'=>'Cell not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['cells'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.38', 'msg'=>'Unable to find Cell'));
        }
        $cell = $rc['cells'][0];
        $cell['settings'] = unserialize($cell['settings']);
    }

    $rsp = array('stat'=>'ok', 'cell'=>$cell, 'widgets'=>array());

    //
    // Load the widget list
    //
    foreach($ciniki['tenant']['modules'] as $module => $m) {
        list($pkg, $mod) = explode('.', $module);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'dashboardWidgets');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $args['tnid'], array());
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.5', 'msg'=>'Error retrieving widgets', 'err'=>$rc['err']));
            }
            if( isset($rc['widgets']) ) {
                $rsp['widgets'] = array_merge($rsp['widgets'], $rc['widgets']);
            }
        }
    }
    
    //
    // Set the default widget as the first one in the list
    //
    if( $args['cell_id'] == 0 ) {
        error_log('test');
        reset($rsp['widgets']);
        $rsp['cell']['widget_ref'] = key($rsp['widgets']);
    }

    return $rsp;
}
?>
