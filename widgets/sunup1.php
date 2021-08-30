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

    $sun_font_size = 20;
    $moon_font_size = 17;
    $time_font_size = 35;
    if( isset($_SERVER['HTTP_USER_AGENT']) && stristr($_SERVER['HTTP_USER_AGENT'], ' Gecko/20') !== false ) {
        $sun_font_size = 18;
        $moon_font_size = 15;
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
    // Add the current GPS coordinates to the response
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'tenantGPSCoords');
    $rc = ciniki_tenants_hooks_tenantGPSCoords($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.i2c.19', 'msg'=>'Unable to get GPS Coordinates', 'err'=>$rc['err']));
    }
    $gps = $rc;

    //
    // Load the rise/set calculator for sun and moon
    //
    require_once($ciniki['config']['ciniki.core']['root_dir'] . '/qruqsp-mods/dashboard/lib/suncalc-php/suncalc.php');

    //
    // Setup current time
    //
    $dt = new DateTime('now', new DateTimezone($intl_timezone));

    $sc = new AurorasLive\SunCalc($dt, $gps['latitude'], $gps['longitude']);
    $sun_times = $sc->getSunTimes();
    $moon_times = $sc->getMoonTimes(false);
    if( isset($widget['settings']['24hour']) && $widget['settings']['24hour'] == 'yes' ) {
        $widget['data']['sunrise'] = $sun_times['sunrise']->format('H:i');
        $widget['data']['sunset'] = $sun_times['sunset']->format('H:i');
        $widget['data']['moonrise'] = isset($moon_times['moonrise']) ? $moon_times['moonrise']->format('H:i') : 'NONE';
        $widget['data']['moonset'] = isset($moon_times['moonset']) ? $moon_times['moonset']->format('H:i') : 'NONE';
        $time_font_size = 35;
        if( isset($_SERVER['HTTP_USER_AGENT']) && stristr($_SERVER['HTTP_USER_AGENT'], ' Gecko/20') !== false ) {
            $time_font_size = 32;
        }
    } else {
        $widget['data']['sunrise'] = $sun_times['sunrise']->format('g:ia');
        $widget['data']['sunset'] = $sun_times['sunset']->format('g:ia');
        $widget['data']['moonrise'] = isset($moon_times['moonrise']) ? $moon_times['moonrise']->format('g:ia') : 'NONE';
        $widget['data']['moonset'] = isset($moon_times['moonset']) ? $moon_times['moonset']->format('g:ia') : 'NONE';
        $time_font_size = 25;
        if( isset($_SERVER['HTTP_USER_AGENT']) && stristr($_SERVER['HTTP_USER_AGENT'], ' Gecko/20') !== false ) {
            $time_font_size = 22;
        }
    }

    if( isset($args['action']) && $args['action'] == 'update' ) {
        return array('stat'=>'ok', 'widget'=>$widget);
    }

    //
    // Setup the svg
    //
    if( ($widget['rowspan']/$widget['colspan']) > 2 ) {
        $widget['content'] .= '<svg viewBox="0 0 100 300">';
        // Sunrise
        $widget['content'] .= "<text x='50' y='30' width='90' height='20' font-size='{$sun_font_size}' fill='#ccc'>"
            . "<tspan text-anchor='middle'>SUNRISE</tspan></text>";
        $widget['content'] .= "<text x='50' y='60' width='90' height='20' font-size='{$time_font_size}' fill='#fff'>"
            . "<tspan id='widget-{$widget['id']}-sunrise' text-anchor='middle'>"
            . $widget['data']['sunrise']
            . "</tspan></text>";
        // Sunset
        $widget['content'] .= "<text x='50' y='100' width='90' height='20' font-size='{$sun_font_size}' fill='#ccc'>"
            . "<tspan text-anchor='middle'>SUNSET</tspan></text>";
        $widget['content'] .= "<text x='50' y='130' width='90' height='20' font-size='{$time_font_size}' fill='#fff'>"
            . "<tspan id='widget-{$widget['id']}-sunset' text-anchor='middle'>"
            . $widget['data']['sunset']
            . "</tspan></text>";
        // Moonrise
        $widget['content'] .= "<text x='50' y='180' width='100' height='20' font-size='{$moon_font_size}' fill='#ccc'>"
            . "<tspan text-anchor='middle'>MOONRISE</tspan></text>";
        $widget['content'] .= "<text x='50' y='210' width='100' height='20' font-size='{$time_font_size}' fill='#fff'>"
            . "<tspan id='widget-{$widget['id']}-moonrise' text-anchor='middle'>"
            . $widget['data']['moonrise']
            . "</tspan></text>";
        // Moonset
        $widget['content'] .= "<text x='50' y='250' width='100' height='20' font-size='{$moon_font_size}' fill='#ccc'>"
            . "<tspan text-anchor='middle'>MOONSET</tspan></text>";
        $widget['content'] .= "<text x='50' y='280' width='100' height='20' font-size='{$time_font_size}' fill='#fff'>"
            . "<tspan id='widget-{$widget['id']}-moonset' text-anchor='middle'>"
            . $widget['data']['moonset']
            . "</tspan></text>";
        // Sunset
        $widget['content'] .= '</svg>';

    } elseif( ($widget['colspan']/$widget['rowspan']) > 12 ) {
        // Really wide
        $sun_font_size = 15;
        $time_font_size = 20;
        $moon_font_size = 15;
        $widget['content'] .= '<svg viewBox="0 0 800 50">';
        // Sunrise
        $widget['content'] .= "<text x='60' y='35' width='100' height='50' font-size='{$sun_font_size}' fill='#ccc'>"
            . "<tspan text-anchor='middle'>SUNRISE</tspan></text>";
        $widget['content'] .= "<text x='140' y='35' width='100' height='50' font-size='{$time_font_size}' fill='#fff'>"
            . "<tspan id='widget-{$widget['id']}-sunrise' text-anchor='middle'>"
            . $widget['data']['sunrise']
            . "</tspan></text>";
        // Sunset
        $widget['content'] .= "<text x='260' y='35' width='100' height='50' font-size='{$sun_font_size}' fill='#ccc'>"
            . "<tspan text-anchor='middle'>SUNSET</tspan></text>";
        $widget['content'] .= "<text x='340' y='35' width='100' height='50' font-size='{$time_font_size}' fill='#fff'>"
            . "<tspan id='widget-{$widget['id']}-sunset' text-anchor='middle'>"
            . $widget['data']['sunset']
            . "</tspan></text>";
        // Moonrise
        $widget['content'] .= "<text x='450' y='35' width='100' height='50' font-size='{$moon_font_size}' fill='#ccc'>"
            . "<tspan text-anchor='middle'>MOONRISE</tspan></text>";
        $widget['content'] .= "<text x='540' y='35' width='100' height='50' font-size='{$time_font_size}' fill='#fff'>"
            . "<tspan id='widget-{$widget['id']}-moonrise' text-anchor='middle'>"
            . $widget['data']['moonrise']
            . "</tspan></text>";
        // Moonset
        $widget['content'] .= "<text x='660' y='35' width='100' height='50' font-size='{$moon_font_size}' fill='#ccc'>"
            . "<tspan text-anchor='middle'>MOONSET</tspan></text>";
        $widget['content'] .= "<text x='740' y='35' width='100' height='50' font-size='{$time_font_size}' fill='#fff'>"
            . "<tspan id='widget-{$widget['id']}-moonset' text-anchor='middle'>"
            . $widget['data']['moonset']
            . "</tspan></text>";
        // Sunset
        $widget['content'] .= '</svg>';

    } else {
        $widget['content'] .= '<svg viewBox="0 0 200 200">';
        // Sunrise
        $widget['content'] .= "<text x='50' y='45' width='90' height='20' font-size='{$sun_font_size}' fill='#ccc'>"
            . "<tspan text-anchor='middle'>SUNRISE</tspan></text>";
        $widget['content'] .= "<text x='50' y='82' width='90' height='20' font-size='{$time_font_size}' fill='#fff'>"
            . "<tspan id='widget-{$widget['id']}-sunrise' text-anchor='middle'>"
            . $widget['data']['sunrise']
            . "</tspan></text>";
        // Sunset
        $widget['content'] .= "<text x='150' y='45' width='90' height='20' font-size='{$sun_font_size}' fill='#ccc'>"
            . "<tspan text-anchor='middle'>SUNSET</tspan></text>";
        $widget['content'] .= "<text x='150' y='82' width='90' height='20' font-size='{$time_font_size}' fill='#fff'>"
            . "<tspan id='widget-{$widget['id']}-sunset' text-anchor='middle'>"
            . $widget['data']['sunset']
            . "</tspan></text>";
        // Moonrise
        $widget['content'] .= "<text x='50' y='130' width='100' height='20' font-size='{$moon_font_size}' fill='#ccc'>"
            . "<tspan text-anchor='middle'>MOONRISE</tspan></text>";
        $widget['content'] .= "<text x='50' y='167' width='100' height='20' font-size='{$time_font_size}' fill='#fff'>"
            . "<tspan id='widget-{$widget['id']}-moonrise' text-anchor='middle'>"
            . $widget['data']['moonrise']
            . "</tspan></text>";
        // Moonset
        $widget['content'] .= "<text x='150' y='130' width='100' height='20' font-size='{$moon_font_size}' fill='#ccc'>"
            . "<tspan text-anchor='middle'>MOONSET</tspan></text>";
        $widget['content'] .= "<text x='150' y='167' width='100' height='20' font-size='{$time_font_size}' fill='#fff'>"
            . "<tspan id='widget-{$widget['id']}-moonset' text-anchor='middle'>"
            . $widget['data']['moonset']
            . "</tspan></text>";
        // Sunset
        $widget['content'] .= '</svg>';
    }

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
            . "if( data.moonrise != null ) {"
                . "db_setInnerHtml(this, 'moonrise', data.moonrise);"
            . "}"
            . "if( data.moonset != null ) {"
                . "db_setInnerHtml(this, 'moonset', data.moonset);"
            . "}"
            . "};",
        'init' => "function() {};",
        );

    return array('stat'=>'ok', 'widget'=>$widget);
}
?>
