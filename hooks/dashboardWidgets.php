<?php
//
// Description
// -----------
// This hooks returns the widgets available from this module.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function qruqsp_dashboard_hooks_dashboardWidgets(&$ciniki, $tnid, $args) {

    $widgets = array(
        'qruqsp.dashboard.date1' => array(
            'name' => 'Date & Time',
            'category' => 'Misc',
            'options' => array(
                '24hour' => array(
                    'label' => '24 Hour Time', 
                    'type' => 'toggle',
                    'default' => 'no',
                    'toggles' => array('no'=>'No', 'yes'=>'Yes'),
                ),
            ),
        ),
        'qruqsp.dashboard.date2' => array(
            'name' => 'Date',
            'category' => 'Misc',
            'options' => array(
            ),
        ),
        'qruqsp.dashboard.date3' => array(
            'name' => 'Time',
            'category' => 'Misc',
            'options' => array(
                '24hour' => array(
                    'label' => '24 Hour Time', 
                    'type' => 'toggle',
                    'default' => 'no',
                    'toggles' => array('no'=>'No', 'yes'=>'Yes'),
                ),
            ),
        ),
        'qruqsp.dashboard.sunup1' => array(
            'name' => 'Sun/Moon Rise & Set Times',
            'category' => 'Misc',
            'options' => array(
                '24hour' => array(
                    'label' => '24 Hour Time', 
                    'type' => 'toggle',
                    'default' => 'no',
                    'toggles' => array('no'=>'No', 'yes'=>'Yes'),
                ),
            ),
        ),
        'qruqsp.dashboard.moon1' => array(
            'name' => 'Moon Phase',
            'category' => 'Misc',
            'options' => array(
            ),
        ),
        'qruqsp.dashboard.cal1' => array(
            'name' => 'ICS Calendar',
            'category' => 'Misc',
            'options' => array(
                'days' => array(
                    'label' => 'Days',
                    'type' => 'toggle',
                    'toggles' => array('1'=>'1', '2'=>'2', '3'=>'3', '4'=>'4', '5'=>'5', '6'=>'6', '7'=>'7'), 
                    'default' => '1',
                ),
                'whitespace' => array(
                    'label' => 'Line Wrap',
                    'type' => 'toggle',
                    'toggles' => array('wrap'=>'Yes', 'nowrap'=>'No'), 
                    'default' => 'wrap',
                ),
/*                'dir' => array(
                    'label' => 'Multi-Day Direction',
                    'type' => 'toggle',
                    'toggles' => array('v'=>'Vertical', 'h'=>'Horizontal'), 
                    'default' => 'v',
                ), */
                'font-size' => array(
                    'label' => 'Font Size',
                    'type' => 'toggle',
                    'toggles' => array('8'=>'8', '10'=>'10', '12'=>'12', '14'=>'14', '16'=>'16', '18'=>'18', '20'=>'20', '22'=>'22'), 
                    'default' => '14',
                ),
            ),
        ),
    );

    $color_defaults = array(
        '#FF0000',
        '#00FF00',
        '#0075FF',
        '#FF00FF',
        '#FFFF00',
        '#00FFFF',
        '#FF7500',
        '#7500FF',
        '#A0A0FF',
        '#FFA0A0',
        );

    for($i = 1; $i <= 10; $i++ ) {
        $widgets['qruqsp.dashboard.cal1']['options']["file{$i}"] = array(
            'label' => "ICS File {$i}",
            'type' => 'text',
            'default' => '',
            );
        $widgets['qruqsp.dashboard.cal1']['options']["refresh{$i}"] = array(
            'label' => "Refresh Time",
            'type' => 'select',
            'options' => array('5'=>'5 Min', '15'=>'15 Min', '30'=>'30 Min', '60'=>'1 Hour', '300'=>'5 Hours', '720'=>'12 Hours', '1440'=>'24 Hours'), 
            'default' => '1440',
            );
        $widgets['qruqsp.dashboard.cal1']['options']["color{$i}"] = array(
            'label' => "Color",
            'type' => 'colour',
            'default' => $color_defaults[($i-1)],
            );
    }

    return array('stat'=>'ok', 'widgets'=>$widgets);
}
?>
