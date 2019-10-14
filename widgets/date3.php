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
function qruqsp_dashboard_widgets_date3(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    if( !isset($args['widget']['widget_ref']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.61', 'msg'=>'No dashboard widget specified'));
    }

    if( !isset($args['widget']['content']) ) {
        $args['widget']['content'] = '';
    }

    if( !isset($args['widget']['settings']) ) {
        $args['widget']['settings'] = array();
    }

    $widget = $args['widget'];

    $time_font_size = 110;
    if( isset($_SERVER['HTTP_USER_AGENT']) && stristr($_SERVER['HTTP_USER_AGENT'], ' Gecko/20') !== false ) {
        $time_font_size = 105;
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
    $widget['content'] .= '<svg viewBox="0 0 300 100">';
    $widget['content'] .= "<text x='150' y='61' width='280' height='95' font-size='{$time_font_size}' fill='#fff'>"
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
