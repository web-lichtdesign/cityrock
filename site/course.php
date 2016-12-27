<?php

include_once('_init.php');

$required_roles = array('Administrator');
$level_0 = array('Administrator');
$level_1 = array('Administrator', 'Verwaltung');


function renderCourseOverview($course_types_filtered, $course_types, $alert = null, $alertClass = null)
{




}

function renderForm($course_types, $root_directory, $course = null, $dateGet = null, $id = null)
{
    $content = "";
    if (!$course) {
        $course = array();
    }
    include_once('_courseForm.php');
    return $content;
}

if (User::withUserObjectData($_SESSION['user'])->hasPermission($GLOBALS['level_2'])) {

    $course_types = getCourseTypes();
    $course_types_filtered = getCourseTypesFiltered();
    /***********************************************************************/
    /* Process form data												   */
    /***********************************************************************/
    if (isset($_POST['date-1'])) {
        // add all given dates

        $dates = array();

        $counter = 1;
        while ($counter < 6) {
            if ($_POST["date-$counter"]) {
                $date = array(
                    "date" => $_POST["date-{$counter}"],
                    "start" => $_POST["start-{$counter}"],
                    "end" => $_POST["end-{$counter}"],
                    "id" => $_POST["id-{$counter}"]
                );

                $dates[] = $date;
            }
            $counter++;
        }

        $course_data = array();

        //Intervall ID abfragen


        $course_data['course_type_id'] = $_POST['type'];

        //if ($_POST['title'])
            $course_data['title'] = $_POST['title'];

        //if ($_POST['interval'] >= 0)
            $course_data['interval_designator'] = $_POST['interval'];

        //if ($_POST['staff'] != null)
            $course_data['min_staff'] = $_POST['staff'];

        if (isset($_POST['staff_cancel']))
            $course_data['staff_cancel'] = 1;

        //if ($_POST['registrants'] != null)

            $course_data['max_participants'] = $_POST['registrants'];

        //if ($_POST['registrants_age'])
            $course_data['participants_age'] = $_POST['registrants_age'];
        //if ($_POST['comment'])
            $course_data['comment'] = $_POST['comment'];

        //if ($_POST['created_from'])
            $history = $_POST['created_from'];

        //if ($_POST['street'])
            $course_data['street'] = $_POST['street'];

        //if ($_POST['phone'])
            $course_data['phone'] = $_POST['phone'];

        //if ($_POST['email'])
            $course_data['email'] = $_POST['email'];

        //if ($_POST['name'])
            $course_data['name'] = $_POST['name'];


        if ($_POST['zip_city']) {
            $address_array = explode(" ", $_POST['zip_city']);

            if (count($address_array) > 1) {
                if (is_numeric($address_array[0])) {
                    $course_data['zip'] = $address_array[0];
                    unset($address_array[0]);
                }

                $course_data['city'] = join(' ', $address_array);
            }
        }

        if (isset($_POST['id'])) {
            // update course


            $success = updateCourse($_POST['id'], $course_data, $dates, $history);

            $title = "Kurs bearbeiten";

            if ($success) {
                header('Location: ' . $root_directory . "/course/" . $_POST['id']);
            } else
                $content = "Fehler: Kurs konnte nicht bearbeitet werden.";
        } else {
            // create course

            $db = Database::createConnection();
            $sql = "select interval_id from course order by interval_id desc limit 1";
            $interval_id = $db->query($sql);
            $interval_id = $interval_id->fetch_assoc()['interval_id'];
            $interval_id++;
            $course_data['interval_id'] = $interval_id;

            //Intervalle laden
            $sql = "select num_days from repeat_interval where id like " . $course_data['interval_designator'];
            $days = $db->query($sql);
            $dm = $days->fetch_assoc()['num_days'];
            $db->close();


            //Enddatum festlegen
            if($_POST['repeat-end-date']){
                //debug($date('j.m.y', strtotime($_POST['repeat-end-date'])));
                //$end_date = $date('j.m.y', strtotime($_POST['repeat-end-date']));
                $course_data['interval_end']= $_POST['repeat-end-date'];

            }else if ($_POST['repeat-end-repeat']){
                $r = $_POST['repeat-end-repeat'];
                $r = $r -1;
                $plusdays = $dm * $r;

                $course_data['interval_end']= date('j.m.Y', strtotime('+'.$plusdays.' days', strtotime($dates[0]['date'])));
                //$end_date = date('j.m.Y', strtotime('+2 years', strtotime($dates[0]['date'])));


            }else {
                $course_data['interval_end'] = date('j.m.Y', strtotime('+2 years', strtotime($dates[0]['date'])));
            }

            $first= addCourse($course_data, $dates, $history);
            $success = true;
            //Schleife für wiederholende Termine erstellen
            if ($course_data['interval_designator'] != 5) {
                addCourseInterval($course_data, $dates, $history);
            }

            $title = "Neuer Kurs";

            if ($success && $first)
                header('Location: ' . $root_directory . "/course/{$first}");
                //echo "Okay";
            else
                $content = "Fehler: Kurs konnte nicht erstellt werden.";
        }
    } else {
        if (isset($_GET["id"])) {
            if ($_GET["id"] == "new") {

                $dateString = "";
                $timeString = "";

                if (isset($_GET["date"])) {
                    $fullDate = str_replace('-I-', '.', $_GET["date"]);
                    $dateArray = explode("-S-", $fullDate);

                    $dateString = $dateArray[0];

                    if (count($dateArray) > 1) {
                        $timeString = $dateArray[1];
                    }
                }

                /***********************************************************************/
                /* Course new 										                   */
                /***********************************************************************/
                $title = "Neuer Kurs";
                $dateGet = $_GET['action'];
                $dateGet = str_replace('-I-', '.', $dateGet);
                $content = renderForm($course_types, $root_directory, null, $dateGet, null);
                $content .="
                <script>
                $(document).ready(function() {
   
      $(window).bind('beforeunload', function() {
         
         return 'Wollen Sie die Seite wirklich verlassen?';
      });
   
 
   $('form').submit(function() {
      $(window).unbind('beforeunload');
   });
});
</script>
                    ";

            } else {
                /***********************************************************************/
                /* Course edit																											   */
                /***********************************************************************/

                if (isset($_GET["action"]) && $_GET["action"] == "edit" && $user->hasPermission($GLOBALS['level_1'])) {
                    $course_id = $_GET["id"];
                    $course = getCourse($course_id);
                    $number_of_days = count($course['dates']);
                    $title = "Kurs bearbeiten";
                    $content = renderForm($course_types, $root_directory, $course, null, $course_id);
                    $content .="
                    ";
                    /*
                    $content .= "
						<form method='post' onsubmit='return cityrock.validateForm(this);'>
							<label for='type'>Kurstyp</label>
							<select name='type' id='type'>";

                    foreach ($course_types as $key => $course_type) {
                        if ($course['course_type_id'] == $key)
                            $content .= "<option selected value='{$key}'>{$course_type['title']}</option>";
                        else
                            $content .= "<option value='{$key}'>{$course_type['title']}</option>";
                    }

                    $content .= "
							</select>
							<label for='title'>Kunde/Titel</label>
							<input type='text' name='title' value='{$course['title']}'>";

                    $counter = 1;
                    foreach ($course['dates'] as $date) {
                        $content .= "
							<div class='day-container'>
								<h3 class='inline'>Tag {$counter}</h3><span>(<a href='#' class='remove-day'>entfernen</a>)</span>
								
								<div class='form-group'>
								<label for='date-{$counter}' class='col-sm-2 control-label'>Datum</label>
								<div class='col-sm-10'>
								<input type='text' value='{$date['date']->format('d.m.Y')}' name='date-{$counter}' class='date datepicker-cr'></div></div>
								<div class='form-group'>
								<label for='start-{$counter}' class='col-sm-2 control-label'>Uhrzeit Start</label>
								<div class='col-sm-10'>
								<input type='text' value='{$date['date']->format('h:i')}' name='start-{$counter}' class='time timepicker-cr'></div></div>
								<div class='form-group'>
								<label for='end-{$counter}' class='col-sm-2 control-label'>Uhrzeit Ende</label>
								<div class='col-sm-10'>
								<input type='text' name='end-{$counter}' class='time timepicker-cr' value='" . getEndTime($date['date'], $date['duration']) . "'></div></div>
							</div>";

                        $counter++;
                    }

                    $content .= "
							<span class='add-day'>
								<a href='#' id='add-day'>Tag hinzufügen</a>
							</span>
							<label for='interval'>Wiederholen</label>
							<select name='interval'>";

                    $intervalArray = getIntervals();

                    foreach ($intervalArray as $interval) {
                        if ($course['interval_designator'] != null)
                            $selected = $interval['id'] == $course['interval_designator'] ? "selected" : "";
                        else
                            $selected = $interval['description'] == "nie" ? "selected" : "";

                        $content .= "<option value='{$interval['id']}' {$selected}>{$interval['description']}</option>";
                    }

                    $content .= "
							</select>
							<label for='staff'>Anzahl Übungsleiter</label>
							<input type='text' name='staff' value='{$course['min_staff']}'>
							
							<input type='checkbox' name='staff_cancel' value='{$course['staff_cancel']}'>
							<label for='staff_cancel'>Übungsleiter dürfen sich selbst wieder austragen</label>
							<br />
							<label for='registrants'>Anzahl an Teilnehmern</label>
							<input type='text' name='registrants' value='{$course['max_participants']}'>
							<label for='registrants_age'>Alter der Teilnehmer</label>
							<input type='text' name='registrants_age' value='{$course['participants_age']}'>
							<br />
							<h3>Adresse der Veranstaltung</h3>
							<label for='street'>Straße</label>
							<input type='text' name='street' value='{$course['street']}'>
							<label for='zip_city'>PLZ/Ort</label>
							<input type='text' name='zip_city' value='{$course['zip']} {$course['city']}'>
							<label for='phone'>Telefon</label>
							<input type='text' name='phone' value='{$course['phone']}'>

							
							<input type='hidden' value='{$course_id}' name='id'>
							<a href='./' class='button error'>Abbrechen</a>
							<input type='submit' value='Speichern' class='button'>
						</form>";
                    //<input type='hidden' value='{$number_of_days}' name='days'>*/
                } else {
                    /***********************************************************************/
                    /* Course details													   */
                    /***********************************************************************/

                    $course_id = $_GET["id"];
                    $course = getCourse($course_id);
                    $registrants = getRegistrants($course_id);
                    $staff = getStaff($course_id);
                    $staff_num = count($staff);
                    $all_users = getUsers();
                    $user_id = $_SESSION['user']['id'];

                    $user_is_subscribed = false;

                    $staff_list = "<span class='staff-list''>";
                    $index = 1;
                    foreach ($staff as $u) {
                        $userObj = $u->serialize();
                        $staff_list .= "<span>ÜL {$index}: {$userObj['first_name']} {$userObj['last_name']} <a href='#' user-id='{$userObj['id']}' class='remove-staff''>entfernen</a></span>";
                        if ($userObj['id'] == $_SESSION['user']['id']) $user_is_subscribed = true;
                        $index++;
                    }
                    $staff_list .= "</span>";


                    $user_list = "<option value='-1' style='display:none;'></option>";
                    foreach ($all_users as $u) {
                        $userObj = $u->serialize();

                        $user_name = $userObj['first_name'] . " " . $userObj['last_name'];
                        if (trim($user_name) == "") $user_name = $userObj['username'];

                        $user_list .= "<option value='{$userObj['id']}'> {$user_name}</option>";
                    }

                    $showAddStaffLink = $course['min_staff'] > count($staff) ? "" : "display: none;";

                    $title = "Kursdetails";
                    $content = "
                        
                        <div class='table-responsive'>
                        <table class='table table-striped '>
                            <tr>
                                <td class='col-sm-2' >Titel</td>
                                <td colspan='4'>{$course['title']}</td>
                            </tr>
                            
                            <tr>
                                <td class='col-sm-2' >Typ</td>
                                <td colspan='4'>{$course_types[$course['course_type_id']]['title']}</td>
                            </tr>
                            
                            <tr>
                                <td class='col-sm-2' >Teilnehmer</td>
								<td colspan='4'>" . count($registrants) . "/{$course['max_participants']} <a href='./{$course_id}/registrants' class='btn btn-primary btn-xs'>Anzeigen</a></td>
                            </tr>
                            
                             
                            ";
                    $counter = 1;
                    foreach ($course['dates'] as $date) {

                        $content .= "
                            <tr class='" . colorizeStaffRow($date['id'], $course['min_staff']) . "'>
                                <td class='col-sm-2'>Datum (Tag $counter) <br />
                                    Uhrzeit (Tag $counter)</td>
                                <td class='col-sm-2'>{$date['date']->format('d.m.Y')} <br />
                                {$date['date']->format('G:i')} - " . getEndTime($date['date'], $date['duration']) . " Uhr
                                </td>
                                
                                <td class='col-sm-3' >" . getStaffDateList(getStaffDate($date['id']), $date['id']) . "</td>
                                <td class='col-sm-1' >" . getNumOfStaffPerDate($date['id']) . "/{$course['min_staff']}" . "</td>
                                
                                <td class='col-sm-3' >
                                <a href='#' date-id='{$date['id']}' class='add-staff btn btn-primary btn-xs " . displayAddStaffButton($date['id'], $course['min_staff']) . "'>Übungsleiter hinzufügen</a><br />
                                <select class='staff-list form-control' style='display: none'>
											{$user_list}
										</select>
										<a href='#' date-id='{$date['id']}' user-id='{$_SESSION['user']['id']}'class='btn btn-xs btn-primary " . displaySelfToStaffButton($date['id'], $course['min_staff'], $_SESSION['user']['id']) . " add-self-staff'>Selbst eintragen</a>
										
                                    </td>
                                
                            </tr>";

                        $counter++;
                    }
                    $last_change;
                    //{$course['created_at']->format('d.m.Y')}

                    $content .= "
                            <tr id='history' style='display: none'>
                                <td class='col-sm-2' >Verlauf</td><td colspan='4'>";
                                foreach ($course['history'] as $h){
                                    $last_change = $h;
                                    $content .= "{$h['history_user']}
                                &nbsp; {$h['history_datetime']->format('d.m.Y ') }Uhr  {$h['history_datetime']->format('d.m.Y ') }<br>";
                                }
                                $content .="
                            </td></tr>
                            <tr id='last-change'>
                                <td class='col-sm-2' >Verlauf</td>
                                <td colspan='4'>{$last_change['history_user']}
                                &nbsp; {$last_change['history_datetime']->format('G:i  ')} Uhr {$last_change['history_datetime']->format('d.m.Y ')}   <button class='btn btn-xs btn-primary btn-show-history'>Verlauf anzeigen</button></td>
                            </tr>";
                    $content .="

                            </table>
                            </div>
                            <h3>Kundeninformationen</h3>
                            <div class='table-responsive'>
                            <table class='table table-striped'>
                            <tr>
                                <td class='col-sm-2' >Name</td>
								<td>{$course['name']}</td>
							</tr>
							<tr>
								<td class='col-sm-2' >Telefon</td>
								<td>{$course['phone']}</td>
							</tr>
							<tr>
								<td class='col-sm-2' >E-Mail</td>
								<td>{$course['email']}</td>
							</tr>
							<tr>	
                                <td class='col-sm-2' >Addresse</td>
								<td>{$course['street']}, {$course['zip']} {$course['city']}</td>
                            </tr>
                            
                            
  
                        </table>
                        <table class='table table-striped'>
                            <tr>
                                <td class='col-sm-2' >Kommentar</td>
                                <td >{$course['comment']}</td>
                            </tr>
                        </table>
</table>
                        </div>";


                    $content .= "
                            <span style='display: none;' id='course-id'>{$course_id}</span>
							<form class='inline' action='{$root_directory}/confirmation' method='post'>
								<input type='hidden' name='confirmation' value='true'>
								<input type='hidden' name='action' value='delete_course'>
								<input type='hidden' name='description' value='Kurs'>
								<input type='hidden' name='table' value='course'>
								<input type='hidden' name='id' value='{$course_id}'>
								<a href='#' class='btn btn-danger confirm'>Löschen</a>
							</form>
						</span>";

                    if ($course['interval_designator'] != 5) {
                        $content .= "<span>
							<form class='inline' action='{$root_directory}/confirmation' method='post'>
								<input type='hidden' name='confirmation' value='true'>
								<input type='hidden' name='action' value='delete_all'>
								<input type='hidden' name='description' value='Kurse'>
								<input type='hidden' name='table' value='course'>
								<input type='hidden' name='id' value='{$course_id}'>
								<input type='hidden' name='interval_id' value='{$course['interval_id']}'>
								<a href='#' class='btn btn-danger confirm'>Alle zukünftigen Löschen</a>
							</form>
						</span>";

                    }
                    if($user->hasPermission($GLOBALS['level_1'])){
                        $content.="<a href='{$root_directory}/course/{$course_id}/edit' class='btn btn-primary'>Bearbeiten</a>";
                    }
                    $content .= "
                        <!--Button menü eintreagen -->
                        <div class='btn-group'>
  <button type='button' class='btn btn-primary dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
    Eintragen <span class='caret'></span>
  </button>
  <ul class='dropdown-menu'>
    <li><a href='#' class='add-staff-course' user-id='{$user_id}' course-id='$course_id'>Kurs eintragen</a> </li>";
                    if ($course['interval_designator'] != 5) {
                        $content .= "
    <li><a href='#' class='add-staff-interval' user-id='{$user_id}' course-id='$course_id' interval-id='{$course['interval_id']}'>Serie eintragen</a></li>";
                    }
    $content .="
    <li><a href='#' data-toggle='modal' data-target='#add-other-user'>Anderen Nutzer eintragen</a></li>
    
  </ul>
</div>
<!-- Button menü austragen -->
";
                    if(isStaffCourse($course_id, $user_id)) {

                    $content .= "
                        <div class='btn-group' >
  <button type = 'button' class='btn btn-danger dropdown-toggle' data-toggle = 'dropdown' aria-haspopup='true' aria-expanded = 'false' >
                            Austragen <span class='caret' ></span >
  </button >
  <ul class='dropdown-menu' >
    <li ><a href='#' class='remove-staff-course' user-id='{$user_id}' course-id='$course_id'>Kurs austragen</a> </li >";
                        if ($course['interval_designator'] != 5) {
                            $content .= "
    <li><a href='#' class='remove-staff-interval' user-id='{$user_id}' course-id='$course_id' interval-id='{$course['interval_id']}'>Serie austragen</a></li>";
                        }

    $content.="
    
  </ul >
</div >";
		}
				$content .="		<a href='{$root_directory}/events' class='btn btn-primary'>Zurück</a>
						<a href='#' class='hidden btn btn-warning' data-toggle='modal' data-target='#duplicate-event-modal'>Kopieren</a>
						
						<!-- Add another Modal -->
<div class='modal fade' id='add-other-user' tabindex='-1' role='dialog' aria-labelledby='myModalLabel'>
    <div class='modal-dialog' role='document'>
        <div class='modal-content'>
            <div class='modal-header'>
                <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                <h4 class='modal-title' id='myModalLabel'>Anderen Nutzer eintragen</h4>
            </div>
            <div class='modal-body'>
	        <select id='select-add-staff' class='form-control'>{$user_list}</select>
	        <script>
	            $('#select-add-staff').change(function() {
	                
	                var user_id = $(this).val();
	                
	                $('#ascb').attr('user-id', user_id);
	                $('#assb').attr('user-id', user_id);
	                
	              
	            })
            </script>
                    
                
            </div>
            <div class='modal-footer'>
                
                <button type='button' id='ascb' class='btn btn-primary add-staff-course' data-dismiss='modal' user-id='{$user_id}' course-id='$course_id' >Kurs eintragen</button>
                <button type='button' id='assb' class='btn btn-primary add-staff-interval' data-dismiss='modal' onclick=''  user-id='{$user_id}' course-id='$course_id' interval-id='{$course['interval_id']}'>Serie eintragen</button>
                <button type='button' class='btn btn-danger' data-dismiss='modal'>Abbrechen</button>
                
            </div>
        </div>
    </div>
</div>


<!-- duplicat-modal -->
<div class='modal fade' id='duplicate-event-modal' tabindex='-1' role='dialog' aria-labelledby='myModalLabel'>
    <div class='modal-dialog' role='document'>
        <div class='modal-content'>
            <div class='modal-header'>
                <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                <h4 class='modal-title' id='myModalLabel'>Termin kopieren</h4>
            </div>
            <div class='modal-body'>
            <label class='control-label' for='title'>Titel</label>
	        <input type='text' name='title' value='{$course['title']}'>
            <label class='control-label' for='date'>Datum</label> 
            <input type='text' class='datepicker-cr' name='date'>
            
            <script type='text/javascript'>
            $('.datepicker-cr').datetimepicker({
                 format: 'DD.MM.YYYY'

                 }
                );
            </script>
                
            </div>
            <div class='modal-footer'>
                
                <button type='button' class='btn btn-primary' data-dismiss='modal' onclick=''>Kurs eintragen</button>
                <button type='button' class='btn btn-primary' data-dismiss='modal' onclick=''>Serie eintragen</button>
                <button type='button' class='btn btn-danger' data-dismiss='modal'>Abbrechen</button>
                
            </div>
        </div>
    </div>
</div>

";


                }
            }
        } else {
            /***********************************************************************/
            /* Course overview													   */
            /***********************************************************************/
            $title = "Kletterkursverwaltung";
            $content = "
                <a href='./course/new' class='btn btn-success'>Kletterkurs hinzufügen</a>
                <p></p>
				<label for='course-filter'>Wähle einen Kurstyp, um die Liste zu filtern: </label>
				<select class='filter form-control col-sm-4' name='course-filter'>
					<option value='Alle'>Alle</option>";

            foreach ($course_types_filtered as $course_type) {
                $content .= "<option value='{$course_type['title']}'>{$course_type['title']}</option>";
            }

            $content .= "
				</select>
				
					<table class='table'>
					    <tr>
						<th class='col-sm-2'>Kurstyp</th>
						<th class='col-sm-2'>Datum</th>
						<th class='col-sm-2'>Teilnehmer</th>
						<th class='col-sm-6'></th>
						</tr>
					</table>";

            $start_date = new DateTime();
            $start_date->modify('-1 day');
            $end_date = new DateTime();
            $end_date->modify('+1 year');


            $courses = getCourses(false, 1, $start_date, $end_date);
            $courses = array_merge($courses, getCourses(false, 2, $start_date, $end_date), getCourses(false, 3, $start_date, $end_date));

            $cleaned_up_courses = removePastDates($courses, new DateTime());
            $repeating_courses = createIntervalDates($courses, new DateTime());

            $all_courses = array_merge($cleaned_up_courses, $repeating_courses);
            usort($all_courses, "courseSort");

            $month = null;
            foreach ($all_courses as $course) {
                $course_type_title = $course_types[$course['course_type_id']]['title'];

                $registrants = getRegistrants($course['id']);
                $num_registrants = count($registrants);

                if (getMonth($course['date']) != $month) {
                    if ($month) {
                        $content .= "</table>";
                    }
                    $month = getMonth($course['date']);
                    $content .= "<table class='table'>
                            <tr>
                                <th colspan='4'>{$month}</th>
                            </tr>";
                }

                $item_class = strtolower($course_types[$course['course_type_id']]['title']);
                if(!$course['title']){
                    $course['title'] = $course_type_title;
                }
                $content .= "
					<tr class='filter-item {$item_class}'>
						<td class='col-sm-2'>{$course['title']}</td>
						<td class='col-sm-2'>{$course['date']->format('d.m.Y')}</td>
						<td class='col-sm-2'>{$num_registrants}/ {$course['max_participants']} <a class='btn btn-primary btn-xs' href='./course/{$course['id']}/registrants'>Liste</a></td>
						<td class='col-sm-6'><a class='btn btn-primary' href='./course/{$course['id']}'>Details</a></td>
						
					</tr>";
            }

            $content .= "
				</div>
				";

        }
    }
} else {
    $title = "Kletterkursverwaltung";
    $content = "Du hast keine Berechtigung für diesen Bereich der Website.";
}

$content_class = "course";
include('_main.php');
?>
