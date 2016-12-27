<?php
$counter = 1;
$number_of_days = count($course['dates']);
$content .= "
<form method='post' class='form-horizontal' data-toggle='validator'><!-- onsubmit='return cityrock.validateForm(this);'-->
    <div class='form-group'>
        <label class='col-sm-2 control-label'for='type'>Kurstyp</label>
        <div class=\"col-sm-10\">
            <select name='type' id='type' class='form-control' required>
                <option value=''>--Bitte Wählen--</option> ";

foreach ($course_types as $key => $course_type) {
    if ($course['course_type_id'] == $key)
        $content .= "<option selected value='{$key}'>{$course_type['title']}</option>";
    else
        $content .= "<option value='{$key}'>{$course_type['title']}</option>";
}

$content .= "
            </select>
        </div>
    </div>
    <div class='form-group'>
        <label for='title' class='col-sm-2 control-label'>Titel(optional)</label>
        <div class='col-sm-10'><input type='text' placeholder='' value='{$course['title']}' name='title'></div></div>
   
";
if (!$course['dates']) {

    $content .= "
    <hr>
    <h3 class='inline'>Tag 1</h3>
    <div class='form-group'>
        <label for='date-1' class='col-sm-2 control-label'>Datum</label>
        <div class='col-sm-10'> <input class='datepicker-cr' type='text'  value='{$dateGet}' name='date-1' class='date'></div></div>
    <div class='form-group'>

        <label for='start-1' class='control-label col-sm-2'>Uhrzeit Start</label>
        <div class='col-sm-10'>

            <input type='text' class=\"timepicker-cr\" name='start-1' id='start-1' value='12:00'  />
        </div></div>


    <div class='form-group'>
        <label class='control-label col-sm-2' for='end-1'>Uhrzeit Ende</label>
        <div class='col-sm-10'>
            <input type='text' placeholder='z.B. 12:00' class='timepicker-cr'  id='end-1' name='end-1' class='time' value='14:00' >
        </div></div>
        <div class='col-sm-2'></div>
        <div class='col-sm-10'>
        <button type='button' class='button-date btn btn-primary btn-xs' start='10:00' end='12:00' date-counter='1'>10 - 12</button>
        <button type='button' class='button-date btn btn-primary btn-xs' start='12:00' end='14:00' date-counter='1'>12 - 14</button>
        <button type='button' class='button-date btn btn-primary btn-xs' start='14:00' end='16:00' date-counter='1'>14 - 16</button>
        <button type='button' class='button-date btn btn-primary btn-xs' start='16:00' end='18:00' date-counter='1'>16 - 18</button>
        </div>
        <hr>
   
							";
} else {
    $content .= "<hr>";
    foreach ($course['dates'] as $date) {
        $content .= "
							<div class='day-container'>
								<h3 class='inline'>Tag {$counter}</h3><span>(<a class='remove-day'>entfernen</a>)</span>
								
								<div class='form-group'>
								<label for='date-{$counter}' class='col-sm-2 control-label'>Datum</label>
								<div class='col-sm-10'>
								<input type='text' value='{$date['date']->format('d.m.Y')}' name='date-{$counter}' class='date datepicker-cr'></div></div>
								<div class='form-group'>
								<label for='start-{$counter}' class='col-sm-2 control-label'>Uhrzeit Start</label>
								<div class='col-sm-10'>
								<input type='text' value='{$date['date']->format('G:i')}' name='start-{$counter}' id='start-{$counter}' class='time timepicker-cr'></div></div>
								<div class='form-group'>
								<label for='end-{$counter}' class='col-sm-2 control-label'>Uhrzeit Ende</label>
								<div class='col-sm-10'>
								<input type='text' name='end-{$counter}' id='end-{$counter}'class='time timepicker-cr' value='" . getEndTime($date['date'], $date['duration']) . "'></div></div>
								<input type='hidden' value='{$date['id']}' name='id-{$counter}'>
							        
							</div>
							<div class='col-sm-2'></div>
        <div class='col-sm-10'>
        <button type='button' class='button-date btn btn-primary btn-xs' start='10:00' end='12:00' date-counter='{$counter}'>10 - 12</button>
        <button type='button' class='button-date btn btn-primary btn-xs' start='12:00' end='14:00' date-counter='{$counter}'>12 - 14</button>
        <button type='button' class='button-date btn btn-primary btn-xs' start='14:00' end='16:00' date-counter='{$counter}'>14 - 16</button>
        <button type='button' class='button-date btn btn-primary btn-xs' start='16:00' end='18:00' date-counter='{$counter}'>16 - 18</button>
        </div>
							<hr>";

        $counter++;
    }


}

$content .= "
    <span class='add-day'>
    <a id='add-day'>Tag hinzufügen</a>
						</span>

    
    ";
//Prüfen ob Kurs schon existiert
if ($course['id']) {
    $content .= "<div class='form-group'>
                         <label class='control-label col-sm-2' for='staff'>Anzahl Übungsleiter</label>
                         <div class='col-sm-10'>
                <input type='text' name='staff' value='{$course['min_staff']}'>
                </div></div>

                <div class='form-group'>
        <label for='registrants' class='control-label col-sm-2'>Anzahl an Teilnehmern</label>
        <div class='col-sm-10'>
            <input type='text' name='registrants' value='{$course['max_participants']}'>
        </div></div>
        <input type='hidden' name='interval' value='{$course['interval_designator']}'>";
} else {
    $content .= "
                
            
                <div class='form-group'>
        <label for='interval' class='col-sm-2 control-label'>Wiederholen</label>
        <div class='col-sm-10'>
            <select class='form-control' name='interval'>";

    $intervalArray = getIntervals();

    foreach ($intervalArray as $interval) {
        $selected = $interval['description'] == "nie" ? "selected" : "";

        $content .= "<option value='{$interval['id']}' {$selected}>{$interval['description']}</option>";
    }

    $content .= "
            </select>

        </div></div>
                <div class='form-group'>
                         <label class='control-label col-sm-2' for='staff'>Anzahl Übungsleiter</label>
                         <div class='col-sm-10'>
        <input type='text' name='staff' value='0'>
        </div></div>
                <div class='form-group'>
        <label for='registrants' class='control-label col-sm-2'>Anzahl an Teilnehmern</label>
        <div class='col-sm-10'>
            <input type='text' name='registrants' value='0'>
        </div></div>";
}
$content .= "

    
    
    
    
    <div class='form-group'>
        <label for='registrants_age' class='control-label col-sm-2'>Alter der Teilnehmer</label>
        <div class='col-sm-10'>
            <input type='text'  name='registrants_age' value='{$course['participants_age']}'>
        </div></div>
    
    <input type=\"hidden\" name=\"created_from\" value=\"{$_SESSION['user']['id']}\">
    
    
<!-- Information Kunde -->
    <h3>Adresse der Veranstaltung/des Kunden</h3>
    <div class='form-group'>
        <label for='name' class='col-sm-2 control-label'>Name</label>
        <div class='col-sm-10'>
            <input type='text' placeholder='' value='{$course['name']}' name='name'>
        </div></div>
        
    <div class='form-group'>
        <label for='street' class='col-sm-2 control-label'>Straße</label>
        <div class='col-sm-10'>
            <input type='text' placeholder='' value='{$course['street']}' name='street'>
        </div></div>
    <div class='form-group'>
        <label for='zip_city' class='col-sm-2 control-label'>PLZ/Ort</label>
        <div class='col-sm-10'>
            <input type='text' value='{$course['zip']} {$course['city']}' placeholder='Bitte mit Leerzeichen zwischen PLZ und Ort eingeben' name='zip_city'>
        </div></div>
    <div class='form-group'>
        <label for='phone' class='col-sm-2 control-label'>Telefon</label>
        <div class='col-sm-10'>
            <input type='text' placeholder='' value='{$course['phone']}' name='phone'>
        </div></div>
        
        <div class='form-group'>
        <label for='email' class='col-sm-2 control-label'>E-Mail</label>
        <div class='col-sm-10'>
            <input type='text' placeholder='' value='{$course['email']}' name='email'>
        </div></div>
        
        <div class='form-group'>
        <label for='comment' class='col-sm-2 control-label'>Kommentar</label>
        <div class='col-sm-10'>
        
            <textarea name='comment' id='comment' class='editor' value=''>{$course['comment']}</textarea>
        </div>
    </div>";
if ($number_of_days) {

    $content .= "<input type='hidden' value='{$number_of_days}' name='days'>
                    <input type='hidden' value='{$id}' name='id'>";
} else {

    $content .= "<input type='hidden' value='1' name='days'>";
}
$content .= "<input type='button' value='Abbrechen' class='btn btn-danger button-back'>
    
         
    <input value='Speichern' type='submit' class='btn btn-primary'>
</form>

<!--  Datetime -Picker init--> 
<script type=\"text/javascript\">

            $(function () {
                $('.datepicker-cr').datetimepicker({
                 format: 'DD.MM.YYYY'

                 }
                );
            });
            $(function () {
                $('.timepicker-cr').datetimepicker({
                 format: 'HH:mm'}


                );
            });

            function reload_picers()
            {
                $('.datepicker-cr').datetimepicker({
                            format: 'DD.MM.YYYY'

                    }
                    );
                  $('.timepicker-cr').datetimepicker({
                    format: 'HH:mm'}


                    );
            }

            $(function(){
					 $('#add-day').click(function()
					 {
					      window.setTimeout('reload_picers()', 1000);
					 });
                 });



        </script>";
