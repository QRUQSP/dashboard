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
        'qruqsp.dashboard.moon1' => array(
            'name' => 'Moon Phase',
            'category' => 'Misc',
            'options' => array(
            ),
        ),
    );

    return array('stat'=>'ok', 'widgets'=>$widgets);
}
?>
