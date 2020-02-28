<?php
//
// Description
// -----------
// This function loads the ICS files for the cal1 widget. 
//
// The settings are the widget settings, and the cache is the widget
// cache data which is used to determine if anything should be reloaded or used
// from the cache. This prevents download ics files more than needed.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function qruqsp_dashboard_loadICSDays($ciniki, $tnid, $settings, $cache, $start_dt, $end_dt, $tz) {
    $events = array();

    $cur_dt = new DateTime('now', new DateTimezone('UTC'));

    ciniki_core_loadMethod($ciniki, 'qruqsp', 'dashboard', 'private', 'getICSEvents');
    //
    // Load the files specified
    //
    for($i = 1; $i < 10; $i++) {    
        if( !isset($settings["file{$i}"]) || $settings["file{$i}"] == '' ) {
            continue;
        }
        $dt = null;
        if( isset($cache["file{$i}_last_updated"]) ) {
            $dt = new DateTime($cache["file{$i}_last_updated"], new DateTimezone('UTC'));
            if( !isset($settings["refresh{$i}"]) ) {
                $settings["refresh$i}"] = '60';
            }
            $last_dt = clone $dt;
            $dt->add(new DateInterval('PT' . $settings["refresh{$i}"] . 'M'));
        }
        //
        // Refresh the cache if it doesn't exist, 
        // or is past refresh time, 
        // or the cache day is different
        // Note* Cache only stores current time period events, so when 
        // change day the time period changes and needs updating
        //
        if( $dt == null || $dt < $cur_dt || $last_dt->format('d') != $cur_dt->format('d') ) {
//        if( $dt == null || $dt < $cur_dt ) {
//            error_log('load ics: ' . $settings["file{$i}"] . ' [' . $cache["file{$i}_last_updated"] . '--' . $cur_dt->format("Y-m-d H:i:s") . ']');
            $rc = qruqsp_dashboard_getICSEvents($ciniki, $tnid, $settings["file{$i}"], $start_dt, $end_dt, $tz);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            foreach($rc['events'] as $eid => $event) {
                $rc['events'][$eid]['filenum'] = $i;
            }
            $events = array_merge($events, $rc['events']);
            $cache["file{$i}_last_updated"] = $cur_dt->format('Y-m-d H:i:s');
            $cache["file{$i}_events"] = $rc['events'];
        } elseif( isset($cache["file{$i}_events"]) ) {
            $events = array_merge($events, $cache["file{$i}_events"]);
        }
    }
  
    //
    // Setup the days array with a empty array for each day in the period selected
    //
    $days = array();
    $dt = clone $start_dt;
    while($dt <= $end_dt) {
        $days[$dt->format('Y-m-d')] = array();
        $dt->add(new DateInterval('P1D'));
    }
    foreach($events as $event) {
        if( isset($event['allday']) && $event['allday'] == 'yes' ) {
            $dt = clone $event['start'];
            while($dt < $event['end']) {
                if( isset($days[$dt->format('Y-m-d')]) ) {
                    $days[$dt->format('Y-m-d')][] = $event;
                }
                $dt->add(new DateInterval('P1D'));
            }
        } elseif( $event['start']->format('Y-m-d') != $event['end']->format('Y-m-d') ) {
            // FIXME: Multi day event that isn't allday
        } else {
            $days[$event['start']->format('Y-m-d')][] = $event;
        }
    }

    //
    // Sort the events for each day
    //
    foreach($days as $k => $day) {
        uasort($days[$k], function($a, $b) {
            if( $a['start'] == $b['start'] ) {
                return 0;
            }
            return $a['start'] < $b['start'] ? -1 : 1;
        });
    }

    return array('stat'=>'ok', 'days'=>$days, 'cache'=>$cache);
}
?>
