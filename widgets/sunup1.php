<?php
//
// Description
// -----------
// This widget will display the sunrise, sunset, moonrise and moonset for each day
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function qruqsp_dashboard_widgets_sunup1(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    if( !isset($args['widget']['widget_ref']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.62', 'msg'=>'No dashboard widget specified'));
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
    // Load the rise/set calculator for sun and moon
    //
    require_once($ciniki['config']['ciniki.core']['root_dir'] . '/qruqsp-mods/dashboard/lib/suncalc-php/suncalc.php');

    //
    // Setup current time
    //
    $dt = new DateTime('now', new DateTimezone($intl_timezone));

    $sc = new AurorasLive\SunCalc($dt, 44.476261, -80.061761);
    $sun_times = $sc->getSunTimes();
    $moon_times = $sc->getMoonTimes(false);
    if( isset($widget['settings']['24hour']) && $widget['settings']['24hour'] == 'yes' ) {
        $widget['data']['sunrise'] = $sun_times['sunrise']->format('H:i');
        $widget['data']['sunset'] = $sun_times['sunset']->format('H:i');
        $widget['data']['moonrise'] = $moon_times['moonrise']->format('H:i');
        $widget['data']['moonset'] = $moon_times['moonset']->format('H:i');
        $label_font_size = 35;
    } else {
        $widget['data']['sunrise'] = $sun_times['sunrise']->format('g:ia');
        $widget['data']['sunset'] = $sun_times['sunset']->format('g:ia');
        $widget['data']['moonrise'] = $moon_times['moonrise']->format('g:ia');
        $widget['data']['moonset'] = $moon_times['moonset']->format('g:ia');
        $label_font_size = 25;
    }

/*$sunTimes = $sc->getSunTimes();
print_r($sunTimes);

$moon = $sc->getMoonPosition($dt);
print_r($moon);
$moon = $sc->getMoonIllumination();
$moon['deg'] = $moon['phase'] * 360;
print_r($moon);
$moon = $sc->getMoonTimes(false);
print_r($moon); */


    if( isset($args['action']) && $args['action'] == 'update' ) {
        return array('stat'=>'ok', 'widget'=>$widget);
    }

    //
    // Setup the svg
    //
    $widget['content'] .= '<svg viewBox="0 0 200 200">';
    // Sunrise
    $widget['content'] .= "<text x='50' y='45' width='90' height='20' font-size='20' fill='#ccc'>"
        . "<tspan text-anchor='middle'>SUNRISE</tspan></text>";
    $widget['content'] .= "<text x='50' y='82' width='90' height='20' font-size='{$label_font_size}' fill='#fff'>"
        . "<tspan id='widget-{$widget['id']}-sunrise' text-anchor='middle'>"
        . $widget['data']['sunrise']
        . "</tspan></text>";
    // Sunset
    $widget['content'] .= "<text x='150' y='45' width='90' height='20' font-size='20' fill='#ccc'>"
        . "<tspan text-anchor='middle'>SUNSET</tspan></text>";
    $widget['content'] .= "<text x='150' y='82' width='90' height='20' font-size='{$label_font_size}' fill='#fff'>"
        . "<tspan id='widget-{$widget['id']}-sunset' text-anchor='middle'>"
        . $widget['data']['sunset']
        . "</tspan></text>";
    // Moonrise
    $widget['content'] .= "<text x='50' y='130' width='100' height='20' font-size='17' fill='#ccc'>"
        . "<tspan text-anchor='middle'>MOONRISE</tspan></text>";
    $widget['content'] .= "<text x='50' y='167' width='100' height='20' font-size='{$label_font_size}' fill='#fff'>"
        . "<tspan id='widget-{$widget['id']}-moonrise' text-anchor='middle'>"
        . $widget['data']['moonrise']
        . "</tspan></text>";
    // Moonset
    $widget['content'] .= "<text x='150' y='130' width='100' height='20' font-size='17' fill='#ccc'>"
        . "<tspan text-anchor='middle'>MOONSET</tspan></text>";
    $widget['content'] .= "<text x='150' y='167' width='100' height='20' font-size='{$label_font_size}' fill='#fff'>"
        . "<tspan id='widget-{$widget['id']}-moonset' text-anchor='middle'>"
        . $widget['data']['moonset']
        . "</tspan></text>";
    // Sunset
    $widget['content'] .= '</svg>';

    //
    // Prepare update JS
    //
    $widget['js'] = array(
        'update_args' => "function() {};",
        'update' => "function(data) {"
            . "if( data.sunrise != null ) {"
                . "db_setInnerHtml(this, 'sunrise', data.sunrise);"
            . "}"
            . "if( data.sunset != null ) {"
                . "db_setInnerHtml(this, 'sunset', data.sunset);"
            . "}"
            . "};",
        'init' => "function() {};",
        );

    return array('stat'=>'ok', 'widget'=>$widget);
}
?>
