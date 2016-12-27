<?php

include_once('_init.php');






if (User::withUserObjectData($_SESSION['user'])->hasPermission($GLOBALS['level_1']) && !(isset($_GET["id"])) || User::withUserObjectData($_SESSION['user'])->hasPermission($GLOBALS['level_0']) && (isset($_GET["id"]))) {


    /***********************************************************************/
    /* Process form data												   */
    /***********************************************************************/
    if (isset($_POST['modify']) && User::withUserObjectData($_SESSION['user'])->hasPermission($GLOBALS['level_0'])) {
        $user_data_array = array();

        // avoid deactivating one's own account
        if ($_POST['user_id'] != $_SESSION['user']['id']) {
            $user_data_array['active'] = $_POST['active'] ? 1 : 0;
        }

        $user_data_array['first_name'] = $_POST['first_name'];
        $user_data_array['last_name'] = $_POST['last_name'];
        $user_data_array['username'] = $_POST['first_name']." ".$_POST['last_name'];
        $user_data_array['phone'] = $_POST['phone'];
        $user_data_array['email'] = $_POST['email'];
        if ($_POST['password']) {
            $user_data_array['password'] = md5($_POST['password']);
        }

        $success = User::updateUserData($user_data_array, $_POST['user_id']);

        // modify POST user object
        if ($success && $_POST['user_id'] == $_SESSION['user']['id']) {
            foreach ($user_data_array as $key => $value) {
                $_SESSION['user'][$key] = $value;
            }
        }

        if ($success)
            header('Location: ' . $root_directory . "/user");
        else
            $content = "Fehler: Die Nutzerdaten konnten nicht gespeichert werden.";
    } else if (isset($_POST['new']) && isset($_POST['first_name']) && isset($_POST['password']) && User::withUserObjectData($_SESSION['user'])->hasPermission($GLOBALS['level_0']) ) {
        $success = addUser($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'], md5($_POST['password']), $_POST['user_role']);

        if ($success) {
            header('Location: ' . $root_directory . "/user");

        } else {
            $title = "Neuer Nutzer";
            $content = "Fehler: Nutzer konnte nicht erstellt werden.";
        }
    } else {
        if (isset($_GET["id"]) ) {
            if ($_GET["id"] == "new") {
                /***********************************************************************/
                /* New user form													   */
                /***********************************************************************/
                $title = "Neuer Nutzer";
                $content = "
					<form method='post'  class='form-horizontal' data-toggle='validator'>
						<div class='form-group'>
						<label for='first_name' class='col-sm-2 control-label'>Vorname</label>
						<div class='col-sm-10'>
						<input type='text' placeholder='Vorname' name='first_name' required>
						</div></div>
						
						<div class='form-group'>
						<label for='last_name' class='col-sm-2 control-label'>Nachname</label>
						<div class='col-sm-10'>
						<input type='text' placeholder='Nachname' name='last_name' required>
						</div></div>
						
						<div class='form-group'>
						<label for='phone' class='col-sm-2 control-label'>Telefon</label>
						<div class='col-sm-10'>
						<input type='text' name='phone'>
						</div></div>
						
						<div class='form-group'>
						<label for='email' class='col-sm-2 control-label'>E-Mail</label>
						<div class='col-sm-10'>
						<input type='email' name='email' required>
						</div></div>
						
						<div class='form-group'>
						<label for='password' class='col-sm-2 control-label'>Passwort (gut merken!)</label>
						<div class='col-sm-10'>
						<input type='password' placeholder='Passwort' name='password' id='password' required>
						</div></div><div class='form-group'>
						<label for=''password-replay' class='col-sm-2 control-label'>Passwort wiederholen</label>
						<div class='col-sm-10'>
						<input type='password' placeholder='Passwort' name='password-replay' data-match='#password'>
						</div></div><div class='form-group'>
						<label for='user-role' class='col-sm-2 control-label'>Zugewiesene Rolle</label>
						<div class='col-sm-10'>
						<select class='form-control' name='user_role' id='user-role'>";

                foreach (getRoles() as $role) {
                    $content .= "<option value='{$role['id']}'>{$role['title']}</option>";
                }

                $content .= "
						</select>
						</div></div>
						<input type='hidden' name='new' value='true'>
						<a href='../' class='btn btn-danger'>Abbrechen</a>
						<input type='submit' class='btn btn-primary' value='Hinzufügen'>
					
					</form>
					";
            } else if (User::withUserObjectData($_SESSION['user'])->hasPermission($GLOBALS['level_0'])) {
                /***********************************************************************/
                /* User details and edit											   */
                /***********************************************************************/
                $user = User::withUserId($_GET["id"])->serialize();

                $qualification_list = "<ul class='qualification-list'>";
                foreach ($user['qualifications'] as $qualification) {

                    $description = $qualification['description'];
                    $hasQualification = $qualification['user_id'] != null;

                    if ($hasQualification) {
                        $qualification_list .= "<li>{$description}";

                        if ($qualification['date'])
                            $qualification_list .= " vom {$qualification['date']}</li>";
                        else
                            $qualification_list .= "</li>";
                    }
                }
                $qualification_list .= "</ul>";

                if ($qualification_list == "<ul class='qualification-list'></ul>")
                    $qualification_list = "<p style='font-style: italic; margin-top: 0.5em; margin-bottom: 0.2em;'>Es wurden noch keine Qualifikationen hinterlegt.</p>";

                $roles_list = "<ul class='roles-list'>";
                foreach ($user['roles'] as $role) {
                    $roles_list .= "<li>{$role['title']} <a href='#' class='remove-role btn btn-danger btn-xs' role='{$role['id']}' style='margin-left: 1em;'>Entfernen</a></li>";
                }
                $roles_list .= "</ul>";

                $event_whitelist = "<ul class='event-whitelist list-group'>
                <li class='list-group-item active'>Der Nutzer kann sich für folgende Veranstaltungstypen eintragen: </li> ";


                $event_whitelist_array = explode(',', $user['event_whitelist']);
                foreach ($event_whitelist_array as $event_id) {
                    if ($event_id != '') {
                        $event_title = getCourseTypes()[$event_id]['title'];
                        $event_whitelist .= "<li class='list-group-item'>{$event_title} <a href='#' class='remove-event btn btn-danger btn-xs' event='{$event_id}' style='margin-left: 1em;'>Entfernen</a></li>";
                    }
                }
                $event_whitelist .= "</ul>";

                if ($event_whitelist == "<ul class='event-whitelist'></ul>")
                    $event_whitelist = "<p style='font-style: italic; margin-top: 0.5em; margin-bottom: 0.2em;'>Der Nutzer kann sich bisher für keine Veranstaltungen eintragen.</p>";


                $checked = $user['active'] ? 'checked' : '';
                $deactivateCheckbox = $user['id'] === $_SESSION['user']['id'] ? 'disabled' : '';

                $content .= "
					<form id='user_data_form' method='post' onsubmit='return cityrock.validateProfile(this);'>
						<span id='user-id-text' style='display: none;'>{$user['id']}</span>
						<div class='show-profile'>
						<table class='table values table-striped'>
							<tr>
								<th>Name</th>
								<th><span id='first-name-text'>{$user['first_name']}</span> <span id='last-name-text'>{$user['last_name']}</span></th>
							</tr>
							<tr>
								<td>E-Mail</td>
								<td><span id='email-text'>{$user['email']}</span> </td>
							</tr>
							<tr>
								<td>Telefon</td>
								<td><span id='phone-text'>{$user['phone']}</span> </td>
							</tr>
							<tr id='password-container' style='display: none;'>
							    <td>Passwort</td>
							    <td id='password-text'></td>
							    
                            </tr>
						</table>
						</div>
						<div class='edit-profile' style='display: none'>
						<table class='table values table-striped'>
							<tr>
								<th>Name</th>
								<th><input type='text' name='first_name' value='{$user['first_name']}'><input type='text' name='last_name' value='{$user['last_name']}'></th>
							</tr>
							<tr>
								<td>E-Mail</td>
								<td><input type='text' name='email' value='{$user['email']}'></td>
							</tr>
							<tr>
								<td>Telefon</td>
								<td><input type='text' name='phone' value='{$user['phone']}'></td>
							</tr>
							<tr>
							    <td>Passwort</td>
							    <td><input type='password' name='password'></td>
							    
                            </tr>
						</table>
						</div>
						
						
							
							";

                $content .= "
				
						
							<span class='list'>
							    <span class='list-item'>
								Der Nutzer hat folgende Rolle: 
								<!-- <a href='#' id='user-add-role'>Weitere Rolle hinzufügen</a> -->
								<select class='form-control' id='erruser-add-role-selection' name='role'>
									<option style='display: none;' selected></option>";

                foreach (getRoles() as $role) {
                    if($role['id'] == $user['roles'][0]['id']){
                        $content .= "<option value='{$role['id']}' selected>{$role['title']}</option>";
                    }else {
                        $content .= "<option value='{$role['id']}'>{$role['title']}</option>";
                    }
                }

                $content .= "
								</select>
							    </span>
							    </span>
						        <span class='list'>
							    <span class='list-item'>
								
								{$event_whitelist}
								<a href='#' id='user-add-event'>Für weiteren Veranstaltungstypen freischalten</a>
								<select id='user-add-event-whitelist' name='event' style='display: none;'>
									<option style='display: none;' selected></option>";

                foreach (getCourseTypes() as $courseType) {
                    $content .= "<option value='{$courseType['id']}'>{$courseType['title']}</option>";
                }

                $content .= "
								</select>
								</span>
								</span>
							
						<span class='list'>
							<span class='list-item'>
								<span class='{$deactivateCheckbox}'>
									<input type='checkbox' name='active' id='active' {$deactivateCheckbox} {$checked} />
									<label for='active'>Nutzerkonto aktiviert</label>
								</span>
							</span>
							<input type='hidden' name='modify' />
							<input type='hidden' name='user_id' value='{$user['id']}' />
						</span>
						<a href='{$root_directory}/user' class='btn btn-primary button'>Zurück</a>
						<a href='#' id='edit-user' class='btn btn-primary'>Bearbeiten</a>
						<a href='#' user-id='{$user['id']}' class='btn btn-danger delete-user'>Löschen</a>
					</form>";
            }
        }
        else {
            /***********************************************************************/
            /* User overview													   */
            /***********************************************************************/
            $title = "Mitarbeiter";
            $groups = getGroups();


            //Groupselect
            $content .= "<div><div class='col-sm-6' id='groups-select-div'>
<select class='form-control chosen-select groups-select' onchange='cityrock.changeSelectGroups()' id='members' name='members' multiple='true' >
                        <option value='all' selected>Alle</option> ";

            foreach ($groups as $group) {
                $content .= "<option value='{$group['id']}'>{$group['title']}</option>";
            }
            $content .= "</select></div><div class='col-sm-6'>";
            if (User::withUserObjectData($_SESSION['user'])->hasPermission($GLOBALS['level_0'])) {
                $content .= "<button type='button' class='btn btn-primary ' data-toggle='modal' data-target='#edit-group'>bearbeiten/hinzufügen</button>";
            }
            $content .= "<button class='btn btn-primary' data-toggle='modal' data-target='#send-message'>Nachricht schicken</button>
            <p></p>
            </div></div>

<div id='user-overview'>
<table class='table table-striped'>
				<tr>
					<th class='col-sm-2'>Name</th>
					<th class='col-sm-3'>Funktion</th>
					<th></th>
</tr>";

            $users = getUsers();


 /*
            foreach ($users as $user) {

                $user = $user->serialize();

                $roles_list = "";
                foreach ($user['roles'] as $role) {
                    $roles_list .= ", " . $role['title'];
                }
                $roles_list = substr($roles_list, 2);

                $qualifications_list = "";
                foreach ($user['qualifications'] as $qualification) {
                    if ($qualification['user_id'] != null)
                        $qualifications_list .= " " . strtolower($qualification['description']);
                }

                $content .= "
						<tr>";
                if (User::withUserObjectData($_SESSION['user'])->hasPermission($GLOBALS['level_0'])) {
                    $content .= "<td><a href='?id={$user['id']}'>{$user['username']}</a></td>
							<td>{$roles_list}</td>
							<td>";
                } else {
                    $content .= "<td><a href='?id={$user['id']}'>{$user['username']}</a></td>
							<td>{$roles_list}</td>
							<td>";
                }

            }

*/
            $content .= "
							</td>
						</tr>";


            $content .=
                "<input type='hidden' id='message-recipes' value='";
                foreach ($users as $user){
                    $user = $user->serialize();
                    if($user['email'])
                        $content .= $user['email'] . ', ';

                }
            $content .="'></div>
                    </table>
                    </div>
					</div>
					";
            if (User::withUserObjectData($_SESSION['user'])->hasPermission($GLOBALS['level_0'])) {
                $content .= "					<div class='action-bar'>
						<a href='./user/new' class='btn btn-success'>Nutzer hinzufügen</a>
					</div>";
            }


            $content .= "
<script>
                    $(document).ready(function() {
  cityrock.changeSelectGroups()
  cityrock.changeSelectGroup()
});
                        
                    </script>
<div class='modal fade' id='edit-group' tabindex='-1' role='dialog' aria-labelledby='myModalLabel'>
    <div class='modal-dialog' role='document'>
        <div class='modal-content'>
            <div class='modal-header'>
                <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                <form class='form-inline'>
                    <h4 class='modal-title col-sm-3' id='myModalLabel'>Gruppen</h4>
                    <div id='select-group-modal-div'>
                    
                    <select id='select-group-modal' onload='cityrock.changeSelectGroup()' onchange='cityrock.changeSelectGroup()' class='col-sm-4 form-control'>";
            $first = true;
            foreach ($groups as $group) {
                if ($first) {
                    $content .= "<option value='{$group['id']}' selected>{$group['title']}</option>";
                    $first = false;
                } else {
                    $content .= "<option value='{$group['id']}'>{$group['title']}</option>";
                }
            }
            $content .= "
                        <option value=''>---------</option>
                        <option value='new'>Neu</option>
                    </select>
                    
                    </div>
                    <div class='col-sm-5'>
                    <input type='button' class='btn btn-primary btn-new-group' value='Neu'>
                    <input type='button' class='btn btn-primary btn-edit-group' value='Bearbeiten'>
                    </div>
                </form>
                
            </div>
            <div class='modal-body'>
                <div id='modal-group-content'>
                    
                </div>
                
            </div>
            <div class='modal-footer'>
                <button type='button' class='btn btn-default' data-dismiss='modal'>Schließen</button>
                
            </div>
        </div>
    </div>
</div>
<!-- Send Message Modal -->
<div class='modal fade' id='send-message' tabindex='-1' role='dialog' aria-labelledby='myModalLabel'>
    <div class='modal-dialog' role='document'>
        <div class='modal-content'>
            <div class='modal-header'>
                <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                <h4 class='modal-title' id='myModalLabel'>Nachricht an Gruppe</h4>
            </div>
            <div class='modal-body'>
                <div id='modal-message-content'>
                <label for='subject'>Betreff</label>
                <input type='text' id='subject' name='subject'>
                <label for='send-message-textarea'>Nachricht</label>
                <textarea id='send-message-textarea' name='send-message-textarea'></textarea>
                
                <input type='hidden' id='message-input' value=''>
                <p>Um offene Termine einzufügen, bitte diesen Tag benutzen %offene-termine%</p>
                <script>CKEDITOR.replace( 'send-message-textarea' );</script>
	
                    
               
                
            </div>
            <div class='modal-footer'>
                <button type='button' class='btn btn-default' data-dismiss='modal'>Schließen</button>
                <button type='button' class='btn btn-primary' data-dismiss='modal' onclick='transInputMsg()'>Absenden</button>
                <script>
                                   function transInputMsg() {
                                     $('#message-input').val(CKEDITOR.instances['send-message-textarea'].getData());
                                     cityrock.sendMailToGroup();
                                   }
</script>
                
            </div>
        </div>
    </div>
</div>
";


        }
        }
    include('_main.php');




} else {
    if (isset($_GET["id"])) {
        /***********************************************************************/
        /* User details 													   */
        /***********************************************************************/
        $user = User::withUserId($_GET["id"])->serialize();

        $title = "Nutzerübersicht";

        $qualification_list = "<ul class='qualification-list hidden'>";
        foreach ($user['qualifications'] as $qualification) {

            $description = $qualification['description'];
            $hasQualification = $qualification['user_id'] != null;

            if ($hasQualification) {
                $qualification_list .= "<li>{$description}";

                if ($qualification['date'])
                    $qualification_list .= " vom {$qualification['date']}</li>";
                else
                    $qualification_list .= "</li>";
            }
        }
        $qualification_list .= "</ul>";

        if ($qualification_list == "<ul class='qualification-list'></ul>")
            $qualification_list = "<p style='font-style: italic; margin-top: 0.5em; margin-bottom: 0.2em;'>Es wurden noch keine Qualifikationen hinterlegt.</p>";

        $content .= "
			<span class='list'>
				<span class='list-item'>
					<span>Vorname</span>
					<span id='first-name-text'>{$user['first_name']}</span>
				</span>
				<span class='list-item'>
					<span>Nachname</span>
					<span id='last-name-text'>{$user['last_name']}</span>
				</span>
				<span class='list-item'>
					<span>Email</span>
					<span id='email-text'>{$user['email']}</span>
				</span>
				<span class='list-item'>
					<span>Telefonnummer</span>
					<span id='phone-text'>{$user['phone']}</span>
				</span>
			</span>
			<!-- Qualifikation ausblendne -->
			<div class='hidden'>
			Der Nutzer hat folgende Qualifikationen:
			{$qualification_list}
			</div>
			<a href='{$root_directory}/user' class='button'>Übersicht</a>";
    } else {
        /***********************************************************************/
        /* User overview													   */
        /***********************************************************************/
        $title = "Mitarbeiter";

        $content = "
		<label for='user-filter' class='hidden'>Wähle eine Eigenschaft, um die Nutzer zu filtern: </label>
		<select class='filter hidden' name='user-filter'>
			<option value='Alle'>Alle</option>
			<option value='kletterbetreuer'>Kletterbetreuer</option>
			<option value='führerschein'>Führerschein</option>
		</select>";

        $content = "<div class='col-sm-9'>
			<table class='table table-striped'>
			<tr>
				<th class='col-sm-2'>Name</th>
				<th class='col-sm-2'>Funktion</th>
			</tr>";

        $users = getUsers();



        foreach ($users as $user) {
            $user = $user->serialize();

            $roles_list = "";
            foreach ($user['roles'] as $role) {
                $roles_list .= ", " . $role['title'];
            }
            $roles_list = substr($roles_list, 2);

            $qualifications_list = "";
            foreach ($user['qualifications'] as $qualification) {
                if ($qualification['user_id'] != null)
                    $qualifications_list .= " " . strtolower($qualification['description']);
            }

            $content .= "
				<tr>
					<td><a href='?id={$user['id']}'>{$user['username']}</a></td>
					<td>{$roles_list}</td>
				</tr>";
        }

        $content .= "
				</table></div><div class='col-sm-3'></div> ";
    }


    include('_main.php');
}
?>
