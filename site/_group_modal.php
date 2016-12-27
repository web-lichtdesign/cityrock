<? $content .="<div class='modal fade' id='edit-group' tabindex='-1' role='dialog' aria-labelledby='myModalLabel'>
    <div class='modal-dialog' role='document'>
        <div class='modal-content'>
            <div class='modal-header'>
                <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                <h4 class='modal-title' id='myModalLabel'>Gruppen</h4>
            </div>
            <div class='modal-body'>
                <div id='new-group'>
                    <form>
                        <div class='form-group'>
                            <label for='titel'>Gruppenname:</label>
                            <input type='titel' class='form-control' id='titel'>
                        </div>
                        <div class='form-group'>
                            <label for='caption'>Beschreibung:</label>
                            <textarea name='caption'></textarea>
                        </div>
                        <div class='form-group'>
                            <label for='member'>Mitglieder:</label>

                            <select class='chosen-select' multiple='true' >";


                                    $users = getUsers();
                                var_dump(§users);
                                    foreach ($users as $user) {
                                        $user = $user->serialize();
                                        $content .= "<option value='{$user['id']}'>{$user['username']}</option>";


}
                           $content .=" </select>



                        </div>
                        <div class='form-group'>
                            <label for='member'>Bedinung:</label>
                            <select class='chosen-select' multiple='true' >
                                <option>Choose...</option>
                                <option>jQuery</option>
                                <option >MooTools</option>
                                <option>Prototype</option>
                                <option>Dojo Toolkit</option>
                            </select>


                        </div>
                        <input type='submit' class='btn btn-primary' value='Speichern'>
                    </form>
                </div>
            </div>
            <div class='modal-footer'>
                <button type='button' class='btn btn-default' data-dismiss='modal'>Schließen</button>
                <button type='button' class='btn btn-primary'>Speichern</button>
            </div>
        </div>
    </div>
</div>