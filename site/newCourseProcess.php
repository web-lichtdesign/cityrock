<?php
include_once('_init.php');

$required_roles = array('Administrator');
$level_0 = array('Administrator');
$level_1 = array('Administrator', 'Verwaltung');

$dates = array();

$counter = 1;
while ($counter < 6) {
    if ($_POST["date-$counter"]) {
        $date = array(
            "date" => $_POST["date-{$counter}"],
            "start" => $_POST["start-{$counter}"],
            "end" => $_POST["end-{$counter}"]
        );

        $dates[] = $date;
    }
    $counter++;
}

var_dump($dates);

$course_data = array();

//Intervall ID abfragen

$db = Database::createConnection();
$sql = "select interval_id from course order by interval_id desc limit 1";
$interval_id = $db->query($sql);
$interval_id = $interval_id->fetch_assoc()['interval_id'];
$interval_id++;
$course_data['interval_id'] = $interval_id;
$db->close();



$course_data['course_type_id'] = $_POST['type'];

if ($_POST['title'])
    $course_data['title'] = $_POST['title'];

if ($_POST['interval'] >= 0)
    $course_data['interval_designator'] = $_POST['interval'];

if ($_POST['staff'])
    $course_data['min_staff'] = $_POST['staff'];

if (isset($_POST['staff_cancel']))
    $course_data['staff_cancel'] = 1;

if ($_POST['registrants'])
    $course_data['max_participants'] = $_POST['registrants'];

if ($_POST['registrants_age'])
    $course_data['participants_age'] = $_POST['registrants_age'];
if ($_POST['comment'])
    $course_data['comment'] = $_POST['comment'];

if ($_POST['created_from'])
    $course_data['created_from'] = $_POST['created_from'];

if ($_POST['street'])
    $course_data['street'] = $_POST['street'];

if ($_POST['phone'])
    $course_data['phone'] = $_POST['phone'];

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
    $success = updateCourse($_POST['id'], $course_data, $dates);

    $title = "Kurs bearbeiten";

    if ($success)
        $content = renderCourseOverview($course_types_filtered, $course_types, "Der Kurs wurde erfolgreich geändert.", "alert-success");
    else
        $content = "Fehler: Kurs konnte nicht bearbeitet werden.";
} else {
    // create course
    $success = addCourse($course_data, $dates);

    //Schleife für wiederholende Termine erstellen
    if ($course_data['interval_designator'] != 5) {
        $db = Database::createConnection();
        $sql = "select num_days from repeat_interval where id like " . $course_data['interval_designator'];
        $days = $db->query($sql);
        $days = $days->fetch_assoc()['num_days'] . " days";

        $datesObj[] = null;
        $end_date = date('j.m.Y', strtotime('+2 years', strtotime($dates[0]['date'])));

        while (DateTime::createFromFormat('j.m.Y', $dates[0]['date']) < DateTime::createFromFormat('j.m.Y', $end_date)) {
            for ($i = 0; $i < sizeof($dates); $i++) {
                $dates[$i]['date'] = date('j.m.Y', strtotime($days, strtotime($dates[$i]['date'])));

            }
            //$dates[0]['date'] = date('j.m.Y', strtotime($days, strtotime($dates[0]['date'])));

            echo($days);
            $success = addCourse($course_data, $dates);

        }

    }
}
