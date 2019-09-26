<?php
/*THE PURPOSE OF THIS FILE IS TO PROVIDE SOME GENERAL FUNCTIONS THAT WILL BE USED BY MULTIPLE PAGES */

/* THIS FUNCTION WILL GET THE ACCOUNT LIST FOR ALL ACCOUNTS */
function accountList(){
	require_once '../classes/Pdo_methods.php';
	$pdo = new PdoMethods();
	$sql = "SELECT id, name FROM account";
	$records = $pdo->selectNotBinded($sql);
	if($records == 'error'){
    	echo 'There was an error getting the accounts list';
    }
    else {
    	if(count($records) != 0){
        	$accounts = '<select id="acctlist" class="form-control">
            <option value="0">Select an account</option>';
        	foreach ($records as $row) {
        		$accounts .= "<option value=".$row['id'].">".$row['name']."</option>";
        	}
        	$accounts .= '</select>';

            $response = (object) [
                'masterstatus' => 'success',
                'accounts' => $accounts
             ];
            $data = json_encode($response);
            echo $data;
	    }
	    else {
	    	$response = (object) [
                'masterstatus' => 'error',
                'msg' => 'No accounts not found',
             ];
            $data = json_encode($response);
            echo $data;
	    }
    }
}

/* THIS FUNCTION WILL GET JOB LIST FOR ALL JOBS THAT ARE FROM AN ACCOUNT */
function jobList($dataObj){
    require_once '../classes/Pdo_methods.php';
    $pdo = new PdoMethods();
    $sql = "SELECT id, name FROM job WHERE account_id = :accountId";
    $bindings = array(
        array(':accountId', $dataObj->accountId, 'int')
    );

    $records = $pdo->selectBinded($sql, $bindings);
    
    if($records == 'error'){
        echo 'There has been and error getting the jobs list';
    }
    else {
        if(count($records) != 0){
            $jobs = "";
            $jobs = '<select id="joblist" class="form-control">
            <option value="0">Select an job</option>';
            foreach ($records as $row) {
                $jobs .= "<option value=".$row['id'].">".$row['name']."</option>";
            }
            $jobs .= '</select>';
            $response = (object) [
                'masterstatus' => 'success',
                'jobs' => $jobs
             ];
            $data = json_encode($response);
            echo $data;
        }
        else {
            $response = (object) [
                'masterstatus' => 'error',
                'msg' => 'No jobs found'
             ];
            $data = json_encode($response);
            echo $data;
        }
    }
}  