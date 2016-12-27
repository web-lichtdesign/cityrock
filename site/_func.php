<?php
require_once('inc/database.php');
require_once('inc/user.php');

/*****************************************************************************/
/* Course functionality																											 */
/*****************************************************************************/

/**
 * Finds all courses with the given course type or all courses if no type is
 * given.
 *
 * @param int $course_type_id
 * @param int $archive
 * @return array of course arrays
 */



function getCourses($archive = false, $course_type_id = null, $start = null, $end = null, $filter = false)
{

    $db = Database::createConnection();


    /*SELECT course.id, course.course_type_id, course.title, course.email, course.name, course.max_participants, course.participants_age, course.min_staff, course.staff_cancel, course.interval_designator, course.street, course.zip, course.city, course.phone, date.id as date_id,date.start, date.duration, course_has_staff.user_id AS staff_id, repeat_interval.num_days AS day_interval, repeat_interval.num_months AS month_interval, repeat_interval.weekend
             FROM course
             LEFT JOIN date
             ON course.id=date.course_id
                 LEFT JOIN course_has_staff
               ON course.id=course_has_staff.course_id*/

    $sql = "SELECT course.id, course.course_type_id, course.title, course.email, course.name, course.max_participants, course.participants_age,
 course.min_staff, course.staff_cancel, course.interval_designator, course.street, course.zip, course.city, course.phone, 
 date.id as date_id,date.start, date.duration, date_has_staff.user_id AS staff_id FROM course LEFT JOIN date ON course.id=date.course_id LEFT JOIN date_has_staff ON 
 date.id=date_has_staff.date_id inner join course_type on course.course_type_id= course_type.id
    				
        			";

    if ($course_type_id != null)
        $sql .= " WHERE course_type_id=$course_type_id";

    if ($start && $end) {
        $startString = $start->format('Y-m-d H:i:s');
        $endString = $end->format('Y-m-d H:i:s');
        if (!$course_type_id) {
            $sql .= " WHERE ";
        } else {
            $sql .= " AND ";
        }
        $sql .= "(DATE(start) BETWEEN '{$startString}' AND '{$endString}')";
    } else {
        $tempDate = new DateTime();
        $dateString = $tempDate->format('Y-m-d H:i:s');

        if ($archive) {
            if (!$course_type_id) {
                $sql .= " WHERE ";
            } else {
                $sql .= " AND ";
            }
            $sql .= " (DATE(start) <= '{$dateString}' ";
            //AND repeat_interval.num_days=0 AND repeat_interval.num_months=0)
        } else {
            if (!$course_type_id) {
                $sql .= " WHERE ";
            } else {
                $sql .= " AND ";
            }
            $sql .= "(DATE(start) >= '{$dateString}' )";
            //OR repeat_interval.num_days>0 OR repeat_interval.num_months>0)
        }
    }
    if($filter) {
        $sql .= " AND course_type.active = 1 ORDER BY course.id, start;";

    }

    $result = $db->query($sql);

    $course_array = array();
    if ($result->num_rows > 0) {
        $last_id = -1;
        $last_date = null;

        while ($row = $result->fetch_assoc()) {
            if ($row['id'] != $last_id) {
                $course_array[] = $row;

                $last_id = $row['id'];
                $last_date = $row['date_id'];
            } else {
                if ($last_date != $row['date_id']) {
                    $course_array[] = $row;
                    $last_date = $row['date_id'];


                }
                $additional_staff = $row['staff_id'];

                if ($additional_staff) {
                    foreach ($course_array as $course) {
                        if ($course['date_id'] == $last_date) {
                            $all_staff = explode(",", $course['staff_id']);
                            if (!in_array($additional_staff, $all_staff)) {

                                $course['staff_id'] .= ',' . $additional_staff;

                            }

                        }
                    }


                    // $course_array[array_search($last_date, $course_array)]

                    reset($course_array);
                }


            }
        }
    }

    foreach ($course_array as $key => $course) {
        $course_array[$key]['date'] = new DateTime($course_array[$key]['start']);
    }

    $db->close();

    usort($course_array, "courseSort");
    return $course_array;
}


/**
 * Find the course with the given id.
 *
 * @param int $course_id
 * @return array of courses
 */
function getCourse($course_id)
{

    $db = Database::createConnection();

    $result = $db->query("SELECT course.id as id, course_type_id, title, max_participants, participants_age, min_staff, staff_cancel, interval_id, interval_designator, street, zip, city, phone,  name, email, comment, repeat_interval.num_days AS day_interval, repeat_interval.num_months AS month_interval, repeat_interval.weekend
						  FROM course
						  	LEFT JOIN repeat_interval
        					ON course.interval_designator=repeat_interval.id
						  WHERE course.id={$course_id};");

    if ($result->num_rows > 0) {
        $result = $result->fetch_assoc();
    }

    $dates = $db->query("SELECT start, duration, id 
						 FROM date 
						 WHERE course_id={$course_id} 
						 ORDER BY start;");

    $dates_array = array();
    if ($dates->num_rows > 0) {
        while ($row = $dates->fetch_assoc()) {
            $dates_array[] = array("date" => new DateTime($row['start']),
                "duration" => $row['duration'], "id" => $row['id']);
        }
    }
    $history = $db->query("SELECT * from course_has_history WHERE course_id={$course_id}");
    if ($history->num_rows > 0) {
        while ($row = $history->fetch_assoc()) {
            $uname = getUserById($row['user_id']);
            $uname = $uname['username'];

            $result['history'][] = array("history_user" => $uname, "history_datetime" => new DateTime($row["datetime"]));
        }
    }
    //$result['created_from'] = getUserById($result['created_from']);
    //$result['created_from'] = $result['created_from'] ["username"];
    //$result['created_at'] = new DateTime($result['created_at']);
    $result['dates'] = $dates_array;

    $db->close();

    return $result;
}

function getCourseExceptions($course_id)
{

    $db = Database::createConnection();

    $result = $db->query("SELECT date, cancelled
					      FROM date_exception
					      WHERE course_id={$course_id};");

    $db->close();

    $exception_array = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $exception_array[] = $row;
        }
    }

    return $exception_array;
}

function addCourseException($course_id, $date, $cancelled = true)
{

    $db = Database::createConnection();

    $result = $db->query("INSERT INTO date_exception (course_id, date, cancelled) 
						  VALUES ({$course_id}, '{$date}', {$cancelled});");

    $db->close();

    return $result;
}

/**
 * Finds all course types.
 *
 * @return array of course types with $key='id' and $value='title'
 */
function getCourseTypes()
{

    $db = Database::createConnection();

    $result = $db->query("SELECT id, title, color, active FROM course_type;");

    $course_type_array = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $course_type_array[$row['id']] = $row;
        }
    }

    $db->close();

    return $course_type_array;
}

function getCourseTypesFiltered()
{

    $db = Database::createConnection();

    $result = $db->query("SELECT id, title, color, active FROM course_type where title like 'Toprope'OR title like 'Schnupper'OR title like'Vorstieg'");

    $course_type_array = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $course_type_array[$row['id']] = $row;
        }
    }

    $db->close();

    return $course_type_array;
}

/**
 * Sets the color for all course types.
 *
 * @return true if successful
 */
function saveCourseTypeColors($typeColorArray)
{

    $db = Database::createConnection();

    $result = true;

    foreach ($typeColorArray as $key => $value) {

        if (!isset($value['active']))
            $value['active'] = 0;

        $result = $result && $db->query("UPDATE course_type 
									     SET color='{$value['color']}', active={$value['active']}
										 WHERE id=$key;");
    }

    return $result;
}

/**
 * Inserts a course with the given course data and dates into the database.
 *
 * @param array $course_data
 * @param array $dates array of dates
 * @return boolean true in case it was successful
 */
function addCourse($course_data, $dates, $history)
{

    $db = Database::createConnection();

    $key_list = "";
    $value_list = "";
    foreach ($course_data as $key => $value) {
        $key_list .= ", " . $key;

        if (is_numeric($value))
            $value_list .= ", " . $value;
        else
            $value_list .= ", '" . $value . "'";
    }
    $key_list = substr($key_list, 2);
    $value_list = substr($value_list, 2);

    //echo "Keys: " . $key_list;
    //echo "Values: " . $value_list;

    $result = $db->query("INSERT INTO course ({$key_list})
						  VALUES ({$value_list});");
    $course_id = $db->insert_id;

    if ($result) {
        foreach ($dates as $date) {

            $datetime_start_string = formatDate($date['date']) . " " . formatTime($date['start']);
            $datetime_start = DateTime::createFromFormat('d.m.Y G:i', $datetime_start_string);

            $datetime_end_string = formatDate($date['date']) . " " . formatTime($date['end']);
            $datetime_end = DateTime::createFromFormat('d.m.Y G:i', $datetime_end_string);

            $mysql_time = $datetime_start->format('Y-m-d H:i:s');
            $duration = $datetime_end->diff($datetime_start);
            $duration = $duration->h * 60 + $duration->i;

            $result = $db->query("INSERT INTO date (start, duration, course_id) 
								  VALUES ('$mysql_time', $duration, $course_id);");
        }
    }
    $sql = "INSERT into course_has_history (course_id, user_id) VALUES ({$course_id}, {$history})";
    $sql;
    $db->query($sql);
    $db->close();

    return $course_id;
}

function addCourseInterval($course_data, $dates, $history){

    $db = Database::createConnection();

    $sql = "select num_days from repeat_interval where id like " . $course_data['interval_designator'];
    $days = $db->query($sql);
    $dm = $days->fetch_assoc()['num_days'];
    $db->close();


    $days = (string)$dm . " days";

    $end_date = $course_data['interval_end'];
    $datesObj[] = null;


    $success = true;


    while (DateTime::createFromFormat('j.m.Y', $dates[0]['date']) < DateTime::createFromFormat('j.m.Y', $end_date)) {
        for ($i = 0; $i < sizeof($dates); $i++) {
            $dates[$i]['date'] = date('j.m.Y', strtotime($days, strtotime($dates[$i]['date'])));

        }


        if(!addCourse($course_data, $dates, $history)){
            $success = false;
        }



    }
    return $success;


}

/**
 * Updates a course with the given id and parameters.
 *
 * @param int $id
 * @param array $course_data
 * @param array $dates array of dates
 * @return boolean true in case it was successful
 */
function updateCourse($id, $course_data, $dates, $history)
{

    $db = Database::createConnection();

    $update_list = "";
    foreach ($course_data as $key => $value) {
        $update_list .= ", " . $key . "=";

        if (is_numeric($value))
            $update_list .= $value;
        else
            $update_list .= "'" . $value . "'";
    }
    $update_list = substr($update_list, 2);


    $sql = "UPDATE course 
				SET {$update_list}
				WHERE id=$id";


    $db->query($sql);


    $dates_old = $db->query("SELECT start, duration, id 
						 FROM date 
						 WHERE course_id={$id} 
						 ORDER BY start;");

    $do = array();
    if ($dates_old->num_rows > 0) {
        while ($row = $dates_old->fetch_assoc()) {
            $do[] = array("date" => new DateTime($row['start']),
                "duration" => $row['duration'], "id" => $row['id']);
        }
    }

    foreach ($do as $dateOld) {
        $inside = false;
        foreach ($dates as $date) {

            if ($dateOld['id'] == $date['id']) {

                $inside = true;
            }
        }
        if (!$inside) {

            $result = $db->query("delete date_has_staff from date_has_staff where date_id ={$dateOld['id']}");
            $result = $db->query("delete date from date where date.id={$dateOld['id']}");

        }

    }


    foreach ($dates as $date) {

        $datetime_start_string = formatDate($date['date']) . " " . formatTime($date['start']);
        $datetime_start = DateTime::createFromFormat('d.m.Y G:i', $datetime_start_string);

        $datetime_end_string = formatDate($date['date']) . " " . formatTime($date['end']);
        $datetime_end = DateTime::createFromFormat('d.m.Y G:i', $datetime_end_string);

        $duration = $datetime_start->diff($datetime_end)->h * 60 + $datetime_start->diff($datetime_end)->i;

        $mysql_time = $datetime_start->format('Y-m-d G:i:s');

        if ($date['id']) {

            $result = $db->query("UPDATE date SET id={$date['id']},start='{$mysql_time}',duration='{$duration}',
                course_id={$id} WHERE id={$date['id']}");
            //$result = $db->query("INSERT INTO date (id, start, duration, course_id)
            //  VALUES ('{$date['id']}', '$mysql_time', $duration, $id);");
        } else {
            $result = $db->query("INSERT INTO date (start, duration, course_id) 
								  VALUES ('$mysql_time', $duration, $id);");
        }
    }
    $sql = "INSERT into course_has_history (course_id, user_id) VALUES ({$id}, {$history})";

    debug($db->query($sql));



    $db->close();

    return $result;
}

/**
 * Formats a date string to match the requirements of the MySQL date format.
 *
 * @param string $dateString
 * @return string
 */
function formatDate($dateString)
{

    $dateArray = explode(".", $dateString);

    $resultString = "";

    foreach ($dateArray as $dateComponent) {
        if (intval($dateComponent) < 10)
            $resultString .= "0" . intval($dateComponent) . ".";
        else
            $resultString .= $dateComponent . ".";
    }

    return substr($resultString, 0, -1);
}

/**
 * Formats a time string to match the requirements of the MySQL time format.
 *
 * @param string $timeString
 * @return string
 */
function formatTime($timeString)
{

    if (strlen($timeString) < 2)
        return "0" . $timeString . ":00";
    else if (strlen($timeString) < 3)
        return $timeString . ":00";
    else {
        $colonPosition = strpos($timeString, ':');
        if ($colonPosition !== false) {

            if (strlen($timeString) == 4)
                return "0" . $timeString;
            else
                return $timeString;

        }

        $dotPosition = strpos($timeString, '.');
        if ($dotPosition !== false) {

            if (strlen($timeString) == 4)
                return "0" . str_replace(".", ":", $timeString);
            else
                return str_replace(".", ":", $timeString);
        }

        return $timeString;
    }
}

/**
 *
 *
 * @param $course_id
 * @return array
 */
function getStaff($course_id)
{

    $db = Database::createConnection();

    $result = $db->query("SELECT user_id
					      FROM course_has_staff
					      WHERE course_id={$course_id}");

    $db->close();

    $user_array = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $user_array[] = User::withUserId($row['user_id']);
        }
    }

    return $user_array;
}

function getStaffDate($date_id)
{

    $db = Database::createConnection();

    $result = $db->query("SELECT user_id
					      FROM date_has_staff
					      WHERE date_id={$date_id}");

    $db->close();

    $user_array = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $user_array[] = User::withUserId($row['user_id']);
        }
    }

    return $user_array;
}

function getStaffDateList($user_array, $date_id)
{

    $staff_list = "<span class='staff-list''>";
    $user = User::withUserObjectData($_SESSION['user']);


    foreach ($user_array as $u) {
        $userObj = $u->serialize();
        $staff_list .= "{$userObj['first_name']} {$userObj['last_name']} ";
        if ($user->hasPermission($GLOBALS['level_1'])) {
            $staff_list .= "<a href='#' date-id='{$date_id}' user-id='{$userObj['id']}' class='btn btn-danger btn-xs remove-staff-date''>entfernen</a>";
        }
        $staff_list .= "<br />";
        if ($userObj['id'] == $_SESSION['user']['id']) $user_is_subscribed = true;

    }
    $staff_list .= "</span>";
    return $staff_list;

}

function getNumOfStaffPerDate($date_id)
{
    $db = Database::createConnection();

    $result = $db->query("select count(date_id) as staffNum from date_has_staff where date_id like '{$date_id}'");
    $result = $result->fetch_assoc();
    $db->close();

    return $result['staffNum'];


}

function hasDayEnoughStaff($date_id, $max_Staff)
{
    if (getNumOfStaffPerDate($date_id) == $max_Staff) {
        return true;
    } else {
        return false;
    }
}


function displayAddStaffButton($date_id, $max_Staff)
{
    if (hasDayEnoughStaff($date_id, $max_Staff)) {
        return 'hidden';
    }
}

function colorizeStaffRow($date_id, $max_Staff)
{

    if (hasDayEnoughStaff($date_id, $max_Staff)) {
        return 'success';
    } else {
        return 'danger';
    }

}

function isStaff($user_id, $date_id)
{
    $db = Database::createConnection();

    $result = $db->query("select count(date_id) as result from date_has_staff where date_id like '{$date_id}' and user_id like '{$user_id}';");
    $result = $result->fetch_assoc();
    $db->close();
    if ($result['result'] >= 1) {
        return true;
    } else {
        return false;
    }
}

function countStaffDate($date_id){
    $db = Database::createConnection();

    $result = $db->query("select count(date_id) as result from date_has_staff where date_id like '{$date_id}';");
    $result = $result->fetch_assoc();
    $db->close();
    return $result['result'];

}
function isFull($max, $date_id){

    if (countStaffDate($date_id) == $max) {
        return true;
    } else {
        return false;
    }

}
function addStaffCourse($course_id, $user_id){
    $course = getCourse($course_id);

    $full_dates = array();
    foreach ($course['dates'] as $date){
        if(!isStaff($user_id, $date['id'])){
            if(isFull($course ["min_staff"], $date['id'])){
                $full_dates[] =$date;

            }else{
                addStaffDate($date['id'], $user_id);
            }

        }

    }
    return $full_dates;

}

function addStaffInterval($course_id, $interval_id, $user_id){
    $db = Database::createConnection();

    $sql = "select id from course where interval_id = $interval_id and id >= $course_id";
    $result = $db->query($sql);
    $full_dates = array();
    while($row = $result->fetch_assoc()){
        $full_dates = array_merge($full_dates, addStaffCourse($row['id'], $user_id));


    }
    return $full_dates;

}
function isStaffCourse($course_id, $user_id){
    $course = getCourse($course_id);
    foreach ($course['dates'] as $date){
        if(isStaff($user_id, $date['id'])){
            return true;
        }
    }
    return false;
}
function createAddStaffDateError($full_dates){

    if(sizeof($full_dates) > 0){
        $err = "ERROR: Der Nutzer konnte für folgende Tage nicht eingetragen werden: <br>";
        foreach ($full_dates as $date){
            $err .= $date['date']->format('d.m.Y')."<br>";

        }
        return $err;
    }else{
        return "SUCCESS";
    }
}

function displaySelfToStaffButton($date_id, $max_Staff, $user_id)
{
    if (hasDayEnoughStaff($date_id, $max_Staff) || isStaff($user_id, $date_id)) {
        return 'hidden';
    }
}

function hasCourseEnoughStaff($dates, $max_staff)
{
    $enough = true;

    foreach ($dates as $date) {
        if (!hasDayEnoughStaff($date['id'], $max_staff)) {
            $enough = false;
        }
    }

    return $enough;

}

function colorizeOverviewStaffRow($date_id, $max_Staff)
{

    if (hasDayEnoughStaff($date_id, $max_Staff)) {
        return 'success';
    } else {
        return 'danger';
    }
}

function removeStaffCourse($course_id, $user_id){
    $db = Database::createConnection();
    $course = getCourse($course_id);
    foreach ($course['dates'] as $date){
        $sql = "DELETE from date_has_staff WHERE user_id={$user_id} and date_id={$date['id']}";
        debug($sql);
        debug($db->query($sql));
    }
    return true;

}
function removeStaffInterval($interval_id, $course_id, $user_id){
    $db = Database::createConnection();
    $sql = "select id from course where interval_id = $interval_id and id >= $course_id";
    $result = $db->query($sql);

    while($row = $result->fetch_assoc()) {
        removeStaffCourse($row['id'], $user_id);
    }
    $db->close();
    return true;
}


function renderTableOpenDays(){
    $start_date = new DateTime();
    $start_date->modify('-1 day');
    $end_date = new DateTime();
    $end_date->modify('+1 month');
    $course_types = getCourseTypes();


    $courses = getCourses(false, 1, $start_date, $end_date);
    $courses = array_merge($courses, getCourses(false, 2, $start_date, $end_date), getCourses(false, 3, $start_date, $end_date));

    $open_days = "<table border='1'><tr><th>Titel</th><th>Datum</th><th>Uhrzeit</th><th>Offen</th></tr>";
    foreach ($courses as $course){
            $course_type_title = $course_types[$course['course_type_id']]['title'];

        $hours = floor($course['duration'] / 60);
        $minutes = ($course['duration'] / 60 - $hours) * 60;

        $course_duration = 'PT' . $hours . 'H' . $minutes . 'M';

        $course_end_time = clone $course['date'];
        $course_duration_object = new DateInterval($course_duration);
        $course_end_time = $course_end_time->add($course_duration_object);
        $course_end_time = $course_end_time->format('G:i');

            $staff = countStaffDate($course['date_id']);
            if(!$course['title']){
                $course['title'] = $course_type_title;
            }
            if($staff != $course ["min_staff"]){
                $open_days .="<tr><td>{$course['title']}</td><td>{$course['date']->format('d.m.Y')}
</td><td>{$course['date']->format('G:i')} - {$course_end_time} Uhr</td><td>{$staff}/{$course ['min_staff']}</td></tr>";
            }



    }
    $open_days .="</table>";
    return $open_days;

}
/**
 *
 *
 * @param $courses
 * @param $interval_start
 * @param $interval_end can be null, then all courses until the end of the year will be returned
 * @return array
 */
function createIntervalDates($courses, $interval_start, $interval_end = null)
{

    $interval_courses = array();

    if ($interval_end == null) {
        $interval_end = clone $interval_start;

        $Y = $interval_end->format('Y');

        $interval_end->setDate($Y, 11, 31);
    }

    foreach ($courses as $course) {

        $course_date = clone $course['date'];

        if ($course['day_interval'] > 0) {

            $weekend = $course['weekend'];

            while ($course_date < $interval_end) {
                $course_date->add(new DateInterval('P' . $course['day_interval'] . 'D'));

                if ($course_date > $interval_start) {
                    // check if course is on weekends also
                    if ($weekend || !$weekend && $course_date->format('N') < 6) {
                        $tempCourse = $course; // arrays are asigned by copy
                        $tempCourse['date'] = clone $course_date;
                        $interval_courses[] = $tempCourse;
                    }
                }
            }
        } else if ($course['month_interval']) {

            while ($course_date < $interval_end) {
                $course_date->add(new DateInterval('P' . $course['month_interval'] . 'M'));

                if ($course_date > $interval_start) {
                    $tempCourse = $course; // arrays are asigned by copy
                    $tempCourse['date'] = clone $course_date;
                    $interval_courses[] = $tempCourse;
                }
            }
        }
    }

    return $interval_courses;
}

/**
 *
 *
 * @param $courses
 * @param $start_date
 * @return array
 */


function removePastDates($courses, $start_date)
{

    $valid_dates = array();

    foreach ($courses as $course) {
        if ($course['date'] > $start_date) $valid_dates[] = $course;
    }

    return $valid_dates;
}

/**
 *
 *
 * @param $courses
 * @return array
 */
function removeDateExceptions($courses)
{
    $result_courses = array();

    foreach ($courses as $course) {
        $course_exceptions = getCourseExceptions($course['id']);
        $is_exception = false;

        foreach ($course_exceptions as $exception) {
            $exception_date = new DateTime($exception['date']);

            if ($exception_date->format('Y-m-d G:i') == $course['date']->format('Y-m-d G:i')) {
                $is_exception = true;
            }

        }

        if (!$is_exception) {
            $result_courses[] = $course;
        }
    }

    return $result_courses;
}

/*****************************************************************************/
/* User functionality																												 */
/*****************************************************************************/

/**
 * Finds and returns all users.
 *
 * @return array of user arrays
 */
function getUsers()
{

    $db = Database::createConnection();

    $result = $db->query("SELECT id
					      FROM user order by last_name;");

    $db->close();

    $user_array = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $user_array[] = User::withUserId($row['id']);
        }
    }

    return $user_array;
}

function getUserById($user_id)
{
    $db = Database::createConnection();

    $result = $db->query("SELECT * FROM user WHERE ID LIKE '{$user_id}'");
    $result = $result->fetch_assoc();
    $db->close();

    return $result;
}

/**
 * Adds a new user to the database with the given parameters.
 *
 * @param string $username
 * @param string $password
 * @param array §roles array of role ids
 * @return boolean true in case it was successful
 */
function addUser($first_name, $last_name, $email, $phone, $password, $roles)
{

    $db = Database::createConnection();
    $username = $first_name . ' ' . $last_name;


    $result = $db->query("INSERT INTO user (username, password, first_name, last_name, email, phone) 
						  VALUES ('$username', '$password', '$first_name', '$last_name', '$email', '$phone');");

    $user_id = $db->insert_id;

    if ($result) {

        $sql = "INSERT INTO user_has_role (user_id, role_id) VALUES ";

        for ($i = 0; $i < count($roles); ++$i) {
            if ($i > 0) $sql .= ", ";
            $sql .= "($user_id, {$roles[i]})";
        }

        $result = $db->query($sql);
    }

    $db->close();

    return $result;
}

/**
 * Add new role to user object.
 *
 * @param $user_id
 * @param $role_id
 * @return boolean true in case it was successful
 */
function addRole($user_id, $role_id)
{

    $db = Database::createConnection();

    $result = $db->query("DELETE FROM user_has_role WHERE user_id = {$user_id}");

    $result = $db->query("INSERT INTO user_has_role (user_id, role_id)
					      VALUES ($user_id, $role_id);");

    $db->close();

    return $result;
}

/**
 * Remove role from user object.
 *
 * @param $user_id
 * @param $role_id
 * @return boolean true in case it was successful
 */
function removeRole($user_id, $role_id)
{

    $db = Database::createConnection();

    $result = $db->query("DELETE FROM user_has_role
 						  WHERE user_id={$user_id} AND role_id={$role_id};");

    $db->close();

    return $result;
}

/**
 * Add new event to user event whitelist.
 *
 * @param $user_id
 * @param $event_id
 * @return boolean true in case it was successful
 */
function addEventToWhitelist($user_id, $event_id)
{

    $db = Database::createConnection();

    $result = $db->query("SELECT event_whitelist
					      FROM user
					      WHERE id={$user_id};");

    if ($result->num_rows > 0) {
        $whitelist = $result->fetch_assoc()['event_whitelist'];

        $event_array = split(',', $whitelist);
        if (!in_array($event_id, $event_array)) {
            $event_array[] = $event_id;
            $whitelist = join(',', $event_array);

            $result = $db->query("UPDATE user
								  SET event_whitelist='{$whitelist}'
							      WHERE id={$user_id};");
        }
    }

    $db->close();

    return $result;
}

/**
 * Remove event from user event whitelist.
 *
 * @param $user_id
 * @param $event_id
 * @return boolean true in case it was successful
 */
function removeEventFromWhitelist($user_id, $event_id)
{

    $db = Database::createConnection();

    $result = $db->query("SELECT event_whitelist
					      FROM user
					      WHERE id={$user_id};");

    if ($result->num_rows > 0) {
        $whitelist = $result->fetch_assoc()['event_whitelist'];

        $event_array = split(',', $whitelist);

        if (($key = array_search($event_id, $event_array)) !== false) {
            unset($event_array[$key]);

            $whitelist = join(',', $event_array);

            $result = $db->query("UPDATE user
								  SET event_whitelist='{$whitelist}'
							      WHERE id={$user_id};");
        }
    }

    $db->close();

    return $result;
}

/**
 * Check if user is authorized for this event and return true in case he is.
 *
 * @param $user_id
 * @param $event_id
 * @return boolean true in case it was successful
 */
function userIsAuthorizedForCourse($user_id, $event_id)
{

    $db = Database::createConnection();

    $isAuthorized = false;

    $result = $db->query("SELECT event_whitelist
					      FROM user
					      WHERE id={$user_id};");

    if ($result->num_rows > 0) {
        $whitelist = $result->fetch_assoc()['event_whitelist'];
        $event_array = split(',', $whitelist);

        $result = $db->query("SELECT course_type_id
						      FROM course
						      WHERE id={$event_id};");

        if ($result->num_rows > 0) {
            $course_type_id = $result->fetch_assoc()['course_type_id'];

            if (($key = array_search($course_type_id, $event_array)) !== false) {
                $isAuthorized = true;
            }
        }
    }

    $db->close();

    return $isAuthorized;
}

/**
 * Finds and returns all user roles.
 *
 * @return array of user roles
 */
function getRoles()
{

    $db = Database::createConnection();

    $result = $db->query("SELECT id, title FROM role;");

    $roles_array = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $roles_array[] = $row;
        }
    }

    $db->close();

    return $roles_array;
}

/*****************************************************************************/
/* Registrants functionality																								 */
/*****************************************************************************/

/**
 * Adds a registrant to the database.
 *
 * @param int $course_id
 * @param string $firstname
 * @param string $lastname
 * @param string $street
 * @param string $zip
 * @param string $city
 * @param string $birthday
 * @param string $email
 * @return boolean true in case it was successful
 */
function addRegistrant($course_id, $firstname, $lastname, $street, $zip, $city, $birthday, $email)
{

    $db = Database::createConnection();

    $firstname = $db->real_escape_string($firstname);
    $lastname = $db->real_escape_string($lastname);
    $street = $db->real_escape_string($street);
    $zip = $db->real_escape_string($zip);
    $city = $db->real_escape_string($city);
    $email = $db->real_escape_string($email);

    $result = $db->query("INSERT INTO registrant (first_name, last_name, street, zip, city, birthday, email) 
												VALUES ('$firstname', '$lastname', '$street', '$zip', '$city', '$birthday', '$email');");
    $registrant_id = $db->insert_id;

    if ($result)
        $result = $db->query("INSERT INTO course_has_registrant (course_id, registrant_id, confirmed) 
													VALUES ($course_id, $registrant_id, 1);");
    $db->close();

    return $result;
}

/**
 * Finds and returns all registrants.
 *
 * @return array of registrant arrays
 */
function getRegistrants($course_id)
{

    $db = Database::createConnection();

    $result = $db->query("SELECT id, first_name, last_name, street, zip, city, birthday, email, phone 
												FROM registrant AS a
												WHERE EXISTS (
													SELECT 1
													FROM course_has_registrant AS b
													WHERE b.course_id={$course_id} AND a.id=b.registrant_id AND b.confirmed=1
													GROUP BY b.registrant_id
													HAVING count(*) > 0) ORDER BY last_name;");

    $registrants_array = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $registrants_array[] = $row;
        }
    }
    $db->close();

    return $registrants_array;
}

/**
 * Moves a registrant from one course to another.
 *
 * @param int $registrant_id
 * @param int $old_course_id
 * @param int $new_course_id
 * @return boolean true in case it was successful
 */
function moveRegistrant($registrant_id, $old_course_id, $new_course_id)
{

    $db = Database::createConnection();

    $db->query("UPDATE course_has_registrant
				SET course_id=$new_course_id
				WHERE (registrant_id=$registrant_id AND 
					 course_id=$old_course_id);");

    $result = ($db->affected_rows > 0) ? true : false;

    $db->close();

    return $result;
}

/**
 *
 * @param $course_id
 * @param $user_id
 * @return mixed
 */
function addStaff($course_id, $user_id)
{

    $db = Database::createConnection();

    $result = $db->query("INSERT INTO course_has_staff (course_id, user_id)
						  VALUES ({$course_id}, {$user_id});");

    $db->close();

    return $result;
}

function addStaffDate($date_id, $user_id)
{

    $db = Database::createConnection();

    $result = $db->query("INSERT INTO date_has_staff (date_id, user_id)
						  VALUES ({$date_id}, {$user_id});");

    $db->close();

    return $result;
}


/**
 *
 *
 * @param $course_id
 * @param $user_id
 * @return mixed
 */
function removeStaff($course_id, $user_id)
{

    $db = Database::createConnection();

    $result = $db->query("DELETE FROM course_has_staff
						  WHERE course_id={$course_id} AND user_id={$user_id};");

    $db->close();

    return $result;
}

function removeStaffDate($date_id, $user_id)
{

    $db = Database::createConnection();

    $result = $db->query("DELETE FROM date_has_staff
						  WHERE date_id={$date_id} AND user_id={$user_id};");

    $db->close();

    return $result;
}

function checkIsStaffAllFree($course_id)
{
    $result = getCourse($course_id);


}

/*****************************************************************************/
/* General functionality																										 */
/*****************************************************************************/

/**
 * Checks the given user details for authentication purposes.
 *
 * @param string $username
 * @param string $password
 * @return int user id or -1 if not valid
 */
function login($username, $password)
{

    $db = Database::createConnection();

    // make sure that the user isn't trying to do some SQL injection
    $username = $db->real_escape_string($username);

    $result = $db->query("SELECT id, password, active
						  FROM user 
						  WHERE username='{$username}';");
    $db->close();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['password'] === md5($password) && $row['active'])
            return $row['id'];
    }

    return -1;
}

/**
 *    Deletes any item with the given item id in the given table.
 *
 * @param int $item_id
 * @param string $table_name
 * @return boolean true in case it was successful
 */
function deleteItem($item_id, $table_name)
{

    $db = Database::createConnection();

    $result = $db->query("DELETE FROM {$table_name} WHERE id={$item_id};");
    $db->close();

    return $result;
}

function deleteUser($user_id){
    $db = Database::createConnection();
    $db->query("DELETE FROM date_has_staff WHERE user_id={$user_id}");
    $db->close();
    return deleteItem($user_id, 'user');

}

function deleteCourse($course_id)
{


    $course = getCourse($course_id);


    foreach ($course['dates'] as $date) {
        $db = Database::createConnection();
        $db->query("DELETE FROM date_has_staff WHERE date_id={$date['id']}");
        $db->close();
        deleteItem($date['id'], 'date');
    }


    return deleteItem($course_id, 'course');

}

function deleteCourseAll($course_id, $interval_id)
{
    $db = Database::createConnection();
    $result = $db->query("select id from course WHERE interval_id like {$interval_id} and id >= $course_id");

    while ($row = $result->fetch_assoc()) {

        $success = deleteCourse($row['id']);
    }
    $db->close();
    return $success;

}

/**
 * Sort function for course arrays.
 *
 * @param array $a
 * @param array $b
 * @return boolean true if $a is later than $b
 */
function courseSort($a, $b)
{
    return $a['date'] > $b['date'];
}


/**
 * Sort function for registrant arrays.
 *
 * @param array $a
 * @param array $b
 * @return boolean true if $a is later than $b
 */
function registrantSort($a, $b)
{
    return $a['last_name'] > $b['last_name'];
}

/**
 *
 *
 * @return array
 */
function getIntervals()
{

    $db = Database::createConnection();

    $result = $db->query("SELECT * FROM repeat_interval;");

    $interval_array = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $interval_array[] = $row;
        }
    }
    $db->close();

    return $interval_array;
}

/**
 * Returns the German month name for a given date.
 *
 * @param date $date
 * @return string
 */
function getMonth($date)
{

    $months = array("Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember");
    return $months[$date->format('n') - 1];
}

/**
 * Calculates the end time for a given start time and duration.
 *
 * @param date $date
 * @param date $duration
 * @return string
 */
function getEndTime($date, $duration)
{

    $date->add(new DateInterval('PT' . $duration . 'M'));
    return $date->format('G:i');
}


function renderNavigation($user)
{

    $u = $_SESSION['user'];

    //$root_directory = "/cityrock";
    $root_directory = "/verwaltung";

    $administration_menu = "";
    $admin_menu = "";

    if ($user->hasPermission($GLOBALS['level_1'])) {
        $administration_menu = "
			<li class='nav-entry'><a href='{$root_directory}/course'>Kletterkursverwaltung</a></li>
			<!-- <li class='nav-entry' ><a href='{$root_directory}/archive'>Kursarchiv</a></li>-->";
    }

    if ($user->hasPermission($GLOBALS['level_0'])) {
        $admin_menu = "
			<li class='nav-entry'><a href='{$root_directory}/user'>Mitarbeiter</a></li>
			<li class='nav-entry'><a href='{$root_directory}/settings'>Einstellungen</a></li>";
    } else {
        $admin_menu = "
			<li class='nav-entry'><a href='{$root_directory}/user'>Mitarbeiter</a></li>";
    }

    return "
		<div class='container-fluid'>
		<div class=\"navbar-header\">
      <button type=\"button\" class=\"navbar-toggle\" data-toggle=\"collapse\" data-target=\"#myNavbar\">
        <span class=\"icon-bar\"></span>
        <span class=\"icon-bar\"></span>
        <span class=\"icon-bar\"></span> 
      </button>
      <a class=\"navbar-brand\" href='{$root_directory}'/>[cityrock]</a>
    </div>
    <div class=\"collapse navbar-collapse\" id=\"myNavbar\">
		
		<ul>
			<li class='nav-entry'><a href='{$root_directory}/calendar'>Belegungsplan</a></li>
			<li class='nav-entry'><a href='{$root_directory}/events'>Terminliste</a></li>
			
			{$administration_menu}
			{$admin_menu}
			
		</ul>
		<ul class=\"nav navbar-nav navbar-right\">
        
        <li class='nav-entry dropdown'>
          <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\" role=\"button\" aria-haspopup=\"true\" aria-expanded=\"false\"><span class=\"glyphicon glyphicon-user\"> </span> {$u['username']}<span class=\"caret\"></span></a>
          <ul class=\"dropdown-menu\">
            
            <li><a href='{$root_directory}/profile'>Mein Profil</a></li>
            <li role=\"separator\" class=\"divider\"></li>
            <li><a href='{$root_directory}/index?logout'>Logout</a></li>
          </ul>
        </li>
      </ul></div></div>";
}

function createAlert($alert, $alertClass)
{
    return "<div class=\"alert {$alertClass} alert-dismissible\" role=\"alert\">
  <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\"><span aria-hidden=\"true\">&times;</span></button>
  {$alert}
</div>";
}

//Group Section

function addGroup($title, $caption){
    $db = Database::createConnection();
    $sql = "INSERT INTO `group` (title, caption)
						  VALUES ('{$title}', '{$caption}')";
    $result = $db->query($sql);

    //Gibt Group id zurück
    $result = $db->query("SELECT id from `group` WHERE title like '{$title}' ORDER BY id DESC LIMIT 1");
    $db->close();
    return $result->fetch_assoc()['id'];

}
function removeGroup($group_id){
    removeAllStatementsFromGroup($group_id);
    removeAllUserFromGroup($group_id);
    $db = Database::createConnection();

    $result = $db->query("DELETE 
					      FROM `group` where id={$group_id};");

    $db->close();
    return $result;




}

function updateGroup($group_id, $title, $caption){
    //Löscht alle Nutzer und Statements raus. Diese müssen über die entsprechnenden funktion wieder hinzugefügt werden
    removeAllUserFromGroup($group_id);
    removeAllStatementsFromGroup($group_id);
    $db = Database::createConnection();
    $sql = "UPDATE `group` SET title='{$title}', caption='$caption' where id= {$group_id}";
    $result = $db->query($sql);
    $db->close();
    return $result;


}
function addUserToGroup($group_id, $user_id){

    $db = Database::createConnection();
    $sql = "INSERT INTO group_has_user
						  VALUES ('{$group_id}', '{$user_id}')";
    echo $sql;
    $result= $db->query($sql);
    $db->close();

    return $result;

}

function removeAllUserFromGroup($group_id){
    $db = Database::createConnection();

    $result = $db->query("DELETE 
					      FROM group_has_user where group_id={$group_id};");

    $db->close();
    return $result;

}
function addStatementToGroup($group_id, $statement){
    $db = Database::createConnection();
    $sql = "INSERT INTO group_has_statement
						  VALUES ('{$group_id}', '{$statement}')";
    echo $sql;
    $result= $db->query($sql);
    $db->close();

    return $result;


}
function removeAllStatementsFromGroup($group_id){

    $db = Database::createConnection();

    $result = $db->query("DELETE 
					      FROM group_has_statement where group_id={$group_id};");

    $db->close();
    return $result;

}
function getGroups(){
    $db = Database::createConnection();

    $result = $db->query("SELECT *
					      FROM `group`;");
    $groups = array();
    while($row = $result->fetch_assoc()){
        $groups []= $row;
    }
    $db->close();
    return $groups;


}

function getGroup($group_id){
    $course_types = getCourseTypes();
    
    $db = Database::createConnection();

    $result = $db->query("SELECT *
					      FROM `group` WHERE id = {$group_id};");

    $group = $result->fetch_assoc();


    $result = $db->query("SELECT *
					      FROM group_has_user WHERE group_id = {$group_id};");


    while ($row = $result->fetch_assoc()) {
        $group ['members'][] = getUserById($row['user_id']);
    }

    $result = $db->query("SELECT *
					      FROM group_has_statement WHERE group_id = {$group_id};");

    while ($row = $result->fetch_assoc()) {

        foreach ($course_types as $course_type) {
            if ($course_type['id'] == $row['statement']){
                $group ['statements'][] = $course_type;
            }
        }

    }
    return $group;

}
function getGroupMembers($group_id)
{
    $db = Database::createConnection();
    $users = getUsers();
    $members = array();

    //Get group statements
    $result = $db->query("SELECT statement
					      FROM group_has_statement WHERE group_id = {$group_id}");

    while ($row = $result->fetch_assoc()) {
        $statements [] = $row;
    }


    $result = $db->query("SELECT user_id
					      FROM group_has_user WHERE group_id = {$group_id}");

    $db->close();
    while ($row = $result->fetch_assoc()) {
        $membersRaw [] = $row;
    }


    foreach ($users as $user) {

        $user = $user->serialize();
        $event_whitelist = explode (',', $user["event_whitelist"]);
        if($membersRaw) {
            foreach ($membersRaw as $member) {
                if ($member['user_id'] == $user['id']) {
                    $members[] = $user;

                }

            }
        }
        if($statements[0]["statement"]) {

            foreach ($statements as $statement) {

                foreach ($event_whitelist as $item) {

                    if ($item == $statement['statement']) {
                        $members[] = $user;

                    }
                }
            }
        }



    }
    $members_final = array();
    foreach ($members as $member){
        if(!in_array($member, $members_final)){
            $members_final[] = $member;
        }
    }
    return $members_final;
    /*foreach ($members_final as $member){
        echo $member['username'];
    }*/

}


function debug($data){
    echo "<pre>";
    var_dump($data);
    echo "</pre>";
}

function sendMail($recipes, $subject, $msg){
    $to = $recipes;

    debug($recipes);
    debug($to);
    $header  = "MIME-Version: 1.0\n";
    $header .= "Content-Type: text/html; charset=utf-8\n";
    $header .= "From: Cityrock <info@cityrock.de>\n";
    $header .= "Reply-To: Cityrock <info@cityrock.de>\n";

    debug($header);

    // send email and return status
    //return mail($to, $subject, $msg, $header);
    $sucess = mail($to, $subject, $msg, $header);

    return $sucess;
    /*
    // DEBUG
    echo "Sende Email: <br /> ";
    echo "Empfänger: $email <br />";
    echo "Betreff: $subject <br />";
    echo "Nachricht: $body <br /><br />";

    return true;
    */

}

?>