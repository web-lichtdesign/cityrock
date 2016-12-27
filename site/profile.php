<?php

include_once('_init.php');
include_once('inc/user.php');

$title = "Nutzerprofil";

$user = $_SESSION['user'];

/***********************************************************************/
/* Process form data												   */
/***********************************************************************/
if(isset($_POST['modify'])) {
	$phone = $_POST['phone'];
	$email = $_POST['email'];
	$password = $_POST['password'];

	$user_data_array = array();

	// do not accept empty passwords
	if($password) $user_data_array['password'] = md5($password);

	$user_data_array['phone'] = $phone;
	$user_data_array['email'] = $email;

	$success = User::updateUserData($user_data_array, $user['id']);

	// modify POST user object
	if($success) {
		foreach ($user_data_array as $key => $value) {
			$_SESSION['user'][$key] = $value;
		}
	}

	$qualifications_array = array();

	foreach($_POST as $key => $value) {

		if(is_numeric($key) && $value) {
			if(!array_key_exists($key, $qualifications_array)) {
				$qualifications_array[$key] = null;
			}
		}
		else if(strpos($key, 'date-') == 0 && $value) {
			$qualification_id = substr($key, 5);

			if(is_numeric($qualification_id)) {
				$qualifications_array[$qualification_id] = $value;
			}
		}
	}

	$success = User::updateUserQualifications($qualifications_array, $user['id']) ? $success : false;

	// modify POST user object
	if($success) 
		$_SESSION['user']['qualifications'] = User::getQualifications($user['id']);

	if($success)
        header('Location: ' . $root_directory . "/profile");

    else
		$content = "Fehler: Deine Daten konnten nicht gespeichert werden.";	
}
else {
	$content .= "
	
	<form method='post' onsubmit='return cityrock.validateProfile(this);'>
		<table class='table table-striped '>
			<tr>
				<th class='col-sm-2'>Nutzername</th>
				<th class=''>{$user['username']}</th>
				<th class='col-sm-6'></th>
				
			</tr>
			<tr>
				<td>Telefonnummer</td>
				<td id='phone-text'>
					{$user['phone']}
					<input  type='hidden' name='phone' value='{$user['phone']}' />
				</td>
				<td></td>
			</tr>
			<tr>
				<td>Email</td>
				<td id='email-text'>
					{$user['email']}
					<input  type='hidden' name='email' value='{$user['email']}' />
				</td>
				<td></td>
			</tr>
			<tr>
				<td>Passwort</td>
				<td id='password-text'>
					*******
					<input  type='hidden' name='password' value='' />
				</td>
				<td></td>
			</tr>
		</table>
		<span class='list'>";

	foreach ($user['qualifications'] as $qualification) {

		$description = strtolower($qualification['description']);
		$checked = $qualification['user_id'] == null ? '' : 'checked';

		$content .= "
                <!-- qualifikationen ausblendne -->
				<span class='list-item hidden'>
					<span>
						<input type='checkbox' name='{$qualification['id']}' id='{$description}' {$checked} />
						<label for='{$description}'>{$qualification['description']}</label>
					</span>";

		if($qualification['date_required'] == 1) {
			$content .= " 		
					<span id='{$description}-date'>
						Datum des Kurses? 
						<input type='text' id='{$description}-date-input' class='date' name='date-{$qualification['id']}' placeholder='01.01.1906' value='{$qualification['date']}' />
					</span>";
		}

		$content .="		
				</span>";
	}

	$content .= "
				<input type='hidden' name='modify' />
				<a href='#' id='edit-user' class='btn btn-primary'>Bearbeiten</a>
			</span>
		</form>";

}

$content_class = "profile";
include('_main.php');

?>