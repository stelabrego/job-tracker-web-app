<?php
/* ALL GET PAGE FUNCTIONS HERE */
function homePage()
{
    $pageData['base'] = "../";
    $pageData['title'] = "Home Page";
    $pageData['heading'] = "Job Tracker Home Page";
    $pageData['nav'] = true;
    $pageData['content'] = file_get_contents('views/admin/home.html');
    $pageData['js'] = "Util^general^login";
    $pageData['security'] = true;
    return $pageData;
}

function weekTime($weeksAgo)
{
    // returns the timestamp of the time exactly x weeks ago
    return (time() - (7 * 24 * 60 * 60 * ($weeksAgo)));
}

function getHours()
{
    require_once '../classes/Pdo_methods.php';
    require_once '../utils/devLog.php';
    $chartSections = array();
    // Create 6 objects, each representing a bar in the bar chart, and add them to an array
    for ($i = 0; $i < 6; $i++) {
        // Create a blank object
        $obj = new stdClass();
        // Create the label of the bar (had to add a day to the beginning to make the chart look more logical)
        $obj->label = [date("M j", (weekTime($i + 1) + (24 * 60 * 60))) . " -", date("M j", weekTime($i))];
        // create timestamps of
        $obj->weekEnd = weekTime($i);
        $obj->weekStart = weekTime($i + 1);
        // the hours from the sql query will go here
        $obj->hours = 0;
        array_push($chartSections, $obj);

    }
    devLog("graphSections", $chartSections);
    $pdo = new PdoMethods();
    foreach ($chartSections as $section) {
        // get the javascript equivalent of the timestamps
        $start = $section->weekStart * 1000;
        $end = $section->weekEnd * 1000;
        $sql = "SELECT job_hours FROM job_hour WHERE job_date > $start AND job_date < $end;";
        $results = $pdo->selectNotBinded($sql);
        devLog("results", $results);
        // add up the hours
        foreach ($results as $result) {
            $section->hours = $section->hours + $result["job_hours"];
        }
    }
    $response = (object) [
        'chartSections' => $chartSections,
    ];

    echo json_encode($response);
}