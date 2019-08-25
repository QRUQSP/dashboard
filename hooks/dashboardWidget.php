<?php
//
// Description
// -----------
// This hook returns content for a widget to be added to panel in a dashboard.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function qruqsp_dashboard_hooks_dashboardWidget(&$ciniki, $tnid, $args) {

    if( !isset($args['widget']['widget_ref']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.9', 'msg'=>'No dashboard widget specified'));
    }

    if( !isset($args['widget']['content']) ) {
        $args['widget']['content'] = '';
    }

    //
    // Load the referenced panel
    //
    $pieces = explode('.', $args['widget']['widget_ref']);
    if( !isset($pieces[2]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.57', 'msg'=>'No dashboard valid widget specified'));
    }
    $package = $pieces[0];
    $module = $pieces[1];
    $widget = $pieces[2];
    $rc = ciniki_core_loadMethod($ciniki, $package, $module, 'widgets', $widget);
    if( $rc['stat'] == 'ok' ) {
        $fn = $rc['function_call'];
        return $fn($ciniki, $tnid, $args);
    }

    return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.55', 'msg'=>'Dashboard widget not found'));
}
?>

