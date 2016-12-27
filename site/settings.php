<?php

include_once('_init.php');



if(User::withUserObjectData($_SESSION['user'])->hasPermission($GLOBALS['level_0'])) {

	// include config_lite library
	require_once('lib/config/Lite.php');
	$config = new Config_Lite('basic.cfg');

	if (isset($_POST['save'])) {
		$config['email'] = array('subject-confirm' => "{$_POST['subject-confirm']}",
			'body-confirm' => "{$_POST['body-confirm']}",
			'subject-reminder' => "{$_POST['subject-reminder']}",
			'body-reminder' => "{$_POST['body-reminder']}",
			'notification' => $_POST['notification']);

		$config['system'] = array('deadline' => $_POST['deadline'],
			'administration' => $_POST['administration'],
			'administration-list' => "{$_POST['administration-list']}",
			'staff-cancel-deadline' => "{$_POST['staff-deadline']}" ); 

		$result = $config->save();

		$typeColorArray = array();

		// save course type colors
		foreach($_POST as $key => $value) {
		    if(strpos($key, 'type-color-') === 0) {
		    	$courseTypeId = substr($key, 11);

		    	if(array_key_exists($courseTypeId, $typeColorArray)) {
					$typeColorArray[$courseTypeId]['color'] = $value;
		    	}
		    	else {
		    		$typeColorArray[$courseTypeId] = array('color' => $value);
		    	}
		    } 
		    else if(strpos($key, 'type-active-') === 0) {
				$courseTypeId = substr($key, 12);

		    	if(array_key_exists($courseTypeId, $typeColorArray)) {
					$typeColorArray[$courseTypeId]['active'] = 1;
		    	}
		    	else {
		    		$typeColorArray[$courseTypeId] = array('active' => 1);
		    	}
		    }
		}

		$result = $result && saveCourseTypeColors($typeColorArray);

		if ($result) {
			$title = "Einstellungen";
			$content = "Einstellungen wurden gespeichert.";
		} else {
			$title = "Einstellungen";
			$content = "Fehler! Einstellungen konnte nicht gespeichert werden.";
		}
	} else {
		$title = "Einstellungen";

		$courseColors = "<h3>Farbcodierung der Kursarten</h3>
			<span class='table'>
				<thead>
					<tr>
						<span class='table-cell'>Kursart</span>
						<span class='table-cell'>Farbcode</span>		
						<span class='table-cell'></span>
						<span class='table-cell'>Terminliste</span>				
					</tr>
				</thead>";

		$courseTypes = getCourseTypes();

		foreach ($courseTypes as $courseType) {
			$checked = intval($courseType['active']) === 1 ? 'checked' : '';

			$courseColors .= "
				<span class='table-row'>
					<span class='table-cell'>{$courseType['title']}</span>
					<span class='table-cell'>
						<input type='text' value='" . $courseType['color'] . "' name='type-color-" . $courseType['id'] . "'>
					</span>
					<span class='table-cell'>
						<span style='display: block; width: 2em; height: 2.3em; background-color: {$courseType['color']}'></span>
					</span>
					<span class='table-cell'><input type='checkbox' name='type-active-" . $courseType['id'] . "' {$checked} /><label for='type-active-" . $courseType['id'] . "'>Aktiv</label></span>
				</span>";
		}

		$courseColors .= "</span>";

		$content = "
			<form method='post'>
				<h3>Email Editor</h3>
				<h4>Erinnerungsmail</h4>
				<label for='subject-reminder'>Betreff</label>
				<input type='text' value='{$config['email']['subject-reminder']}' name='subject-reminder'>
				<label for='body-reminder'>Nachricht</label>
				<textarea class='editor' name='body-reminder' rows='8'>{$config['email']['body-reminder']}</textarea>
				<label for='notification'>Wieviel Tage vor dem Kurs soll die Erinnerungsemail verschickt werden?</label>
				<input type='text' value='{$config['email']['notification']}' name='notification'>
				<h4>Bestätigungsmail</h4>
				<label for='subject-confirm'>Betreff</label>
				<input type='text' value='{$config['email']['subject-confirm']}' name='subject-confirm'>
				<label for='body-confirm'>Nachricht</label>
				<textarea class='editor' name='body-confirm' rows='8'>{$config['email']['body-confirm']}</textarea>
				<h3>Systemeinstellungen</h3>
				<label for='deadline'>Wieviel Tage vor Kursbeginn ist Anmeldeschluss?</label>
				<input type='text' value='{$config['system']['deadline']}' name='deadline'>
				<label for='administration'>Wieviel Tage vor Kursbeginn soll die Teilnehmerliste an die Verwaltung geschickt werden?</label>
				<input type='text' value='{$config['system']['administration']}' name='administration'>
				<label for='administration-list'>An welche Email-Adressen soll die Liste geschickt werden? <br />Bitte die Adressen mit // voneinander trennen.</label>
				<textarea name='administration-list' rows='6'>{$config['system']['administration-list']}</textarea>
				<label for='staff-deadline'>Bis wieviele Tage vor Kursbeginn können sich Mitarbeiter wieder austragen?</label>
				<input type='text' value='{$config['system']['staff-cancel-deadline']}' name='staff-deadline'>" .
				$courseColors . "
				<input type='hidden' name='save' value='1'>
				<p>Keine weiteren Einstellungen vorhanden.</p>
				<a href='./' class='button error'>Abbrechen</a>
				<input type='submit' value='Speichern' class='button'>
				<script>CKEDITOR.replace( 'body-reminder' );</script>
	<script>CKEDITOR.replace( 'body-confirm' );</script>
			</form>";
	}
}
else {
	$title = "Einstellungen";
	$content = "Du hast keine Berechtigung für diesen Bereich der Website.";
}

$content_class = "settings";
include('_main.php');
?>
