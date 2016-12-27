<?php
include_once('_init.php');


if($_POST['action'] == 'SHOW_OVERVIEW'){

    if($_POST['groups']) {
        $groups = explode(',', $_POST['groups']);
    if($groups[0] == "all"){

        $usersObj = getUsers();
        foreach ($usersObj as $user) {

            $users[] = $user->serialize();
        }


    }

    else
    {
        $members = array();
        $users = array();
        foreach ($groups as $group){
            $m = getGroupMembers($group);
            foreach ($m as $item){
                $members [] = $item;

            }

        }
        foreach ($members as $member){
            if(!in_array($member, $users)){
                $users[] = $member;
            }
        }


    }
    }


    $content = "<div id='#content'><table class='table table-striped'>
				<tr>
					<th class='col-sm-3'>Name</th>
					<th class='col-sm-2'>Telefon</th>
					<th class='col-sm-4'>E-Mail</th>
					<th class='col-sm-3'>Funktion</th>
					<th></th>
                </tr>";





            foreach ($users as $user) {



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
							<td><a href='?id={$user['id']}'>{$user['last_name']}, {$user['first_name']}</a></td>
							<td>{$user['phone']}</td>
							<td>{$user['email']}</td>
							<td>{$roles_list}</td>
							<td>";



                $content .= "
							</td>
						</tr>";
            }

            $content .= "
                    </table></div>
                    
                    <!-- Recipes dynamisch erstellen -->
                    <div id='#recipes'>
                    <input type='hidden' id='message-recipes' value='";
    foreach ($users as $user){


            if($user['email'])
                $content .= $user['email'] . ', ';



                }
            $content .="'></div>
           ";





}else if($_POST['action'] == 'RELOAD_CHOSER'){
    $groups = getGroups();
    $selected = explode(',', $_POST['groups']);
    $content .= "<select class='form-control chosen-select groups-select' onchange='cityrock.changeSelectGroups()' id='members' name='members' multiple='true' >";
    if(in_array('all', $selected)){
        $content .= "<option value='all' selected>Alle</option>";
    }else {
        $content .= "<option value='all'>Alle</option>";
    }
    foreach ($groups as $group) {

        if(in_array($group['id'], $selected)){
            $content .= "<option value='{$group['id']}' selected>{$group['title']}</option>";
        }else {
            $content .= "<option value='{$group['id']}'>{$group['title']}</option>";
        }
    }
    $content.="</select>
    <script> jQuery(document).ready(function(){
			jQuery('.chosen-select').chosen({width: '100%'});
			
		        });</script>";


}
echo $content;



/*

                    </div>
                */