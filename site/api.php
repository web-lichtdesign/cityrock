<?php

include_once('_init.php');

$authenticated_user = $_SESSION['user'];
$authenticated_user_object = User::withUserObjectData($_SESSION['user']);



if (isset($_POST['action'])) {

    switch ($_POST['action']) {



        case "DATE_ADD_STAFF":

            if (!$_POST['date_id'] || !$_POST['user_id'] || !$_POST['course_id']) {
                echo "ERROR: Parameter fehlen.";
                break;
            }

            if($authenticated_user['id'] != $_POST['user_id'] &&
                !$authenticated_user_object->hasPermission($GLOBALS['level_2'])) {

                echo "ERROR: Nicht authorisiert.";
                break;
            }
            if(!$authenticated_user_object->hasPermission($GLOBALS['level_1'])) {
                if (!userIsAuthorizedForCourse($_POST['user_id'], $_POST['course_id'])) {

                    echo "ERROR: Du bist für diesen Veranstaltungstypen nicht freigeschalten.";
                    break;
                }
            }
            echo "add staff";

            $success = addStaffDate($_POST['date_id'], $_POST['user_id']);

            if ($success) echo "SUCCESS";
            else echo "ERROR: Datenbank Fehler.";
            break;
        case "COURSE_ADD_STAFF":

            if (!$_POST['user_id'] || !$_POST['course_id']) {
                echo "ERROR: Parameter fehlen.";
                break;
            }

            if($authenticated_user['id'] != $_POST['user_id'] &&
                !$authenticated_user_object->hasPermission($GLOBALS['level_2'])) {

                echo "ERROR: Nicht authorisiert.";
                break;
            }
            if(!$authenticated_user_object->hasPermission($GLOBALS['level_1'])) {
                if (!userIsAuthorizedForCourse($_POST['user_id'], $_POST['course_id'])) {

                    echo "ERROR: Du bist für diesen Veranstaltungstypen nicht freigeschalten.";
                    break;
                }
            }

            echo createAddStaffDateError(addStaffCourse($_POST['course_id'], $_POST['user_id']));
            break;
        case "INTERVAL_ADD_STAFF":

            if (!$_POST['user_id'] || !$_POST['course_id'] || !$_POST['interval_id']) {
                echo "ERROR: Parameter fehlen.";
                break;
            }

            if($authenticated_user['id'] != $_POST['user_id'] &&
                !$authenticated_user_object->hasPermission($GLOBALS['level_2'])) {

                echo "ERROR: Nicht authorisiert.";
                break;
            }
            if(!$authenticated_user_object->hasPermission($GLOBALS['level_1'])) {
                if (!userIsAuthorizedForCourse($_POST['user_id'], $_POST['course_id'])) {

                    echo "ERROR: Du bist für diesen Veranstaltungstypen nicht freigeschalten.";
                    break;
                }
            }

            echo createAddStaffDateError(addStaffInterval($_POST['course_id'], $_POST['interval_id'], $_POST['user_id']));
            break;
        case "INTERVAL_REMOVE_STAFF":

            if (!$_POST['course_id'] || !$_POST['user_id']) {
                echo "ERROR: Parameters missing";
                break;
            }

            if($authenticated_user['id'] != $_POST['user_id'] &&
                !$authenticated_user_object->hasPermission($GLOBALS['level_1'])) {

                echo "ERROR: Nicht authorisiert.";
                break;
            }

            // check deadline
            /*
            if($authenticated_user['id'] == $_POST['user_id'] &&
                !$authenticated_user_object->hasPermission($GLOBALS['level_1'])) {

                if(!isset($_POST['deadline'])) {
                    echo "ERROR: Deadline nicht gesetzt.";
                    break;
                }
                else {
                    $deadline = intval($_POST['deadline']) - 1;
                    $course = getCourse($_POST['course_id']);

                    $durationString = 'P' . $deadline . 'D';
                    $temp_date = new DateTime();
                    $deadline_date = $temp_date->add(new DateInterval($durationString));

                    if($course['day_interval'] == 0 && $course['month_interval'] == 0) {

                        if($deadline_date > $course['dates'][0]['date']) {
                            echo "ERROR: Du kannst dich nicht mehr austragen. Deadline {$deadline} Tage vor Kursbeginn.";
                            break;
                        }

                        if(!$course['staff_cancel']) {
                            echo "ERROR: Du kannst dich nicht austragen. Bitte kontaktiere den Kursverantwortlichen.";
                            break;
                        }
                    }
                }
            }
            */
            $success = removeStaffInterval($_POST['interval_id'], $_POST['course_id'], $_POST['user_id']);

            if ($success) echo "SUCCESS";
            else echo "ERROR: Datenbank Fehler.";
            break;

        case "COURSE_REMOVE_STAFF":

            if (!$_POST['course_id'] || !$_POST['user_id']) {
                echo "ERROR: Parameters missing";
                break;
            }

            if($authenticated_user['id'] != $_POST['user_id'] &&
                !$authenticated_user_object->hasPermission($GLOBALS['level_1'])) {

                echo "ERROR: Nicht authorisiert.";
                break;
            }

            // check deadline
            /*
            if($authenticated_user['id'] == $_POST['user_id'] &&
                !$authenticated_user_object->hasPermission($GLOBALS['level_1'])) {

                if(!isset($_POST['deadline'])) {
                    echo "ERROR: Deadline nicht gesetzt.";
                    break;
                }
                else {
                    $deadline = intval($_POST['deadline']) - 1;
                    $course = getCourse($_POST['course_id']);

                    $durationString = 'P' . $deadline . 'D';
                    $temp_date = new DateTime();
                    $deadline_date = $temp_date->add(new DateInterval($durationString));

                    if($course['day_interval'] == 0 && $course['month_interval'] == 0) {

                        if($deadline_date > $course['dates'][0]['date']) {
                            echo "ERROR: Du kannst dich nicht mehr austragen. Deadline {$deadline} Tage vor Kursbeginn.";
                            break;
                        }

                        if(!$course['staff_cancel']) {
                            echo "ERROR: Du kannst dich nicht austragen. Bitte kontaktiere den Kursverantwortlichen.";
                            break;
                        }
                    }
                }
            }
            */
            $success = removeStaffCourse($_POST['course_id'], $_POST['user_id']);

            if ($success) echo "SUCCESS";
            else echo "ERROR: Datenbank Fehler.";
            break;
        case "DATE_REMOVE_STAFF":


            if (!$_POST['course_id'] || !$_POST['user_id'] || !$_POST['date_id'] ) {
                echo "ERROR: Parameters missing";
                break;
            }

            if($authenticated_user['id'] != $_POST['user_id'] &&
                !$authenticated_user_object->hasPermission($GLOBALS['level_1'])) {

                echo "ERROR: Nicht authorisiert.";
                break;
            }
            /*
            // check deadline
            if($authenticated_user['id'] == $_POST['user_id'] &&
                !$authenticated_user_object->hasPermission($GLOBALS['level_1'])) {

                if(!isset($_POST['deadline'])) {
                    echo "ERROR: Deadline nicht gesetzt.";
                    break;
                }
                else {
                    $deadline = intval($_POST['deadline']) - 1;
                    $course = getCourse($_POST['course_id']);

                    $durationString = 'P' . $deadline . 'D';
                    $temp_date = new DateTime();
                    $deadline_date = $temp_date->add(new DateInterval($durationString));

                    if($course['day_interval'] == 0 && $course['month_interval'] == 0) {

                        if($deadline_date > $course['dates'][0]['date']) {
                            echo "ERROR: Du kannst dich nicht mehr austragen. Deadline {$deadline} Tage vor Kursbeginn.";
                            break;
                        }

                        if(!$course['staff_cancel']) {
                            echo "ERROR: Du kannst dich nicht austragen. Bitte kontaktiere den Kursverantwortlichen.";
                            break;
                        }
                    }
                }
            }
            */
            $success = removeStaffDate($_POST['date_id'], $_POST['user_id']);

            if ($success) echo "SUCCESS";
            else echo "ERROR: Datenbank Fehler.";
            break;
        case "COURSE_ADD_EXCEPTION":
            if (!$_POST['course_id'] || !$_POST['date'] || !$_POST['user_id']) {
                echo "ERROR: Parameter fehlen.";
                break;
            }

            if($authenticated_user['id'] != $_POST['user_id'] &&
                !$authenticated_user_object->hasPermission($GLOBALS['level_1'])) {

                echo "ERROR: Nicht authorisiert.";
                break;
            }

            $cancellation = isset($_POST['cancellation']) && $_POST['cancellation'] == 1;
            $success = addCourseException($_POST['course_id'], $_POST['date'], $cancellation);

            if ($success) echo "SUCCESS";
            else echo "ERROR: Datenbank Fehler.";
            break;

        case "USER_ADD_ROLE":
            if(!$authenticated_user_object->hasPermission($GLOBALS['level_1'])) {
                echo "ERROR: Nicht authorisiert.";
                break;
            }
            if (!$_POST['user_id'] || !$_POST['role_id']) {
                echo "ERROR: Parameter fehlen.";
                break;
            }

            $success = addRole($_POST['user_id'], $_POST['role_id']);

            if ($success) echo "SUCCESS";
            else echo "ERROR: Datenbank Fehler.";
            break;


        case "USER_REMOVE_ROLE":
            if(!$authenticated_user_object->hasPermission($GLOBALS['level_1'])) {
                echo "ERROR: Nicht authorisiert.";
                break;
            }
            if (!$_POST['user_id'] || !$_POST['role_id']) {
                echo "ERROR: Parameter fehlen.";
                break;
            }

            $success = removeRole($_POST['user_id'], $_POST['role_id']);

            if ($success) echo "SUCCESS";
            else echo "ERROR: Datenbank Fehler.";
            break;

        case "USER_ADD_EVENT":
            if(!$authenticated_user_object->hasPermission($GLOBALS['level_1'])) {
                echo "ERROR: Nicht authorisiert.";
                break;
            }
            if (!$_POST['user_id'] || !$_POST['event_id']) {
                echo "ERROR: Parameter fehlen.";
                break;
            }

            $success = addEventToWhitelist($_POST['user_id'], $_POST['event_id']);

            if ($success) echo "SUCCESS";
            else echo "ERROR: Datenbank Fehler.";
            break;


        case "USER_REMOVE_EVENT":
            if(!$authenticated_user_object->hasPermission($GLOBALS['level_1'])) {
                echo "ERROR: Nicht authorisiert.";
                break;
            }
            if (!$_POST['user_id'] || !$_POST['event_id']) {
                echo "ERROR: Parameter fehlen.";
                break;
            }

            $success = removeEventFromWhitelist($_POST['user_id'], $_POST['event_id']);


            if ($success) echo "SUCCESS";
            else echo "ERROR: Datenbank Fehler.";
            break;

        case "USER_DELETE":
            if(!$authenticated_user_object->hasPermission($GLOBALS['level_1'])) {
                echo "ERROR: Nicht authorisiert.";
                break;
            }
            if (!$_POST['user_id']) {
                echo "ERROR: Parameter fehlt.";
                break;
            }

            $success = deleteUser($_POST['user_id']);

            if ($success) echo "SUCCESS";
            else echo "ERROR: Datenbank Fehler.";
            break;
        case "ADD_GROUP":
            $members = explode(',',$_POST['members']);
            $statements = explode(',',$_POST['statements']);
            $group_id = addGroup($_POST['title'], $_POST['caption']);

            foreach ($members as $member) {

                addUserToGroup($group_id, $member);

            }
            foreach ($statements as $statement) {

                addStatementToGroup($group_id, $statement);

            }


            break;
        case "DELETE_GROUP":
            echo removeGroup($_POST['group_id']);
            break;

        case "UPDATE_GROUP":
            $group_id = $_POST['group_id'];
            $members = explode(',',$_POST['members']);
            $statements = explode(',',$_POST['statements']);
            updateGroup($group_id, $_POST['title'], $_POST['caption']);
            foreach ($members as $member) {

                addUserToGroup($group_id, $member);

            }
            foreach ($statements as $statement) {

                addStatementToGroup($group_id, $statement);

            }

            break;
        case "SEND_MAIL_TO_GROUP":

            $recipes = $_POST['recipes'];

            $subject= $_POST['subject'];

            $err = "ERROR: Es gab einen Fehler beim versenden";
            $open_days = renderTableOpenDays();
            $msg = $_POST['msg'];
            $msg = str_replace('[%offene_termine%]', $open_days, $msg);

            $success = sendMail($recipes, $subject, $msg);
            debug($success);
            /*
            debug($recipes);
            foreach ($recipes as $recipe){
                if($recipe) {
                    debug($recipe);
                    $recipe .= ";";
                    $success = sendMail($_POST['recipes'], $recipe, $_POST['msg']);

                    if(!$success){
                        $err .= $recipe.'<br>';
                    }
                }

            }
            */
            if(!$success) {
                echo $err;
                break;
            }
            break;
        case "DUPLICATE_COURSE":
            $course = $_POST['course'];
            $title = $_POST['title'];
            $date = $_POST['date'];
        default:
            echo "Unknown.";
            break;
    }
}

?>