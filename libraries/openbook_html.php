<?php

//module handles all openbook html formatting
//no styling here, just html

function openbook_html_getCoverImage($url_coverimage, $title, $ol_url, $revisionnumber) {

	if ($url_coverimage == "") return "";

	//tooltip text that shows when user hovers over cover image
	$hovertext = OB_DISPLAY_CLICKTOVIEWTITLEINOL_LANG;

	//assemble image html
	$html_image = "<img src='" . $url_coverimage . "' alt='" . $title . "' title='" . $hovertext . "' />";

	//if a revision number is given, modify page url
	if ($revisionnumber != "") $ol_url = $ol_url . "?v=" . $revisionnumber;

	//wrap in link to book record in Open Library
	$html_coverimage = "<a href='" . $ol_url . "' >" . $html_image . "</a>";

	return $html_coverimage;
}

function openbook_html_getTitle($ol_url, $revisionnumber, $title, $subtitle) {

	if ($subtitle != "") $title .= ": " . $subtitle;

	//if a revision number is given, modify page url
	if ($revisionnumber != "") $ol_url = $ol_url . "?v=" . $revisionnumber;

	$html_title = "<a href='" . $ol_url . "' title='" . OB_DISPLAY_CLICKTOVIEWTITLEINOL_LANG . "' >" . $title . "</a>";

	return $html_title;
}

function openbook_html_getAuthors($authors, $bystatement, $contributions) {

	$html_authors = "";

	if (count($authors)>0) {

		$authorlinks = array();

		foreach($authors as $author) {

			$authorname = openbook_openlibrary_extractValue($author, 'name');
			$authorurl = openbook_openlibrary_extractValue($author, 'url');

			$html_author =  "<a href='" . $authorurl . "' title='" . OB_DISPLAY_CLICKTOVIEWAUTHORINOL_LANG . "' >" . $authorname . "</a>";
			$authorlinks[] = $html_author;
		}

		$html_authors = join(', ', $authorlinks);
	}

	//if no author, use alternate, no author link
	if (!$html_authors) $html_authors = $bystatement;
	if (!$html_authors) $html_authors = $contributions;

	return $html_authors;
}

function openbook_html_getPublisher($publisher, $publisherurl) {

	$html_publisher = "";
	if ($publisher != '') {
		$html_publisher = $publisher;
		if ($publisherurl != '') {
			$html_publisher = "<a href='" . $publisherurl . "' title='" . OB_DISPLAY_CLICKTOVIEWPUBLISHER_LANG . "' >" . $publisher . "</a>";
		}
	}

	return 	$html_publisher;
}

function openbook_html_getPublishYear($publishdate) {

	try {
		$html_publishdate = "";

		if (strlen($publishdate)==4) $html_publishdate = $publishdate;
		else {
			preg_match("/[0-2][0-9][0-9][0-9]/", $publishdate, $matches);
			$html_publishdate = $matches[0];
		}

		return 	$html_publishdate;
	}
	catch(Exception $e) {
		return "";
	}
}

function openbook_html_getReadOnline($url) {

	$readonline = "";
	if ($url != '') {
		$readonline = '<a href="' . $url . '" title="' . OB_DISPLAY_READONLINE_TITLE_LANG . '">' . OB_DISPLAY_READONLINE_LANG . '</a>';
	}
	return $readonline;
}

function openbook_html_getFindInLibrary($openurlresolver, $openurl, $findinlibraryphrase, $isbn, $title, $author) {

	$html_findinlibrary = "";

	if (!$openurlresolver || !$findinlibraryphrase) return ""; //if resolver or phrase is not configured this feature will be blank

	$url = $openurl;
	$html_findinlibrary = '<a href="' . $url . '" title="' . $findinlibraryphrase . '">' . $findinlibraryphrase . '</a>';

	return $html_findinlibrary;
}

function openbook_html_getFindInLibraryImage($openurlresolver, $openurl, $findinlibraryimagesrc, $findinlibraryphrase, $isbn, $title, $author) {

	$html_findinlibraryimage = "";

	if (!$openurlresolver || !$findinlibraryimagesrc) return ""; //if resolver or src is not configured this feature will be blank

	$url = $openurl;
	$html_findinlibraryimage = '<a href="' . $url . '" title="' . $findinlibraryphrase . '">' . '<img src="' . $findinlibraryimagesrc . '" alt="' . $findinlibraryphrase . '" /></a>';

	return $html_findinlibraryimage;
}

function openbook_html_getOpenUrl($openurlresolver, $title, $isbn, $authorlist, $publishplace, $publisher, $publishdate, $pages) {

	if (!$openurlresolver) return "";

	$openurl = $openurlresolver;
	$openurl .= '?url_ver=Z39.88-2004';
	$openurl .= '&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook';
	$openurl .= openbook_html_getCoinsContents($title, $isbn, $authorlist, $publishplace, $publisher, $publishdate, $pages);

	return $openurl;
}

//build the HTML for coins, as per http://ocoins.info/
function openbook_html_getCoins($title, $isbn, $authorlist, $publishplace, $publisher, $publishdate, $pages) {

	$domain = openbook_utilities_getDomain();

	//meta values
	$coins .= '<span class="Z3988" ';
	$coins .= 'title="ctx_ver=Z39.88-2004';
	$coins .= '&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook';
	$coins .= '&amp;rfr_id=info%3Asid%2F' . $domain . '%3AOpenBook';
	$coins .= '&amp;rft.genre=book';

	$coins .= openbook_html_getCoinsContents($title, $isbn, $authorlist, $publishplace, $publisher, $publishdate, $pages);

	//end
	//inserted space so that COinS would not get clipped during Ajax insert from Preview pane
	$coins .= '">&nbsp;</span>';

	return $coins;
}

function openbook_html_getCoinsContents($title, $isbn, $authorlist, $publishplace, $publisher, $publishdate, $pages) {

	$contents = "";

	//title, includes subtitle
	$title = urlencode($title);
	if ($title != "") $contents .= '&amp;rft.btitle=' . $title;

	if ($isbn != "" && openbook_utilities_validISBN($isbn)) $contents .= "&amp;rft.isbn=" . $isbn;

	//authors
	$authors_coins = "";

	$authors = explode(",", $authorlist);
	$authorcount = count($authors);
	for($i=0;$i<$authorcount;$i++) {
		$author = $authors[$i]; //Open Library shows "William Shakespeare", i.e., first and lastname as one field;
		$author = urlencode($author);
		$author_coins = '&amp;rft.au=' . $author;
		$authors_coins .= $author_coins;
	}
	if ($authors_coins != "") $contents .= $authors_coins;

	$publishplace = urlencode($publishplace);
	if ($publishplace != "") $contents .= "&amp;rft.place=" . $publishplace;

	$publisher = urlencode($publisher);
	if ($publisher != "") $contents .= "&amp;rft.pub=" . $publisher;

	$publishdate = urlencode($publishdate);
	if ($publishdate != "") $contents .= "&amp;rft.date=" . $publishdate;

	$pages = urlencode($pages);
	if ($pages != "") $contents .= "&amp;rft.tpages=" . $pages;

	return $contents;
}

function openbook_html_getLinks($links) {

	if (count($links)==0) return "";

	$linklinks = array();

	foreach($links as $link) {

		$linktitle = openbook_openlibrary_extractValue($link, 'title');
		$linkurl = openbook_openlibrary_extractValue($link, 'url');

		$html_link =  "<a href='" . $linkurl . "' title='" . $linktitle . "' >" . $linktitle . "</a>";
		$linklinks[] = $html_link;
	}

	$html_links = join(', ', $linklinks);

	return $html_links;
}

function openbook_html_getLinkAmazon($OL_ID_AMAZON) {

	if (!$OL_ID_AMAZON) return "";
	$url = 'http://www.amazon.com/gp/product/' . $OL_ID_AMAZON;
	$html_link = '<a href="' . $url . '" title="' . OB_DISPLAY_AMAZON_TITLE_LANG . '">' . OB_DISPLAY_AMAZON_LANG . '</a>';

	return $html_link;
}

function openbook_html_getLinkGoodreads($OL_ID_GOODREADS) {

	if (!$OL_ID_GOODREADS) return "";
	$url = 'http://www.goodreads.com/book/show/' . $OL_ID_GOODREADS;
	$html_link = '<a href="' . $url . '" title="' . OB_DISPLAY_GOODREADS_TITLE_LANG . '">' . OB_DISPLAY_GOODREADS_LANG . '</a>';

	return $html_link;
}

function openbook_html_getLinkGoogleBooks($OL_ID_GOOGLE, $isbn, $title, $author) {

	if ($OL_ID_GOOGLE) {
		$url = 'http://books.google.com/books?id=' . $OL_ID_GOOGLE;
	}
	elseif ($isbn) {
		$url = 'http://books.google.com/books?as_isbn=' . $isbn; //isbn search
	}
	elseif ($title) {
		//search by title and author
		$url = 'http://books.google.com/books?&as_vt=' . $title;
		if ($author) $url .= '&as_auth=' . $author;
	}
	else { return ""; }

	$html_link = '<a href="' . $url . '" title="' . OB_DISPLAY_GOOGLEBOOKS_TITLE_LANG . '">' . OB_DISPLAY_GOOGLEBOOKS_LANG . '</a>';
	return $html_link;
}

function openbook_html_getLinkLibraryCongress($OL_ID_LCCN) {

	if (!$OL_ID_LCCN) return "";
	$url = 'http://lccn.loc.gov/' . $OL_ID_LCCN;
	$html_link = '<a href="' . $url . '" title="' . OB_DISPLAY_LIBRARYCONGRESS_TITLE_LANG . '">' . OB_DISPLAY_LIBRARYCONGRESS_LANG . '</a>';

	return $html_link;
}

function openbook_html_getLinkLibraryThing($OL_ID_LIBRARYTHING, $isbn, $title, $author) {

	if ($OL_ID_LIBRARYTHING) {
		$url = 'http://www.librarything.com/work/' . $OL_ID_LIBRARYTHING;
	}
	elseif ($isbn) {
		$url = 'http://librarything.com/isbn/' . $isbn; //isbn search
	}
	elseif ($title) {
		//search by title and author
		$url = 'http://www.librarything.com/search_works.php?q=' . $title;
		if ($author) $url .= '+' . $author;
	}
	else { return ""; }

	$html_link = '<a href="' . $url . '" title="' . OB_DISPLAY_LIBRARYTHING_TITLE_LANG . '">' . OB_DISPLAY_LIBRARYTHING_LANG . '</a>';
	return $html_link;
}

function openbook_html_getLinkWorldCat($OL_ID_OCLCWORLDCAT, $isbn, $title, $author) {

	if ($OL_ID_OCLCWORLDCAT) {
		$url = 'http://www.worldcat.org/oclc/' . $OL_ID_OCLCWORLDCAT;
	}
	elseif ($isbn) {
		$url = 'http://worldcat.org/isbn/' . $isbn; //isbn search
	}
	elseif ($title) {
		//search by title and author
		$url = 'http://www.worldcat.org/search?q=ti%3A' . $title;
		if ($author) $url .= '+au%3A' . $author;
		$url .= '&qt=advanced';
	}
	else { return ""; }

	$html_link = '<a href="' . $url . '" title="' . OB_DISPLAY_WORLDCAT_TITLE_LANG . '">' . OB_DISPLAY_WORLDCAT_LANG . '</a>';
	return $html_link;
}

function openbook_html_getLinkProjectGutenberg($OL_ID_PROJECTGUTENBERG) {

	if (!$OL_ID_PROJECTGUTENBERG) return "";
	$url = 'http://www.gutenberg.org/etext/' . $OL_ID_PROJECTGUTENBERG;
	$html_link = '<a href="' . $url . '" title="' . OB_DISPLAY_PROJECTGUTENBERG_TITLE_LANG . '">' . OB_DISPLAY_PROJECTGUTENBERG_LANG . '</a>';

	return $html_link;
}

function openbook_html_getLinkBookFinder($isbn, $title, $author) {

	$html_bookfinder = "";

	if (!$isbn && !$title) return ""; //if no isbn or title, this feature will be blank

	if ($isbn) $url = 'http://www.bookfinder.com/search/?st=xl&ac=qr&isbn=' . $isbn; //isbn search
	else {
		//search by title and author -- expects spaces in these values as '+'
		$url = 'http://www.bookfinder.com/search/?submit=Begin+search&new_used=*&mode=basic&st=sr&ac=qr&title=' . $title;
		if ($author) $url .= '&author=' . $author;
		//there is an available language parameter for the search
	}

	$html_bookfinder = '<a href="' . $url . '" title="' . OB_DISPLAY_BOOKFINDER_TITLE_LANG . '">' . OB_DISPLAY_BOOKFINDER_LANG . '</a>';

	return $html_bookfinder;
}

function openbook_html_setDelimiters($display) {

	//clear double dots, e.g., read online link might be blank
	$exceptions = array('[OB_DOT]  [OB_DOT]', '[OB_DOT] [OB_DOT]', '[OB_DOT][OB_DOT]');
	$display = str_replace($exceptions, '[OB_DOT]', $display);
	$display = str_replace($exceptions, '[OB_DOT]', $display); //first run is supposed to replace all, but doesn't

	$display = str_replace('[OB_DOT]', '&#8226;', $display);

	return $display;
}

?>
