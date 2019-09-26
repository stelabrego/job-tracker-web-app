<?php
function addContactPage()
{
    $pageData['base'] = "../";
    $pageData['title'] = "Add Contact Page";
    $pageData['heading'] = "Job Tracker Add Contact Page";
    $pageData['nav'] = true;
    $pageData['content'] = file_get_contents('views/admin/add_contact.html');
    $pageData['js'] = "Util^general^contact";
    $pageData['security'] = true;

    return $pageData;
}

function updateContactPage()
{
    $pageData['base'] = "../";
    $pageData['title'] = "Update Contact Page";
    $pageData['heading'] = "Job Tracker Update Contact Page";
    $pageData['nav'] = true;
    $pageData['content'] = file_get_contents('views/admin/update_contact.html');
    $pageData['js'] = "Util^general^contact";
    $pageData['security'] = true;

    return $pageData;
}

function manageContactPage()
{
    $pageData['base'] = "../";
    $pageData['title'] = "Manage Contacts Page";
    $pageData['heading'] = "Job Tracker Manage Contacts Page";
    $pageData['nav'] = true;
    $pageData['content'] = file_get_contents('views/admin/manage_contacts.html');
    $pageData['js'] = "Util^general^contact";
    $pageData['security'] = true;

    return $pageData;
}

function deleteContactPage()
{
    $pageData['base'] = "../";
    $pageData['title'] = "Delete Contact Page";
    $pageData['heading'] = "Job Tracker Delete Contact Page";
    $pageData['nav'] = true;
    $pageData['content'] = file_get_contents('views/admin/delete_contacts.html');
    $pageData['js'] = "Util^general^contact";
    $pageData['security'] = true;

    return $pageData;
}

/* ALL XHR FUNCTIONS HERE */
function addUpdateContact($dataObj)
{
    require_once '../classes/Validation.php';
    require_once '../classes/Pdo_methods.php';
    require_once '../classes/General.php';
    require_once '../utils/devLog.php';

    devLog("contact.php, addUpdateContact()", print_r($dataObj, true));

    $validate = new Validation();

    $i = 0;
    $error = false;

    while ($i < count($dataObj->elements)) {
        if (!$validate->validate($dataObj->elements[$i]->regex, $dataObj->elements[$i]->value)) {
            $error = true;
            $dataObj->elements[$i]->status = 'error';
        }
        $i++;
    }

    if ($error) {
        $dataObj->masterstatus = 'fielderrors';
        $data = json_encode($dataObj);
        echo $data;
    } elseif ($dataObj->flag == "addcontact") {
        $General = new General();
        $pdo = new PdoMethods();
        $result = $General->checkDuplicates($dataObj, 'contact', $pdo);

        if ($result != 'error') {
            if (count($result) != 0) {
                $response = (object) [
                    'masterstatus' => 'error',
                    'msg' => 'There is already a contact with that email',
                ];
                echo json_encode($response);
            } else {

                $sql = "INSERT INTO contact (name, work_phone, mobile_phone, email) VALUES (:name, :workphone, :mobilephone, :email)";
                $elementNames = array('name^^str', 'workphone^^str', 'mobilephone^^str', 'email^^str');
                $bindings = $General->createBindedArray($elementNames, $dataObj);
                $result = $pdo->otherBinded($sql, $bindings);

                if ($result = 'noerror') {
                    $response = (object) [
                        'masterstatus' => 'success',
                        'msg' => 'The contact has been added',
                    ];
                    echo json_encode($response);

                } else {
                    $response = (object) [
                        'masterstatus' => 'error',
                        'msg' => 'There was a problem adding the contact',
                    ];
                    echo json_encode($response);
                }
            }
        } else {
            $object = (object) [
                'masterstatus' => 'error',
                'msg' => 'There was an error with our sql statement',
            ];
            echo json_encode($object);
        }

    } else {
        $General = new General();
        $pdo = new PdoMethods();
        foreach ($dataObj->elements as $element) {
            if ($element->id === 'email') {
                $newEmail = $element->value;
                break;
            }
        }
        /* HERE I LOOP THROUGH DATA ELEMENTS AND GET THE ORIGINAL NAME THAT IS STORED IN A HIDDEN FIELD ON THE FORM. IT CONTAINS THE LAST NAME USED FOR THE ACCOUNT.*/
        foreach ($dataObj->elements as $element) {
            if ($element->id === 'hiddenEmail') {
                $origEmail = $element->value;
                break;
            }
        }
        devLog("addUpdatecontact() origEmail, newEmail", $origEmail . "   " . $newEmail);
        /* I COMPARE BOTH NAMES AND IF THEY ARE THE SAME THEN I MOVE ON SETTING THE RESULT TO AN EMPTY ARRAY WHICH IS WHAT THE CHECK DUPLICATES FUNCTION WILL RETURN IF THERE ARE NO DUPLICATE NAMES FOUND IN THE DATABASE.  I HAVE TO DO THIS BECAUSE TO ELIMINATE THE ERROR IF THE USER DID NOT CHANGE THE AND I RAN CHECK DUPCLIATES IT WOULD THINK THERE WAS A DUPLICATE NAME WHEN THERE WAS NOT.  OPTIONALLY I COULD HAVE SENT THE RECORD ID WITH THE NAME AND COMPARED IT IN THE DATABASE.*/
        if ($origEmail === $newEmail) {
            $result = [];
        }

        /* IF THE ORIGNAME AND NEWNAME DO NOT MATCH THEN A CHECK DUPLIATE WILL BE RUN ON THE NEW NAME TO INSURE THAT IT IS NOT BEING USED BY ANOTHER ACCOUNT. */
        else {
            $result = $General->checkDuplicates($dataObj, 'contact', $pdo);
        }

        if ($result != 'error') {
            if (count($result) != 0) {
                $response = (object) [
                    'masterstatus' => 'error',
                    'msg' => 'There is already a contact with that email',
                ];
                echo json_encode($response);
            } else {

                $sql = "UPDATE contact set name=:name, work_phone=:workphone, mobile_phone=:mobilephone, email=:email WHERE id=:id";
                $elementNames = array('name^^str', 'workphone^^str', 'mobilephone^^str', 'email^^str');
                $bindings = $General->createBindedArray($elementNames, $dataObj);
                array_push($bindings, array(":id", $dataObj->contactId, "int"));
                devLog(print_r($bindings, true));
                $result = $pdo->otherBinded($sql, $bindings);

                if ($result = 'noerror') {
                    $response = (object) [
                        'masterstatus' => 'success',
                        'msg' => 'The contact has been updated',
                    ];
                    echo json_encode($response);

                } else {
                    $response = (object) [
                        'masterstatus' => 'error',
                        'msg' => 'There was a problem updating the contact',
                    ];
                    echo json_encode($response);
                }

            }
        } else {
            $object = (object) [
                'masterstatus' => 'error',
                'msg' => 'There was an error with our sql statement',
            ];
            echo json_encode($object);
        }
    }

}

function getContactList($dataObj)
{
    require_once '../classes/Pdo_methods.php';
    $pdo = new PdoMethods();
    $sql = "SELECT id, name FROM contact";
    $records = $pdo->selectNotBinded($sql);
    if ($records == 'error') {
        echo 'There was an error getting the contacts list';
    } else {
        if (count($records) != 0) {
            $contacts = '<select id="contlst" class="form-control">
            <option value="0">Select a contact</option>';
            foreach ($records as $row) {
                $contacts .= "<option value=" . $row['id'] . ">" . $row['name'] . "</option>";
            }
            $contacts .= '</select>';

            $response = (object) [
                'masterstatus' => 'success',
                'list' => $contacts,
            ];
            $data = json_encode($response);
            echo $data;
        } else {
            $response = (object) [
                'masterstatus' => 'error',
                'msg' => 'No contacts found',
            ];
            $data = json_encode($response);
            echo $data;
        }
    }
}

function getContact($dataObj)
{
    devLog("contact.php, getContact", print_r($dataObj, true));
    require '../classes/Pdo_methods.php';
    $pdo = new PdoMethods();
    $sql = "SELECT * FROM contact WHERE id=:id";
    $bindings = array(
        array(':id', $dataObj->id, 'int'),
    );

    $records = $pdo->selectBinded($sql, $bindings);

    if ($records == 'error') {
        echo "ERROR";
    } else {
        if (count($records) != 0) {
            $entry = $records[0];
            devLog("contact.php, getContact()", print_r($entry, true));
            $table = '
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="name">Name:</label>
                  <input type="text" class="form-control" id="name" name="name" value="' . $entry['name'] . '">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="workphone">Work Phone:</label>
                  <input type="text" class="form-control" name="workphone" id="workphone" value="' . $entry['work_phone'] . '">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="mobilephone">Mobile Phone: (optional)</label>
                  <input type="text" class="form-control" name="mobilephone" id="mobilephone" value="' . $entry['mobile_phone'] . '">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="email">Email:</label>
                  <input type="text" class="form-control" name="email" id="email" value="' . $entry['email'] . '">
                  <input type="hidden" name="hiddenEmail" id="hiddenEmail" value="' . $entry['email'] . '">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <input type="button" name="updatecontact" id="updatecontactBtn" class="btn btn-primary" value="Update Contact" />
                </div>
              </div>
            </div>
            ';
            echo $table;
        } else {
            echo "ERROR";
        }
    }
}

function mcInterface($dataObj)
{
    devLog("contact.php, mcInterface()", $dataObj);
    require '../classes/Pdo_methods.php';
    $pdo = new PdoMethods();

    // Get contact name
    $sql = "SELECT name FROM contact WHERE id=:contId;";
    $bindings = array(
        array(':contId', $dataObj->contId, 'int'),
    );
    $name = $pdo->selectBinded($sql, $bindings)[0]["name"];

    // get account list (taken from)
    $pdo = new PdoMethods();
    $sql = "SELECT id, name FROM account";
    $records = $pdo->selectNotBinded($sql);
    if ($records == 'error') {
        echo 'There was an error getting the accounts list';
    } else {
        if (count($records) != 0) {
            $accounts = '<select id="acclst" class="form-control">
            <option value="0">Select an account</option>';
            foreach ($records as $row) {
                $accounts .= "<option value=" . $row['id'] . ">" . $row['name'] . "</option>";
            }
            $accounts .= '</select>';

        } else {
            $accounts = 'No accounts found';
        }

    }

    $response = (object) [
        'name' => $name,
        'associations' => updateAssocTable($dataObj),
        'accounts' => $accounts,
    ];
    $data = json_encode($response);
    echo $data;

}

function addAssoc($dataObj)
{
    devLog("addAssoc() dataObj", $dataObj);
    require_once '../classes/Pdo_methods.php';
    require_once '../utils/devLog.php';
    $pdo = new PdoMethods();

    $bindings = array(
        array(":acctId", $dataObj->acctId, "int"),
        array(":jobId", $dataObj->jobId, "int"),
        array(":contId", $dataObj->contId, "int"),
    );
    $sql = "SELECT account_id, job_id, contact_id FROM job_contact WHERE account_id=:acctId AND job_id=:jobId AND contact_id=:contId";
    devLog("addAssoc() bindings", $bindings);

    $result = $pdo->selectBinded($sql, $bindings);

    if (count($result) > 0) {
        $response = (object) [
            'masterstatus' => 'error',
            'msg' => 'This association already exists',
        ];
        echo json_encode($response);
        return;
    }
    $sql = "INSERT INTO job_contact (account_id, job_id, contact_id) VALUES (:acctId, :jobId, :contId);";
    devLog("addAssoc() sql", $sql);

    devLog("addAssoc() bindings", $bindings);
    $result = $pdo->otherBinded($sql, $bindings);

    if ($result = 'noerror') {
        $response = (object) [
            'masterstatus' => 'success',
            'msg' => 'The association has been added',
        ];
    } else {
        $response = (object) [
            'masterstatus' => 'error',
            'msg' => 'There was a problem adding the association',
        ];
    }
    $response->associations = updateAssocTable($dataObj);

    echo json_encode($response);
}

function updateAssocTable($dataObj)
{
    require_once '../classes/Pdo_methods.php';
    require_once '../utils/devLog.php';
    $pdo = new PdoMethods();
    $sql = "SELECT account.name AS accountname, job.name AS jobname, job.id AS jobid, account.id AS accountid, job_contact.id AS jobcontactid  FROM account, job, job_contact WHERE account.id = job_contact.account_id AND job.id = job_contact.job_id AND job_contact.contact_id = :contId;";
    $bindings = array(
        array(':contId', $dataObj->contId, 'int'),
    );

    $associations = $pdo->selectBinded($sql, $bindings);
    if (count($associations) == 0) {
        $associationsTable = "No associations!";
    } else {
        $associationsTable = "<table class='table table-bordered table-striped' id='assocTable'><thead><tr><th>Account</th><th>Job</th><th></th></tr></thead><tbody>";
        foreach ($associations as $row) {
            $associationsTable .= "<tr><td>" . $row["accountname"] . "</td><td>" . $row["jobname"] . "</td><td style='width: 40px;'><button value='Delete' type='button' id='" . $row["accountid"] . "&&&" . $row["jobid"] . "' class='btn btn-danger'>Delete</button></td></tr>";
        }
        $associationsTable .= "</tbody></table>";
    }
    return $associationsTable;
}

function delAssoc($dataObj)
{
    require '../classes/Pdo_methods.php';
    $pdo = new PdoMethods();
    $sql = "DELETE FROM job_contact WHERE account_id=:acctId AND contact_id=:contId AND job_id=:jobId;";
    // Bindings have to be an array of arrays
    $bindings = array(
        array(":acctId", $dataObj->acctId, "int"),
        array(":jobId", $dataObj->jobId, "int"),
        array(":contId", $dataObj->contId, "int"),
    );
    $result = $pdo->otherBinded($sql, $bindings);
    if ($result == "error") {
        $response = (object) [
            'masterstatus' => 'error',
            'msg' => "Failed to delete association",
        ];
    } else {
        $response = (object) [
            'masterstatus' => 'success',
            'msg' => "Successfully deleted association",
        ];
    }
    $response->associations = updateAssocTable($dataObj);
    echo json_encode($response);

}

function contactTable()
{
    $output = "<table class='table table-bordered table-striped' id='contTable'><thead><tr><th>Name</th><th>Work Phone</th><th>Mobile Phone</th><th>Email</th><th></th></tr></thead><tbody>";
    require_once '../classes/Pdo_methods.php';
    $pdo = new PdoMethods();
    $sql = "SELECT id, name, work_phone, mobile_phone, email FROM contact";
    $records = $pdo->selectNotBinded($sql);
    if ($records == 'error') {
        echo 'There was an error getting the contacts list';
    } elseif (count($records) == 0) {
        echo 'There are no contacts!';

    } else {
        devLog("contactTable()", print_r($records, true));
        foreach ($records as $record) {
            $output .= "<tr><td>" . $record["name"] . "</td><td>" . $record["work_phone"] . "</td><td>" . $record["mobile_phone"] . "</td><td>" . $record["email"] . "</td><td style='width: 40px;'><button type='button' id='" . $record["id"] . "' class='btn btn-danger'>Delete</button></td></tr>";
        }
        $output .= "</tbody></table";
        echo $output;
    }
}

function deleteContact($dataObj)
{
    require '../classes/Pdo_methods.php';
    $pdo = new PdoMethods();
    $sql = "DELETE FROM contact WHERE id=:id";
    // Bindings have to be an array of arrays
    $bindings = [[":id", $dataObj->contId, "int"]];
    $result = $pdo->otherBinded($sql, $bindings);

    if ($result == "error") {
        $response = (object) [
            'masterstatus' => 'error',
            'msg' => "Failed to delete contact",
        ];
        $data = json_encode($response);
        echo $data;
    } else {
        // Delete the contact associations as well (although it won't truly matter to the user anyway since we never reuse ID's)
        $sql2 = "DELETE FROM job_contact WHERE contact_id=:id";
        $pdo->otherBinded($sql, $bindings);
        $response = (object) [
            'masterstatus' => 'success',
            'msg' => "Successfully deleted contact",
        ];
        $data = json_encode($response);
        echo $data;
    }
}
    if (count($records) == 0) {
        echo 'There are now assets for this account';
    } else {
        $table = '<table class="table table-bordered table-striped" id="accountAssetTable"><thead><tr><th>Name</th><th>Delete</th></tr></thead><tbody>';

        foreach ($records as $row) {
            $table .= '<tr><td style="width: 80%"><a href="../docs/' . $row['file'] . '">' . $row['name'] . '</a></td>';
            $table .= '<td style="width: 20%"><input type="button" class="btn btn-danger" id="' . $row['id'] . '" value="Delete"></td></tr>';
        }

        $table .= '</table>';

        echo $table;
    }

}

function delAsset($dataObj)
{
    require_once '../classes/Pdo_methods.php';
    $pdo = new PdoMethods();

    $sql = "SELECT file FROM account_asset WHERE id = :id";

    $bindings = array(
        array(':id', $dataObj->id, 'int'),
    );

    $records = $pdo->selectBinded($sql, $bindings);

    foreach ($records as $row) {
        $filepath = $row['file'];
    }

    if (!unlink($filepath)) {
        $object = (object) [
            'masterstatus' => 'error',
            'msg' => 'Could not delete file',
        ];
        echo json_encode($object);
        exit;
    }

    $sql = "DELETE FROM account_asset WHERE id=:id";

    $result = $pdo->otherBinded($sql, $bindings);

    if ($result = 'noerror') {
        $object = (object) [
            'masterstatus' => 'success',
            'msg' => 'Record Deleted',
        ];
        echo json_encode($object);
    } else {
        $object = (object) [
            'masterstatus' => 'error',
            'msg' => 'Could not delete record',
        ];
        echo json_encode($object);
    }

}
