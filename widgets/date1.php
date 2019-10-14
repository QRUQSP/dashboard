<?php
//
// Description
// -----------
// This widget display the current month and date, along with current hour and minute
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function qruqsp_dashboard_widgets_date1(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    if( !isset($args['widget']['widget_ref']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.54', 'msg'=>'No dashboard widget specified'));
    }

    if( !isset($args['widget']['content']) ) {
        $args['widget']['content'] = '';
    }

    if( !isset($args['widget']['settings']) ) {
        $args['widget']['settings'] = array();
    }

    $widget = $args['widget'];

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    //
    // Setup current time
    //
    $dt = new DateTime('now', new DateTimezone($intl_timezone));

    $widget['data']['date'] = strtoupper($dt->format('M j'));
    if( isset($widget['settings']['24hour']) && $widget['settings']['24hour'] == 'yes' ) {
        $widget['data']['time'] = $dt->format('H:i');
    } else {
        $widget['data']['time'] = $dt->format('g:i');
    }

    if( isset($args['action']) && $args['action'] == 'update' ) {
        return array('stat'=>'ok', 'widget'=>$widget);
    }

    //
    // Setup the svg
    //
    $widget['content'] .= '<svg viewBox="0 0 200 150">';
    $widget['content'] .= "<text x='100' y='48' width='180' height='74' font-size='54' fill='#ccc'>"
        . "<tspan id='widget-{$widget['id']}-date' dominant-baseline='middle' alignment-baseline='middle' text-anchor='middle'>"
        . $widget['data']['date']
        . "</tspan></text>";
    $widget['content'] .= "<text x='100' y='114' width='180' height='74' font-size='80' fill='#fff'>"
        . "<tspan id='widget-{$widget['id']}-time' dominant-baseline='middle' alignment-baseline='middle' text-anchor='middle'>"
        . $widget['data']['time']
        . "</tspan></text>";
    $widget['content'] .= '</svg>';

    //
    // Prepare update JS
    //
    $widget['js'] = array(
        'update_args' => "function() {};",
        'update' => "function(data) {"
            . "if( data.date != null ) {"
                . "db_setInnerHtml(this, 'date', data.date);"
            . "}"
            . "if( data.time != null ) {"
                . "db_setInnerHtml(this, 'time', data.time);"
            . "}"
            . "};",
        'init' => "function() {};",
        );

    return array('stat'=>'ok', 'widget'=>$widget);
}
?>
