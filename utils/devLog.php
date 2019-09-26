<?php
function devLog($description, $output)
{
    $logPath = "/var/www/html/job-tracker/logs/dev.log";
    $logFile = fopen($logPath, "a") or die("Unable to open file!");
    $separator = "\n--------------------\n";
    $timeStamp = date('Y-m-d H:i:s');
    $finalOutput = $separator . $timeStamp . "\n" . strtoupper($description) . "\n" . print_r($output, true) . $separator;
    fwrite($logFile, $finalOutput);
    fclose($logFile);
}