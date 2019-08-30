<?php
//
// Description
// -----------
// Load the widget data for the cell
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function qruqsp_dashboard_loadCell(&$ciniki, $tnid, $action, $cell) {

    if( !isset($cell['widget_ref']) || $cell['widget_ref'] == '' ) {
        return array('stat'=>'ok', 'cell'=>$cell);
    }

    $p = explode('.', $cell['widget_ref']);
    if( isset($p[1]) && $p[1] != '' ) {
        $rc = ciniki_core_loadMethod($ciniki, $p[0], $p[1], 'hooks', 'dashboardWidget');
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $tnid, array(
                'action'=>$action, 
                'widget'=>$cell,
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.51', 'msg'=>'Unable to load widget', 'err'=>$rc['err']));
            } 
            $cell['content'] = $rc['widget']['content'];
            $cell['css'] = $rc['widget']['css'];
            $cell['js'] = $rc['widget']['js'];
            $cell['data'] = $rc['widget']['data'];
        }
    }

    return array('stat'=>'ok', 'cell'=>$cell);
}
?>
