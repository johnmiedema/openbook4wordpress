<?php
/*
Plugin Name: OpenBook
Plugin URI: http://wordpress.org/extend/plugins/openbook-book-data/
Description: Displays a book's cover image, title, author, links, and other book data from Open Library.
Version: 3.5.1
Author: John Miedema
Author URI: http://code.google.com/p/openbook4wordpress/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

include_once('libraries/openbook_language.php'); //include before constants
include_once('libraries/openbook_constants.php');
include_once('libraries/openbook_html.php');
include_once('libraries/openbook_openlibrary.php');
include_once('libraries/openbook_utilities.php');

if ( ! defined( 'ABSPATH' ) )
	die( "Can't load this file directly" );

class MyOpenBook
{
	function __construct() {
		register_activation_hook(__FILE__, 'ob_activation_check');
		register_deactivation_hook(__FILE__, 'ob_deactivation');
		add_action('admin_menu', 'openbook_add_pages');
		add_action('admin_head', 'my_add_mce_button_openbook');
		add_shortcode('openbook', 'openbook_insertbookdata');
		add_filter('widget_text', 'do_shortcode'); //allows shortcodes in widgets
		add_action( 'admin_enqueue_scripts', 'openbook_add_stylesheet' ); //add stylesheet for WordPress visual editor
		add_action('wp_enqueue_scripts', 'openbook_add_stylesheet'); //add stylesheet for final display
	}
}

//handles any processing when the plugin is activated
function ob_activation_check() {

	$plugin = trim( $GET['plugin'] );

	//if json_decode is missing (< PHP5.2) use local json library
	if(!function_exists('json_decode')) {
		include_once('libraries/openbook_json.php');
		function json_decode($data) {
			$json = new Services_JSON_ob();
			return( $json->decode($data) );
		}
	}

	//test if cURL is enabled
	if (!function_exists('curl_init')) {
		deactivate_plugins($plugin);
		wp_die(OB_ENABLECURL_LANG);
	}

	//initialize options
	openbook_utilities_setDefaultOptions();
}

//handles any cleanup when plugin is deactivated
function ob_deactivation() {
	$savetemplates = get_option(OB_OPTION_SAVETEMPLATES_NAME);
	if ($savetemplates!=OB_HTML_CHECKED_TRUE) {
		openbook_utilities_deleteOptions();
	}
}

// action function for admin hooks
function openbook_add_pages() {
	add_options_page('OpenBook', 'OpenBook', 8, 'openbook_options.php', 'openbook_options_page'); // add a new submenu under Options:
}

// displays the page content for the options submenu
function openbook_options_page() {
	require_once('openbook_options.php');
}

function my_add_mce_button_openbook() {

	// check user permissions
	if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
		return;
	}

	// check if WYSIWYG is enabled
	if ( 'true' == get_user_option( 'rich_editing' ) ) {
		add_filter( 'mce_external_plugins', 'my_add_tinymce_plugin_openbook' );
		add_filter( 'mce_buttons', 'my_register_mce_button_openbook' );
		add_filter('mce_css', 'filter_mce_css');
		openbook_add_stylesheet(); //add stylesheet to the thickbox dialog
	}
}

// declare script for new button
function my_add_tinymce_plugin_openbook( $plugin_array ) {
	$plugin_array['my_mce_button_openbook'] = plugin_dir_url( __FILE__ ) . 'libraries/openbook_button.js';
	return $plugin_array;
}

// register new button in the editor
function my_register_mce_button_openbook( $buttons ) {
	array_push( $buttons, 'my_mce_button_openbook' );
	return $buttons;
}

function filter_plugin_actions_links($links, $file)
{
	$settings_link = $settings_link = '<a href="options-general.php?page=openbook_options.php">' . __('Settings') . '</a>';
	array_unshift($links, $settings_link);
	return $links;
}

//main function finds and replaces [openbook] shortcodes with HTML
function openbook_insertbookdata($atts, $content = null) {

	try {
		//get arguments
		$args = new openbook_arguments($atts, $content);

		$booknumber=$args->booknumber;
		$revisionnumber=$args->revisionnumber;
		$template=$args->template;
		$publisherurl=$args->publisherurl;
		$openurlresolver=$args->openurlresolver;
		$findinlibraryphrase=$args->findinlibraryphrase;
		$findinlibraryimagesrc=$args->findinlibraryimagesrc;
		$domain=$args->domain;
		$proxy=$args->proxy;
		$proxyport=$args->proxyport;
		$timeout=$args->timeout;
		$showerrors=$args->showerrors;
		$savetemplates=$args->savetemplates;

		//get book data
		$bdata = new openbook_openlibrary_bookdata($domain, $booknumber, $timeout, $proxy, $proxyport, $showerrors);

		$bookdata = $bdata->bookdata;

		if (!$bookdata || $bookdata=='{}') return openbook_getDisplayMessage(OB_NOBOOKDATAFORBOOKNUMBER_LANG);
		$bibkeys = $bdata->bibkeys;

		//extract book data values
		$obj = json_decode($bookdata);

		//-------------------------------------------------------------------------
		//extract individual book data elements from result for use in templates

		$bookdataresult = $obj->{$bibkeys};

		$OL_URL = openbook_openlibrary_extractValue($bookdataresult, 'url');

		$cover = $bookdataresult->{'cover'};
		$OL_COVER_SMALL = openbook_openlibrary_extractValueExact($cover, 'small');
		$OL_COVER_MEDIUM = openbook_openlibrary_extractValueExact($cover, 'medium');
		$OL_COVER_LARGE = openbook_openlibrary_extractValueExact($cover, 'large');

		$OL_TITLE = openbook_openlibrary_extractValue($bookdataresult, 'title');
		$OL_SUBTITLE = openbook_openlibrary_extractValue($bookdataresult, 'subtitle');

		$authors = $bookdataresult ->{'authors'};
		$OL_AUTHORLIST = openbook_openlibrary_extractList($authors, 'name');
		$OL_AUTHORURLLIST = openbook_openlibrary_extractList($authors, 'url');
		$OL_AUTHORFIRST = openbook_openlibrary_extractFirstFromList($authors, 'name');
		$OL_AUTHORURLFIRST = openbook_openlibrary_extractFirstFromList($authors, 'url');

		$OL_BYSTATEMENT = openbook_openlibrary_extractValueExact($bookdataresult, 'by_statement');

//$contributions - Missing at present, expecting soon from Open Library
//		$contributions = $bookdataresult ->{'contributions'};
//		$OL_CONTRIBUTIONLIST = openbook_openlibrary_extractList($contributions, '???');

		$publishers = $bookdataresult ->{'publishers'};
		$OL_PUBLISHERLIST = openbook_openlibrary_extractList($publishers, 'name');
		$OL_PUBLISHERFIRST = openbook_openlibrary_extractFirstFromList($publishers, 'name');

		$publishplaces = $bookdataresult ->{'publish_places'};
		$OL_PUBLISHPLACELIST = openbook_openlibrary_extractList($publishplaces, 'name');
		$OL_PUBLISHPLACEFIRST = openbook_openlibrary_extractFirstFromList($publishplaces, 'name');

		$OL_PUBLISHDATE = openbook_openlibrary_extractValue($bookdataresult, 'publish_date');
		$OL_PAGINATION = openbook_openlibrary_extractValue($bookdataresult, 'pagination');

//OL_SIZE MISSING - Missing at present, expecting soon from Open Library
//		$OL_SIZE = openbook_openlibrary_extractValue($bookdataresult, 'physical_dimensions');

		$OL_PAGES = openbook_openlibrary_extractValue($bookdataresult, 'number_of_pages');

//OL_FORMAT MISSING - Missing at present, expecting soon from Open Library
//		$OL_FORMAT = openbook_openlibrary_extractValue($bookdataresult, 'physical_format');

		$OL_WEIGHT = openbook_openlibrary_extractValue($bookdataresult, 'weight');

		$identifiers = $bookdataresult ->{'identifiers'};
		$OL_ID_AMAZON = openbook_openlibrary_extractFirstFromArray($identifiers, 'amazon');
		$OL_ID_GOODREADS = openbook_openlibrary_extractFirstFromArray($identifiers, 'goodreads');
		$OL_ID_GOOGLE = openbook_openlibrary_extractFirstFromArray($identifiers, 'google');
		$OL_ID_ISBN10 = openbook_openlibrary_extractFirstFromArray($identifiers, 'isbn_10');
		$OL_ID_ISBN13 = openbook_openlibrary_extractFirstFromArray($identifiers, 'isbn_13');
		$OL_ID_LCCN = openbook_openlibrary_extractFirstFromArray($identifiers, 'lccn');
		$OL_ID_LIBRARYTHING = openbook_openlibrary_extractFirstFromArray($identifiers, 'librarything');
		$OL_ID_OCLCWORLDCAT = openbook_openlibrary_extractFirstFromArray($identifiers, 'oclc');
		$OL_ID_PROJECTGUTENBERG = openbook_openlibrary_extractFirstFromArray($identifiers, 'project_gutenberg');
		$OL_ID_OPENLIBRARY = openbook_openlibrary_extractFirstFromArray($identifiers, 'openlibrary');

		$isbn = "";
		if (openbook_utilities_validISBN($OL_ID_ISBN13)) $isbn=$OL_ID_ISBN13;
		elseif (openbook_utilities_validISBN($OL_ID_ISBN10)) $isbn=$OL_ID_ISBN10;
		elseif (openbook_utilities_validISBN($booknumber)) $isbn = $booknumber;
		$OL_ISBN = $isbn;

		$subjects = $bookdataresult ->{'subjects'};
		$OL_SUBJECTLIST = openbook_openlibrary_extractList($subjects, 'name');

//OL_DESCRIPTION - Missing at present, expecting soon from Open Library
//		$OL_DESCRIPTION = openbook_openlibrary_extractValueFromPair($bookdataresult, 'description');

		$ebooks = $bookdataresult ->{'ebooks'};
		$OL_PREVIEW_URL = openbook_openlibrary_extractFirstFromList($ebooks, 'preview_url');

		$links = $bookdataresult ->{'links'};
		$OL_LINKTITLES = openbook_openlibrary_extractList($links, 'title');
		$OL_LINKURLS = openbook_openlibrary_extractList($links, 'url');
		$OL_LINKTITLEFIRST = openbook_openlibrary_extractFirstFromList($links, 'title');
		$OL_LINKURLFIRST = openbook_openlibrary_extractFirstFromList($links, 'url');

		$excerpts = $bookdataresult ->{'excerpts'};
		$OL_EXCERPT_COMMENT_FIRST = openbook_openlibrary_extractFirstFromList($excerpts, 'comment');
		$OL_EXCERPT_TEXT_FIRST = openbook_openlibrary_extractFirstFromList($excerpts, 'text');

		//-------------------------------------------------------------------------
		//prepare formatted OB data elements, prefixed with $OB_
		//corresponds to list in help_dataelements.txt, each element can be used in the WordPress options panel

		$OB_COVER_SMALL = openbook_html_getCoverImage($OL_COVER_SMALL, $OL_TITLE, $OL_URL, $revisionnumber);
		$OB_COVER_MEDIUM = openbook_html_getCoverImage($OL_COVER_MEDIUM, $OL_TITLE, $OL_URL, $revisionnumber);
		$OB_COVER_LARGE = openbook_html_getCoverImage($OL_COVER_LARGE, $OL_TITLE, $OL_URL, $revisionnumber);

		$OB_TITLE = openbook_html_getTitle($OL_URL, $revisionnumber, $OL_TITLE, $OL_SUBTITLE);
		$OB_AUTHORS = openbook_html_getAuthors($authors, $OL_BYSTATEMENT, $OL_CONTRIBUTIONLIST);
		$OB_PUBLISHER = openbook_html_getPublisher($OL_PUBLISHERFIRST, $publisherurl);
		$OB_PUBLISHYEAR = openbook_html_getPublishYear($OL_PUBLISHDATE);

		$OB_READONLINE = openbook_html_getReadOnline($OL_PREVIEW_URL);

		$openurl = openbook_html_getOpenUrl($openurlresolver, $OL_TITLE, $OL_ISBN, $OL_AUTHORLIST, $OL_PUBLISHPLACEFIRST, $OL_PUBLISHERFIRST, $OL_PUBLISHDATE, $OL_PAGES);
		$OB_LINK_FINDINLIBRARY = openbook_html_getFindInLibrary($openurlresolver, $openurl, $findinlibraryphrase, $OL_ISBN, $OL_TITLE, $OL_AUTHORFIRST);
		$OB_IMAGE_FINDINLIBRARY = openbook_html_getFindInLibraryImage($openurlresolver, $openurl, $findinlibraryimagesrc, $findinlibraryphrase, $OL_ISBN, $OL_TITLE, $OL_AUTHORFIRST);
		$OB_COINS = openbook_html_getCoins($OL_TITLE, $OL_ISBN, $OL_AUTHORLIST, $OL_PUBLISHPLACEFIRST, $OL_PUBLISHERFIRST, $OL_PUBLISHDATE, $OL_PAGES);

		$OB_LINKS = openbook_html_getLinks($links);

		$OB_LINK_AMAZON = openbook_html_getLinkAmazon($OL_ID_AMAZON);
		$OB_LINK_GOODREADS = openbook_html_getLinkGoodreads($OL_ID_GOODREADS);
		$OB_LINK_GOOGLEBOOKS = openbook_html_getLinkGoogleBooks($OL_ID_GOOGLE, $OL_ISBN, $OL_TITLE, $OL_AUTHORFIRST);
		$OB_LINK_LIBRARYCONGRESS = openbook_html_getLinkLibraryCongress($OL_ID_LCCN);
		$OB_LINK_LIBRARYTHING = openbook_html_getLinkLibraryThing($OL_ID_LIBRARYTHING, $OL_ISBN, $OL_TITLE, $OL_AUTHORFIRST);
		$OB_LINK_WORLDCAT = openbook_html_getLinkWorldCat($OL_ID_OCLCWORLDCAT, $OL_ISBN, $OL_TITLE, $OL_AUTHORFIRST);
		$OB_LINK_PROJECTGUTENBERG = openbook_html_getLinkProjectGutenberg($OL_ID_PROJECTGUTENBERG);
		$OB_LINK_BOOKFINDER = openbook_html_getLinkBookFinder($OL_ISBN, $OL_TITLE, $OL_AUTHORFIRST);

		//-------------------------------------------------------------------------
		//substitue OL elements in template

		$display = $template;

		$display = str_ireplace('[OL_URL]', $OL_URL, $display);

		$display = str_ireplace('[OL_COVER_SMALL]', $OB_COVER_SMALL, $display);
		$display = str_ireplace('[OL_COVER_MEDIUM]', $OB_COVER_MEDIUM, $display);
		$display = str_ireplace('[OL_COVER_LARGE]', $OB_COVER_LARGE, $display);

		$display = str_ireplace('[OL_TITLE_PREFIX]', $OL_TITLE_PREFIX, $display);
		$display = str_ireplace('[OL_TITLE]', $OL_TITLE, $display);
		$display = str_ireplace('[OL_SUBTITLE]', $OL_SUBTITLE, $display);

		$display = str_ireplace('[OL_AUTHORLIST]', $OL_AUTHORLIST, $display);
		$display = str_ireplace('[OL_AUTHORURLLIST]', $OL_AUTHORURLLIST, $display);
		$display = str_ireplace('[OL_AUTHORFIRST]', $OL_AUTHORFIRST, $display);
		$display = str_ireplace('[OL_AUTHORURLFIRST]', $OL_AUTHORURLFIRST, $display);

		$display = str_ireplace('[OL_BYSTATEMENT]', $OL_BYSTATEMENT, $display);
		$display = str_ireplace('[OL_CONTRIBUTIONLIST]', $OL_CONTRIBUTIONLIST, $display);

		$display = str_ireplace('[OL_PUBLISHERLIST]', $OL_PUBLISHERLIST, $display);
		$display = str_ireplace('[OL_PUBLISHERFIRST]', $OL_PUBLISHERFIRST, $display);
		$display = str_ireplace('[OL_PUBLISHPLACELIST]', $OL_PUBLISHPLACELIST, $display);
		$display = str_ireplace('[OL_PUBLISHPLACEFIRST]', $OL_PUBLISHPLACEFIRST, $display);
		$display = str_ireplace('[OL_PUBLISHDATE]', $OL_PUBLISHDATE, $display);

		$display = str_ireplace('[OL_PAGINATION]', $OL_PAGINATION, $display);
		$display = str_ireplace('[OL_SIZE]', $OL_SIZE, $display);
		$display = str_ireplace('[OL_PAGES]', $OL_PAGES, $display);
		$display = str_ireplace('[OL_FORMAT]', $OL_FORMAT, $display);
		$display = str_ireplace('[OL_WEIGHT]', $OL_WEIGHT, $display);

		$display = str_ireplace('[OL_ID_AMAZON]', $OL_ID_AMAZON, $display);
		$display = str_ireplace('[OL_ID_GOODREADS]', $OL_ID_GOODREADS, $display);
		$display = str_ireplace('[OL_ID_GOOGLE]', $OL_ID_GOOGLE, $display);
		$display = str_ireplace('[OL_ID_ISBN10]', $OL_ID_ISBN10, $display);
		$display = str_ireplace('[OL_ID_ISBN13]', $OL_ID_ISBN13, $display);
		$display = str_ireplace('[OL_ID_LCCN]', $OL_ID_LCCN, $display);
		$display = str_ireplace('[OL_ID_LIBRARYTHING]', $OL_ID_LIBRARYTHING, $display);
		$display = str_ireplace('[OL_ID_OCLCWORLDCAT]', $OL_ID_OCLCWORLDCAT, $display);
		$display = str_ireplace('[OL_ID_PROJECTGUTENBERG]', $OL_ID_PROJECTGUTENBERG, $display);
		$display = str_ireplace('[OL_ID_OPENLIBRARY]', $OL_ID_OPENLIBRARY, $display);

		$display = str_ireplace('[OL_ISBN]', $OL_ISBN, $display);
		$display = str_ireplace('[OL_SUBJECTLIST]', $OL_SUBJECTLIST, $display);
		$display = str_ireplace('[OL_DESCRIPTION]', $OL_DESCRIPTION, $display);
		$display = str_ireplace('[OL_PREVIEW_URL]', $OL_PREVIEW_URL, $display);

		$display = str_ireplace('[OL_LINKTITLES]', $OL_LINKTITLES, $display);
		$display = str_ireplace('[OL_LINKURLS]', $OL_LINKURLS, $display);
		$display = str_ireplace('[OL_LINKTITLEFIRST]', $OL_LINKTITLEFIRST, $display);
		$display = str_ireplace('[OL_LINKURLFIRST]', $OL_LINKURLFIRST, $display);

		$display = str_ireplace('[OL_EXCERPT_COMMENT_FIRST]', $OL_EXCERPT_COMMENT_FIRST, $display);
		$display = str_ireplace('[OL_EXCERPT_TEXT_FIRST]', $OL_EXCERPT_TEXT_FIRST, $display);

		//-------------------------------------------------------------------------
		//substitue OB elements in template

		$display = str_ireplace('[OB_COVER_SMALL]', $OB_COVER_SMALL, $display);
		$display = str_ireplace('[OB_COVER_MEDIUM]', $OB_COVER_MEDIUM, $display);
		$display = str_ireplace('[OB_COVER_LARGE]', $OB_COVER_LARGE, $display);

		$display = str_ireplace('[OB_TITLE]', $OB_TITLE, $display);
		$display = str_ireplace('[OB_AUTHORS]', $OB_AUTHORS, $display);
		$display = str_ireplace('[OB_PUBLISHER]', $OB_PUBLISHER, $display);
		$display = str_ireplace('[OB_PUBLISHYEAR]', $OB_PUBLISHYEAR, $display);

		$display = str_ireplace('[OB_READONLINE]', $OB_READONLINE, $display);

		$display = str_ireplace('[OB_LINK_FINDINLIBRARY]', $OB_LINK_FINDINLIBRARY, $display);
		$display = str_ireplace('[OB_IMAGE_FINDINLIBRARY]', $OB_IMAGE_FINDINLIBRARY, $display);
		$display = str_ireplace('[OB_COINS]', $OB_COINS, $display);

		$display = str_ireplace('[OB_LINKS]', $OB_LINKS, $display);
		$display = str_ireplace('[OB_LINK_AMAZON]', $OB_LINK_AMAZON, $display);
		$display = str_ireplace('[OB_LINK_GOODREADS]', $OB_LINK_GOODREADS, $display);
		$display = str_ireplace('[OB_LINK_GOOGLEBOOKS]', $OB_LINK_GOOGLEBOOKS, $display);
		$display = str_ireplace('[OB_LINK_LIBRARYCONGRESS]', $OB_LINK_LIBRARYCONGRESS, $display);
		$display = str_ireplace('[OB_LINK_LIBRARYTHING]', $OB_LINK_LIBRARYTHING, $display);
		$display = str_ireplace('[OB_LINK_WORLDCAT]', $OB_LINK_WORLDCAT, $display);
		$display = str_ireplace('[OB_LINK_PROJECTGUTENBERG]', $OB_LINK_PROJECTGUTENBERG, $display);
		$display = str_ireplace('[OB_LINK_BOOKFINDER]', $OB_LINK_BOOKFINDER, $display);

		//last substitution: delimiters
		$display = openbook_html_setDelimiters($display);
	}
	catch(Exception $e) {

		$message = $e->getMessage();
		return openbook_getDisplayMessage($message);
	}

	//===================================================
	//6. return book data

	return $display;
}

class openbook_arguments {

	public $atts='';
	public $content='';

	public $booknumber='';
	public $revisionnumber='';
	public $template='';
	public $publisherurl='';
	public $openurlresolver='';
	public $findinlibraryphrase='';
	public $findinlibraryimagesrc='';
	public $domain='';
	public $proxy='';
	public $proxyport='';
	public $timeout='';
	public $showerrors='';
	public $savetemplates='';

	function __construct($atts, $content) {

		$this->atts = $atts;
		$this->content = $content;

		//first check for current shortcode format
		//shortcode format takes parameters from inside the tags, e.g., [openbook booknumber="1234"]
		//if both are provided, use new shortcodes
		extract( shortcode_atts( array(
			'booknumber' => '',
			'templatenumber' => '',
			'publisherurl' => ''
			), $atts ) );

		//if no shortcodes, check for legacy values
		if ($booknumber == '')
		{
			//legacy version took parameters between two tags, e.g., [openbook]booknumber="1234"[/openbook]
			if ($content != null) {
				$args = explode(",", $content);
				$argcount = count($args);
				if ($argcount==0) throw new Exception(OB_BOOKNUMBERREQUIRED_LANG);

				$booknumber=$args[0];
				if ($argcount>=1) $templatenumber=$args[1];
				if ($argcount>=2) $publisherurl=$args[2];
				//old revision number no longer supported
			}
		}

		if (!$booknumber) throw new Exception(OB_BOOKNUMBERREQUIRED_LANG);

		//revision number
		//only applicable for OLID
		$olid_start = stripos($booknumber,"OLID");
		$amp_start = stripos($booknumber,"@");
		if (is_integer($olid_start) && is_integer($amp_start)) $revisionnumber = substr($booknumber, $amp_start + 1);

		//collect option configurations
		//use if inline value not provided above

		if (!$templatenumber) $templatenumber = OB_OPTION_TEMPLATENUMBER_1;
		if ($templatenumber == OB_OPTION_TEMPLATENUMBER_1) $template = trim(get_option(OB_OPTION_TEMPLATE1_NAME));
		elseif ($templatenumber == OB_OPTION_TEMPLATENUMBER_2) $template = trim(get_option(OB_OPTION_TEMPLATE2_NAME));
		elseif ($templatenumber == OB_OPTION_TEMPLATENUMBER_3) $template = trim(get_option(OB_OPTION_TEMPLATE3_NAME));
		elseif ($templatenumber == OB_OPTION_TEMPLATENUMBER_4) $template = trim(get_option(OB_OPTION_TEMPLATE4_NAME));
		elseif ($templatenumber == OB_OPTION_TEMPLATENUMBER_5) $template = trim(get_option(OB_OPTION_TEMPLATE5_NAME));
		else throw new Exception(OB_INVALIDTEMPLATENUMBER_LANG);
		if (!$template) throw new Exception(OB_INVALIDTEMPLATENUMBER_LANG);

		$publisherurl = trim($publisherurl); //don't url encode the url

		$openurlresolver = trim(get_option(OB_OPTION_FINDINLIBRARY_OPENURLRESOLVER_NAME));

		$findinlibraryphrase = trim(get_option(OB_OPTION_FINDINLIBRARY_PHRASE_NAME));
		$findinlibraryimagesrc = trim(get_option(OB_OPTION_FINDINLIBRARY_IMAGESRC_NAME));

		$domain = trim(get_option(OB_OPTION_LIBRARY_DOMAIN_NAME));
		if (!$domain) throw new Exception(OB_INVALIDDOMAIN_LANG);

		$timeout = trim(get_option(OB_OPTION_TIMEOUT_NAME));
		$proxy = trim(get_option(OB_OPTION_PROXY_NAME));
		$proxyport = trim(get_option(OB_OPTION_PROXYPORT_NAME));

		$showerrors = get_option(OB_OPTION_SHOWERRORS_NAME);
		$savetemplates = get_option(OB_OPTION_SAVETEMPLATES_NAME);

		//set return values
		$this->booknumber=$booknumber;
		$this->revisionnumber=$revisionnumber;
		$this->template=$template;
		$this->publisherurl=$publisherurl;
		$this->template=$template;
		$this->openurlresolver=$openurlresolver;
		$this->findinlibraryphrase=$findinlibraryphrase;
		$this->findinlibraryimagesrc=$findinlibraryimagesrc;
		$this->domain=$domain;
		$this->proxy=$proxy;
		$this->proxyport=$proxyport;
		$this->timeout=$timeout;
		$this->showerrors=$showerrors;
		$this->savetemplates=$savetemplates;
	}
}

$myopenbook = new MyOpenBook();

add_action('wp_ajax_openbook_action', 'openbook_action_callback');

//server-side call for ajax visual editor button
function openbook_action_callback() {

	$booknumber = $_POST['booknumber'];
	$templatenumber = $_POST['templatenumber'];
	$publisherurl = $_POST['publisherurl'];
	$revisionnumber = $_POST['revisionnumber'];

	$shortcode_array = array( 'booknumber' => $booknumber, 'templatenumber' => $templatenumber, 'publisherurl' => $publisherurl, 'revisionnumber' => $revisionnumber);

	$ret = openbook_insertbookdata($shortcode_array, null);
	echo $ret;
	die();
}

//add custom stylesheet
function openbook_add_stylesheet() {
	$myStyleUrl = plugins_url('libraries/openbook_style.css', __FILE__); // Respects SSL, Style.css is relative to the current file
    $myStyleFile = WP_PLUGIN_DIR . '/openbook-book-data/libraries/openbook_style.css';
    if ( file_exists($myStyleFile) ) {
    	wp_register_style('openbook', $myStyleUrl);
        wp_enqueue_style( 'openbook');
    }
}

//returns stylesheet for visual editor
function filter_mce_css($url) {
	if(!empty($url)) $url .= ',';
	$url .= plugin_dir_url( __FILE__ ) . 'libraries/openbook_style.css';
	return $url;
}

?>
