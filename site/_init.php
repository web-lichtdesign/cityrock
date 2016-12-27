<?php

setlocale (LC_ALL, 'de_DE');

require_once('_func.php');
include_once('inc/user.php');

// add session for user authentication
session_start();

// configuration parameters
//$root_directory = "/cityrock";
$root_directory = "/verwaltung";

//Nutzerrechte
$level_0 = array('Administrator');
$level_1 = array('Administrator', 'Verwaltung');
$level_2 = array('Administrator', 'Verwaltung', 'Ausbilder');
// variables used throughout the templates
$profile = null;
$title = null;
$content = null;

$navigation = null;

if(isset($_SESSION['user'])) {
    $user = User::withUserObjectData($_SESSION['user']);
    $navigation = renderNavigation($user);
}

$content_class = null;
$hide_navigation = false;

$day_translations = array(
    'Monday'    => 'Montag',
    'Tuesday'   => 'Dienstag',
    'Wednesday' => 'Mittwoch',
    'Thursday'  => 'Donnerstag',
    'Friday'    => 'Freitag',
    'Saturday'  => 'Samstag',
    'Sunday'    => 'Sonntag'
);

?>
