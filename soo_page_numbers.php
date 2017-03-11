<?php

$plugin['name'] = 'soo_page_numbers';
$plugin['version'] = '0.4.1';
$plugin['author'] = 'Jeff Soo';
$plugin['author_uri'] = 'http://ipsedixit.net/txp/';
$plugin['description'] = 'Article list nav and page count widgets';
$plugin['type'] = 1; 

defined('PLUGIN_LIFECYCLE_NOTIFY') or define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); 
$plugin['flags'] = PLUGIN_LIFECYCLE_NOTIFY;

$plugin['textpack'] = <<< EOT
#@soo_page_numbers
#@language en-gb
soo_page_count => {prev} Page {current} of {total} {next}
#@language en-us
soo_page_count => {prev} Page {current} of {total} {next}
#@language de-de
soo_page_count => {prev} Seite {current} von {total} {next}
#@language da-dk
soo_page_count => {prev} Side {current} af {total} {next}
#@language cs-cz
soo_page_count => {prev} Stránka {current} z {total} {next}
#@language fr-fr
soo_page_count => {prev} Page {current} sur {total} {next}
#@language it-it
soo_page_count => {prev} Pagina {current} di {total} {next}
#@language sv-se
soo_page_count => {prev} Sida {current} av {total} {next}
#@language no-no
soo_page_count => {prev} Side {current} av {total} {next}
#@language fi-fi
soo_page_count => {prev} {current} sivu {total} sivusta {next}
#@language ru-ru
soo_page_count => {prev} Страница {current}, всего {total} {next}
#@language nl-nl
soo_page_count => {prev} Pagina {current} van {total} {next}
#@language es-es
soo_page_count => {prev} Página {current} de {total} {next}
#@language ca-es
soo_page_count => {prev} Pàgina {current} de {total} {next}
#@language lt-lt
soo_page_count => {prev} Puslapis {current} iš {total} {next}
#@language lv-lv
soo_page_count => {prev} Lapa {current} no {total} {next}
#@language pt-br
soo_page_count => {prev} Página {current} de {total} {next}
#@language pt-pt
soo_page_count => {prev} Página {current} de {total} {next}
#@language ro-ro
soo_page_count => {prev} Pagina {current} din {total} {next}
#@language id-id
soo_page_count => {prev} Halaman {current} dari {total} {next}
#@language fa-ir
soo_page_count => {next} {total} از {current} صفحه {prev}
EOT;

/******************************************************************/
/////////////////// DEVELOPMENT CYCLE ONLY /////////////////////////
///// Load gTxt() strings when running plugin from cache ///////////

// if ( ! empty($plugin['textpack']) && @in_array(txpinterface, array('public', 'admin')) )
// {
//  global $textarray;
//  $is_current_lang = false;
//  foreach ( explode(n, $plugin['textpack']) as $line )
//  {
//      if ( preg_match('/^#@language\s+([a-z]{2,2}-[a-z]{2,2})/', $line, $match) )
//      {
//          if ( $match[1] == LANG )
//              $is_current_lang = true;
//          elseif ( $is_current_lang )
//              break;
//          else
//              continue;
//      }
//      if ( $is_current_lang && preg_match('/^(\w+)\s*=>\s*(.+)/', $line, $match) )
//          $textarray[$match{1}] = $match[2];
//  }
// }

/////////////////// DEVELOPMENT CYCLE ONLY /////////////////////////
/******************************************************************/

defined('txpinterface') or @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---

if(class_exists('\Textpattern\Tag\Registry')) {
    Txp::get('\Textpattern\Tag\Registry')
        ->register('soo_page_links')
        ->register('soo_page_count')
        ->register('soo_prev_page')
        ->register('soo_next_page')
        ;
}

if ( @txpinterface == 'admin' ) 
{
    add_privs('plugin_lifecycle.soo_page_numbers','1,2');
    register_callback('soo_page_numbers_lifecycle', 'plugin_lifecycle.soo_page_numbers');
}

// delete the Textpack
// Note: callback_event() does not display message from lifecycle callbacks
function soo_page_numbers_lifecycle ( $event, $step )
{
    if ( substr($event, strlen('plugin_lifecycle.')) === 'soo_page_numbers' && $step === 'deleted' )
    {
        safe_delete('txp_lang', "event = 'soo_page_numbers'");
    }
}

// load the Textpack
if ( @txpinterface == 'public' )
{
    if ( $soo_page_numbers_textpack = load_lang_event('soo_page_numbers') )
    {
        global $textarray;
        $textarray += $soo_page_numbers_textpack;
        unset($soo_page_numbers_textpack);
    }
}

function soo_page_links ( $atts )
{
    extract(lAtts(array(
        'placeholder'   =>  '&hellip;',
        'window_size'   =>  5,
        'active_class'  =>  'here',
        'wraptag'       =>  '',
        'class'         =>  '',
        'html_id'       =>  '',
        'break'         =>  '',
        'showalways'    =>  false,
    ), $atts));
    
    global $thispage; 

    if ( ! is_array($thispage) ) 
        return _soo_page_numbers_secondpass(__FUNCTION__, $atts);
    
    if ( ! $showalways and $thispage['numPages'] <= 1 ) return;
    
    $numPages = $thispage['numPages'];
    $pg = $thispage['pg'];

    $w_start = max(1, 
        min($pg - floor($window_size / 2), $numPages - $window_size + 1));
    $w_end = min($w_start + $window_size - 1, $numPages);
    
    $pgs = array_unique(array_merge(
        array(1), range($w_start, $w_end), array($numPages)
    ));
    
    $break_text = $wraptag ? '' : $break;
    $text_tag = ( $break and $wraptag ) ? '' : 'span';
    $active_class = $active_class ? " class=\"$active_class\"" : '';

    while ( $pgs )
    {
        $n = array_shift($pgs);
        $fill = $pgs ? ( $pgs[0] > $n + 1 ? $placeholder : $break_text ) : '';
        if ( $n == $pg )
            $items[] = $text_tag ?
                tag($n, $text_tag, $active_class) : $n;
        else
        {
            $uri = _soo_page_numbers_uri($n);
            $items[] = href($n, $uri, ' title="'.gTxt('page').sp.$n.'"');
        }
        if ( $n < $numPages and $fill )
            $items[] = $text_tag ?
                tag($fill, $text_tag) : $fill;
    }
    if ( isset($items) )
        return $wraptag ? str_replace("<$break>$pg<", "<$break$active_class>$pg<",
            doWrap($items, $wraptag, $break, $class, '', '', '', $html_id)) 
            : implode($break_text ? '' : n, $items);
}

function soo_page_count ( $atts )
{
    extract(lAtts(array(
        'format'        =>  gTxt('soo_page_count'),
        'prev'          =>  '&laquo;',
        'next'          =>  '&raquo;',
        'first'         =>  '|&laquo;',
        'last'          =>  '&raquo;|',
        'showalways'    =>  false,
        'wraptag'       =>  '',
    ), $atts));
    
    global $thispage;       

    if ( ! is_array($thispage) ) 
        return _soo_page_numbers_secondpass(__FUNCTION__, $atts);
    
    if ( ! $showalways and $thispage['numPages'] <= 1 ) return;
    
    $numPages = $thispage['numPages'];
    $pg = $thispage['pg'];

    if ( $pg > 1 )
    {
        $first = href($first, _soo_page_numbers_uri(1), ' title="' . gTxt('page') . ' 1"');
        $prev = href($prev, _soo_page_numbers_uri($pg - 1), ' title="' . gTxt('prev') . '"' );
    }
    elseif ( ! $showalways )
        $first = $prev = '';
    
    if ( $pg < $numPages ) {
        $last = href($last, _soo_page_numbers_uri($numPages), ' title="' . gTxt('page') . sp . $numPages . '"');
        $next = href($next, _soo_page_numbers_uri($pg + 1), ' title="' . gTxt('next') . '"' );
    }
    elseif ( ! $showalways )
        $last = $next = '';
            
    $out = str_replace(
        array('{prev}', '{next}', '{first}', '{last}', '{current}', '{total}'),
        array($prev, $next, $first, $last, $pg, $numPages), $format);
    return $wraptag ? tag($out, $wraptag) : $out;
}

function soo_prev_page ( $atts )
{
    if ( isset($atts['text']) )
    {
        $atts['prev'] = $atts['text'];
        unset($atts['text']);
    }
    $atts['format'] = '{prev}';
    return soo_page_count($atts);
}

function soo_next_page ( $atts )
{
    if ( isset($atts['text']) )
    {
        $atts['next'] = $atts['text'];
        unset($atts['text']);
    }
    $atts['format'] = '{next}';
    return soo_page_count($atts);
}

function _soo_page_numbers_secondpass ( $func, $atts )
{
// in case $func's associated tag comes before an article tag, 
// this runs the tag again during textpattern()'s second parse() pass
    global $pretext;
    if ( $pretext['secondpass'] ) return; // you only live twice
    foreach ( $atts as $k => $v )
        $a[] = $k . '="' . $v . '" ';
    return "<txp:$func " . ( isset($a) ? implode('', $a) : '' ) . '/>';
}

function _soo_page_numbers_uri($pg)
{
    static $baseUri, $qParams;
    
    if (empty($baseUri)) {
        $baseUri = preg_replace ('%(.+)\?.+%', '$1', $_SERVER['REQUEST_URI']);
        $qParams = array();
        parse_str($_SERVER['QUERY_STRING'], $qParams);
        if (isset($qParams['p'])) {
            unset($qParams['p']);
        }
    }
    
    if ($pg > 1) {
        $qParams['pg'] = $pg;
    } elseif (isset($qParams['pg'])) {
        unset($qParams['pg']);
    }
    if (empty($qParams)) {
        return $baseUri;
    }
    return $baseUri.'?'.http_build_query($qParams, '', '&amp;');
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

 <div id="toc">

h2. Contents

* "Overview":#overview
* "Tags":#tags
** "soo_page_links":#soo_page_links
** "soo_page_count":#soo_page_count
** "soo_prev_page":#soo_prev_page
* "History":#history

 </div>

h2(#overview). Overview

Display page navigation widgets and information for article list pages. (Inspired by @rsx_page_number@, an old plugin from Txp's early days.)

Version 0.3.0 includes a Textpack to localize pre-formatted text output such as "Page {current} of {total}". Currently includes 21 languages.

NB: Version 0.4.0 did away with the requirement for an external library plugin, but by moving to native Txp URI handling this changed behavior on sites with custom URI schemes. 0.4.1 restores that behavior. Note that the @p@ (image ID) query parameter is not preserved; this is intentional.

%(warning)Note:% If you have more than one pagination-capable tag on the page (@article@ if not @status="sticky"@, or any of @images@, @file_download_list@, or @linklist@ if both @limit@ and @pageby@ are set) *soo_page_numbers* will take its values from the first such tag. This is true no matter where you put any *soo_page_numbers* tags.

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
Class for the current page number's tag (the @break@ tag, if any, otherwise @span@)
* @wraptag@ _(text)_ %(default)default% empty
HTML tag name (no brackets) to wrap the output
* @class@ _(text)_ %(default)default% empty
"HTML class name(s)":http://www.w3.org/TR/html401/struct/global.html#adef-class for the @wraptag@.
* @html_id@ _(text)_ %(default)default% empty
"HTML id":http://www.w3.org/TR/html401/struct/global.html#adef-id for the @wraptag@.
* @break@ _(mixed)_ %(default)default% empty
HTML tag name (no brackets) to wrap or text to place between adjacent page numbers. If @wraptag@ and @break@ are set, @break@ is assumed to be a tag name. Otherwise it is treated as text (so don't use @break="br"@).

h3(#soo_page_count). soo_page_count

pre. <txp:soo_page_count />

h4. Attributes

* @format@ _(format string)_ %(default)default% @ "{prev} Page {current} of {total} {next}" @
Tag will output this string (in your language, if among the 21 included) after replacing @{prev}@, @{next}@, @{first}@, and @{last}@ with links, and @{current}@ and @{total}@ with page numbers
* @prev@ _(text)_ %(default)default% @&laquo;@ (&laquo;)
Link text for the @{prev}@ link
* @next@ _(text)_ %(default)default% @&raquo;@ (&raquo;)
Link text for the @{next}@ link
* @first@ _(text)_ %(default)default% @|&laquo;@ (|&laquo;)
Link text for the @{first}@ link
* @last@ _(text)_ %(default)default% @&raquo;|@ (&raquo;|)
Link text for the @{last}@ link
* @showalways@ _(boolean)_ %(default)default% @0@
Whether or not to show @{prev}@ and @{next}@ on the first and last pages, respectively, or anything at all when the list is a single page
* @wraptag@ _(XHTML tag name, no brackets)_ optional tag to wrap the output

h3(#soo_prev_page). soo_prev_page, soo_next_page

Shortcuts for @soo_page_count@ when all you want is a single link. For example, @soo_prev_page@ is a shortcut for @<txp:soo_page_count format="{prev}" />@.

h4. Attributes

In addition to @soo_page_count@ attributes, each of these tags also accepts a @text@ attribute for setting the link text. The following tags are equivalent:

pre. <txp:soo_next_page text="Next" />
<txp:soo_page_count format="{next}" next="Next" />

h2(#history). Version History

h3. 0.4.1 (2017-03-10)

* New URI handling algorithm, reverting to version 0.3 behavior, but as a freestanding plugin

h3. 0.4.0 (2017-03-04)

* Refactored to eliminate the requirement for an external library plugin. (NB: this means, inter alia, that the @p@ (image ID) query parameter is no longer preserved in page links.)

h3. 0.3.2 (2017-02-15)

* Textpattern 4.6 compatibility update

h3. 0.3.1 (2012-01-27)

* @soo_page_count@ (hence also @soo_prev_page@ and @soo_next_page@) now preserve non-Txp query params for "next" and "prev" links.

h3. 0.3.0 (2011-01-18)

* Plugin now includes a Textpack to localize pre-formatted text output such as "Page {current} of {total}". Currently includes 21 languages.

h3. 0.2.7 (2010-02-11)

* Fixed Textpattern notice about non-existent attribute when using @soo_prev_page@ or @soo_next_page@ (functionality not affected)

h3. 0.2.6 (2009-11-23)

* @soo_page_links@ now restores the @'pg'@ query parameter to its initial state, to avoid conflicts with other context-dependent plugins

h3. 0.2.5 (2009-10-21)

* New attributes and shortcut tags for @soo_page_count@

h3. 0.2.4 (2009-07-16)

* Fixed @showalways@ bug when an article list returns 0 pages (e.g. empty category)

h3. 0.2.3 (2009-07-09)

* Improved context check to prevent raw tag output

h3. 0.2.2 (2009-07-09)

* When both @wraptag@ and @break@ are set, non-linked text items (i.e., current page number or placeholder text) are no longer wrapped in @span@ tags, and @active_class@ is applied to the @break@ element containing the current page number.
* Both tags now do a context check and show nothing if the page is not an article list

h3. 0.2.1 (2009-07-07)

* Changed file name and one tag name 
* The @showalways@ attribute of @soo_page_count@ now also affects output when the article list is only one page
* @soo_page_links@ has also been given the @showalways@ attribute
* Scrapped @soo_article_count@, which was inherently buggy (as is the @rsx_to_of@ it was based on)

h3. 0.2 (2009-05-22)

Not publicly released. Code overhaul, fixed to work with any query string

h3. 0.1 (ages ago) 

Not publicly released, not very good either, just a sorry hack of what was probably a quick one-off plugin to begin with (it was early days for Txp).

h2. <!-- end -->

 </div>

# --- END PLUGIN HELP ---
-->
<?php
}

?>