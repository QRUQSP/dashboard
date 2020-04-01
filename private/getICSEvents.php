<?php
//
// Description
// -----------
// This function loads and parses a ICS file, which can be local file or url (http://...)
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function qruqsp_dashboard_getICSEvents(&$ciniki, $tnid, $file, $start_dt, $end_dt, $tz) {
    //
    // Load the file
    //
    $lines = file($file, FILE_IGNORE_NEW_LINES);

    // Save file for testing to /tmp for debugging
    // file_put_contents('/tmp/' . preg_replace("/[^a-zA-Z]/", '', $file), join("\n", $lines));

    $event = null;
    $events = array();
    $repeats = array();
    foreach($lines as $line) {
        if( preg_match("/BEGIN:VEVENT/", $line) ) {
            $event = array();
        } 
        elseif( preg_match("/END:VEVENT/", $line) && is_array($event) ) {
            // Check if missing end time, assume all day event
            if( $event['start']->format('H:i:s') == '00:00:00' && !isset($event['end']) ) {
                $event['end'] = clone $event['start'];
                $event['end']->add(new DateInterval('P1D'));
            }
            if( $event['start']->format('H:i:s') == '00:00:00' 
                && $event['end']->format('H:i:s') == '00:00:00'
                ) {
                $event['allday'] = 'yes';
            }
            if( isset($event['repeat']) ) {
                $dt = null;
                if( preg_match("/UNTIL=([0-9]+T[0-9]+)Z/", $event['repeat'], $m) ) {
                    $dt = new DateTime($m[1], new DateTimezone('UTC'));
                } elseif( preg_match("/UNTIL=([0-9]+)T([0-9]+)/", $event['repeat'], $m) ) {
                    $dt = new DateTime($m[1], new DateTimezone($tz));
                } elseif( preg_match("/UNTIL=([0-9]+)/", $event['repeat'], $m) ) {
                    $dt = new DateTime($m[1], new DateTimezone($tz));
                } elseif( preg_match("/FREQ=WEEKLY;.*COUNT=([0-9]+)/", $event['repeat'], $m) ) {
                    $dt = clone $event['end'];
                    $dt->add(new DateInterval('P' . $m[1] . 'W'));
                }
                if( $dt != null ) {
                    if( $dt < $start_dt ) {
                        continue;
                    }
                }
                if( $dt == null || $dt > $start_dt ) {
                    $repeats[] = $event;
                }
            } else {
                //
                // If the event is between the range of dates we want, then add event
                //
                if( ($event['start'] >= $start_dt && $event['start'] <= $end_dt) 
                    || ($event['end'] >= $start_dt && $event['end'] <= $end_dt) 
                    ) {
                    $events[] = $event;
                }
            }
        }
        elseif( preg_match("/RRULE:(.*)/", $line, $m) && is_array($event) ) {
            $event['repeat'] = $m[1];
        }
        elseif( preg_match("/DTSTART;TZID=(.*):(.*)/", $line, $m) && is_array($event) ) {
            $dt = new Datetime($m[2], new DateTimezone($m[1]));
            $dt->setTimezone(new DateTimezone($tz));
            $event['start'] = $dt;
        }
        elseif( preg_match("/DTSTART:([0-9]+T[0-9]+Z)/", $line, $m) && is_array($event) ) {
            $dt = new Datetime($m[1], new DateTimezone('UTC'));
            $dt->setTimezone(new DateTimezone($tz));
            $event['start'] = $dt;
        }
        elseif( preg_match("/DTSTART:([0-9]+T[0-9]+)/", $line, $m) && is_array($event) ) {
            $dt = new Datetime($m[1], new DateTimezone($tz));
            $event['start'] = $dt;
        }
        elseif( preg_match("/DTSTART;VALUE=DATE:([0-9]+)/", $line, $m) && is_array($event) ) {
            $dt = new Datetime($m[1], new DateTimezone($tz));
            $event['start'] = $dt;
        }
        elseif( preg_match("/DTEND;TZID=(.*):(.*)/", $line, $m) && is_array($event) ) {
            $dt = new Datetime($m[2], new DateTimezone($m[1]));
            $dt->setTimezone(new DateTimezone($tz));
            $event['end'] = $dt;
        }
        elseif( preg_match("/DTEND:([0-9]+T[0-9]+Z)/", $line, $m) && is_array($event) ) {
            $dt = new Datetime($m[1], new DateTimezone('UTC'));
            $dt->setTimezone(new DateTimezone($tz));
            $event['end'] = $dt;
        }
        elseif( preg_match("/DTEND:([0-9]+T[0-9]+)/", $line, $m) && is_array($event) ) {
            $dt = new Datetime($m[1], new DateTimezone($tz));
            $event['end'] = $dt;
        }
        elseif( preg_match("/DTEND;VALUE=DATE:([0-9]+)/", $line, $m) && is_array($event) ) {
            $dt = new Datetime($m[1], new DateTimezone($tz));
            $event['end'] = $dt;
        }
        elseif( preg_match("/SUMMARY:(.*)/", $line, $m) && is_array($event) ) {
            $event['summary'] = $m[1];
        }
    }

    //
    // Process Repeats, create an event for each day the repeat 
    // is active during the requested window
    //
    foreach($repeats as $rid => $repeat) {
        //
        // Decide on the interval
        //
        if( preg_match("/FREQ=YEARLY/", $repeat['repeat']) ) {
            $repeat['interval'] = new DateInterval('P1Y');
        } 
        elseif( preg_match("/FREQ=WEEKLY/", $repeat['repeat']) ) {
            $repeat['interval'] = new DateInterval('P1W');
        }
        elseif( preg_match("/FREQ=MONTHLY;BYMONTHDAY=([0-9]+)/", $repeat['repeat'], $m) ) {
            $repeat['interval'] = new DateInterval('P1M');
        }
        //
        // Calculate event length
        //
        $repeat['length'] = $repeat['start']->diff($repeat['end']);
        while($repeat['start'] < $end_dt) {
            if( $repeat['start'] > $end_dt ) {
                break;
            }
            //
            // Check if repeat starts or ends during the requested timeframe
            //
            if( ($repeat['start'] > $start_dt && $repeat['start'] < $end_dt) 
                || ($repeat['end'] > $start_dt && $repeat['end'] < $end_dt) 
                ) {
                $events[] = array(
                    'start' => clone $repeat['start'],
                    'end' => clone $repeat['end'],
                    'repeat' => $repeat['repeat'],
                    'summary' => $repeat['summary'],
                    );
            }

            
            //
            // Advance to next instance
            //
            if( isset($repeat['interval']) ) {
                $repeat['start']->add($repeat['interval']);
                $repeat['end']->add($repeat['interval']);
            } 
            elseif( preg_match("/FREQ=MONTHLY;.*BYDAY=([0-9]+)([A-Z]+)/", $repeat['repeat'], $m) ) {
                // Set to first of month, advance and then find day
                $repeat['start']->setDate($repeat['start']->format('Y'), $repeat['start']->format('m'), 1);
                $repeat['start']->add(new DateInterval('P1M'));
                $count = 0;
                // Advance 1 day at a time through the month to find the right day
                while($repeat['start']->format('d') <= $repeat['start']->format('t')) {
                    if( strncmp(strtoupper($repeat['start']->format('D')), $m[2], 2) == 0 ) {
                        $count++;
                        if( $count == $m[1] ) {
                            //
                            // If the day and count (eg: 3rd saturday), 
                            // setup end datetime add the repeat as an event
                            //
                            $repeat['end'] = clone $repeat['start'];
                            $repeat['end']->add($repeat['length']);
                            break;
                        }
                    }
                    $repeat['start']->add(new DateInterval('P1D'));
                }
            }
            else {
                return array('stat'=>'fail', 'err'=>array('code'=>'qruqsp.dashboards.99', 'msg'=>'Unable to understand repeat event: ' . $repeat['repeat']));
                error_log('UNABLE TO UNDERSTAND REPEAT: ' . $repeat['repeat']);
                break;
            }
        }

    }

    return array('stat'=>'ok', 'events'=>$events);
}
?>
