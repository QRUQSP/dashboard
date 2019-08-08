<?php
//
// This script is the only entry point from the public web server
// to the dashboard. All content in the dashboard should be 
// read only for display purposes.
//
$start_time = microtime(true);

//
// Load ciniki
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
// Some systems don't follow symlinks like others
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}

//
// Initialize Ciniki
//
require_once($ciniki_root . '/ciniki-mods/core/private/loadCinikiConfig.php');
require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');

$ciniki = array();
if( ciniki_core_loadCinikiConfig($ciniki, $ciniki_root) == false ) {
    print_error(NULL, 'There is currently a configuration problem, please try again later.');
    exit;
}

//
// Load required functions
//
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'checkModuleFlags');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInit');

//
// Initialize Database
//
$rc = ciniki_core_dbInit($ciniki);
if( $rc['stat'] != 'ok' ) {
    print_error($rc, 'There is a currently a problem with the system, please try again later.');
    exit;
}

//
// Parse the GET and POST variables
//
$args = array(
    'path'=>array(),        // Default path is empty, root of dashboard
);
if( isset($_GET) && is_array($_GET) ) {
    foreach($_GET as $k => $v) {
        $args[$k] = $v;
    }
}
if( isset($_POST) && is_array($_POST) ) {
    foreach($_POST as $k => $v) {
        $args[$k] = $v;
    }
}

//
// Split the Request variables
//
$uri = preg_replace('/^\//', '', $_SERVER['REQUEST_URI']);
$u = preg_split('/\?/', $uri);
$args['path'] = preg_split('/\//', $u[0]);
if( !is_array($args['path']) ) {
    $args['path'] = array($args['path']);
}

//
// Default to master tenant
//
$tnid = $ciniki['config']['ciniki.core']['master_tnid'];

//
// Setup the base_url the dashboard is running under
//
$args['base_url'] = '';

//
// Check if tenant is not master based on URL
//
if( isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] != '' ) {
    //
    // Lookup the domain in the database
    //
    $strsql = "SELECT tnid "
        . "FROM ciniki_tenant_domains "
        . "WHERE domain = '" . ciniki_core_dbQuote($ciniki, $_SERVER['HTTP_HOST']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.dashboard', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        print_error($rc, 'There is a currently a problem with the system, please try again later.');
        exit;
    }
    if( isset($rc['tenant']) ) {
        $tnid = $rc['tenant']['tnid'];
    } 
    elseif( isset($args['path'][0]) && $args['path'][0] != '' ) {
        //
        // Check if the tenant is a subdomain
        //
        $strsql = "SELECT id "
            . "FROM ciniki_tenants "
            . "WHERE sitename = '" . ciniki_core_dbQuote($ciniki, $args['path'][0]) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'qruqsp.dashboard', 'tenant');
        if( $rc['stat'] != 'ok' ) {
            print_error($rc, 'There is a currently a problem with the system, please try again later.');
            exit;
        }
        if( isset($rc['tenant']) ) {
            $args['base_url'] .= '/' . array_shift($args['path']);
            $tnid = $rc['tenant']['id'];
        } 
    }
    //
    // If nothing found, then will operate as master tenant
    //
}

//
// Load the modules for the tenant
//
ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkModuleAccess');
$rc = ciniki_tenants_checkModuleAccess($ciniki, $tnid, 'qruqsp', 'dashboard');
if( $rc['stat'] != 'ok' ) {
    print_error($rc, 'Dashboard not enabled');
    exit;
}

//
// Check if being run under sub directory
//
if( isset($args['path'][0]) && $args['path'][0] == 'dashboard' ) {
    array_shift($args['path']);
    $args['base_url'] .= '/dashboard';
}

//
// Generate the dashboard
//
ciniki_core_loadMethod($ciniki, 'qruqsp', 'dashboard', 'private', 'generate');
$rc = qruqsp_dashboard_generate($ciniki, $tnid, $args);
if( $rc['stat'] != 'ok' ) {
    print_error($rc, 'Unable to generate dashboard');
    exit;
}
elseif( isset($rc['html']) && $rc['html'] != ''  ) {
    print $rc['html'];
    exit;
} 
// Just data returned no html, send back as json.
elseif( isset($rc['data']) && $rc['data'] != ''  ) {
    print json_encode($rc);
    exit;
} 
else {
    print_error(NULL, 'No content - empty dashboard');
    exit;
}

//
// Done
//
exit;

//
// Support functions
//
function print_error($rc, $msg) {
print "<!DOCTYPE html>\n";
?>
<html>
<head><title>Error</title></head>
<body style="margin: 0px; padding: 0px; border: 0px;">
<div style="display: table-cell; text-align: middle; width: 100vw; height: 100vh; margin: 0; padding: 0; box-sizing: border-box; vertical-align: middle;">
    <div style="margin: 0 auto; vertical-align: middle; text-align: center; ">
            <p style="font-size: 1.5em;">Error:  <?php echo $msg; ?></p>
            <br/><br/>
<?php
    if( $rc != null && isset($rc['stat']) && $rc['stat'] != 'ok' ) {
        print '<p style="font-size: 1em; "><b>Errors</b><br/>';
        while($rc != null ) {
            print $rc['err']['code'] . ': ' . $rc['err']['msg'] . '<br/><br/>'; 
            if( isset($rc['err']['err']) ) {
                $rc = $rc['err'];
            } else {
                $rc = null;
            }
        }
        print '</p>';
    }
?>
    </div>
</div>
</body>
</html>
<?php
}
?>
