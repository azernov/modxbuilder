<?php

$debug = true;     // if true, will include verbose debugging info, including SQL errors.
$verbose = true;    // if true, will print status info.

// If you specify a table prefix, you probably want this set to 'true'. E.g. if you
// have custom tables alongside the modx_xxx tables, restricting the prefix ensures
// that you only generate classes/maps for the tables identified by the $package_table_prefix.
$restrict_prefix = true;




//------------------------------------------------------------------------------
//  DO NOT TOUCH BELOW THIS LINE
//------------------------------------------------------------------------------
if (!defined('MODX_CORE_PATH')) {
    print_msg('<h1>Reverse Engineering Error</h1>
        <p>MODX_CORE_PATH not defined! Did you include the correct config file?</p>');
    exit;
}

$xpdo_path = $sources['root'] . 'core/xpdo/xpdo.class.php';
include_once ( $xpdo_path );

// A few variables used to track execution times.
$mtime= microtime();
$mtime= explode(' ', $mtime);
$mtime= $mtime[1] + $mtime[0];
$tstart= $mtime;

// Validations
if ( empty($package_name) ) {
    print_msg('<h1>Reverse Engineering Error</h1>
        <p>The $package_name cannot be empty!  Please adjust the configuration and try again.</p>');
    exit;
}

// Create directories if necessary
$dirs = array($sources["package_dir"], $sources["schema_dir"] ,$sources["mysql_class_dir"], $sources["class_dir"]);

foreach ($dirs as $d) {
    if ( !file_exists($d) ) {
        if ( !mkdir($d, 0777, true) ) {
            print_msg( sprintf('<h1>Reverse Engineering Error</h1>
                <p>Error creating <code>%s</code></p>
                <p>Create the directory (and its parents) and try again.</p>'
                , $d
            ));
            exit;
        }
    }
    if ( !is_writable($d) ) {
        print_msg( sprintf('<h1>Reverse Engineering Error</h1>
            <p>The <code>%s</code> directory is not writable by PHP.</p>
            <p>Adjust the permissions and try again.</p>'
            , $d));
        exit;
    }
}

if ( $verbose ) {
    print_msg( sprintf('<br/><strong>Ok:</strong> The necessary directories exist and have the correct permissions inside of <br/>
        <code>%s</code>', $sources["package_dir"]));
}

// Delete/regenerate map files?
if ( file_exists($sources["new_xml_schema_file"]) && !$regenerate_schema && $verbose) {
    print_msg( sprintf('<br/><strong>Ok:</strong> Using existing XML schema file:<br/><code>%s</code>',$sources["new_xml_schema_file"]));
}

$xpdo = new xPDO("mysql:host=$database_server;dbname=$dbase", $database_user, $database_password, $table_prefix);


// Set the package name and root path of that package
$xpdo->setPackage($package_name, $sources["package_dir"]);
$xpdo->setDebug($debug);

$manager = $xpdo->getManager();
//$generator = $manager->getGenerator();  // Станадртное получение mysql генератора

//Подключаем наш класс генератора
include_once ( $sources['root']."modxbuilder/$package_name/tools/xpdogenerator.class.php");

$generator = new xPDOGenerator_my($manager);  // Свой генератор, который умеет добавлять префиксы к имени класса
$generator->setClassPrefix($package_class_prefix);

//Use this to create an XML schema from an existing database
if ($regenerate_schema)
{
    if(!file_exists($sources['new_xml_schema_file']))
    {
        touch($sources['new_xml_schema_file']);
    }
    $xml = $generator->writeSchema($sources["new_xml_schema_file"], $package_name, 'xPDOObject', '', $restrict_prefix, $package_table_prefix);
    if ($verbose)
    {
        print_msg( sprintf('<br/><strong>Ok:</strong> XML schema file generated: <code>%s</code>',$sources["new_xml_schema_file"]));
    }
}

// Use this to generate classes from your schema
if ($regenerate_classes)
{
    print_msg('<br/>Attempting to remove/regenerate class files...');
    delete_class_files( $sources["class_dir"] );
    delete_class_files( $sources["mysql_class_dir"] );
}

// Use this to generate maps from your schema
if ($regenerate_maps)
{
    print_msg('<br/>Attempting to remove/regenerate map files...');
    delete_map_files( $sources["mysql_class_dir"] );
}

$mtime= microtime();
$mtime= explode(" ", $mtime);
$mtime= $mtime[1] + $mtime[0];
$tend= $mtime;
$totalTime= ($tend - $tstart);
$totalTime= sprintf("%2.4f s", $totalTime);

if ($verbose) {
    print_msg("<br/><br/><strong>Finished!</strong> Execution time: {$totalTime}<br/>");

    if ($regenerate_schema)
    {
        print_msg("<br/>If you need to define aggregate/composite relationships in your XML schema file, be sure to regenerate your class files.");
    }
}

/*------------------------------------------------------------------------------
INPUT: $dir: a directory containing class files you wish to delete.
------------------------------------------------------------------------------*/
function delete_class_files($dir) {
    global $verbose;

    $all_files = scandir($dir);
    foreach ( $all_files as $f ) {
        if ( preg_match('#\.class\.php$#i', $f)) {
            if ( unlink("$dir/$f") ) {
                if ($verbose) {
                    print_msg( sprintf('<br/>Deleted file: <code>%s/%s</code>',$dir,$f) );
                }
            }
            else {
                print_msg( sprintf('<br/>Failed to delete file: <code>%s/%s</code>',$dir,$f) );
            }
        }
    }
}

function delete_map_files($dir) {
    global $verbose;

    $all_files = scandir($dir);
    foreach ( $all_files as $f ) {
        if ( preg_match('#\.map\.inc\.php$#i', $f)) {
            if ( unlink("$dir/$f") ) {
                if ($verbose) {
                    print_msg( sprintf('<br/>Deleted file: <code>%s/%s</code>',$dir,$f) );
                }
            }
            else {
                print_msg( sprintf('<br/>Failed to delete file: <code>%s/%s</code>',$dir,$f) );
            }
        }
    }
}
/*------------------------------------------------------------------------------
Formats/prints messages.  The behavior is different if the script is run
via the command line (cli).
------------------------------------------------------------------------------*/
function print_msg($msg) {
    if ( php_sapi_name() == 'cli' ) {
        $msg = preg_replace('#<br\s*/>#i', "\n", $msg);
        $msg = preg_replace('#<h1>#i', '== ', $msg);
        $msg = preg_replace('#</h1>#i', ' ==', $msg);
        $msg = preg_replace('#<h2>#i', '=== ', $msg);
        $msg = preg_replace('#</h2>#i', ' ===', $msg);
        $msg = strip_tags($msg) . "\n";
    }
    print $msg;
}

/* EOF */