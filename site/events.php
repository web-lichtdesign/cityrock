<?php

include_once('_init.php');

$authenticated_user = $_SESSION['user'];
$authenticated_user_object = User::withUserObjectData($_SESSION['user']);

$title = "Terminliste";
$content = "Noch nicht fertig.";

$number_of_days = 60;
$course_types = getCourseTypes();
$level_0 = array('Administrator');
$level_1 = array('Administrator', 'Verwaltung');


// include config_lite library
require_once('lib/config/Lite.php');
$config = new Config_Lite('basic.cfg');

if(isset($_GET["id"])) {
    /***********************************************************************/
    /* Event details                                                       */
    /***********************************************************************/
    $course_id = $_GET["id"];
    $course = getCourse($course_id);
    $user_id = $_SESSION['user']['id'];

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
								<td colspan='4'>".count($registrants) ."/{$course['max_participants']} </td>
                            </tr>
                            
                             
                            ";
    $counter = 1;

    foreach ($course['dates'] as $date) {

        $content .= "
                            <tr class='".colorizeStaffRow($date['id'], $course['min_staff'] )."'>
                                <td class='col-sm-2'>Datum (Tag $counter) <br />
                                    Uhrzeit (Tag $counter)</td>
                                <td class='col-sm-2'>{$date['date']->format('d.m.Y')} <br />
                                {$date['date']->format('G:i')} - " . getEndTime($date['date'], $date['duration']) . " Uhr
                                </td>
                                
                                <td class='col-sm-3' >".getStaffDateList(getStaffDate($date['id']), $date['id'])."</td>
                                <td class='col-sm-1' >".getNumOfStaffPerDate($date['id'])."/{$course['min_staff']}"."</td>
                                
                                <td class='col-sm-3' >
                                
                                <select class='staff-list form-control' style='display: none'>
											{$user_list}
										</select>
                                    <a  date-id='{$date['id']}' user-id='{$_SESSION['user']['id']}'class='".displaySelfToStaffButton($date['id'], $course['min_staff'], $_SESSION['user']['id'])." btn btn-primary btn-xs add-self-staff'>Selbst eintragen</a></td>
                                
                            </tr>";

        $counter++;
    }

    $content.="
                           

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
								<td>{$course['street']}, {$course['plz']} {$course['city']}</td>
                            </tr>
                            
                            
  
                        </table>
                         <table class='table table-striped'>
                            <tr>
                                <td class='col-sm-2' >Kommentar</td>
                                <td colspan='4'>{$course['comment']}</td>
                            </tr>
                        </table>
                        </div>
						
						<a  href='{$root_directory}/events' class='btn btn-primary'>Zurück</a>
						<span style='display: none;' id='course-id'>{$course_id}</span>
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




}
else {
    /***********************************************************************/
    /* Event overview                                                      */
    /***********************************************************************/
    $title = "Terminliste";

    $table_heading = "
        <table class='table'>
            <tr>
                <th>Art</th>
                <th>Uhrzeit</th>
                <th></th>
            </tr>
        ";

    $user_id = $authenticated_user['id'];

    $all_active = $_GET['filter'] === 'all' || !isset($_GET['filter']) ? 'active' : '';
    $user_active = $_GET['filter'] === 'user' ? 'active' : '';
    $open_active = $_GET['filter'] === 'open' ? 'active' : '';

    $content = "
    <div id='event-filter' class='filter'>
        <span event-type='all' class='all {$all_active}'>Alle Termine</span>
        <div class='attention'>
        <span event-type='user' class='{$user_active}'>Meine Termine</span>
        <span event-type='open' class='{$open_active}'>Offene Termine</span>";

        if($user->hasPermission($level_1)){
        $content .= "<a class='btn btn-success' href='./course/new'>Neuer Termin erstellen </a>";
             }
        $content .= "  </div> </div>";

    



        $date_object = new DateTime();
    $date_object->modify('-1 day');
    $date = $date_object->format('d.m.Y');
    $date_object = $date_object->modify('-2 days');
    $duration_string = 'P' . $number_of_days . 'D';
    $end_date = clone $date_object;
    $end_date->add(new DateInterval($duration_string));
    $start_date = new DateTime();
    $start_date = $start_date->modify('-1 day');
    $courses = getCourses(false, null, $start_date, $end_date, true);

    $cleaned_up_events = removePastDates($courses, $date_object);


    $merged_events = $cleaned_up_events;

    $all_events = removeDateExceptions($merged_events);

    $temp_date = $start_date;

    foreach ($all_events as $course) {
        $staff = getStaffDate($course['date_id']);
        $staff_num = count($staff);
        $staff_is_full = $staff_num >= $course['min_staff'];
        $user_list = "";
        $user_is_subscribed = false;
        foreach($staff as $u) {
            $userObj = $u->serialize();
            $user_list .= $userObj['first_name'] . ' ' . $userObj['last_name'] . '<br />';
            if($userObj['id'] == $authenticated_user['id']) $user_is_subscribed = true;
        }

        // check if authenticated user is registered for this course
        if($user_active && !$user_is_subscribed)
            continue;
        // check if course has missing staff
        if($open_active && $staff_is_full)
            continue;
        $display_subscribe_button = $user_is_subscribed || $staff_is_full ? "display: none;" : "";
        $display_unsubscribe_button = $user_is_subscribed ? "" : "display: none;";





        if ($course['date']->format('d.m.Y') != $d) {
            $d = $course['date']->format('d.m.Y');
            $date = $course['date']->format('l, d.m.Y');
            $date = strtr($date, $day_translations);

            $day_color = date('l', strtotime($d)) == 'Sunday' ||  date('l', strtotime($d)) == 'Saturday' ? '#990000' : '';
            $content .= "<table class='table'>
                <tr>
            <th colspan='3'><span style='color: {$day_color};'>{$date}</span></th>
            </tr>";
                }

        $hours = floor($course['duration'] / 60);
        $minutes = ($course['duration'] / 60 - $hours) * 60;

        $course_duration = 'PT' . $hours . 'H' . $minutes . 'M';

        $course_end_time = clone $course['date'];
        $course_duration_object = new DateInterval($course_duration);
        $course_end_time = $course_end_time->add($course_duration_object);
        $course_end_time = $course_end_time->format('G:i');

        $course_type_title = $course_types[$course['course_type_id']]['title'];
        $course_type_color = $course_types[$course['course_type_id']]['color'];





        if(!$course['title']){
            $course['title'] = $course_type_title;
        }
        $content .= "
        <tr class='".colorizeOverviewStaffRow($course['date_id'], $course['min_staff'])."'>
            <td class='col-md-3' ><span style='display: inline-block; width: 1em; height: 1em; margin-right: 0.2em; background-color: {$course_type_color}'></span>
                {$course['title']}</td>
            
            <td class='col-md-2' >{$course['date']->format('G:i')} - {$course_end_time} Uhr</td>
            <td class='col-md-1'>{$staff_num}/{$course['min_staff']}</td> 
            <td  >
            
            ";


        if($user->hasPermission($GLOBALS['level_2'])) {

            $content .= "<a class='btn btn-primary' href='{$root_directory}/course/{$course['id']}'>Details</a>";
        } else {

            $content .= "<a class='btn btn-primary' href='{$root_directory}/events/{$course['id']}'>Details</a>";
        }
        $content .= "    
          <a  style='{$display_subscribe_button}' course-id='{$course['id']}' date-id='{$course['date_id']}' user-id='{$_SESSION['user']['id']}'class='btn btn-primary add-self-staff-list'>Eintragen</a>";
        if(false) {

            $content.=" <a href = '#' style = '{$display_unsubscribe_button}' course - id = '{$course['id']}' date - id = '{$course['date_id']}' user - id = '{$_SESSION['user']['id']}'class='btn btn-danger remove-self-staff-list' > Austragen</a > ";
            }
        $content.= "</td>
        ";
    }

    $content .= "
    </div>"; 
}

$content_class = "course";
include('_main.php');
?>
