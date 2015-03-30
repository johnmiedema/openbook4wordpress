<?php

//module contains logic specific to Open Library

//if json_decode is missing (< PHP5.2) use local json library
//included in main openbook.php but also required here
if(!function_exists('json_decode')) {
	include_once('openbook_json.php');
	function json_decode($data) {
		$json = new Services_JSON_ob();
		return( $json->decode($data) );
	}
}

//get data for one book in Open Library
class openbook_openlibrary_bookdata {

	public $bibkeys='';
	public $bookdata='';

	function __construct($domain, $booknumber, $timeout, $proxy, $proxyport, $showerrors) {

		//clean book number
		$booknumber = trim($booknumber);
		$booknumber = str_replace("'", "", $booknumber); //single quote - prevent problems with string concatenation

		//determine bibkeys
		//first check if using OL key - legacy or current format

		$obn_start_legacy = stripos($booknumber,"/b/OL");
		$obn_start = stripos($booknumber,"/books/OL");
		if (is_integer($obn_start_legacy)) {
			$bibkeys = "OLID:" . substr($booknumber, 3);
		}
		elseif (is_integer($obn_start)) {
			$bibkeys = "OLID:" . substr($booknumber, 7);
		}
		else {

			//if using standard identifier, use it
			//else assume isbn
			$obn_standardid = stripos($booknumber,":");
			if (is_integer($obn_standardid)) {
				$bibkeys = $booknumber;
			}
			else {
				$isbn = $booknumber;

				$bibkeys = "ISBN:" . $booknumber;
			}
		}

		$url = $domain . "/api/books?bibkeys=".$bibkeys."&jscmd=data&format=json"; //server-side Books API
		$result = openbook_utilities_getUrlContents($url, $timeout, $proxy, $proxyport, OB_OPENLIBRARYDATAUNAVAILABLE_BOOK_LANG, $showerrors);

		$this->bibkeys = $bibkeys;
		$this->bookdata = $result;
	}
}

function openbook_openlibrary_getBookData($domain, $booknumber, $timeout, $proxy, $proxyport, &$bookkey, $showerrors) {

}

function openbook_openlibrary_extractValue($result, $elementname) {
	$value = $result ->{$elementname};
	$value = htmlspecialchars($value, ENT_QUOTES);
	return $value;
}

//no formatting
function openbook_openlibrary_extractValueExact($result, $elementname) {
	$value = $result ->{$elementname};
	return $value;
}

function openbook_openlibrary_extractList($result_array, $elementname) {

	if (count($result_array)==0) return "";

	$result_values = array();

	foreach($result_array as $result) {
		$result_value = openbook_openlibrary_extractValue($result, $elementname);
		$result_values[] = $result_value;
	}

	$result_list = join(', ', $result_values);

	return $result_list;
}

function openbook_openlibrary_extractFirstFromList($result_array, $elementname) {
	if (count($result_array)==0) return "";
	$result = $result_array[0];
	$result_value = openbook_openlibrary_extractValue($result, $elementname);
	return $result_value;
}

function openbook_openlibrary_extractFirstFromArray($result_array, $elementname) {
	if (count($result_array)==0) return "";
	$result =  $result_array ->{$elementname};
	$value = $result[0];
	$value = htmlspecialchars($value);
	return $value;
}

?>
