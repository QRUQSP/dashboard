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
        'dashboard_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Dashboard'),
        'generate'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Generate Type'),
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
        $seq = 1;
        if( isset($args['dashboard_id']) && $args['dashboard_id'] > 0 ) {
            //
            // Get the next sequence number
            //
            $strsql = "SELECT MAX(sequence) AS num "
                . "FROM qruqsp_dashboard_panels "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND dashboard_id = '" . ciniki_core_dbQuote($ciniki, $args['dashboard_id']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.dashboard','item');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $seq = (isset($rc['item']['num']) ? $rc['item']['num'] + 1 : 1);
        }
        $panel = array('id'=>0,
            'title'=>'',
            'sequence'=>$seq,
            'numrows'=>'2',
            'numcols'=>'3',
            'settings'=>array(
                ),
        );
    }

    //
    // Generate the panel for editing
    //
    elseif( isset($args['generate']) && $args['generate'] == 'editui' ) {
        $strsql = "SELECT qruqsp_dashboard_panels.id, "
            . "qruqsp_dashboard_panels.dashboard_id, "
            . "qruqsp_dashboard_panels.title, "
            . "qruqsp_dashboard_panels.sequence, "
            . "qruqsp_dashboard_panels.numrows, "
            . "qruqsp_dashboard_panels.numcols, "
            . "qruqsp_dashboard_panels.settings "
            . "FROM qruqsp_dashboard_panels "
            . "WHERE qruqsp_dashboard_panels.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND qruqsp_dashboard_panels.id = '" . ciniki_core_dbQuote($ciniki, $args['panel_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.dashboard', array(
            array('container'=>'panels', 'fname'=>'id', 
                'fields'=>array('dashboard_id', 'title', 'sequence', 'numrows', 'numcols', 'settings'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.58', 'msg'=>'Panel not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['panels'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.59', 'msg'=>'Unable to find Panel'));
        }
        $panel = $rc['panels'][0];

        ciniki_core_loadMethod($ciniki, 'qruqsp', 'dashboard', 'private', 'generate');
        $rc = qruqsp_dashboard_generate($ciniki, $args['tnid'], array(
            'action' => 'editui',
            'dashboard_id' => $panel['dashboard_id'],
            'panel_id' => $args['panel_id'],
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.56', 'msg'=>'', 'err'=>$rc['err']));
        }

        return $rc;
    }

    //
    // Get the details for an existing Panel
    //
    else {
        $strsql = "SELECT qruqsp_dashboard_panels.id, "
            . "qruqsp_dashboard_panels.title, "
            . "qruqsp_dashboard_panels.sequence, "
            . "qruqsp_dashboard_panels.numrows, "
            . "qruqsp_dashboard_panels.numcols, "
            . "qruqsp_dashboard_panels.settings "
            . "FROM qruqsp_dashboard_panels "
            . "WHERE qruqsp_dashboard_panels.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND qruqsp_dashboard_panels.id = '" . ciniki_core_dbQuote($ciniki, $args['panel_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.dashboard', array(
            array('container'=>'panels', 'fname'=>'id', 
                'fields'=>array('title', 'sequence', 'numrows', 'numcols', 'settings'),
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

        //
        // Get the list of widgets
        //
        $strsql = "SELECT cells.id, "
            . "cells.panel_id, "
            . "cells.row, "
            . "cells.col, "
            . "cells.rowspan, "
            . "cells.colspan, "
            . "cells.widget_ref, "
            . "cells.settings "
            . "FROM qruqsp_dashboard_cells AS cells "
            . "WHERE cells.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND cells.panel_id = '" . ciniki_core_dbQuote($ciniki, $args['panel_id']) . "' "
            . "ORDER BY cells.row, cells.col "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'qruqsp.dashboard', array(
            array('container'=>'cells', 'fname'=>'id', 
                'fields'=>array('id', 'panel_id', 'row', 'col', 'rowspan', 'colspan', 'widget_ref', 'settings'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.37', 'msg'=>'Cells not found', 'err'=>$rc['err']));
        }
        $panel['cells'] = isset($rc['cells']) ? $rc['cells'] : array();

        foreach($panel['cells'] as $cid => $cell) {
            $panel['cells'][$cid]['name'] = '';
            $panel['cells'][$cid]['settings'] = unserialize($cell['settings']);
            if( isset($panel['cells'][$cid]['settings']['name']) ) {
                $panel['cells'][$cid]['name'] = $panel['cells'][$cid]['settings']['name'];
            }
            if( $panel['cells'][$cid]['name'] == '' && isset($panel['cells'][$cid]['settings']['title']) ) {
                $panel['cells'][$cid]['name'] = $panel['cells'][$cid]['settings']['title'];
            }
            if( $panel['cells'][$cid]['name'] == '' && isset($panel['cells'][$cid]['settings']['label']) ) {
                $panel['cells'][$cid]['name'] = $panel['cells'][$cid]['settings']['label'];
            }
            if( $panel['cells'][$cid]['name'] == '' && $cell['widget_ref'] != '' ) {
                list($pkg, $mod, $widget) = explode('.', $cell['widget_ref']);
                $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'dashboardWidgets');
                if( $rc['stat'] == 'ok' ) {
                    $fn = $rc['function_call'];
                    $rc = $fn($ciniki, $args['tnid'], array());
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.39', 'msg'=>'Error retrieving widgets', 'err'=>$rc['err']));
                    }
                    if( isset($rc['widgets'][$cell['widget_ref']]['name']) ) {
                        $panel['cells'][$cid]['name'] = $rc['widgets'][$cell['widget_ref']]['name'];
                    }
                }
            }
        }
    }

    $rsp = array('stat'=>'ok', 'panel'=>$panel);

    //
    // Get the list of available panels
    //
/*    foreach($ciniki['tenant']['modules'] as $module => $m) {
        list($pkg, $mod) = explode('.', $module);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'dashboardPanels');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $args['tnid'], array());
            if( $rc['stat'] != 'ok' ) { return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.32', 'msg'=>'Error retrieving panels', 'err'=>$rc['err'])); }
            if( isset($rc['panels']) ) {
                $rsp['panels'] = array_merge($rsp['panels'], $rc['panels']);
            }
        }
    }
    
    //
    // Set the default panel as the first one in the list
    //
    if( $args['panel_id'] == 0 ) {
        reset($rsp['panels']);
        $rsp['panel']['panel_ref'] = key($rsp['panels']);
    }
*/

    return $rsp;
}
?>
