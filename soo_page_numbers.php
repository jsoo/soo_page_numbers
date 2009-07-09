<?php

$plugin['name'] = 'soo_page_numbers';
$plugin['version'] = '0.2.1';
$plugin['author'] = 'Jeff Soo';
$plugin['author_uri'] = 'http://ipsedixit.net/';
$plugin['description'] = 'Article list nav and page count widgets';
$plugin['type'] = 0; 

@include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---

require_plugin('soo_txp_obj');

  //---------------------------------------------------------------------//
 //									Tags								//
//---------------------------------------------------------------------//

function soo_page_links ( $atts ) {

	extract(lAtts(array(
		'placeholder'	=>	'&hellip;',
		'window_size'	=>	5,
		'active_class'	=>	'here',
		'wraptag'		=>	'',
		'class'			=>	'',
		'html_id'		=>	'',
		'break' 		=>	'',
		'showalways'	=>	false,
	), $atts));
	
	global $thispage, $is_article_list; 
	// $thispage: 'pg', 'numPages', 's', 'c', 'grand_total', 'total'
	if ( ! $is_article_list ) return;
	if ( is_array($thispage) )
		extract($thispage);
	else {
		foreach ( $atts as $k => $v )
			$a[] = $k . '="' . $v . '" ';
		return '<txp:soo_page_links ' . ( isset($a) ? implode(' ', $a) : '' ) . '/>';
	}	// so the tag can come before its associated article tag
	
	if ( ! $showalways and $numPages == 1 ) return;
			
	$w_start = max(1, 
		min($pg - floor($window_size / 2), $numPages - $window_size + 1));
	$w_end = min($w_start + $window_size - 1, $numPages);
	
	$pgs = array_unique(array_merge(
		array(1), range($w_start, $w_end), array($numPages)
	));
	
	$br = $wraptag ? '' : $break;

	$uri = new Soo_Uri;
	while ( $pgs ) {
		$n = array_shift($pgs);
		$uri->set_query_param('pg', ( $n > 1 ? $n : null ));
		$fill = $pgs ? ( $pgs[0] > $n + 1 ? $placeholder : $br ) : '';
		if ( $n == $pg )
			$out[] = tag($n, 'span', " class=\"$active_class\"");
		else
			$out[] = href($n, $uri->full, " title='Page $n'");
		if ( $n < $numPages and $fill )
			$out[] = span($fill);
	}	
	return isset($out) ? 
		( $wraptag ? 
			doWrap($out, $wraptag, $break, $class, '', '', '', $html_id) 
				: implode("\n", $out) )
		: '';
}

function soo_page_count ( $atts ) {

	extract(lAtts(array(
		'format' 		=>	'{prev} Page {current} of {total} {next}',
		'prev'			=>	'&laquo;',
		'next'			=>	'&raquo;',
		'showalways'	=>	false,
	), $atts));
	
	global $thispage, $is_article_list; 
	// $thispage: 'pg', 'numPages', 's', 'c', 'grand_total', 'total'
	if ( ! $is_article_list ) return;
	if ( is_array($thispage) )
		extract($thispage);
	else {
		foreach ( $atts as $k => $v )
			$a[] = $k . '="' . $v . '" ';
		return '<txp:soo_page_count ' . ( isset($a) ? implode(' ', $a) : '' ) . '/>';
	}	// so the tag can come before its associated article tag
	
	if ( ! $showalways and $numPages == 1 ) return;
	
	$prev = $pg > 1 ? newer(array(), $prev) : ( $showalways ? $prev : '' );
	$next = $pg < $numPages ? older(array(), $next) : ( $showalways ? $next : '' ); 
		
	return str_replace(array('{prev}', '{next}', '{current}', '{total}'),
		array($prev, $next, $pg, $numPages), $format);
}

# --- END PLUGIN CODE ---

if (0) {
?>
<!-- CSS SECTION
# --- BEGIN PLUGIN CSS ---
<style type="text/css">
div#sed_help pre {padding: 0.5em 1em; background: #eee; border: 1px dashed #ccc;}
div#sed_help h1, div#sed_help h2, div#sed_help h3, div#sed_help h3 code {font-family: sans-serif; font-weight: bold;}
div#sed_help h1, div#sed_help h2, div#sed_help h3 {margin-left: -1em;}
div#sed_help h2, div#sed_help h3 {margin-top: 2em;}
div#sed_help h1 {font-size: 2.4em;}
div#sed_help h2 {font-size: 1.8em;}
div#sed_help h3 {font-size: 1.4em;}
div#sed_help h4 {font-size: 1.2em;}
div#sed_help h5 {font-size: 1em;margin-left:1em;font-style:oblique;}
div#sed_help h6 {font-size: 1em;margin-left:2em;font-style:oblique;}
div#sed_help li {list-style-type: disc;}
div#sed_help li li {list-style-type: circle;}
div#sed_help li li li {list-style-type: square;}
div#sed_help li a code {font-weight: normal;}
div#sed_help li code:first-child {background: #ddd;padding:0 .3em;margin-left:-.3em;}
div#sed_help li li code:first-child {background:none;padding:0;margin-left:0;}
div#sed_help dfn {font-weight:bold;font-style:oblique;}
div#sed_help .required, div#sed_help .warning {color:red;}
div#sed_help .default {color:green;}
</style>
# --- END PLUGIN CSS ---
-->
<!-- HELP SECTION
# --- BEGIN PLUGIN HELP ---
 <div id="sed_help">

h1. soo_page_numbers

h2(#overview). Overview

Display page navigation widgets and information for article list pages. A rehash of the @rsx_page_number@ plugin, bringing it into the modern (Txp 4.0.8) era with more attributes for greater control, and also correct function with multiple query string parameters (as with search results, messy URL mode, or other Txp plugins that add their own query params).

%(required)Requires PHP5 and the *soo_txp_obj* library plugin% (but see the README included in the download if you don't already have *soo_txp_obj* installed).

h2(#tags). Tags

h3(#soo_page_links). soo_page_links

Display a "Google-style" page navigation widget, i.e., a group of numbered links representing pages.

pre. <txp:soo_page_links />

h4. Attributes

* @placeholder@ _(text)_ %(default)default% @&hellip;@
Text to place on either end of the central page range (when there are many pages)
* @window_size@ _(integer)_ %(default)default% @5@
Size of central page range
* @showalways@ _(boolean)_ %(default)default% @0@
Whether or not to show anything when the list is a single page
* @active_class@ _(HTML class)_ %(default)default% @here@
Class for the @span@ surrounding the current page number
* @wraptag@ _(text)_ %(default)default% empty
HTML tag name (no brackets) to wrap the output
* @class@ _(text)_ %(default)default% empty
"HTML class name(s)":http://www.w3.org/TR/html401/struct/global.html#adef-class for the @wraptag@.
* @html_id@ _(text)_ %(default)default% empty
"HTML id":http://www.w3.org/TR/html401/struct/global.html#adef-id for the @wraptag@.
* @break@ _(mixed)_ %(default)default% empty
HTML tag name (no brackets) to wrap or text to place between adjacent page numbers. If @wraptag@ and @break@ are set, @break@ is assumed to be a tag name. Otherwise it is treated as text.

h3(#soo_page_count). soo_page_count

pre. <txp:soo_page_count />

h4. Attributes

* @format@ _(format string)_ %(default)default% @ "{prev} Page {current} of {total} {next}" @
Tag will output this string after replacing @{prev}@ and @{next}@ with links, and @{current}@ and @{total}@ with page numbers
* @prev@ _(text)_ %(default)default% @&laquo;@
Link text for the @{prev}@ link
* @next@ _(text)_ %(default)default% @&raquo;@
Link text for the @{next}@ link
* @showalways@ _(boolean)_ %(default)default% @0@
Whether or not to show @{prev}@ and @{next}@ on the first and last pages, respectively, or anything at all when the list is a single page

h2(#history). Version History

h3. 0.2.1 (2009/07/07)

* Changed file name and one tag name 
* The @showalways@ attribute of @soo_page_count@ now also affects output when the article list is only one page
* @soo_page_links@ has also been given the @showalways@ attribute
* Scrapped @soo_article_count@, which was inherently buggy (as is the @rsx_to_of@ it was based on)

h3. 0.2 (2009/05/22)

Not publicly released. Code overhaul, fixed to work with any query string

h3. 0.1 (ages ago) 

Not publicly released, not very good either, just a sorry hack of what was probably a quick one-off plugin to begin with (it was early days for Txp).

 </div>
# --- END PLUGIN HELP ---
-->
<?php
}

?>