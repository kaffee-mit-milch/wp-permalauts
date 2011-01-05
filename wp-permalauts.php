<?php
/*
Plugin Name: WP-Permalauts
Plugin URI: http://permalauts.de/
Description: This plugin transforms the german umlauts into well-formed entities (needed ONLY for permalinks). It's based on o42-clean-umlauts.
Version: 0.5.0.304
Author: Christoph Grabo
Author URI: http://blogcraft.de/

This plugin transforms the german umlauts into well-formed entities (needed ONLY for permalinks). It's based on o42-clean-umlauts.

Replaces german umlauts in permalinks only! (All other sanitizing actions should be done natively by wordpress).

*/

$WPL_VERSION = "0.5.0.304";

#helper
function u8e($c){
	return utf8_encode($c);
} #u8e
function u8d($c){
	return utf8_decode($c);
} #u8d
					
$wpl_chartable = array(
	'raw'	  => array('ä'     ,'Ä'     ,'ö'     ,'Ö'     ,'ü'     ,'Ü'     ,'ß'      ),
	'in'	  => array(chr(228),chr(196),chr(246),chr(214),chr(252),chr(220),chr(223) ),
	'perma'	=> array('ae'    ,'Ae'    ,'oe'    ,'Oe'    ,'ue'    ,'Ue'    ,'ss'     ),
	'post'	=> array('&auml;','&Auml;','&ouml;','&Ouml;','&uuml;','&Uuml;','&szlig;'),
	'feed'	=> array('&#228;','&#196;','&#246;','&#214;','&#252;','&#220;','&#223;' ),
	'utf8'	=> array(u8e('ä'),u8e('Ä'),u8e('ö'),u8e('Ö'),u8e('ü'),u8e('Ü'),u8e('ß') )
); #chartable

# the dashes won't be converted so far, but leave them here as an example

function wpl_permalink($slug){
	global $wpl_chartable;
	
    if (seems_utf8($slug)) {
	$invalid_latin_chars = array(
		chr(197).chr(146) => 'OE',
		chr(197).chr(147) => 'oe',
		chr(197).chr(160) => 'S',
		chr(197).chr(189) => 'Z',
		chr(197).chr(161) => 's',
		chr(197).chr(190) => 'z',
		chr(226).chr(130).chr(172) => 'E');
	$slug = u8d(strtr($slug, $invalid_latin_chars));
    }
    
    $slug = str_replace($wpl_chartable['raw'], $wpl_chartable['perma'], $slug);
    $slug = str_replace($wpl_chartable['in'], $wpl_chartable['perma'], $slug);
    
	$slug = sanitize_title_with_dashes($slug);
	
    return $slug;
} #wpl_permalink

function wpl_content($content){
	global $wpl_chartable;
	
    if (strtoupper(get_option('blog_charset')) == 'UTF-8') {
	$content = str_replace($wpl_chartable['utf8'], $wpl_chartable['feed'], $content);
    }
	
    $content = str_replace($wpl_chartable['raw'], $wpl_chartable['feed'], $content);
    $content = str_replace($wpl_chartable['in'], $wpl_chartable['feed'], $content);

    return $content;
} #wpl_content

function wpl_footer(){
	$s = '<div id="wplfooter">This blog uses <a href="http://permalauts.de/">WP Permalauts</a> (clean german umlauts in permalinks) developed by <strong><a href="http://blogcraft.de/">blogcraft</a></strong>.</div>';
	echo $s;
}

function wpl_header_admin(){
	$s  = '<style type="text/css">';
	$s .= '  #wplfooter { text-align: center; } #wplfooter-preview { padding: 10px; background:#ccc; }';
	$s .= '  .wpl_admin_footer_info {border-top: 1px dotted #666;background: #ffcc66;padding: 10px;margin-top: 5px;text-align: center;}';
	$s .= '  .wpl_admin_footer_info a {color: #001133;}';
	$s .= '</style>';

  
	echo $s;
}

function wpl_header(){
	$s = '<style type="text/css">#wplfooter { text-align: center; }</style>';
	echo $s;
}

function wpl_options(){
  if (!current_user_can('manage_options'))  {
    wp_die( __('You do not have sufficient permissions to access this page.') );
  };

  ?><div class="wrap">
  <h2>WP PermaLauts</h2>
  <div>
    <p>
      <strong>Important!</strong> This plugin can only modify permalinks of new items. Old permalinks will never be re-sanitized! (You have to do this manually.)
    </p>
  </div>
  <form method="post" action="options.php">
  <?php wp_nonce_field('update-options'); ?>
	<table class="form-table">
	<tr valign="top">
		<th scope="row">What should be "cleaned" (sanitized) by Permalauts?</th>
		<td>
      Permalinks of: <br />
		  <select name="wpl_what2sanitize">
				<option value="all" <?php if (get_option('wpl_what2sanitize') == 'all') print "selected"; ?>><strong>(recommended)</strong> Everything! <small>(posts, pages, categories, tags, ...)</small></option>
				<option value="postpages" <?php if (get_option('wpl_what2sanitize') == 'postpages' or get_option('wpl_what2sanitize') == '') print "selected"; ?>>Posts and Pages</option>
				<option value="categories" <?php if (get_option('wpl_what2sanitize') == 'categories') print "selected"; ?>>Categories</option>
				<option value="taxonomies" <?php if (get_option('wpl_what2sanitize') == 'taxonomies') print "selected"; ?>>Taxonomies <small>(incl. categories, tags and maybe self defined tax.)</small></option>
				<option value="nothing" <?php if (get_option('wpl_what2sanitize') == 'nothing') print "selected"; ?>>Nothing. (Why you should do that?)</option>
		  </select>
      <br />
      Default (after update or fresh installation) is <em>Permalinks of <strong>Posts and Pages</strong></em> (like in versions prior <strong>0.5.0.304</strong>).
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">Show or hide footer text of PermaLauts</th>
		<td>
		  <select name="wpl_show_footer">
				<option value="visible" <?php if (get_option('wpl_show_footer') == 'visible') print "selected"; ?>>Visible Footer</option>
				<option value="hidden" <?php if (get_option('wpl_show_footer') == 'hidden') print "selected"; ?>>Hidden Footer</option>
		  </select>
		</td>
	</tr>
	<tr><td colspan="2">
			  Footer text preview:<br />
			  <div id="wplfooter-preview">
					<?php wpl_footer(); ?>
			  </div>
	</td></tr>
	</table>
	  <input type="hidden" name="action" value="update" />
	  <input type="hidden" name="page_options" value="wpl_show_footer,wpl_what2sanitize" />
  <p class="submit">
	<input type="submit" class="button-primary" value="<?php _e('Save Changes'); ?>" />
  </p>
  </form>
  
<div class="wpl_admin_footer_info">
	Please support the developer of this plugin! 
	<strong>Make a donation via 
	<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=K7386WKAAVYWQ">Paypal</a> 
	or <a href="https://flattr.com/thing/62915/WP-PermaLauts-Wordpress-Plugin-blogcraft-de">flattr</a>. 
	Thank you!</strong> <em>&mdash;<a href="http://blogcraft.de/">Blogcrafter Chris</a></em>
</div>

  
  </div><?php
  ;
}

function wpl_options_menu(){
	$plugin_page = add_options_page( 'WP PermaLauts', 'WP PermaLauts', 8, __FILE__, 'wpl_options');
	add_action( 'admin_head-'. $plugin_page, 'wpl_header_admin' );
}
add_action('admin_menu', 'wpl_options_menu');


/**
 * Add Settings link to plugins - code from GD Star Ratings (Thanks!)
 */
function add_settings_link($links, $file) {
  static $this_plugin;
  if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);
  if ($file == $this_plugin){
    $settings_link = '<strong><a href="options-general.php?page=wp-permalauts/wp-permalauts.php"  style="color:#093;">'.__("Settings").'</a></strong>';
     array_unshift($links, $settings_link);
  }
  return $links;
}
add_filter('plugin_action_links', 'add_settings_link', 10, 2 );

// activate only the desired filters
$wpl_what2sanitize = "" + get_option('wpl_what2sanitize');
switch($wpl_what2sanitize) {
  case "nothing":
    // do nothing ...
    break;
  case "all":
    remove_filter( 'sanitize_title',    'sanitize_title_with_dashes' );
    add_filter(    'sanitize_title',    'wpl_permalink'              );
    remove_filter( 'sanitize_term',     'sanitize_title_with_dashes' );
    add_filter(    'sanitize_term',     'wpl_permalink'              );
    break;
  case "taxonomies":
    remove_filter( 'sanitize_term',     'sanitize_title_with_dashes' );
    add_filter(    'sanitize_term',     'wpl_permalink'              );
    break;
  case "categories":
    remove_filter( 'sanitize_category', 'sanitize_title_with_dashes' );
    add_filter(    'sanitize_category', 'wpl_permalink'              );
    break;
  case "postpages": /* default of versions prior 0.5 */
  default:
    remove_filter( 'sanitize_title',    'sanitize_title_with_dashes' );
    add_filter(    'sanitize_title',    'wpl_permalink'              );
};

if(get_option("wpl_show_footer") == "visible") {
	add_action('wp_head', 'wpl_header');
	add_action('wp_footer', 'wpl_footer',99);
}

/*wpl-eof*/ ?>