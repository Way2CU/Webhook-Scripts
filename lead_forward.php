<?php

/**
 * Lead Forward Script
 *
 * Copyright (c) 2014. by Way2CU, http://way2cu.com
 * Author: Mladen Mijatov
 *
 * This script will forward data received through CTM webhook
 * call to a specified API endpoint.
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
	$auth = base64_encode($config['username'].':'.$config['password']);
	return "Authorization: Basic {$auth}\n";
}

/**
 * Parse input JSON formated object sent by CTM webhook call.
 *
 * @return array
 */
function getData() {
	return json_decode(file_get_contents('php://input'));
}

/**
 * Send data to specified API endpoint.
 *
 * @param array $config
 * @param array $data
 * @return boolean
 */
function sendData($config, $data) {
	$result = false;
	$header = array();

	$content = http_build_query($data);
	$content_length = strlen($content);

	// compile default headers
	$header[] = "POST {$config['endpoint']} HTTP/1.1";
	$header[] = 'Host: '.$config['hostname'];
	$header[] = 'Content-Type: application/x-www-form-urlencoded';
	$header[] = 'Content-Length: '.$content_length;
	$header[] = 'Connect-time: 0';
	$header[] = 'Connection: close';

	// add authentication if needed
	if (!is_null($config['username']) && !is_null($config['password']))
		$header[] = makeAuthorization($config);

	$header_string = implode("\r\n", $header);
	
	// open socket
	$port = $config['port'];
	if (is_null($port))
		$port = !is_null($config['encryption']) ? 443 : 80;

	$prefix = '';
	if (!is_null($config['encryption']))
		$prefix = $config['encryption'].'://';

	$response = null;
	$socket = fsockopen($prefix.$config['hostname'], $port, $error_number, $error_string, 5);

	if ($socket && $error_number == 0) {
		// send and receive data
		fwrite($socket, $header_string."\r\n\r\n".$content);
		$raw_data = stream_get_contents($socket, 1024);

		// parse response
		$response = json_decode($raw_data);

		// close socket
		fclose($socket);

		// assume everything went according to plan
		$result = true;
	} else {
		trigger_error('There was a problem opening "'.$prefix.$config['hostname'].'".', E_USER_ERROR);
	}

	return $result;
}

/**
 * Prepare data to sent. Returns array to be sent to specified
 * API endpoint.
 *
 * @param array $config
 * @param array $data
 * @return array
 */
function prepareData($config, $data) {
	$result = array(
		'globalpass'	=> 'yav3329',
		'projectid'		=> 157,
		'publisher'		=> 1380,
		'fullname'		=> '',
		'phone'			=> $data['caller_number'],
		'email'			=> '',
		'note'			=> $data['audio'],
		'banner'		=> 'callbox'
	);

	return $result;
}

// remote interface configuration
$config = array(
		'hostname'		=> 'nadlan2u.co.il',
		'endpoint'		=> '/leadsbanner.asmx/getleadsbanner',
		'port'			=> null,
		'encryption'	=> null,
		'username'		=> null,
		'password'		=> null
	);

// prepare for sending
$call_data = getData();
$data = prepareData($config, $call_data);

// send data
$result = sendData($config, $data);

if ($result)
	header("HTTP/1.1 200 OK"); else
	header("HTTP/1.1 400 error");

?>
