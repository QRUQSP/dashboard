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
function qruqsp_dashboard_widgets_date2(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    if( !isset($args['widget']['widget_ref']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.53', 'msg'=>'No dashboard widget specified'));
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

    $widget['data']['month'] = strtoupper($dt->format('M'));
    $widget['data']['day'] = strtoupper($dt->format('j'));

    if( isset($args['action']) && $args['action'] == 'update' ) {
        return array('stat'=>'ok', 'widget'=>$widget);
    }

    //
    // Setup the svg
    //
    $widget['content'] .= '<svg viewBox="0 0 200 200">';
    $widget['content'] .= "<text x='100' y='54' width='180' height='40' font-size='65' fill='#ccc'>"
        . "<tspan id='widget-{$widget['id']}-month' dominant-baseline='middle' alignment-baseline='middle' text-anchor='middle'>"
        . $widget['data']['month']
        . "</tspan></text>";
    $widget['content'] .= "<text x='100' y='148' width='180' height='140' font-size='135' fill='#fff'>"
        . "<tspan id='widget-{$widget['id']}-day' dominant-baseline='middle' alignment-baseline='middle' text-anchor='middle'>"
        . $widget['data']['day']
        . "</tspan></text>";
    $widget['content'] .= '</svg>';

    //
    // Prepare update JS
    //
    $widget['js'] = array(
        'update_args' => "function() {};",
        'update' => "function(data) {"
            . "if( data.month != null ) {"
                . "db_setInnerHtml(this, 'month', data.month);"
            . "}"
            . "if( data.day != null ) {"
                . "db_setInnerHtml(this, 'day', data.day);"
            . "}"
            . "};",
        'init' => "function() {};",
        );

    return array('stat'=>'ok', 'widget'=>$widget);
}
?>
