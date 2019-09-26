<?php
function addJobPage()
{
    $pageData['base'] = "../";
    $pageData['title'] = "Add Job Page";
    $pageData['heading'] = "Job Tracker Add Job Page";
    $pageData['nav'] = true;
    $pageData['content'] = file_get_contents('views/admin/add_job.html');
    $pageData['js'] = "Util^general^job";
    $pageData['security'] = true;

    return $pageData;
}

function viewJobContactsPage()
{
    $pageData['base'] = "../";
    $pageData['title'] = "View Job Contacts Page";
    $pageData['heading'] = "Job Tracker View Job Contacts Page";
    $pageData['nav'] = true;
    $pageData['content'] = file_get_contents('views/admin/view_job_contacts.html');
    $pageData['js'] = "Util^general^job";
    $pageData['security'] = true;

    return $pageData;
}

function addJobNotePage()
{
    $pageData['base'] = "../";
    $pageData['title'] = "Add Job Note Page";
    $pageData['heading'] = "Job Tracker Add Job Note Page";
    $pageData['nav'] = true;
    $pageData['content'] = file_get_contents('views/admin/add_job_notes.html');
    $pageData['js'] = "Util^general^job";
    $pageData['security'] = true;

    return $pageData;
}

function viewUpdateDeleteNotePage()
{
    $pageData['base'] = "../";
    $pageData['title'] = "Update/Delete Note Page";
    $pageData['heading'] = "Job Tracker Update/Delete Note Page";
    $pageData['nav'] = true;
    $pageData['content'] = file_get_contents('views/admin/view_delete_job_notes.html');
    $pageData['js'] = "Util^general^job";
    $pageData['security'] = true;

    return $pageData;
}

function addJobAssetPage()
{
    $pageData['base'] = "../";
    $pageData['title'] = "Add Job Asset Page";
    $pageData['heading'] = "Job Tracker Add Job Assets Page";
    $pageData['nav'] = true;
    $pageData['content'] = file_get_contents('views/admin/add_job_assets.html');
    $pageData['js'] = "Util^general^job";
    $pageData['security'] = true;

    return $pageData;
}

function viewDeleteJobAssetPage()
{
    $pageData['base'] = "../";
    $pageData['title'] = "Delete Job Asset Page";
    $pageData['heading'] = "Job Tracker Delete Job Assets Page";
    $pageData['nav'] = true;
    $pageData['content'] = file_get_contents('views/admin/view_delete_job_assets.html');
    $pageData['js'] = "Util^general^job";
    $pageData['security'] = true;

    return $pageData;
}

function addJobHoursPage()
{
    $pageData['base'] = "../";
    $pageData['title'] = "Add Job Hours Page";
    $pageData['heading'] = "Job Tracker Add Job Hours Page";
    $pageData['nav'] = true;
    $pageData['content'] = file_get_contents('views/admin/add_job_hours.html');
    $pageData['js'] = "Util^general^job";
    $pageData['security'] = true;

    return $pageData;
}

function updateDeleteJobHoursPage()
{
    $pageData['base'] = "../";
    $pageData['title'] = "Update/Delete Job Hours Page";
    $pageData['heading'] = "Job Tracker Update/Delete Job Hours Page";
    $pageData['nav'] = true;
    $pageData['content'] = file_get_contents('views/admin/update_delete_hours.html');
    $pageData['js'] = "Util^general^job";
    $pageData['security'] = true;

    return $pageData;
}

function printInvoicePage()
{
    $pageData['base'] = "../";
    $pageData['title'] = "Print Invoice Page";
    $pageData['heading'] = "Job Tracker Print Invoice Page";
    $pageData['nav'] = true;
    $pageData['content'] = file_get_contents('views/admin/print_invoice.html');
    $pageData['js'] = "Util^general^job";
    $pageData['security'] = true;

    return $pageData;
}

/* ALL XHR FUNCTIONS HERE */

function addJob($dataObj)
{
    require_once '../classes/Validation.php';
    require_once '../classes/Pdo_methods.php';
    require_once '../classes/General.php';
    require_once '../utils/devLog.php';

    devLog("job.php, addJob()", print_r($dataObj, true));
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
    } else {
        $General = new General();
        $pdo = new PdoMethods();

        /* IF EVERYTHING IS VALID THEN CHECK FOR A DUPLICATE NAME

        USE THE CHECKDUPLICATES METHOD FROM THE GENRERAL CLASS.  THE SECOND PARAMETER IS THE TABLE WE ARE GOING TO CHECK. IN THE METHOD BASED UPON THAT DECIDES WHAT QUERY WE USE. THE $PDO IS THE CONNECTION INSTANCE  SEE GENERAL METHOD FOR MORE INFO */

        $result = $General->checkDuplicates($dataObj, 'job', $pdo);

        /* BASED UPON THE RESULT CREATE A CUSTOM OBJECT CONTAINING THE MASTERSTATUS AND MESSAGE.  ON THE JAVASCRIPT END I CREATE A RESPONSE THAT BASED UPON WHAT IS SENT WILL DISPLAY A MESSAGE BOX SHOWING THE MESSAGE.  THIS IS A NICE WAY OF A CUSTOM MESSAGES BOX FOR THE USER BASED UPON WHAT HAD HAPPENED ON THE SERVER */
        if ($result != 'error') {
            if (count($result) != 0) {
                $response = (object) [
                    'masterstatus' => 'error',
                    'msg' => 'There is already a job with that name',
                ];
                echo json_encode($response);
            } else {
                /* GET THE JOB NAME FROM THE DATAOBJ */
                $i = 0;
                $name = '';
                while ($i < count($dataObj->elements)) {
                    if ($dataObj->elements[$i]->id === 'name') {
                        $name = $dataObj->elements[$i]->value;
                        break;
                    }

                    $i++;
                }

                // skip adding an asset folder because contacts don't have assets

                /* ADD ACCOUNT TO DATABASE
                IMPORTANT NOTE:  THE ORDER OF THE
                 */
                /* CREATE AND THE FOLDER BY ADDING THE NAME AND A TIMESTAMP TO KEEP IT UNIQUE */
                $foldername = $name . time();
                $foldername = str_replace(" ", "_", $foldername);
                $foldername = strtolower($foldername);

                /* ADD THE FOLDER TO THE SERVER GIVE IT 777 PERMISSIONS */
                $path = '../public/job_folders/' . $foldername;
                $dir = mkdir($path, 0777);

                /* ADD ACCOUNT TO DATABASE
                IMPORTANT NOTE:  THE ORDER OF THE
                 */
                // the first paren are the column names in the table, second parens are the id names of the vaules from the $dataObj json
                $sql = "INSERT INTO job (account_id, name, folder) VALUES (:account_id, :name, :folder);";

                /* HERE I CREATE AN ARRAY THAT LISTS THE ELEMENT NAME, WHICH IS THE ID AND THE DATATYPE NEEDED BY PDO.  THEY ARE SEPERATED BY A ^^.  WHEN THIS IS RUN THROUGH THE CREATEBINDEDARRAY OF THE GENERAL CLASS, THAT MEHTHOD WILL CREATE A BINDED ARRAY*/
                $elementNames = array('account_id^^int', 'name^^str', 'folder^^str');

                /* CREATE BINDINGS NEEDED FOR PDO QUERY.  I CREATED A METHOD IN THE GENERAL CLASS THAT DOES THIS AUTOMATICALLY BY SENDING IN THE ELEMENTNAMES ARRAY AND THE DATAOBJ.  FOR THIS TO WORK YOU JUST HAVE THE CORRECT DATAOBJ STRUCTURE*/
                $bindings = array(
                    array(':account_id', $dataObj->accountId, 'int'),
                    array(':name', $name, 'str'),
                    array(':folder', $path, 'str'),
                );
                /* IF THE DIRECTORY WAS CREATED THEN ADD TO THE DATABASE OTHERWISE SEND ERROR MESSAGE */

                $result = $pdo->otherBinded($sql, $bindings);

                if ($result = 'noerror') {
                    $response = (object) [
                        'masterstatus' => 'success',
                        'msg' => 'The job has been added',
                    ];
                    echo json_encode($response);

                } else {
                    $response = (object) [
                        'masterstatus' => 'error',
                        'msg' => 'There was a problem adding the job',
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

function addAsset($dataObj, $file)
{
    require '../classes/Validation.php';
    $Validation = new Validation();
    if (!$Validation->validate($dataObj->elements[0]->regex, $dataObj->elements[0]->value)) {
        $dataObj->masterstatus = 'fielderrors';
        $dataObj->elements[0]->status = 'error';
    }

    if (empty($_FILES)) {
        $dataObj->masterstatus = 'fielderrors';
        $dataObj->elements[1]->msg = 'You must select a file';
        $dataObj->elements[1]->status = 'error';
        echo json_encode($dataObj);
        return;
    }

    //return;

    /* I HAD TO CREATE THE VARIABLES BECAUSE PHP DID NOT LIKE ME PASSING IT BY REFERENCE */
    $filename = $_FILES['file']['name'];
    $filesize = $_FILES['file']['size'];
    $filetype = $_FILES['file']['type'];
    $filetempname = $_FILES['file']['tmp_name'];

    /* CHECK FILE SIZE AND TYPE */
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if (finfo_file($finfo, $filetempname) !== "application/pdf") {
        finfo_close($finfo);
        $dataObj->masterstatus = 'fielderrors';
        $dataObj->elements[1]->msg = 'File is wrong type';
        $dataObj->elements[1]->status = 'error';
        echo json_encode($dataObj);
    } elseif ($filesize > 1000000) {
        $dataObj->masterstatus = 'fielderrors';
        $dataObj->elements[1]->msg = 'File size is too big';
        $dataObj->elements[1]->status = 'error';
        echo json_encode($dataObj);
    }

    /* IF ALL IS GOOD THEN ADD FILE AND UPDATE DATABASE */
    else {
        require_once '../classes/Pdo_methods.php';
        $pdo = new PdoMethods();

        /* GET THE FOLDER PATH FROM THE ACCOUNT DATABASE */
        $sql = "SELECT folder FROM job WHERE id = :id";

        /* SINCE THERE IS ONLY ONE BINDING I DID NOT  NEED TO USE THE GENERAL CLASS*/
        $bindings = array(
            array(':id', $dataObj->jobId, 'int'),
        );

        $records = $pdo->selectBinded($sql, $bindings);

        foreach ($records as $row) {
            $folder = $row['folder'];
        }
        /* REMOVE ALL SPACES FROM THE FILE NAME AND ADD UNDERSCORES */
        $filename = str_replace(" ", "_", $filename);
        $path = $folder . "/" . $filename;

        if (!move_uploaded_file($filetempname, $path)) {
            $dataObj->masterstatus = 'fielderrors';
            $dataObj->elements[1]->msg = 'There was an problem with the file';
            $dataObj->elements[1]->status = 'error';
            echo json_encode($dataObj);
            exit;
        }

        $sql = "INSERT INTO job_asset (job_id, name, file) VALUES (:id, :name, :file)";

        $bindings = array(
            array(':id', $dataObj->jobId, 'int'),
            array(':name', $dataObj->elements[0]->value, 'str'),
            array(':file', $path, 'str'),
        );

        $result = $pdo->otherBinded($sql, $bindings);

        if ($result == 'noerror') {
            $object = (object) [
                'masterstatus' => 'success',
                'msg' => 'Asset has been added',
            ];
            echo json_encode($object);

        } else {
            $object = (object) [
                'masterstatus' => 'error',
                'msg' => 'There was an error adding the asset',
            ];
            echo json_encode($object);
        }
    }
}

function viewDeleteAsset($dataObj)
{
    $output = "<table class='table table-bordered table-striped' id='viewdelassetstable'><thead><tr><th>Name</th><th></th></tr></thead><tbody>";
    require_once '../classes/Pdo_methods.php';
    $pdo = new PdoMethods();
    $sql = "SELECT id, name, file  FROM job_asset WHERE job_id=:jobId";
    $bindings = array(
        array(":jobId", $dataObj->jobId, "int"),
    );
    $records = $pdo->selectBinded($sql, $bindings);
    devLog("viewdleteasset() count of records", count($records));
    if ($records == 'error') {
        $object = (object) [
            'masterstatus' => 'error',
            'msg' => 'Table could not be loaded',
        ];
        echo json_encode($object);
    } elseif (count($records) == 0) {
        $object = (object) [
            'masterstatus' => 'success',
            'table' => '<p>There are no assets for this job</p>',
        ];
        echo json_encode($object);
    } else {
        devLog("viewDeleteAsset()", print_r($records, true));
        foreach ($records as $record) {
            $output .= "<tr><td><a href='" . $record["file"] . "'>" . $record["name"] . "</a></td><td style='width: 40px;'><button value='Delete' type='button' id='" . $record["id"] . "' class='btn btn-danger'>Delete</button></td></tr>";
        }
        $output .= "</tbody></table>";
        $object = (object) [
            'masterstatus' => 'success',
            'table' => $output,
        ];
        echo json_encode($object);
    }
}

function viewJobNotes($dataObj)
{
    $output = "<table class='table table-bordered table-striped'><thead><tr><th>Date</th><th>Name</th><th>Note</th><th></th><th></th></tr></thead><tbody>";
    require_once '../classes/Pdo_methods.php';
    $pdo = new PdoMethods();
    $sql = "SELECT id, job_id, note_date, note_name, note  FROM job_note WHERE job_id=:jobid";
    $bindings = array(
        array(":jobid", $dataObj->jobid, "int"),
    );
    $records = $pdo->selectBinded($sql, $bindings);
    if ($records == 'error') {
        $object = (object) [
            'masterstatus' => 'error',
            'msg' => 'Table could not be loaded',
        ];
        echo json_encode($object);
    } elseif (count($records) == 0) {
        $object = (object) [
            'masterstatus' => 'success',
            'table' => '<p>There are no notes for this job</p>',
        ];
        echo json_encode($object);
    } else {
        foreach ($records as $record) {
            $date = date("m-d-Y", $timestamp = $record["note_date"] / 1000);
            $output .= "<tr><td>" . $date . "</td><td>" . $record["note_name"] . "</td><td>" . $record["note"] . "</td><td style='width: 40px;'><button value='Update' type='button' id='" . $record["id"] . "' class='btn btn-primary'>Update</button></td><td style='width: 40px;'><button value='Delete' type='button' id='" . $record["id"] . "' class='btn btn-danger'>Delete</button></td></tr>";
        }
        $output .= "</tbody></table>";
        $object = (object) [
            'masterstatus' => 'success',
            'table' => $output,
        ];
        echo json_encode($object);
    }
}

function deleteNote($dataObj)
{
    require '../classes/Pdo_methods.php';
    $pdo = new PdoMethods();
    $sql = "DELETE FROM job_note WHERE id=:id";
    // Bindings have to be an array of arrays
    $bindings = [[":id", $dataObj->id, "int"]];
    $result = $pdo->otherBinded($sql, $bindings);
    if ($result == "error") {
        $response = (object) [
            'masterstatus' => 'error',
            'msg' => "Failed to delete note",
        ];
        $data = json_encode($response);
        echo $data;
    } else {
        $response = (object) [
            'masterstatus' => 'success',
            'msg' => "Successfully deleted note",
        ];
        $data = json_encode($response);
        echo $data;
    }
}

function updateNote($dataObj)
{
    require_once '../classes/Validation.php';
    require_once '../classes/Pdo_methods.php';
    require_once '../classes/General.php';
    require_once '../utils/devLog.php';

    devLog("job.php, updateNote()", print_r($dataObj, true));

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
    } else {
        $pdo = new PdoMethods();
        $General = new General();

        $sql = "UPDATE job_note SET note_date=:jobDate, note_name=:notename, note=:note WHERE id=:noteId;";
        $elementNames = array("jobDate^^str", "notename^^str", "note^^str");
        $bindings = $General->createBindedArray($elementNames, $dataObj);

        array_push($bindings, (array(":noteId", $dataObj->noteId, "int")));
        devLog("job.php, updateNote() bindings", print_r($bindings, true));

        $result = $pdo->otherBinded($sql, $bindings);

        if ($result = 'noerror') {
            $response = (object) [
                'masterstatus' => 'success',
                'msg' => 'The note has been updated',
            ];
            echo json_encode($response);

        } else {
            $response = (object) [
                'masterstatus' => 'error',
                'msg' => 'There was a problem updating the note',
            ];
            echo json_encode($response);
        }
    }
}

function updateNoteForm($dataObj)
{
    require_once '../classes/Pdo_methods.php';
    $pdo = new PdoMethods();
    $sql = "SELECT id, job_id, note_date, note_name, note  FROM job_note WHERE id=:noteId";
    $bindings = array(
        array(":noteId", $dataObj->noteId, "int"),
    );
    $record = $pdo->selectBinded($sql, $bindings)[0];
    if ($record == 'error') {
        $object = (object) [
            'masterstatus' => 'error',
            'msg' => 'Note could not be loaded',
        ];
        echo json_encode($object);
    } else {
        devLog("updateNoteForm() \$records", print_r($record, true));
        $date = date("Y-m-d", $timestamp = $record["note_date"] / 1000);

        $output = '<div class="form">
        <div class="row">
          <div class="col-md-4">
            <div class="form-group">
              <label for="jobDate">Date:</label>
              <input type="date" class="form-control" id="jobDate" name="date" value="' . $date . '">
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4">
          <div class="form-group">
            <label for="notename">Note Title:</label>
            <input type="text" class="form-control" id="notename" value="' . $record["note_name"] . '">
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="note">Note:</label>
            <textarea name="note" id="note" class="form-control">' . $record["note"] . '</textarea>
          </div>
        </div>
      </div>

    <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <input type="button" class="btn btn-success" name="' . $record["id"] . '" id="updatejobnoteBtn" value="Update Job Note">
          </div>
        </div>
      </div>
    </div>';
        $object = (object) [
            'masterstatus' => 'success',
            'form' => $output,
        ];
        echo json_encode($object);
    }
}

function delAsset($dataObj)
{
    require_once '../classes/Pdo_methods.php';
    $pdo = new PdoMethods();

    $sql = "SELECT file FROM job_asset WHERE id = :id";

    $bindings = array(
        array(':id', $dataObj->assetId, 'int'),
    );

    $records = $pdo->selectBinded($sql, $bindings);

    foreach ($records as $row) {
        $filepath = $row['file'];
    }
    devLog("delAsset() \$filepath", print_r($filepath, true));
    if (!unlink($filepath)) {
        $object = (object) [
            'masterstatus' => 'error',
            'msg' => 'Could not delete file',
        ];
        echo json_encode($object);
        exit;
    }

    $sql = "DELETE FROM job_asset WHERE id=:id";

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

function addJobNote($dataObj)
{
    require_once '../classes/Validation.php';
    require_once '../classes/Pdo_methods.php';
    require_once '../classes/General.php';
    require_once '../utils/devLog.php';

    devLog("job.php, addJobNote()", print_r($dataObj, true));

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
        return;
    }
    $General = new General();
    $pdo = new PdoMethods();

    $sql = "INSERT INTO job_note (job_id, note_date, note_name, note) VALUES (:jobid, :jobDate, :notename, :note);";

    /* HERE I CREATE AN ARRAY THAT LISTS THE ELEMENT NAME, WHICH IS THE ID AND THE DATATYPE NEEDED BY PDO.  THEY ARE SEPERATED BY A ^^.  WHEN THIS IS RUN THROUGH THE CREATEBINDEDARRAY OF THE GENERAL CLASS, THAT MEHTHOD WILL CREATE A BINDED ARRAY*/
    $elementNames = array('jobid^^int', 'jobDate^^str', 'notename^^str', 'note^^str');

    /* CREATE BINDINGS NEEDED FOR PDO QUERY.  I CREATED A METHOD IN THE GENERAL CLASS THAT DOES THIS AUTOMATICALLY BY SENDING IN THE ELEMENTNAMES ARRAY AND THE DATAOBJ.  FOR THIS TO WORK YOU JUST HAVE THE CORRECT DATAOBJ STRUCTURE*/
    $bindings = $General->createBindedArray($elementNames, $dataObj);
    /* ADD THE FOLDER TO THE BINDINGS ARRAY*/
    array_push($bindings, array(':jobid', $dataObj->jobid, 'int'));
    devLog("addJobNote() \$bindings", print_r($bindings, true));
    /* IF THE DIRECTORY WAS CREATED THEN ADD TO THE DATABASE OTHERWISE SEND ERROR MESSAGE */

    $result = $pdo->otherBinded($sql, $bindings);
    devLog("addJobNote() \$result", print_r($result, true));

    if ($result = 'noerror') {
        $response = (object) [
            'masterstatus' => 'success',
            'msg' => 'The note has been added',
        ];
        echo json_encode($response);

    } else {
        $response = (object) [
            'masterstatus' => 'error',
            'msg' => 'There was a problem adding the note',
        ];
        echo json_encode($response);
    }

}

function addHours($dataObj)
{
    require_once '../classes/Validation.php';
    require_once '../classes/Pdo_methods.php';
    require_once '../classes/General.php';
    require_once '../utils/devLog.php';

    devLog("job.php, addJobNote()", print_r($dataObj, true));
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
        return;
    }
    $General = new General();
    $pdo = new PdoMethods();

    $sql = "INSERT INTO job_hour (job_id, job_date, job_hours, hourly_rate, description) VALUES (:jobid, :jobDate, :hours, :hourlyRate, :description);";

    /* HERE I CREATE AN ARRAY THAT LISTS THE ELEMENT NAME, WHICH IS THE ID AND THE DATATYPE NEEDED BY PDO.  THEY ARE SEPERATED BY A ^^.  WHEN THIS IS RUN THROUGH THE CREATEBINDEDARRAY OF THE GENERAL CLASS, THAT MEHTHOD WILL CREATE A BINDED ARRAY*/
    $elementNames = array('jobid^^int', 'jobDate^^str', 'hours^^str', 'hourlyRate^^int', 'description^^str');

    /* CREATE BINDINGS NEEDED FOR PDO QUERY.  I CREATED A METHOD IN THE GENERAL CLASS THAT DOES THIS AUTOMATICALLY BY SENDING IN THE ELEMENTNAMES ARRAY AND THE DATAOBJ.  FOR THIS TO WORK YOU JUST HAVE THE CORRECT DATAOBJ STRUCTURE*/
    $bindings = $General->createBindedArray($elementNames, $dataObj);
    /* ADD THE FOLDER TO THE BINDINGS ARRAY*/
    array_push($bindings, array(':jobid', $dataObj->jobId, 'int'));
    // devLog("addJobNote() \$bindings", print_r($bindings, true));
    /* IF THE DIRECTORY WAS CREATED THEN ADD TO THE DATABASE OTHERWISE SEND ERROR MESSAGE */

    $result = $pdo->otherBinded($sql, $bindings);
    // devLog("addJobNote() \$result", print_r($result, true));

    if ($result = 'noerror') {
        $response = (object) [
            'masterstatus' => 'success',
            'msg' => 'The hours have been added',
        ];
        echo json_encode($response);

    } else {
        $response = (object) [
            'masterstatus' => 'error',
            'msg' => 'There was a problem adding the hours',
        ];
        echo json_encode($response);
    }

}

function getJobHours($dataObj)
{
    $output = "<table class='table table-bordered table-striped'><thead><tr><th>Date</th><th>Hours</th><th>Rate</th><th>Description</th><th></th><th></th></tr></thead><tbody>";
    require_once '../classes/Pdo_methods.php';
    $pdo = new PdoMethods();
    $sql = "SELECT id, job_id, job_date, job_hours, hourly_rate, description  FROM job_hour WHERE job_id=:jobId";
    $bindings = array(
        array(":jobId", $dataObj->jobId, "int"),
    );
    $records = $pdo->selectBinded($sql, $bindings);
    devLog("getJobHours() records", $records);

    if ($records == 'error') {
        $object = (object) [
            'masterstatus' => 'error',
            'msg' => 'Table could not be loaded',
        ];
        echo json_encode($object);
    } elseif (count($records) == 0) {
        $object = (object) [
            'masterstatus' => 'success',
            'table' => '<p>There are no hours for this job</p>',
        ];
        echo json_encode($object);
    } else {
        foreach ($records as $record) {
            $date = date("m-d-Y", $timestamp = $record["job_date"] / 1000);
            $output .= "<tr><td>" . $date . "</td><td>" . $record["job_hours"] . "</td><td>" . $record["hourly_rate"] . "</td><td>" . $record["description"] . "</td><td style='width: 40px;'><button value='Update' type='button' id='" . $record["id"] . "' class='btn btn-primary'>Update</button></td><td style='width: 40px;'><button value='Delete' type='button' id='" . $record["id"] . "' class='btn btn-danger'>Delete</button></td></tr>";
        }
        $output .= "</tbody></table>";
        $object = (object) [
            'masterstatus' => 'success',
            'table' => $output,
        ];
        echo json_encode($object);
    }
}

function updateHours($dataObj)
{
    require_once '../classes/Validation.php';
    require_once '../classes/Pdo_methods.php';
    require_once '../classes/General.php';
    require_once '../utils/devLog.php';

    devLog("job.php, updateHours()", $dataObj);

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
    } else {
        $pdo = new PdoMethods();
        $General = new General();

        $sql = "UPDATE job_hour SET job_date=:jobDate, job_hours=:hours, hourly_rate=:hourlyRate, description=:description WHERE id=:hourId;";
        $elementNames = array("jobDate^^str", "hours^^str", "hourlyRate^^int", "description^^str");
        $bindings = $General->createBindedArray($elementNames, $dataObj);

        array_push($bindings, (array(":hourId", $dataObj->hourId, "int")));
        devLog("job.php, updateHours() bindings", print_r($bindings, true));

        $result = $pdo->otherBinded($sql, $bindings);

        if ($result = 'noerror') {
            $response = (object) [
                'masterstatus' => 'success',
                'msg' => 'The hours have been updated',
            ];
            echo json_encode($response);

        } else {
            $response = (object) [
                'masterstatus' => 'error',
                'msg' => 'There was a problem updating the hours',
            ];
            echo json_encode($response);
        }
    }
}

function deleteHours($dataObj)
{
    require '../classes/Pdo_methods.php';
    $pdo = new PdoMethods();
    $sql = "DELETE FROM job_hour WHERE id=:id";
    // Bindings have to be an array of arrays
    $bindings = [[":id", $dataObj->hourId, "int"]];
    $result = $pdo->otherBinded($sql, $bindings);
    if ($result == "error") {
        $response = (object) [
            'masterstatus' => 'error',
            'msg' => "Failed to delete hours",
        ];
        $data = json_encode($response);
        echo $data;
    } else {
        $response = (object) [
            'masterstatus' => 'success',
            'msg' => "Successfully deleted hours",
        ];
        $data = json_encode($response);
        echo $data;
    }
}

function getjobcontacts($dataObj)
{
    $output = "<table class='table table-bordered table-striped'><thead><tr><th>Name</th><th>Work Phone</th><th>Mobile Phone</th><th>Email</th></tr></thead><tbody>";
    require_once '../classes/Pdo_methods.php';
    $pdo = new PdoMethods();
    $sql = "SELECT contact.name AS name, contact.work_phone AS work_phone, contact.mobile_phone AS mobile_phone, contact.email AS email, job_contact.job_id, job_contact.contact_id  FROM contact, job_contact WHERE job_contact.job_id=:jobId AND contact.id=job_contact.contact_id;";
    $bindings = array(
        array(":jobId", $dataObj->jobId, "int"),
    );
    $records = $pdo->selectBinded($sql, $bindings);
    if ($records == 'error') {
        $object = (object) [
            'masterstatus' => 'error',
            'msg' => 'Table could not be loaded',
        ];
        echo json_encode($object);
    } elseif (count($records) == 0) {
        $object = (object) [
            'masterstatus' => 'success',
            'table' => '<p>There are no contacts associated with this job</p>',
        ];
        echo json_encode($object);
    } else {
        devLog("getjobcontact()", print_r($records, true));
        foreach ($records as $record) {
            $output .= "<tr><td>" . $record["name"] . "</td><td>" . $record["work_phone"] . "</td><td>" . $record["mobile_phone"] . "</td><td>" . $record["email"] . "</td></tr>";
        }
        $output .= "</tbody></table>";
        $object = (object) [
            'masterstatus' => 'success',
            'table' => $output,
        ];
        echo json_encode($object);
    }
}

function getHoursUpdateForm($dataObj)
{
    require_once '../classes/Pdo_methods.php';
    $pdo = new PdoMethods();
    $sql = "SELECT id, job_id, job_date, job_hours, hourly_rate, description  FROM job_hour WHERE id=:hourId";
    $bindings = array(
        array(":hourId", $dataObj->hourId, "int"),
    );
    $record = $pdo->selectBinded($sql, $bindings)[0];
    if ($record == 'error') {
        $object = (object) [
            'masterstatus' => 'error',
            'msg' => 'Hours could not be loaded',
        ];
        echo json_encode($object);
    } else {
        devLog("getHoursUpdateForm() \$records", $record);
        $date = date("Y-m-d", $timestamp = $record["job_date"] / 1000);

        $output = '<div id="updateHoursForm" class="form">
        <div class="row">
         <div class="col-md-4">
           <div class="form-group">
             <label for="jobDate">Date:</label>
             <input type="date" class="form-control" id="jobDate" name="date" value="' . $date . '">
           </div>
         </div>
       </div>
       <div class="row">
         <div class="col-md-3">
           <div class="form-group">
             <label for="hours">Hours:</label>
             <input type="text" class="form-control" name="hours" id="hours" value="' . $record["job_hours"] . '">
           </div>
         </div>
         <div class="col-md-3">
           <div class="form-group">
             <label for="hourlyRate">Hourly Rate:</label>
             <input type="text" class="form-control" name="hourlyRate" id="hourlyRate" value="' . $record["hourly_rate"] . '">
           </div>
         </div>
       </div>
       <div class="row">
         <div class="col-md-6">
           <div class="form-group">
             <label for="description">Description:</label><br />
             <textarea rows="10" cols="10" id="description" class="form-control">' . $record["description"] . '</textarea>
           </div>
         </div>
       </div>
       <div class="row">
         <div class="col-md-6">
           <input type="button" class="btn btn-success" value="Update Hours" id="updatejobhoursBtn">
         </div>
       </div>
     </div>';
        $object = (object) [
            'masterstatus' => 'success',
            'form' => $output,
        ];
        echo json_encode($object);
    }
}