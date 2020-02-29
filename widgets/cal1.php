<?php
//
// Description
// -----------
// This widget will display calendar entries from ICS files either local or remote URL.
// This is used to display google calendars on a dashboard.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function qruqsp_dashboard_widgets_cal1(&$ciniki, $tnid, $args) {

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

    if( !isset($args['widget']['cache']) ) {
        $args['widget']['cache'] = serialize(array());
        $cache = array();
    } else {
        $cache = unserialize($args['widget']['cache']);
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
    // Setup the time period to pull data for
    //
    $start_dt = new DateTime('now', new DateTimezone($intl_timezone));
    $start_dt->setTime(0,0,0);
   
    $end_dt = clone $start_dt;
    $num_days = 1;
    if( isset($widget['settings']['days']) && is_numeric($widget['settings']['days']) ) {
        $num_days = $widget['settings']['days'];
    }
    $end_dt->add(new DateInterval('P' . $num_days . 'D'));

    //
    // Load the ICS events and parse into days
    //
    ciniki_core_loadMethod($ciniki, 'qruqsp', 'dashboard', 'private', 'loadICSDays');
    $rc = qruqsp_dashboard_loadICSDays($ciniki, $tnid, $widget['settings'], $cache, $start_dt, $end_dt, $intl_timezone);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.64', 'msg'=>'Unable to load ICS Calendars', 'err'=>$rc['err']));
    }
    $days = $rc['days'];
    $newcache = serialize($rc['cache']);

    //
    // Loop through days
    //
    $count = 0;
    $tableclasses = '';
    if( isset($widget['settings']['whitespace']) && $widget['settings']['whitespace'] == 'nowrap' ) {
        $tableclasses .= ($tableclasses != '' ? ' ' : '') . 'nowrap';
    }
    if( isset($widget['settings']['background']) && $widget['settings']['background'] == 'solid' ) {
        $tableclasses .= ($tableclasses != '' ? ' ' : '') . 'solidbg';
    } else {
        $tableclasses .= ($tableclasses != '' ? ' ' : '') . 'leftbar';
    }
    $table = "<table cellspacing='0' cellpadding='0' class='{$tableclasses}'>";
    $dir = (isset($widget['settings']['dir']) && $widget['settings']['dir'] == 'h' ? 'h' : 'v');
    $table .= $dir == 'h' ? '<tr>' : '';
    while( $start_dt < $end_dt ) {
        //
        // Output days date
        //
//        $table .= $dir == 'v' ? '<tr>' : '';
//        $table .= "<td>";
        $table .= "<tr class='title' colspan=2><td>"
            . ($count == 0 ? 'TODAY' : strtoupper($start_dt->format('l')))
            . "</td></tr>"
            . "";
//        $table .= "<div class='title'><span class='title'>"
//            . ($count == 0 ? 'TODAY' : strtoupper($start_dt->format('l')))
//            . "</span></div>"
//            . "";
      
        //
        // Output the events for the day
        //
        if( isset($days[$start_dt->format('Y-m-d')]) && count($days[$start_dt->format('Y-m-d')]) > 0 ) {
            $day = $days[$start_dt->format('Y-m-d')];
            foreach($day as $event) {
                if( isset($event['allday']) && $event['allday'] == 'yes' ) {
                    $table .= "<tr class='event allday file{$event['filenum']}'>";
                    $table .= "<td colspan=2 class='summary'><span>" 
                        . $event['summary'] 
                        . "</span></td>";
                    $table .= "</tr>";
//                    $table .= "<div class='event allday file{$event['filenum']}'>";
//                    $table .= "<span class='summary'>" . $event['summary'] . "</span>";
//                    $table .= "</div>";
                } else {
                    $table .= "<tr class='event file{$event['filenum']}'>";
                    $table .= "<td class='time'><span>" 
                        . $event['start']->format('g:i') 
                        . "</span></td>";
                    $table .= "<td class='summary'><span>" . $event['summary'] . "</span></td>";
                    $table .= "</tr>";
//                    $table .= "<div class='event file{$event['filenum']}'>";
//                    $table .= "<span class='time'>" . $event['start']->format('g:i') . "</span>";
//                    $table .= "<span class='summary'>" . $event['summary'] . "</span>";
//                    $table .= "</div>";
                }
            }
        } else {
            $table .= "<tr class='event empty'>";
            $table .= "<td class='empty'>&nbsp;</td>";
            $table .= "</tr>";
//            $table .= "<div class='event'>";
//            $table .= "<span class='empty'>&nbsp;</span>";
//            $table .= "</div>";
        }
        $count++;
        $start_dt->add(new DateInterval('P1D'));
    }

    $table .= ($dir == 'h' ? '</tr>' : '');
    $table .= "</table>";


    //
    // Update the cache
    //
    error_log('Check cache');
    if( !isset($widget['cache']) || $widget['cache'] != $newcache ) {
        error_log('Update cache for widget: ' . $widget['id']);
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'qruqsp.dashboard.cell', $widget['id'], array(
            'cache' => $newcache,
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboard.65', 'msg'=>'Unable to update the widget cache.'));
        }
    }

    //
    // Return data if only an update
    //
    if( isset($args['action']) && $args['action'] == 'update' ) {
        $widget['data'] = $table;
        return array('stat'=>'ok', 'widget'=>$widget);
    }

    $widget['content'] .= '<div id="widget-' . $widget['id'] . '-table">'
        . $table 
        . "</div>";
    //
    // Setup the CSS for the calendar
    //
    $widget['css'] = ''
        . "#widget-{$widget['id']} {"
            . "padding: 0.25em; "
            . "font-size: {$widget['settings']['font-size']}px; "
            . "font-family: sans-serif;"
            . "color: #ddd;"
        . "}\n"
        . "#widget-{$widget['id']}-table table {"
            . "width: 100%;"
            . "max-width: 100%;"
            . "table-layout: fixed;"
        . "}\n"
        . "#widget-{$widget['id']}-table table > tbody > tr {"
            . "max-width: 100%;"
        . "}\n"
        . "#widget-{$widget['id']}-table table > tbody > tr > td {"
            . "padding: 0.35em;"
            . "box-sizing: border-box;"
            . "width: 20%;"
            . "min-width: 5em;"
        . "}\n"
        . "#widget-{$widget['id']}-table .title {"
            . "font-size: 1.1em;"
        . "}\n"
        . "#widget-{$widget['id']}-table .time {"
            . "max-width: 20%;"
            . "width: 20%;"
            . "min-width: 5em;"
            . "vertical-align: top;"
            . "text-align: right;"
            . "padding: 0.95em 0.1em 0.95em 0.2em;"
            . "font-size: 0.9em;"
        . "}\n"
        . "#widget-{$widget['id']}-table .summary span {"
//            . "border-radius: 0.25em;"
            . "font-size: 1em;"
            . "padding: 0.5em 0.5em;"
            . "display: block;"
        . "}\n"
        . "#widget-{$widget['id']}-table .summary {"
            . "width: 80%;"
            . "min-width: 50%;"
            . "max-width: 80%;"
            . "overflow: hidden;"
        . "}\n"
        . "#widget-{$widget['id']}-table .nowrap .summary span {"
            . "white-space: nowrap;"
            . "display: block;"
            . "overflow: hidden;"
            . "text-overflow: ellipsis;"
        . "}\n";
    if( isset($widget['settings']['background']) && $widget['settings']['background'] == 'solid' ) {
        $widget['css'] .= ""
                . "#widget-{$widget['id']}-table .file1 .summary span {"
                . "background: #750075;"
            . "}\n"
            . "#widget-{$widget['id']}-table .file2 .summary span {"
                . "background: #007500;"
            . "}\n"
            . "#widget-{$widget['id']}-table .file3 .summary span {"
                . "background: #0050A0;"
            . "}\n"
            . "#widget-{$widget['id']}-table .file4 .summary span {"
                . "background: #755050;"
            . "}\n"
            . "#widget-{$widget['id']}-table .file5 .summary span {"
                . "background: #A05000;"
            . "}\n"
            . "#widget-{$widget['id']}-table .file6 .summary span {"
                . "background: #757575;"
            . "}\n";
    } else {
        for($i = 1; $i <= 10; $i++) {
            if( isset($widget['settings']["color{$i}"]) && $widget['settings']["color{$i}"] != '' ) {
                $widget['css'] .= ""
                    . "#widget-{$widget['id']}-table .file{$i} .summary span {"
                        . "border-left: 4px solid " . $widget['settings']["color{$i}"] . ";"
                    . "}\n";
            }
        }
    }
    $widget['css'] .= "#widget-{$widget['id']}-table .empty {"
            . "min-height: 1em;"
        . "}\n"
        . "#widget-{$widget['id']}-table .allday {"
            . "min-width: 95%;"
            . "max-width: 95%;"
            . "margin-left: 5%;"
        . "}\n"
        . '';

    //
    // Prepare update JS
    //
    $widget['js'] = array(
        'update_args' => "function() {};",
        'update' => "function(data) {"
            . "var e=db_ge(this, 'table');"
            . "e.innerHTML=data;"
            . "};",
        'init' => "function() {};",
        ); 

    return array('stat'=>'ok', 'widget'=>$widget);
}
?>
