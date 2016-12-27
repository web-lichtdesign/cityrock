<?php

include_once('_init.php');

$title = "Belegungsplan";
$level_0 = array('Administrator');
$level_1 = array('Administrator', 'Verwaltung');

if(isset($_SESSION['user'])) {
	$user_id = $_SESSION['user']['id'];

	$content = "
	<div id='calendar-filter' class='filter'>
		<span event-type='all' class='all active'>Alle Termine</span>
		<div class=' attention'>
		<span event-type='user' user-id='{$user_id}'>Meine Termine</span>
		<span event-type='open'>Offene Termine</span>
		";

        if($user->hasPermission($level_1)){
        $content .= "<a class='btn btn-success' href='./course/new'>Neuer Termin erstellen </a>";
             }
        $content .= "  </div> </div>";



	/*Neuer Button Kurs erstellen hinzugefügt */
	
	$content .= "<div id='calendar'></div>";
}
else {
	$content = "Du musst dich erst einloggen, um den Kalender anzeigen zu können.";
}

$content_class = "calendar";
include('_main.php');
?>
