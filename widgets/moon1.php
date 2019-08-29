<?php
//
// Description
// -----------
// This widget displays the current phase of the moon.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function qruqsp_dashboard_widgets_moon1(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    if( !isset($args['widget']['widget_ref']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.60', 'msg'=>'No dashboard widget specified'));
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
    $phase = ($dt->format('U') - 614100) % 2551443;
    $phase = ($phase/(24*3600));
    // The current phase will be between 0 and 28
    // Convert to between 0 - 360
    // 29.53 is the period of the moon in days
    $deg = $phase * (360/29.53);
    if( $deg >= 360 ) {
        $deg = 0;
    }

    if( $deg <= 90 ) {
        $path = "M100,10 A90,90 0 1 0 100,190 A" . round((90-$deg), 2) . ",90 0 0 0 100,10";;
    } elseif( $deg <= 180 ) {
        $path = "M100,10 A90,90 0 1 0 100,190 A" . round(($deg-90), 2) . ",90 0 0 1 100,10";;
    } elseif( $deg <= 270 ) {
        $path = "M100,10 A90,90 0 0 1 100,190 A" . round((90-($deg-180)), 2) . ",90 0 0 0 100,10";;
    } elseif( $deg <= 360 ) {
        $path = "M100,10 A90,90 0 0 1 100,190 A" . round(($deg-270), 2) . ",90 0 0 1 100,10";;
    } 
    $widget['data']['path'] = $path;

    if( isset($args['action']) && $args['action'] == 'update' ) {
        return array('stat'=>'ok', 'widget'=>$widget);
    }

    //
    // Setup the moon background and shadow path
    //
    $widget['content'] .= '<svg viewBox="0 0 200 200">';
    $background_filename = $ciniki['config']['qruqsp.core']['modules_dir'] . '/dashboard/widgets/assets/moon1.jpg';
    if( file_exists($background_filename) ) {
        $widget['content'] .= "<image x='10' y='10' width='180' height='180' xlink:href='data:image/jpg;base64," 
            . base64_encode(file_get_contents($background_filename))
            . "' />";
    }
    $widget['content'] .= "<path id='widget-{$widget['id']}-path' class='moon' d='{$path}' fill='rgba(0,0,0,0.8)'></path>";
    $widget['content'] .= '</svg>';

    //
    // Prepare update JS
    //
    $widget['js'] = array(
        'update_args' => "function() {};",
        'update' => "function(data) {"
            . "if( data.path != null && data.path != '' ) {"
                . "var shadow=db_ge(this, 'path');"
                . "shadow.setAttributeNS(null,'d', data.path);"
            . "}};",
        'init' => "function() {};",
        );

    return array('stat'=>'ok', 'widget'=>$widget);
}
?>
