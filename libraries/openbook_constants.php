<?php

//constants used in the constants file
//note: include language file before this one - language constants used as defaults

define('OB_HTML_CHECKED_TRUE', 'checked');
define('OB_HTML_CHECKED_FALSE', '');

//options

define('OB_OPTION_TEMPLATENUMBER_1', '1');
define('OB_OPTION_TEMPLATENUMBER_2', '2');
define('OB_OPTION_TEMPLATENUMBER_3', '3');
define('OB_OPTION_TEMPLATENUMBER_4', '4');
define('OB_OPTION_TEMPLATENUMBER_5', '5');

define('OB_OPTION_TEMPLATE1_NAME', 'openbook_template1');
define('OB_OPTION_TEMPLATE1_VAL', '<span class="openbook_wrapper1"><span class="openbook_cover1">[OB_COVER_MEDIUM]</span><span class="openbook_title1"> [OB_TITLE]</span><span class="openbook_author1">[OB_AUTHORS]; [OB_PUBLISHER] [OB_PUBLISHYEAR]</span><span class="openbook_links1">[OB_LINK_WORLDCAT][OB_DOT][OB_READONLINE][OB_DOT][OB_LINK_LIBRARYTHING][OB_DOT][OB_LINK_GOOGLEBOOKS][OB_DOT][OB_LINK_BOOKFINDER]</span>[OB_COINS]</span>');

define('OB_OPTION_TEMPLATE2_NAME', 'openbook_template2');
define('OB_OPTION_TEMPLATE2_VAL', '<span class="openbook_wrapper1"><span class="openbook_cover2">[OB_COVER_SMALL]</span><span class="openbook_title2">[OB_TITLE]; [OL_AUTHORFIRST]</span>[OB_COINS]</span>');

define('OB_OPTION_TEMPLATE3_NAME', 'openbook_template3');
define('OB_OPTION_TEMPLATE3_VAL', '<span class="openbook_wrapper3"><span class="openbook_cover3">[OB_COVER_LARGE]</span><span class="openbook_links1">[OB_LINK_WORLDCAT][OB_DOT][OB_READONLINE][OB_DOT][OB_LINK_LIBRARYTHING][OB_DOT][OB_LINK_GOOGLEBOOKS][OB_DOT][OB_LINK_BOOKFINDER]</span>[OB_COINS]</span>');

define('OB_OPTION_TEMPLATE4_NAME', 'openbook_template4');
define('OB_OPTION_TEMPLATE4_VAL', '[OB_TITLE]');

define('OB_OPTION_TEMPLATE5_NAME', 'openbook_template5');
define('OB_OPTION_TEMPLATE5_VAL', '[OB_AUTHORS] ([OB_PUBLISHYEAR]). [OB_TITLE]. [OB_PUBLISHER].[OB_COINS]');

define('OB_OPTION_FINDINLIBRARY_OPENURLRESOLVER_NAME', 'openbook_findinlibrary_openurlresolver');
define('OB_OPTION_FINDINLIBRARY_OPENURLRESOLVER_VAL', '');

define('OB_OPTION_FINDINLIBRARY_PHRASE_NAME', 'openbook_findinlibrary_phrase');
define('OB_OPTION_FINDINLIBRARY_PHRASE_VAL', OB_OPTIONS_FINDINLIBRARY_LANG);

define('OB_OPTION_FINDINLIBRARY_IMAGESRC_NAME', 'openbook_imagesrc_phrase');
define('OB_OPTION_FINDINLIBRARY_IMAGESRC_VAL', '');

define('OB_OPTION_LIBRARY_DOMAIN_NAME', 'openbook_openlibrary_domain');
define('OB_OPTION_LIBRARY_DOMAIN_VAL', 'http://openlibrary.org');

define('OB_OPTION_PROXY_NAME', 'openbook_proxy');
define('OB_OPTION_PROXY_VAL', '');

define('OB_OPTION_PROXYPORT_NAME', 'openbook_proxyport');
define('OB_OPTION_PROXYPORT_VAL', '');

define('OB_OPTION_TIMEOUT_NAME', 'openbook_timeout');
define('OB_OPTION_TIMEOUT_VAL', '10');

define('OB_OPTION_SHOWERRORS_NAME', 'openbook_showerrors');
define('OB_OPTION_SHOWERRORS_VALUE', OB_HTML_CHECKED_FALSE);

define('OB_OPTION_SAVETEMPLATES_NAME', 'openbook_savetemplates');
define('OB_OPTION_SAVETEMPLATES_VALUE', OB_HTML_CHECKED_TRUE);

//other constants

//define('OB_OPENLIBRARY_IMGSRC_COVERSIZE1', '-S');
//define('OB_OPENLIBRARY_IMGSRC_COVERSIZE2', '-M');
//define('OB_OPENLIBRARY_IMGSRC_COVERSIZE3', '-L');

define('OB_OPENLIBRARY_PREVIEW_NOVIEW', 'noview');


?>
