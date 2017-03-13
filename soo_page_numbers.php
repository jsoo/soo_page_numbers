<?php
$plugin['version'] = '0.4.1';
$plugin['author'] = 'Jeff Soo';
$plugin['author_uri'] = 'http://ipsedixit.net/txp/';
$plugin['description'] = 'Article list nav and page count widgets';
$plugin['type'] = 1; 
$plugin['allow_html_help'] = 1;

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

if (! defined('txpinterface')) {
    global $compiler_cfg;
    @include_once('config.php');
    @include_once($compiler_cfg['path']);
}

# --- BEGIN PLUGIN CODE ---

if(class_exists('\Textpattern\Tag\Registry')) {
    Txp::get('\Textpattern\Tag\Registry')
        ->register('soo_page_links')
        ->register('soo_page_count')
        ->register('soo_prev_page')
        ->register('soo_next_page')
        ;
}

if ( @txpinterface == 'admin' ) {
    add_privs('plugin_lifecycle.soo_page_numbers','1,2');
    register_callback('soo_page_numbers_lifecycle', 'plugin_lifecycle.soo_page_numbers');
}

// delete the Textpack
// Note: callback_event() does not display message from lifecycle callbacks
function soo_page_numbers_lifecycle ($event, $step)
{
    if (substr($event, strlen('plugin_lifecycle.')) === 'soo_page_numbers' && $step === 'deleted') {
        safe_delete('txp_lang', "event = 'soo_page_numbers'");
    }
}

// load the Textpack
if (@txpinterface == 'public') {
    if ($soo_page_numbers_textpack = load_lang_event('soo_page_numbers')) {
        global $textarray;
        $textarray += $soo_page_numbers_textpack;
        unset($soo_page_numbers_textpack);
    }
}

function soo_page_links ($atts)
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

    if (! is_array($thispage)) {
        return _soo_page_numbers_secondpass(__FUNCTION__, $atts);
    }
    
    if (! $showalways and $thispage['numPages'] <= 1) return;
    
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

    while ($pgs) {
        $n = array_shift($pgs);
        $fill = $pgs ? ( $pgs[0] > $n + 1 ? $placeholder : $break_text ) : '';
        if ( $n == $pg ) {
            $items[] = $text_tag ? tag($n, $text_tag, $active_class) : $n;
        } else {
            $uri = _soo_page_numbers_uri($n);
            $items[] = href($n, $uri, ' title="'.gTxt('page').sp.$n.'"');
        }
        if ($n < $numPages and $fill) {
            $items[] = $text_tag ? tag($fill, $text_tag) : $fill;
        }
    }
    if (isset($items)) {
        return $wraptag ? str_replace("<$break>$pg<", 
            "<$break$active_class>$pg<",
            doWrap($items, $wraptag, $break, $class, '', '', '', $html_id)) 
            : implode($break_text ? '' : n, $items);
    }
}

function soo_page_count ($atts)
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

    if (! is_array($thispage)) {
        return _soo_page_numbers_secondpass(__FUNCTION__, $atts);
    }
    
    if (! $showalways && $thispage['numPages'] <= 1) return;
    
    $numPages = $thispage['numPages'];
    $pg = $thispage['pg'];

    if ($pg > 1) {
        $first = href($first, _soo_page_numbers_uri(1), ' title="'.gTxt('page').' 1"');
        $prev = href($prev, _soo_page_numbers_uri($pg - 1), ' title="'.gTxt('prev').'"' );
    } elseif (! $showalways) $first = $prev = '';
    
    if ( $pg < $numPages ) {
        $last = href($last, _soo_page_numbers_uri($numPages), ' title="'.gTxt('page').sp.$numPages.'"');
        $next = href($next, _soo_page_numbers_uri($pg + 1), ' title="'.gTxt('next').'"' );
    }
    elseif (! $showalways) $last = $next = '';
            
    $out = str_replace(
        array('{prev}', '{next}', '{first}', '{last}', '{current}', '{total}'),
        array($prev, $next, $first, $last, $pg, $numPages), $format);
    return $wraptag ? tag($out, $wraptag) : $out;
}

function soo_prev_page ($atts)
{
    if (isset($atts['text'])) {
        $atts['prev'] = $atts['text'];
        unset($atts['text']);
    }
    $atts['format'] = '{prev}';
    return soo_page_count($atts);
}

function soo_next_page ($atts)
{
    if (isset($atts['text'])) {
        $atts['next'] = $atts['text'];
        unset($atts['text']);
    }
    $atts['format'] = '{next}';
    return soo_page_count($atts);
}

function _soo_page_numbers_secondpass ($func, $atts)
{
// in case $func's associated tag comes before an article tag, 
// this runs the tag again during textpattern()'s second parse() pass
    global $pretext;
    if ($pretext['secondpass']) return; // you only live twice
    foreach ($atts as $k => $v)
        $a[] = $k . '="' . $v . '" ';
    return "<txp:$func ".(isset($a) ? implode('', $a) : '' ).'/>';
}

function _soo_page_numbers_uri($pg)
{
    static $baseUri, $qParams;
    
    if (empty($baseUri)) {
        $baseUri = preg_replace ('%(.+)\?.+%', '$1', $_SERVER['REQUEST_URI']);
        $qParams = array();
        parse_str($_SERVER['QUERY_STRING'], $qParams);
        unset($qParams['p']);
    }
    
    if ($pg > 1) {
        $qParams['pg'] = $pg;
    } else {
        unset($qParams['pg']);
    }
    if (empty($qParams)) {
        return $baseUri;
    }
    return $baseUri.'?'.http_build_query($qParams, '', '&amp;');
}

# --- END PLUGIN CODE ---

?>
