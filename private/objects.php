<?php
//
// Description
// -----------
// This function returns the list of objects for the module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function qruqsp_dashboard_objects(&$ciniki) {
    //
    // Build the objects
    //
    $objects = array();


    $objects['dashboard'] = array(
        'name' => 'Dashboards',
        'sync' => 'yes',
        'o_name' => 'dashboard',
        'o_container' => 'dashboards',
        'table' => 'qruqsp_dashboards',
        'fields' => array(
            'name' => array('name'=>'Name'),
            'permalink' => array('name'=>'Permalink', 'default'=>''),
            'theme' => array('name'=>'Theme', 'default'=>'default'),
            'password' => array('name'=>'Password', 'default'=>''),
            'settings' => array('name'=>'Settings', 'default'=>''),
            ),
        'history_table' => 'qruqsp_dashboard_history',
        );
    $objects['panel'] = array(
        'name' => 'Panel',
        'sync' => 'yes',
        'o_name' => 'panel',
        'o_container' => 'panels',
        'table' => 'qruqsp_dashboard_panels',
        'fields' => array(
            'title' => array('name'=>'Title'),
            'sequence' => array('name'=>'Order', 'default'=>''),
            'panel_ref' => array('name'=>'Panel'),
            'settings' => array('name'=>'Settings', 'default'=>''),
            ),
        'history_table' => 'qruqsp_dashboard_history',
        );

    //
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
