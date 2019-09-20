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
            'dashboard_id' => array('name'=>'Dashboard'),
            'title' => array('name'=>'Title'),
            'sequence' => array('name'=>'Order', 'default'=>''),
            'numrows' => array('name'=>'Rows'),
            'numcols' => array('name'=>'Columns'),
            'settings' => array('name'=>'Settings', 'default'=>''),
            ),
        'history_table' => 'qruqsp_dashboard_history',
        );
    $objects['cell'] = array(
        'name' => 'Cell',
        'sync' => 'yes',
        'o_name' => 'cell',
        'o_container' => 'cells',
        'table' => 'qruqsp_dashboard_cells',
        'fields' => array(
            'panel_id' => array('name'=>'Panel'),
            'row' => array('name'=>'Row'),
            'col' => array('name'=>'Column'),
            'rowspan' => array('name'=>'Row Span'),
            'colspan' => array('name'=>'Column Span'),
            'widget_ref' => array('name'=>'Widget Reference'),
            'settings' => array('name'=>'Settings', 'default'=>''),
            ),
        'history_table' => 'qruqsp_dashboard_history',
        );

    //
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
