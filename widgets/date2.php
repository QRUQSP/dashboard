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

    $month_font_size = 65;
    $day_font_size = 135;
    if( isset($_SERVER['HTTP_USER_AGENT']) && stristr($_SERVER['HTTP_USER_AGENT'], ' Gecko/20') !== false ) {
        $month_font_size = 58;
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

    $widget['data']['month'] = strtoupper($dt->format('M'));
    $widget['data']['day'] = strtoupper($dt->format('j'));

    if( isset($args['action']) && $args['action'] == 'update' ) {
        return array('stat'=>'ok', 'widget'=>$widget);
    }

    //
    // Setup the svg
    //
    if( ($widget['colspan']/$widget['rowspan']) >= 2 ) {
        $day_font_size = 110;
        if( isset($_SERVER['HTTP_USER_AGENT']) && stristr($_SERVER['HTTP_USER_AGENT'], ' Gecko/20') !== false ) {
            $day_font_size = 105;
        }
        $widget['content'] .= '<svg viewBox="0 0 300 100">';
        $widget['content'] .= "<text x='90' y='72' width='150' height='95' font-size='{$month_font_size}' fill='#ccc'>"
            . "<tspan id='widget-{$widget['id']}-month' dominant-baseline='middle' alignment-baseline='middle' text-anchor='middle'>"
            . $widget['data']['month']
            . "</tspan></text>";
        $widget['content'] .= "<text x='215' y='61' width='120' height='95' font-size='{$day_font_size}' fill='#fff'>"
            . "<tspan id='widget-{$widget['id']}-day' dominant-baseline='middle' alignment-baseline='middle' text-anchor='middle'>"
            . '1' . $widget['data']['day']
            . "</tspan></text>";
        $widget['content'] .= '</svg>';

    } else {
        $widget['content'] .= '<svg viewBox="0 0 200 200">';
        $widget['content'] .= "<text x='100' y='54' width='180' height='40' font-size='{$month_font_size}' fill='#ccc'>"
            . "<tspan id='widget-{$widget['id']}-month' dominant-baseline='middle' alignment-baseline='middle' text-anchor='middle'>"
            . $widget['data']['month']
            . "</tspan></text>";
        $widget['content'] .= "<text x='100' y='148' width='180' height='140' font-size='{$day_font_size}' fill='#fff'>"
            . "<tspan id='widget-{$widget['id']}-day' dominant-baseline='middle' alignment-baseline='middle' text-anchor='middle'>"
            . $widget['data']['day']
            . "</tspan></text>";
        $widget['content'] .= '</svg>';
    }

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
