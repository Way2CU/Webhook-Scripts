<?php

/**
 * Google Analytics Webhook for Callbox
 *
 * Copyright (c) 2014. by Way2CU, http://way2cu.com
 * Author: Mladen Mijatov
 *
 * This script will post single or mutiple events to Google Analytics
 * through CTM API call.
 *
 * This script is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this script. If not, see http://www.gnu.org/licenses/.
 */

/**
 * Create authorization header string.
 *
 * @param array $config
 * @return string
 */
function makeAuthorization($config) {
	$auth = base64_encode($config['access_code'].':'.$config['secret_code']);
	return "Authorization: Basic {$auth}\n";
}

/**
 * Send Google Analytics event through CTM API.
 *
 * @param array $config
 * @param array $event
 * @param integer $call_id
 * @return boolean
 */
function sendEvent($config, $event, $call_id) {
	$result = false;
	$content = http_build_query($event);
	$content_length = strlen($content);

	// prepare header
	$header = "POST /api/v1/ga/{$call_id}.json HTTP/1.1\n";
	$header .= "Content-Type: application/x-www-form-urlencoded\n";
	$header .= "Content-Length: {$content_length}\n";
	$header .= makeAuthorization($config);
	$header .= "Connection: close\n\n";

	// open socket
	$response = null;
	$socket = fsockopen('ssl://'.$config['endpoint_url'], 443, $error_number, $error_string, 5);

	if ($socket && $error_number == 0) {
		// send and receive data
		fputs($socket, $header.$content);
		$raw_data = stream_get_contents($socket, 1024);

		// parse response
		$response = json_decode($raw_data);

		// close socket
		fclose($socket);

		// assume everything went according to plan
		// TODO: properly parse response
		$result = true;
	}

	return $result;
}

// get call data
$call_data = json_decode(file_get_contents('php://input'));

// data to send
$call_id = $call_data['id'];
$event_data = array(
	array(
		'ga_category'	=> 'Calls',
		'ga_action'		=> $call_data['source'],
		'ga_label'		=> '',
		'ga_value'		=> $call_data['duration'],
		'uacode'		=> ''
	)
);

// access configuration
$config = array(
		'validation_code'	=> '',
		'endpoint_url'		=> 'api.calltrackingmetrics.com',
		'access_code' 		=> '',
		'secret_code'		=> ''
	);

// post all the events
$result = false;
if ($_REQUEST['code'] == $config['validation_code']) {
	$result = true;  // assume all events can be sent

	if (isset($call_data['cvars']) && isset($call_data['cvars']['analytics_id_list'])) {
		// send first event to all the analytics
		$event = $event_data[0];

		foreach($call_data['cvars']['analytics_id_list'] as $analytic_id) {
			$event['uacode'] = $analytic_id;
			$result &= sendEvent($config, $event, $call_id);
		}

	} else {
		// sent all events to specified analytics ids
		foreach ($event_data as $event)
			$result &= sendEvent($config, $event, $call_id);
	}
}

// set response code appropriately
if ($result)
	http_response_code(200); else
	http_response_code(400);

?>
