<?php
include_once('_init.php');
$content ="";

if($_POST['action'] == "SHOW_GROUP"){

    if ($_POST['group_id'] == 'new'){
        $content .= "<div id='new-group'>
                    <form id='create-new-group' onsubmit='cityrock.createNewGroup(); return false;' data-toggle='validator'>
                        <div class='form-group'>
                            <label for='title'>Gruppenname:</label>
                            <input type='title' class='form-control' name='title' id='title' required>
                        </div>
                        <div class='form-group'>
                            <label for='caption'>Beschreibung:</label>
                            <textarea name='caption' id='caption'></textarea>
                        </div>
                        <div class='form-group'>
                            <label for='members'>Mitglieder:</label>

                            <select class='chosen-select members-select' id='members' name='members' multiple='true' >";


            $users = getUsers();

            foreach ($users as $user) {
                $user = $user->serialize();
                $content .= "<option value='{$user['id']}'>{$user['username']}</option>";


            }
            $content .=" </select>



                        </div>
                        <div class='form-group'>
                            <label for='members'>Bedinung:</label>
                            <select class='chosen-select statements-select' multiple='true' >";

                            $statements = getCourseTypes();
            foreach ($statements as $statement) {
                $content.="<option value='{$statement['id']}'>{$statement['title']}</option>";
                            }
                           $content .=" </select>


                        </div>
                        <input type='submit' class='btn btn-primary' value='Speichern'>
                    </form>
                </div>
                <script> jQuery(document).ready(function(){
			jQuery('.chosen-select').chosen({width: \"100%\"});
			$('#create-new-group').validator();
		});</script>
                ";

    } else {
        $group = getGroup($_POST['group_id']);

        $content .= "<label>Gruppenname</label>
                      <p>{$group['title']}</p>
                      <label>Beschreibung</label>
                      <p>{$group['caption']}</p>
                      <label>Mitgleider</label>";
        foreach ($group['members'] as $member){

            $content .= "<p>{$member['username']}</p>";
        }


        $content .="<label>Bedinung</label>";
        foreach ($group['statements'] as $statement){

            $content .= "<p>{$statement['title']}</p>";
        }
         $content.="               <p></p>
                      <button class='btn btn-danger btn-delete-group' onclick='cityrock.deleteGroup()'>Gruppe Löschen</button> ";

    }

}else if ($_POST['action'] == "EDIT_GROUP"){
    $group = getGroup($_POST['group_id']);

    $content .= "<script> jQuery(document).ready(function(){
			jQuery('.chosen-select').chosen({width: '100%'});
			$('#create-new-group').validator();
		        });</script>
                <div id='new-group'>
                    <form id='create-new-group' onsubmit='cityrock.updateGroup({$group['id']}); return false;'>
                        <div class='form-group'>
                            <label for='title'>Gruppenname:</label>
                            <input type='title' class='form-control' name='title' id='title' value='{$group['title']}' required>
                        </div>
                        <div class='form-group'>
                            <label for='caption'>Beschreibung:</label>
                            <textarea name='caption' id='caption' >{$group['caption']}</textarea>
                        </div>
                        <div class='form-group'>
                            <label for='members'>Mitglieder:</label>

                            <select class='chosen-select members-select' id='members' name='members' multiple='true' >";



    $users = getUsers();

    foreach ($users as $user) {
        $user = $user->serialize();
        $isInGroup = false;
        foreach ($group['members'] as $member){
            if($user['id'] == $member ['id']){
                $content .= "<option value='{$user['id']}' selected>{$user['username']}</option>";
                $isInGroup = true;
                break;

            }

        }
        if(!$isInGroup){
            $content .= "<option value='{$user['id']}'>{$user['username']}</option>";
        }


    }
    $content .=" </select>



                        </div>
                        <div class='form-group'>
                            <label for='members'>Bedinung:</label>
                            <select class='chosen-select statements-select' multiple='true' >";

    $statements = getCourseTypes();
    foreach ($statements as $statement) {
        $isInGroup = false;
        foreach ($group['statements'] as $s){
            if($s['id'] == $statement['id']){
                $content.="<option value='{$statement['id']}' selected>{$statement['title']}</option>";
                $isInGroup = true;
                break;

            }
        }
        if(!$isInGroup) {
            $content .= "<option value='{$statement['id']}'>{$statement['title']}</option>";
        }
    }
    $content .=" </select>


                        </div>
                        <input type='submit' class='btn btn-primary' value='Speichern'>
                        <input type='button' class='btn btn-danger btn-delete-group' value='Gruppe Löschen' onclick='cityrock.deleteGroup()>
                    </form>
                </div>
                
                            ";


}else if  ($_POST['action'] == "RELOAD_CHOSER"){
    $groups = getGroups();
    $content .="<select id='select-group-modal'  onchange='cityrock.changeSelectGroup()' class='col-sm-4 form-control'>";
    if (count($groups) > 0){
                    foreach ($groups as $group){
                        if (next($groups)) {
                            $content .= "<option value='{$group['id']}'>{$group['title']}</option>";
                        } else {
                            $content .= "<option value='{$group['id']}' selected>{$group['title']}</option>";
                        }
                    }}
            $content .="
                        <option value=''>---------</option>
                        <option value='new'>Neu</option>
                    </select>
                    <script>
                        $('#select-group-modal').change();
                    </script>";
}


echo $content;