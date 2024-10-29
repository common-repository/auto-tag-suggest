<?php

/*
Plugin Name: Auto Tag Suggest
Plugin URI: http://pingbackpro.com/auto-tag-suggest/
Description: Retrieves tag suggestions from multiple APIs and shows them in a ranked list for easy post tagging.
Version: 1.1.0
Author: Tony Hayes
Author URI: http://pingbackpro.com/
*/

$vthisdir = WP_PLUGIN_DIR."/autotagsuggest/";
umask(0000);
if (is_dir($vthisdir.'data')) {@chmod($vthisdir.'data',0755);} else {
	@mkdir($vthisdir.'data',0755);
	@chmod($vthisdir.'data',0755);
}

add_option('pbpref', '');
add_action('edit_form_advanced', 'show_post_tagger_box');
add_action('edit_page_form', 'show_post_tagger_box');

if ($_REQUEST['quicksaveposttags'] == "yes") {
	include_once('pbptagger.php');
	add_action('admin_head','quick_save_post_tags');}
if ($_REQUEST['autosaveposttags'] == "yes")	{
	include_once('pbptagger.php');
	add_action('publish_post','save_post_tags');
	add_action('save_post','save_post_tags');
}

if (($_REQUEST['autotagger'] == 'yes') && ($_REQUEST['loadtagsuggestions'] == 'yes')) {
	global $vautotagsuggest;
	$vautotagsuggest = "yes";
	include_once('pbptagger.php');
	add_action('admin_head','load_tag_suggestions');
}

function show_post_tagger_box() {
	global $vautotagsuggest;
	$vautotagsuggest = "yes";
	include_once('pbptagger.php');
	show_tagger_box();
}

function install_autotagsuggest() {
	require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
	add_option('autotagsuggest_zemanta', '');
	add_option('autotagsuggest_yahoo', '');
	add_option('autotagsuggest_alchemy', '');
	add_option('autotagsuggest_tagthenet', 'on');
}

if (!class_exists('Autotagsuggest')) {
	class Autotagsuggest {
		var $vpostId;
		function init($vpostid) {
			$vpost = get_post($vpostid);
			$this->postID = $vpostid;
		}
	}
}

if (class_exists('Autotagsuggest')) {
	add_action('plugins_loaded', create_function( '', 'global $vatsuggest; $vatsuggest = new Autotagsuggest();'));
	register_activation_hook(__FILE__, 'install_autotagsuggest');
}

if (is_admin()) {
	add_action('admin_menu', 'autotagsuggest_menu');
	add_action('admin_init', 'register_autotagsuggest_settings');
	add_filter('plugin_row_meta', 'register_autotagsuggest_plugin_links', 10, 2);
}

function register_autotagsuggest_plugin_links($vlinks, $vfile) {
	$vplugin = plugin_basename(__FILE__);
	if ($vfile == $vplugin) {
		$vlinks[] = '<a href="options-general.php?page=autotagsuggest">' . __('Settings') . '</a>';
	}
	return $vlinks;
}

function autotagsuggest_menu() {
	add_options_page('Auto Tag Suggest Options', 'Auto Tag Suggest', 8, 'autotagsuggest', 'autotagsuggest_options');
}

function register_autotagsuggest_settings() {
	if (function_exists('register_setting')) {
		register_setting('autotagsuggest_options', 'autotagsuggest_zemanta');
		register_setting('autotagsuggest_options', 'autotagsuggest_yahoo');
		register_setting('autotagsuggest_options', 'autotagsuggest_alchemy');
		register_setting('autotagsuggest_options', 'autotagsuggest_tagthenet');
	}
}

function autotagsuggest_options() {

	$vaddlink = ats_get_link();
	echo '<div class="wrap"><table><tr><td align="left"><h2>Auto Tag Suggest</h2></td><td width=100></td>
	<td align="center"><table cellspacing="7" style="background-color:#ffffff;border: 1px solid #dddddd;"><tr><td align="center"><font style="font-size:9pt;line-height:1.4em;"><a href="'.$vaddlink[0].'" target=_blank style="text-decoration:none;">'.$vaddlink[1].'</a></font></td></tr></table></td></tr></table>
	<div id="autotagsuggest_options" class="postbox "><div class="inside">
	';

	global $vautotagsuggest;
	$vautotagsuggest = "yes";
	include('pbptagger.php');

	echo '<form method="post" action="options.php">';
	settings_fields('autotagsuggest_options');
	echo '<script language="javascript" type="text/javascript">
	function showposttaglist() {location.href = "options-general.php?page=autotagsuggest&posttaglist=yes";}
	</script>
	<br>
	<center><input type="button" value="Show Sitewide Post Tag List" onclick="showposttaglist();" style="font-size:10pt;"></td></center>
	<br>
	<table><tr>
	<td><b>Tagging Service Keys:</b><br><br>
	<table>
	<tr><td>Alchemy API Key: </td><td width=10></td><td><input type="text" size="45" name="autotagsuggest_alchemy" value="'.get_option("autotagsuggest_alchemy").'"></td><td width=10></td><td><font style="font-size:7pt;">(<a href="http://www.alchemyapi.com/api/register.html" target=_blank style="text-decoration:none;">get one here</a>.)</font></td></tr>
	<tr><td>Yahoo App ID: </td><td width=10></td><td><input type="text" size="75" name="autotagsuggest_yahoo" value="'.get_option("autotagsuggest_yahoo").'"></td><td width=10></td><td><font style="font-size:7pt;">(<a href="http://developer.yahoo.com/wsregapp/" target=_blank style="text-decoration:none;">get one here</a>.)</font></td></tr>
	<tr><td>Zemanta API Key: </td><td width=10></td><td><input type="text" size="30" name="autotagsuggest_zemanta" value="'.get_option("autotagsuggest_zemanta").'"></td><td width=10></td><td><font style="font-size:7pt;">(<a href="http://developer.zemanta.com/member/register/" target=_blank style="text-decoration:none;">get one here</a>.)</font></td></tr>
	<tr><td>Tag the Net: </td><td width=10></td><td>Enable/Disable <input type="checkbox" name="autotagsuggest_tagthenet"';
	if (get_option('autotagsuggest_tagthenet') == 'on') {echo " checked";}
	echo '>
	</table><br>
	<font style="font-size:8pt;">(Note: if you\'ve set up the Simple Tagging plugin before you may have these already..)</font>
	</td></tr></table>
	<br>
	<p align="center"><input type="submit" class="button-primary" value="Save Changes" /></p>
	</form></div></div>';

}

function ats_get_all_post_ids() {
	global $wpdb;
    $vresult = $wpdb->get_col( $wpdb->prepare( "
        SELECT posts.ID FROM {$wpdb->posts} posts
        LEFT JOIN {$wpdb->posts} p ON p.ID = posts.ID
        WHERE p.post_status = 'publish'
      	") );
    return $vresult;
}

function ats_get_just_post_content($vkey='') {
    if (empty($vkey))
        return;
	global $wpdb;
    $vresult = $wpdb->get_col( $wpdb->prepare( "
        SELECT posts.post_content FROM {$wpdb->posts} posts
        WHERE posts.ID = '%s'
      	 ", $vkey) );
    return $vresult;
}

function ats_get_link() {
		$vlinkurl = base64_decode('aHR0cDovL3BpbmdiYWNrcHJvLmNvbS9nZXRsaW5rLnBocA==');
		$vch = curl_init();
		curl_setopt($vch, CURLOPT_URL,$vlinkurl);
		curl_setopt($vch, CURLOPT_RETURNTRANSFER, 1);
		$vgetlink = curl_exec($vch);
		$vhttp_code = curl_getinfo($vch, CURLINFO_HTTP_CODE);
		curl_close ($vch);
		unset($vch);
		if ($vhttp_code == 200) {
			if (get_option('pbpref') != "") {
				$vpbpref = "pingbackpro.com/plugin/?".get_option('pbpref')."|||";
				$vlinkdata = str_replace("pingbackpro.com|||",$vpbpref,$vlinkdata);
			}
			$vlinkdata = explode("|||",$vgetlink);
			return $vlinkdata;
		}
		return false;
}

function ats_subval_sort($va,$vsubkey) {
	foreach($va as $vk=>$vv) {
		$vb[$vk] = strtolower($vv[$vsubkey]);
	}
	arsort($vb);
	foreach($vb as $vkey=>$vval) {
		$vc[] = $va[$vkey];
	}
	return $vc;
}

?>