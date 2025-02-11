<?php
/*
Plugin Name: Inhalts-Passwortschutz
Plugin URI: https://n3rds.work/piestingtal_source/inhalt-passwortschutz-plugin/
Description: Ermöglicht es Dir, ausgewählte Inhalte innerhalb eines Beitrags oder einer Seite mit einem Passwort zu schützen, während der Rest der Inhalte öffentlich bleibt.
Author: WMS N@W
Version: 1.2.4
Author URI: http://n3rds.work/
Textdomain: psc
*/

/* 
Copyright 2014-2021 WMS N@W (https://n3rds.work)
Author - DerN3rd

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

require 'psource/psource-plugin-update/plugin-update-checker.php';
$MyUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://n3rds.work//wp-update-server/?action=get_metadata&slug=protect-selected-content', 
	__FILE__, 
	'protect-selected-content' 
);


class PartialPostPassword {

  function __construct() {

    //shortcodes
    add_shortcode( 'protect', array(&$this, 'shortcode') );
    
    //localize the plugin
	  add_action( 'plugins_loaded', array(&$this, 'localization') );

		//handle cookie
		add_action( 'wp_ajax_nopriv_psc-set', array(&$this, 'set_password') );
    add_action( 'wp_ajax_psc-set', array(&$this, 'set_password') );
    
    // TinyMCE options
		add_action( 'wp_ajax_protectTinymceOptions', array(&$this, 'tinymce_options') );
    add_action( 'admin_init', array(&$this, 'load_tinymce') );

	  //load dashboard notice
	  //include_once( 'dash-notice/wpmudev-dash-notification.php' );
  }

  function localization() {
    // Load up the localization file if we're using WordPress in a different language
  	// Place it in this plugin's "languages" folder and name it "psc-[value in wp-config].mo"
	  load_plugin_textdomain('psc', false, dirname(plugin_basename(__FILE__)) . '/languages');
  }

  function shortcode( $atts, $content = null ) {
    extract( shortcode_atts( array(
      'password' => false
  	), $atts ) );

		//skip check for no content
    if ( is_null( $content ) )
      return;

		//if no pass set don't protect
		if ( !$password )
    	return do_shortcode( $content );
    	
		//check cookie for password
		if ( isset( $_COOKIE['psc-postpass_' . COOKIEHASH] ) && $_COOKIE['psc-postpass_' . COOKIEHASH] == sha1( $password ) ) {
   		return do_shortcode( $content );
		} else {
		  $label = 'pwbox-' . rand();
			return '<form action="' . admin_url('admin-ajax.php') . '" method="post"><input type="hidden" name="action" value="psc-set" />
			<p>' . __("Dieser Inhalt ist passwortgeschützt. Um es anzuzeigen, gib bitte unten Dein Passwort ein:", 'psc') . '</p>
			<p><label for="' . $label . '">' . __("Passwort:", 'psc') . ' <input name="post_password" id="' . $label . '" type="password" size="20" /></label> <input type="submit" name="Submit" value="' . esc_attr__("Entsperren", 'psc') . '" /></p>
			</form>
			';
		}
  }

	function set_password() {
	
	  if ( get_magic_quotes_gpc() )
			$_POST['post_password'] = stripslashes( $_POST['post_password'] );
	
	  //set cookie for 10 days
    setcookie( 'psc-postpass_' . COOKIEHASH, sha1( $_POST['post_password'] ), time() + 864000, COOKIEPATH );
    
		//jump back to post
		wp_safe_redirect( wp_get_referer() );
		exit;
	}
	
	function load_tinymce() {
    if ( (current_user_can('edit_posts') || current_user_can('edit_pages')) && get_user_option('rich_editing') == 'true') {
   		add_filter( 'mce_external_plugins', array(&$this, 'tinymce_add_plugin') );
			add_filter( 'mce_buttons', array(&$this,'tinymce_register_button') );
			add_filter( 'mce_external_languages', array(&$this,'tinymce_load_langs') );
		}
	}
	
		/**
	 * TinyMCE dialog content
	 */
	function tinymce_options() {
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
		<html>
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <?php
	                /**
	                 * TODO: We are including our own copy of tiny_mce_popup.js until this issue is resolved in the core file: https://core.trac.wordpress.org/ticket/41124
	                 */
                ?>
                <script type="text/javascript"
                        src="<?php echo plugins_url(dirname(plugin_basename(__FILE__)) . '/tinymce/tiny_mce_popup.js'); ?>">
                </script>
				<script type="text/javascript" src="../wp-includes/js/tinymce/utils/form_utils.js?ver=327-1235"></script>
				<script type="text/javascript" src="../wp-includes/js/tinymce/utils/editable_selects.js?ver=327-1235"></script>

				<script type="text/javascript" src="../wp-includes/js/jquery/jquery.js"></script>

				<script type="text/javascript">

          tinyMCEPopup.storeSelection();
          
					var insertProtect = function (ed) {
						var password = jQuery.trim(jQuery('#psc-password').val());
						if (!password) {
              jQuery('#psc-error').show();
              jQuery('#psc-password').focus();
              return false;
						}
						tinyMCEPopup.restoreSelection();
						output = '[protect password="'+password+'"]'+tinyMCEPopup.editor.selection.getContent()+'[/protect]';

						tinyMCEPopup.execCommand('mceInsertContent', 0, output);
						tinyMCEPopup.editor.execCommand('mceRepaint');
            tinyMCEPopup.editor.focus();
						// Return
						tinyMCEPopup.close();
					};
				</script>
				<style type="text/css">
				td.info {
					vertical-align: top;
					color: #777;
				}
				</style>

				<title><?php _e("Inhalt mit Passwort schützen", 'psc'); ?></title>
			</head>
			<body style="display: none">
				<form onsubmit="insertProtect();return false;" action="#">

					<div id="general_panel" class="panel current">
						<div id="psc-error" style="display: none;color:#C00;padding: 2px 0;"><?php _e("Bitte Passwort eingeben!", 'psc'); ?></div>
							<fieldset>
						  <table border="0" cellpadding="4" cellspacing="0">
								<tr>
									<td><label for="chat_width"><?php _e("Passwort", 'psc'); ?></label></td>
									<td>
										<input type="text" id="psc-password" name="psc-password" value="" class="size" size="15" />
									</td>
									<td class="info"><?php _e("Gib ein Passwort ein, das auf den ausgewählten Inhalt angewendet werden soll.", 'psc'); ?></td>
								</tr>
							</table>
						</fieldset>
					</div>

					<div class="mceActionPanel">
						<div style="float: left">
							<input type="button" id="cancel" name="cancel" value="<?php _e("Abbrechen", 'psc'); ?>" onclick="tinyMCEPopup.close();" />
						</div>

						<div style="float: right">
							<input type="submit" id="insert" name="insert" value="<?php _e("Einfügen", 'psc'); ?>" />
						</div>
					</div>
				</form>
			</body>
		</html>
		<?php
		exit(0);
	}
	
	/**
	 * @see		http://codex.wordpress.org/TinyMCE_Custom_Buttons
	 */
	function tinymce_register_button($buttons) {
		array_push($buttons, "separator", "protect");
		return $buttons;
	}

	/**
	 * @see		http://codex.wordpress.org/TinyMCE_Custom_Buttons
	 */
	function tinymce_load_langs($langs) {
		$langs["protect"] = plugins_url(dirname(plugin_basename(__FILE__)) . '/tinymce/langs/langs.php');
		return $langs;
	}

	/**
	 * @see		http://codex.wordpress.org/TinyMCE_Custom_Buttons
	 */
	function tinymce_add_plugin($plugin_array) {
		$plugin_array['protect'] = plugins_url(dirname(plugin_basename(__FILE__)) . '/tinymce/editor_plugin.js');
		return $plugin_array;
	}
	
} //end class

//load class
$psc = new PartialPostPassword();