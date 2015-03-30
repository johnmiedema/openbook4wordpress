<?php

function openbook_getDisplayMessage($message) {
	return "<i>[" . $message . "]</i> ";
}

//set default options on first activation and on reset
function openbook_utilities_setDefaultOptions() {

	$deprecated='';
    	$autoload='no';

	//test if options exist, if not create them
	$template = get_option(OB_OPTION_TEMPLATE1_NAME); //a required field

	if ($template == FALSE) {
		add_option(OB_OPTION_TEMPLATE1_NAME, OB_OPTION_TEMPLATE1_VAL, $deprecated, $autoload);
		add_option(OB_OPTION_TEMPLATE2_NAME, OB_OPTION_TEMPLATE2_VAL, $deprecated, $autoload);
		add_option(OB_OPTION_TEMPLATE3_NAME, OB_OPTION_TEMPLATE3_VAL, $deprecated, $autoload);
		add_option(OB_OPTION_TEMPLATE4_NAME, OB_OPTION_TEMPLATE4_VAL, $deprecated, $autoload);
		add_option(OB_OPTION_TEMPLATE5_NAME, OB_OPTION_TEMPLATE5_VAL, $deprecated, $autoload);
		add_option(OB_OPTION_FINDINLIBRARY_OPENURLRESOLVER_NAME, OB_OPTION_FINDINLIBRARY_OPENURLRESOLVER_VAL, $deprecated, $autoload);
		add_option(OB_OPTION_FINDINLIBRARY_PHRASE_NAME, OB_OPTION_FINDINLIBRARY_PHRASE_VAL, $deprecated, $autoload);
		add_option(OB_OPTION_FINDINLIBRARY_IMAGESRC_NAME, OB_OPTION_FINDINLIBRARY_IMAGESRC_VAL, $deprecated, $autoload);
		add_option(OB_OPTION_LIBRARY_DOMAIN_NAME, OB_OPTION_LIBRARY_DOMAIN_VAL, $deprecated, $autoload);
		add_option(OB_OPTION_PROXY_NAME, OB_OPTION_PROXY_VAL, $deprecated, $autoload);
		add_option(OB_OPTION_PROXYPORT_NAME, OB_OPTION_PROXYPORT_VAL, $deprecated, $autoload);
		add_option(OB_OPTION_TIMEOUT_NAME, OB_OPTION_TIMEOUT_VAL, $deprecated, $autoload);
		add_option(OB_OPTION_SHOWERRORS_NAME, OB_OPTION_SHOWERRORS_VALUE, $deprecated, $autoload);
		add_option(OB_OPTION_SAVETEMPLATES_NAME, OB_OPTION_SAVETEMPLATES_VALUE, $deprecated, $autoload);
	}
}

function openbook_utilities_deleteOptions() {

	delete_option(OB_OPTION_TEMPLATE1_NAME);
	delete_option(OB_OPTION_TEMPLATE2_NAME);
	delete_option(OB_OPTION_TEMPLATE3_NAME);
	delete_option(OB_OPTION_TEMPLATE4_NAME);
	delete_option(OB_OPTION_TEMPLATE5_NAME);
	delete_option(OB_OPTION_FINDINLIBRARY_OPENURLRESOLVER_NAME);
	delete_option(OB_OPTION_FINDINLIBRARY_PHRASE_NAME);
	delete_option(OB_OPTION_FINDINLIBRARY_IMAGESRC_NAME);
	delete_option(OB_OPTION_LIBRARY_DOMAIN_NAME);
	delete_option(OB_OPTION_PROXY_NAME);
	delete_option(OB_OPTION_PROXYPORT_NAME);
	delete_option(OB_OPTION_TIMEOUT_NAME);
	delete_option(OB_OPTION_SHOWERRORS_NAME);
	delete_option(OB_OPTION_SAVETEMPLATES_NAME);
}

function openbook_utilities_getUrlContents($url, $timeout, $proxy, $proxyport, $errmessage, $showerrors) {

	//establish a cURL handle.
	$ch = curl_init($url);

	//set options
	curl_setopt($ch, CURLOPT_HEADER, false); //false=do not include headers
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //true=return as string
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); //timeout for when OL is down

	//set user defined constants
	//timeout for when OL is down or slow
	if ($timeout) {
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); // timeout on connect
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); // timeout on response
	}

	if ($proxy) curl_setopt($ch, CURLOPT_PROXY, $proxy); //proxy server name
	if ($proxyport) curl_setopt($ch, CURLOPT_PROXYPORT, $proxyport); //proxy port

	// Execute the request
	$output = curl_exec($ch);
	if (stripos($output, 'Server')>0 && stripos($output, 'Error')>0) { throw new Exception (OB_OLSERVERERROR_LANG); }

	//handle errors
	$err = curl_errno($ch);
	if($err!=0) {
		if ($err == '28') {
			curl_close($ch);
			throw new Exception(OB_CURLTIMEOUT_LANG);
		}
		else {
			if ($showerrors == OB_HTML_CHECKED_TRUE) {
				$errmsg = curl_error($ch); //see more at http://us.php.net/manual/en/function.curl-getinfo.php
				//$header = curl_getinfo($ch);
				curl_close($ch); //close after obtaining error info
				throw new Exception('cURL error ' . $err . ' - ' . $errmsg . ' - ' . $url);
			}
			throw new Exception(OB_CURLERROR_LANG);
		}
	}
	elseif($output == "" || $output == FALSE) {
		curl_close($ch);
		throw new Exception($errmessage);
	}

	// Close the cURL session.
	curl_close($ch);

	return $output;
}

//test if 10 or 13 digits ISBN
function openbook_utilities_validISBN($testisbn) {
	return (ereg ("([0-9]{10})", $testisbn, $regs) || ereg ("([0-9]{13})", $testisbn, $regs));
}

function openbook_utilities_getDomain()
{
	return strip_tags( $_SERVER[ "SERVER_NAME" ] );
}

?>
