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
function qruqsp_dashboard_widgets_date4(&$ciniki, $tnid, $args) {

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

    $month_font_size = 85;
    $day_font_size = 135;
    if( isset($_SERVER['HTTP_USER_AGENT']) && stristr($_SERVER['HTTP_USER_AGENT'], ' Gecko/20') !== false ) {
        $month_font_size = 77;
        $day_font_size = 125;
    }

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

    $widget['data']['weekday'] = strtoupper($dt->format('D'));
    $widget['data']['month'] = strtoupper($dt->format('M'));
    $widget['data']['day'] = strtoupper($dt->format('j'));

    if( isset($widget['settings']['weekday']) && $widget['settings']['weekday'] == 'yes' ) {
        $widget['data']['text'] = $widget['data']['weekday'] . ', ' . $widget['data']['month'] . ' ' . $widget['data']['day'];
    } else {
        $widget['data']['text'] = $widget['data']['month'] . ' ' . $widget['data']['day'];
    }

    if( isset($args['action']) && $args['action'] == 'update' ) {
        return array('stat'=>'ok', 'widget'=>$widget);
    }

    //
    // Setup the svg
    //
    $widget['content'] .= '<svg viewBox="0 0 600 100">';
    $widget['content'] .= "<text x='300' y='59' width='600' height='95' font-size='{$month_font_size}' fill='#fff'>"
        . "<tspan id='widget-{$widget['id']}-text' dominant-baseline='middle' alignment-baseline='middle' text-anchor='middle'>"
        . $widget['data']['text']
        . "</tspan></text>";
    $widget['content'] .= '</svg>';

    //
    // Prepare update JS
    //
    $widget['js'] = array(
        'update_args' => "function() {};",
        'update' => "function(data) {"
            . "if( data.text != null ) {"
                . "db_setInnerHtml(this, 'text', data.text);"
            . "}"
            . "};",
        'init' => "function() {};",
        );

    return array('stat'=>'ok', 'widget'=>$widget);
}
?>
