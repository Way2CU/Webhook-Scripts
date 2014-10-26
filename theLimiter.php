<?php
/**
 * Send Google Analytics event through CTM API.
 */

$account_id = '';
$access_code = '';
$secret_code = '';
$start_date = date('Y-m-01');
$end_date = date('Y-m-t');
$url = "https://api.caltrackingmetrics.com/api/v1/accounts/" . $account_id . "/reports.json?start_date=" . $start_date . "&end_date=" . $end_date;

/**
 * Open and close connection.
 */
function connect($url, $access_code, $secret_code)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "$access_code:$secret_code");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $out = curl_exec($ch);
    if (curl_error($ch)) {
        print "error:" . curl_error($ch) . "<br />";
    } else {
        return $out;
    }
    curl_close($ch);
}

/**
 * Getting a responce
 */
function getData($url, $access_code, $secret_code)
{
    $out = connect($url, $access_code, $secret_code);
    $file = json_decode($out);
    if ($file->authentication == 'failed') {
        http_response_code(400);
    } else {
        return $file;
    }
}

/**
 * Calculate nubmer of minutes
 */
function getNum($file)
{
    $theTime = $file->average_talk_time * $file->total_calls / 60;
    return $theTime;
}

/**
 * Pause or resume account
 */
function control($num, $account_id, $access_code, $secret_code)
{
    if ($num > 299) {
        $url = "https://api.calltrackingmetrics.com/api/v1/accounts/" . $account_id . "/pause";
        connect($url, $access_code, $secret_code);
        return 'paused';
    } else {
        $url = "https://api.calltrackingmetrics.com/api/v1/accounts/" . $account_id . "/resume";
        connect($url, $access_code, $secret_code);
        return 'unpaused';
    }
}

$theNum = getNum(getData($url, $access_code, $secret_code));
control($theNum, $account_id, $access_code, $secret_code);