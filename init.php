<?php

if(!function_exists('add_action')){
	echo 'You are not allowed to access this page directly.';
	exit;
}

define('LOGINGUARD_VERSION', '1.0.0');
define('LOGINGUARD_DIR', WP_PLUGIN_DIR.'/'.basename(dirname(LOGINGUARD_FILE)));
define('LOGINGUARD_URL', plugins_url('', LOGINGUARD_FILE));
define('LOGINGUARD_PRO_URL', 'https://dailyguard.io/plugins/loginguard');
define('LOGINGUARD_DOCS', 'https://loginguard.com/wiki/');

   ini_set('display_errors', 1); 

   
 require_once  dirname(__FILE__)."/vendor/autoload.php";
include_once(LOGINGUARD_DIR.'/functions.php');

loginguard_get_country();



// Ok so we are now ready to go
register_activation_hook(LOGINGUARD_FILE, 'loginguard_activation');

// Is called when the ADMIN enables the plugin
function loginguard_activation(){

	global $wpdb;

	$sql = array();
	
	$sql[] = "DROP TABLE IF EXISTS `".$wpdb->prefix."loginguard_logs`";
	
	$sql[] = "CREATE TABLE `".$wpdb->prefix."loginguard_logs` (
				`username` varchar(255) NOT NULL DEFAULT '',
				`time` int(10) NOT NULL DEFAULT '0',
				`count` int(10) NOT NULL DEFAULT '0',
				`lockout` int(10) NOT NULL DEFAULT '0',
				`ip` varchar(255) NOT NULL DEFAULT '',
				UNIQUE KEY `ip` (`ip`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

	foreach($sql as $sk => $sv){
		$wpdb->query($sv);
	}
	
	add_option('loginguard_version', LOGINGUARD_VERSION);
	add_option('loginguard_options', array());
	add_option('loginguard_last_reset', 0);
	add_option('loginguard_whitelist', array());
	add_option('loginguard_blacklist', array());

}

// Checks if we are to update ?
function loginguard_update_check(){

global $wpdb;

	$sql = array();
	$current_version = get_option('loginguard_version');
	
	// It must be the 1.0 pre stuff
	if(empty($current_version)){
		$current_version = get_option('loginguard_version');
	}
	
	$version = (int) str_replace('.', '', $current_version);
	
	// No update required
	if($current_version == LOGINGUARD_VERSION){
		return true;
	}
	
	// Is it first run ?
	if(empty($current_version)){
		
		// Reinstall
		loginguard_activation();
		
		// Trick the following if conditions to not run
		$version = (int) str_replace('.', '', LOGINGUARD_VERSION);
		
	}
	
//	// Is it less than 1.0.1 ?
//	if($version < 101){
//		
//		// TODO : GET the existing settings
//	
//		// Get the existing settings		
//		$loginguard_failed_logs = loginguard_selectquery("SELECT * FROM `".$wpdb->prefix."loginguard_failed_logs`;", 1);
//		$loginguard_options = loginguard_selectquery("SELECT * FROM `".$wpdb->prefix."loginguard_options`;", 1);
//		$loginguard_iprange = loginguard_selectquery("SELECT * FROM `".$wpdb->prefix."loginguard_iprange`;", 1);
//				
//		// Delete the three tables
//		$sql = array();
//		$sql[] = "DROP TABLE IF EXISTS ".$wpdb->prefix."loginguard_failed_logs;";
//		$sql[] = "DROP TABLE IF EXISTS ".$wpdb->prefix."loginguard_options;";
//		$sql[] = "DROP TABLE IF EXISTS ".$wpdb->prefix."loginguard_iprange;";
//
//		foreach($sql as $sk => $sv){
//			$wpdb->query($sv);
//		}
//		
//		// Delete option
//		delete_option('loginguard_version');
//	
//		// Reinstall
//		loginguard_activation();
//	
//		// TODO : Save the existing settings
//
//		// Update the existing failed logs to new table
//		if(is_array($loginguard_failed_logs)){
//			foreach($loginguard_failed_logs as $fk => $fv){
//				$wpdb->query("INSERT INTO ".$wpdb->prefix."loginguard_logs SET `username` = '".$fv['username']."', `time` = '".$fv['time']."', `count` = '".$fv['count']."', `lockout` = '".$fv['lockout']."', `ip` = '".$fv['ip']."';");
//			}			
//		}
//
//		// Update the existing options to new structure
//		if(is_array($loginguard_options)){
//			foreach($loginguard_options as $ok => $ov){
//				
//				if($ov['option_name'] == 'loginguard_last_reset'){
//					update_option('loginguard_last_reset', $ov['option_value']);
//					continue;
//				}
//				
//				$old_option[str_replace('loginguard_', '', $ov['option_name'])] = $ov['option_value'];
//			}
//			// Save the options
//			update_option('loginguard_options', $old_option);
//		}
//
//		// Update the existing iprange to new structure
//		if(is_array($loginguard_iprange)){
//			
//			$old_blacklist = array();
//			$old_whitelist = array();
//			$bid = 1;
//			$wid = 1;
//			foreach($loginguard_iprange as $ik => $iv){
//				
//				if(!empty($iv['blacklist'])){
//					$old_blacklist[$bid] = array();
//					$old_blacklist[$bid]['start'] = long2ip($iv['start']);
//					$old_blacklist[$bid]['end'] = long2ip($iv['end']);
//					$old_blacklist[$bid]['time'] = strtotime($iv['date']);
//					$bid = $bid + 1;
//				}
//				
//				if(!empty($iv['whitelist'])){
//					$old_whitelist[$wid] = array();
//					$old_whitelist[$wid]['start'] = long2ip($iv['start']);
//					$old_whitelist[$wid]['end'] = long2ip($iv['end']);
//					$old_whitelist[$wid]['time'] = strtotime($iv['date']);
//					$wid = $wid + 1;
//				}
//			}
//			
//			if(!empty($old_blacklist)) update_option('loginguard_blacklist', $old_blacklist);
//			if(!empty($old_whitelist)) update_option('loginguard_whitelist', $old_whitelist);
//		}
//		
//	}
//	
	// Save the new Version
	update_option('loginguard_version', LOGINGUARD_VERSION);
	
}

// Add the action to load the plugin 
add_action('plugins_loaded', 'loginguard_load_plugin');

// The function that will be called when the plugin is loaded
function loginguard_load_plugin(){
	
	global $loginguard;
	
	// Check if the installed version is outdated
	loginguard_update_check();
	
	// Set the array
	$loginguard = array();
	
	// The IP Method to use
	$loginguard['ip_method'] = get_option('loginguard_ip_method');
	
	// Load settings
	$options = get_option('loginguard_options');
	$loginguard['max_retries'] = empty($options['max_retries']) ? 3 : $options['max_retries'];
	$loginguard['lockout_time'] = empty($options['lockout_time']) ? 900 : $options['lockout_time']; // 15 minutes
	$loginguard['max_lockouts'] = empty($options['max_lockouts']) ? 5 : $options['max_lockouts'];
	$loginguard['lockouts_extend'] = empty($options['lockouts_extend']) ? 86400 : $options['lockouts_extend']; // 24 hours
	$loginguard['reset_retries'] = empty($options['reset_retries']) ? 86400 : $options['reset_retries']; // 24 hours
	$loginguard['notify_email'] = empty($options['notify_email']) ? 0 : $options['notify_email'];
		
	// Load the blacklist and whitelist
	$loginguard['blacklist'] = get_option('loginguard_blacklist');
	$loginguard['whitelist'] = get_option('loginguard_whitelist');
	
	// When was the database cleared last time
	$loginguard['last_reset']  = get_option('loginguard_last_reset');
	
	//print_r($loginguard);
	
	// Clear retries
	if((time() - $loginguard['last_reset']) >= $loginguard['reset_retries']){
		loginguard_reset_retries();
	}
	
	$ins_time = get_option('loginguard_ins_time');
	if(empty($ins_time)){
		$ins_time = time();
		update_option('loginguard_ins_time', $ins_time);
	}
	$loginguard['ins_time'] = $ins_time;
	
	// Set the current IP
	$loginguard['current_ip'] = loginguard_getip();

	/* Filters and actions */
	
	// Use this to verify before WP tries to login
	// Is always called and is the first function to be called
	//add_action('wp_authenticate', 'loginguard_wp_authenticate', 10, 2);// Not called by XML-RPC
	add_filter('authenticate', 'loginguard_wp_authenticate', 10001, 3);// This one is called by xmlrpc as well as GUI
	
	// Is called when a login attempt fails
	// Hence Update our records that the login failed
	add_action('wp_login_failed', 'loginguard_login_failed');
	
	// Is called before displaying the error message so that we dont show that the username is wrong or the password
	// Update Error message
	add_action('wp_login_errors', 'loginguard_error_handler', 10001, 2);
	
	
        
		
		// The promo time
		$loginguard['promo_time'] = get_option('loginguard_promo_time');
		if(empty($loginguard['promo_time'])){
			$loginguard['promo_time'] = time();
			update_option('loginguard_promo_time', $loginguard['promo_time']);
		}
		
		// Are we to show the loginguard promo
		if(!empty($loginguard['promo_time']) && $loginguard['promo_time'] > 0 && $loginguard['promo_time'] < (time() - (30*24*3600))){
		
			add_action('admin_notices', 'loginguard_promo');
		
		}
		
		// Are we to disable the promo
		if(isset($_GET['loginguard_promo']) && (int)$_GET['loginguard_promo'] == 0){
			update_option('loginguard_promo_time', (0 - time()) );
			die('DONE');
		}
		
	

}

// Show the promo
function loginguard_promo(){
	
	echo '
<style>
.loginguard_button {
background-color: #4CAF50; /* Green */
border: none;
color: white;
padding: 8px 16px;
text-align: center;
text-decoration: none;
display: inline-block;
font-size: 16px;
margin: 4px 2px;
-webkit-transition-duration: 0.4s; /* Safari */
transition-duration: 0.4s;
cursor: pointer;
}

.loginguard_button:focus{
border: none;
color: white;
}

.loginguard_button1 {
color: white;
background-color: #4CAF50;
border:3px solid #4CAF50;
}

.loginguard_button1:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
border:3px solid #4CAF50;
}

.loginguard_button2 {
color: white;
background-color: #0085ba;
}

.loginguard_button2:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
}

.loginguard_button3 {
color: white;
background-color: #365899;
}

.loginguard_button3:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
}

.loginguard_button4 {
color: white;
background-color: rgb(66, 184, 221);
}

.loginguard_button4:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
}

.loginguard_promo-close{
float:right;
text-decoration:none;
margin: 5px 10px 0px 0px;
}

.loginguard_promo-close:hover{
color: red;
}
</style>	

<script>
jQuery(document).ready( function() {
	(function($) {
		$("#loginguard_promo .loginguard_promo-close").click(function(){
			var data;
			
			// Hide it
			$("#loginguard_promo").hide();
			
			// Save this preference
			$.post("'.admin_url('?loginguard_promo=0').'", data, function(response) {
				//alert(response);
			});
		});
	})(jQuery);
});
</script>

<div class="notice notice-success" id="loginguard_promo" style="min-height:120px">
	<a class="loginguard_promo-close" href="javascript:" aria-label="Dismiss this Notice">
		<span class="dashicons dashicons-dismiss"></span> Dismiss
	</a>
	<img src="'.LOGINGUARD_URL.'/loginguard-200.png" style="float:left; margin:10px 20px 10px 10px" width="100" />
	<p style="font-size:16px">We are glad you like Loginguard and have been using it since the past few days. It is time to take the next step </p>
	<p>
		<a class="loginguard_button loginguard_button1" target="_blank" href="https://dailyguard.io">Dailyguard.io</a>
		<a class="loginguard_button loginguard_button2" target="_blank" href="https://wordpress.org/support/view/plugin-reviews/loginguard">Rate it 5★\'s</a>
		<a class="loginguard_button loginguard_button3" target="_blank" href="https://www.facebook.com/Loginguard-815504798591884/">Like Us on Facebook</a>
		<a class="loginguard_button loginguard_button4" target="_blank" href="https://twitter.com/home?status='.rawurlencode('I use @dailyguard_io LoginGuard  to secure my #WordPress site https://dailyguard.io').'">Tweet about Loginguard</a>
	</p>
</div>';

}

// Should return NULL if everything is fine
function loginguard_wp_authenticate($user, $username, $password){
	
	global $loginguard, $loginguard_error, $loginguard_cannot_login, $loginguard_user_pass;
	
	if(!empty($username) && !empty($password)){
		$loginguard_user_pass = 1;
	}
	
	// Are you whitelisted ?
	if(loginguard_is_whitelisted()){
		$loginguard['ip_is_whitelisted'] = 1;
		return $user;
	}
	
	// Are you blacklisted ?
	if(loginguard_is_blacklisted()){
		$loginguard_cannot_login = 1;
		return new WP_Error('ip_blacklisted', implode('', $loginguard_error), 'loginguard');
	}
	
	// Is the username blacklisted ?
	if(function_exists('loginguard_user_blacklisted')){
		if(loginguard_user_blacklisted($username)){
			$loginguard_cannot_login = 1;
			return new WP_Error('user_blacklisted', implode('', $loginguard_error), 'loginguard');
		}
	}
	
	if(loginguard_can_login()){
		return $user;
	}
	
	$loginguard_cannot_login = 1;
	
	return new WP_Error('ip_blocked', implode('', $loginguard_error), 'loginguard');
	
}

function loginguard_can_login(){
	
	global $wpdb, $loginguard, $loginguard_error;
	
	// Get the logs
	$result = loginguard_selectquery("SELECT * FROM `".$wpdb->prefix."loginguard_logs` WHERE `ip` = '".$loginguard['current_ip']."';");
	
	if(!empty($result['count']) && ($result['count'] % $loginguard['max_retries']) == 0){
		
		// Has he reached max lockouts ?
		if($result['lockout'] >= $loginguard['max_lockouts']){
			$loginguard['lockout_time'] = $loginguard['lockouts_extend'];
		}
		
		// Is he in the lockout time ?
		if($result['time'] >= (time() - $loginguard['lockout_time'])){
			$banlift = ceil((($result['time'] + $loginguard['lockout_time']) - time()) / 60);
			
			//echo 'Current Time '.date('m/d/Y H:i:s', time()).'<br />';
			//echo 'Last attempt '.date('m/d/Y H:i:s', $result['time']).'<br />';
			//echo 'Unlock Time '.date('m/d/Y H:i:s', $result['time'] + $loginguard['lockout_time']).'<br />';
			
			$_time = $banlift.' minute(s)';
			
			if($banlift > 60){
				$banlift = ceil($banlift / 60);
				$_time = $banlift.' hour(s)';
			}
			
			$loginguard_error['ip_blocked'] = 'You have exceeded maximum login retries<br /> Please try after '.$_time;
			
			return false;
		}
	}
	
	return true;
}

function loginguard_is_blacklisted(){
	
	global $wpdb, $loginguard, $loginguard_error;
	
	$blacklist = $loginguard['blacklist'];
			
	foreach($blacklist as $k => $v){
		
		// Is the IP in the blacklist ?
		if(ip2long($v['start']) <= ip2long($loginguard['current_ip']) && ip2long($loginguard['current_ip']) <= ip2long($v['end'])){
			$result = 1;
			break;
		}
		
		// Is it in a wider range ?
		if(ip2long($v['start']) >= 0 && ip2long($v['end']) < 0){
			
			// Since the end of the RANGE (i.e. current IP range) is beyond the +ve value of ip2long, 
			// if the current IP is <= than the start of the range, it is within the range
			// OR
			// if the current IP is <= than the end of the range, it is within the range
			if(ip2long($v['start']) <= ip2long($loginguard['current_ip'])
				|| ip2long($loginguard['current_ip']) <= ip2long($v['end'])){				
				$result = 1;
				break;
			}
			
		}
		
	}
		
	// You are blacklisted
	if(!empty($result)){
		$loginguard_error['ip_blacklisted'] = 'Your IP has been blacklisted';
		return true;
	}
	
	return false;
	
}

function loginguard_is_whitelisted(){
	
	global $wpdb, $loginguard, $loginguard_error;
	
	$whitelist = $loginguard['whitelist'];
			
	foreach($whitelist as $k => $v){
		
		// Is the IP in the blacklist ?
		if(ip2long($v['start']) <= ip2long($loginguard['current_ip']) && ip2long($loginguard['current_ip']) <= ip2long($v['end'])){
			$result = 1;
			break;
		}
		
		// Is it in a wider range ?
		if(ip2long($v['start']) >= 0 && ip2long($v['end']) < 0){
			
			// Since the end of the RANGE (i.e. current IP range) is beyond the +ve value of ip2long, 
			// if the current IP is <= than the start of the range, it is within the range
			// OR
			// if the current IP is <= than the end of the range, it is within the range
			if(ip2long($v['start']) <= ip2long($loginguard['current_ip'])
				|| ip2long($loginguard['current_ip']) <= ip2long($v['end'])){				
				$result = 1;
				break;
			}
			
		}
		
	}
		
	// You are whitelisted
	if(!empty($result)){
		return true;
	}
	
	return false;
	
}


// When the login fails, then this is called
// We need to update the database
function loginguard_login_failed($username){
	
	global $wpdb, $loginguard, $loginguard_cannot_login;

	if(empty($loginguard_cannot_login) && empty($loginguard['ip_is_whitelisted']) && empty($loginguard['no_loginguard_logs'])){
		
		$result = loginguard_selectquery("SELECT * FROM `".$wpdb->prefix."loginguard_logs` WHERE `ip` = '".$loginguard['current_ip']."';");
		
		if(!empty($result)){
			$lockout = floor((($result['count']+1) / $loginguard['max_retries']));
			$sresult = $wpdb->query("UPDATE `".$wpdb->prefix."loginguard_logs` SET `username` = '".$username."', `time` = '".time()."', `count` = `count`+1, `lockout` = '".$lockout."' WHERE `ip` = '".$loginguard['current_ip']."';");
			
			// Do we need to email admin ?
			if(!empty($loginguard['notify_email']) && $lockout >= $loginguard['notify_email']){
				
				$sitename = loginguard_is_multisite() ? get_site_option('site_name') : get_option('blogname');
				$mail = array();
				$mail['to'] = loginguard_is_multisite() ? get_site_option('admin_email') : get_option('admin_email');	
				$mail['subject'] = 'Failed Login Attempts from IP '.$loginguard['current_ip'].' ('.$sitename.')';
				$mail['message'] = 'Hi,

'.($result['count']+1).' failed login attempts and '.$lockout.' lockout(s) from IP '.$loginguard['current_ip'].'

Last Login Attempt : '.date('d/m/Y H:i:s', time()).'
Last User Attempt : '.$username.'
IP has been blocked until : '.date('d/m/Y H:i:s', time() + $loginguard['lockout_time']).'

Regards,
Loginguard';

				@wp_mail($mail['to'], $mail['subject'], $mail['message']);
			}
		}else{
			$insert = $wpdb->query("INSERT INTO `".$wpdb->prefix."loginguard_logs` SET `username` = '".$username."', `time` = '".time()."', `count` = '1', `ip` = '".$loginguard['current_ip']."', `lockout` = '0';");
		}
	
		// We need to add one as this is a failed attempt as well
		$result['count'] = $result['count'] + 1;
		$loginguard['retries_left'] = ($loginguard['max_retries'] - ($result['count'] % $loginguard['max_retries']));
		$loginguard['retries_left'] = $loginguard['retries_left'] == $loginguard['max_retries'] ? 0 : $loginguard['retries_left'];
		
	}
}

// Handles the error of the password not being there
function loginguard_error_handler($errors, $redirect_to){
	
	global $wpdb, $loginguard, $loginguard_user_pass, $loginguard_cannot_login;
	
	//echo 'loginguard_error_handler :';print_r($errors->errors);echo '<br>';
	
	// Remove the empty password error
	if(is_wp_error($errors)){
		
		$codes = $errors->get_error_codes();
		
		foreach($codes as $k => $v){
			if($v == 'invalid_username' || $v == 'incorrect_password'){
				$show_error = 1;
			}
		}
		
		$errors->remove('invalid_username');
		$errors->remove('incorrect_password');
		
	}
	
	// Add the error
	if(!empty($loginguard_user_pass) && !empty($show_error) && empty($loginguard_cannot_login)){
		$errors->add('invalid_userpass', '<b>ERROR:</b> Incorrect Username or Password');
	}
	
	// Add the number of retires left as well
	if(count($errors->get_error_codes()) > 0 && isset($loginguard['retries_left'])){
		$errors->add('retries_left', loginguard_retries_left());
	}
	
	return $errors;
	
}

// Returns a string with the number of retries left
function loginguard_retries_left(){
	
	global $wpdb, $loginguard, $loginguard_user_pass, $loginguard_cannot_login;
	
	// If we are to show the number of retries left
	if(isset($loginguard['retries_left'])){
		return '<b>'.$loginguard['retries_left'].'</b> attempt(s) left';
	}
	
}

function loginguard_reset_retries(){
	
	global $wpdb, $loginguard;
	
	$deltime = time() - $loginguard['reset_retries'];	
	$result = $wpdb->query("DELETE FROM `".$wpdb->prefix."loginguard_logs` WHERE `time` <= '".$deltime."';");
	
	update_option('loginguard_last_reset', time());
	
}

add_filter("plugin_action_links_$plugin_loginguard", 'loginguard_plugin_action_links');

// Add settings link on plugin page
function loginguard_plugin_action_links($links) {
	
	if(!defined('LOGINGUARD_PREMIUM')){
		 $links[] = '<a href="'.LOGINGUARD_PRO_URL.'" style="color:#3db634;" target="_blank">'._x('Dailyguard.io', 'Plugin action link label.', 'loginguard').'</a>';
	}

	$settings_link = '<a href="admin.php?page=loginguard">Settings</a>';	
	array_unshift($links, $settings_link); 
	
	return $links;
}

add_action('admin_menu', 'loginguard_admin_menu');

// Shows the admin menu of Loginguard
function loginguard_admin_menu() {
	
	global $wp_version, $loginguard;
	
	// Add the menu page
	add_menu_page(__('Loginguard Dashboard'), __('LoginGuard'), 'activate_plugins', 'loginguard', 'loginguard_page_dashboard');
	
	// Dashboard
	add_submenu_page('loginguard', __('LoginGuard Dashboard'), __('Dashboard'), 'activate_plugins', 'loginguard', 'loginguard_page_dashboard');
	
	// Brute Force
	add_submenu_page('loginguard', __('LoginGuard Brute Force Settings'), __('Brute Force'), 'activate_plugins', 'loginguard_brute_force', 'loginguard_page_brute_force');
	// Brute Force
	add_submenu_page('loginguard', __('LoginGuard Brute Force Settings'), __('Country Block'), 'activate_plugins', 'loginguard_country_blocks', 'loginguard_page_country_blocks');
	
	if(defined('LOGINGUARD_PREMIUM')){
	
		// PasswordLess
		add_submenu_page('loginguard', __('LoginGuard PasswordLess Settings'), __('PasswordLess'), 'activate_plugins', 'loginguard_passwordless', 'loginguard_page_passwordless');
		
		// Two Factor Auth
		add_submenu_page('loginguard', __('LoginGuard Two Factor Authentication'), __('Two Factor Auth'), 'activate_plugins', 'loginguard_2fa', 'loginguard_page_2fa');
		
		// reCaptcha
		add_submenu_page('loginguard', __('LoginGuard reCAPTCHA Settings'), __('reCAPTCHA'), 'activate_plugins', 'loginguard_recaptcha', 'loginguard_page_recaptcha');
		
		// Security Settings
		add_submenu_page('loginguard', __('LoginGuard Security Settings'), __('Security Settings'), 'activate_plugins', 'loginguard_security', 'loginguard_page_security');
		
		// Security Settings
		add_submenu_page('loginguard', __('LoginGuard File Checksums'), __('File Checksums'), 'activate_plugins', 'loginguard_checksums', 'loginguard_page_checksums');
	
	}elseif(!defined('LOGINGUARD_PREMIUM') && !empty($loginguard['ins_time']) && $loginguard['ins_time'] < (time() - (30*24*3600))){
		
		// Go Pro link
		add_submenu_page('loginguard', __('LoginGuard Go Pro'), __('Go Pro'), 'activate_plugins', LOGINGUARD_PRO_URL);
		
	}
	
}

// The Loginguard Admin Options Page
function loginguard_page_header($title = 'LoginGuard'){
	/*wp_enqueue_script('common');
	wp_enqueue_script('wp-lists');
	wp_enqueue_script('postbox');
	wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
	
	echo '
<script>
jQuery(document).ready( function() {
	//add_postbox_toggles("loginguard");
});
</script>';*/

?>
<style>
.metabox-holder .handlediv {
    display: block;
    color: #fff;
    text-shadow: 0 -1px 1px #8A0609, 1px 0 1px #8A0609, 0 1px 1px #8A0609, -1px 0 1px #8A0609;
	cursor: auto !important;
}
.metabox-holder h2.hndle {
    /* 
    background: #c7393b;
    color: #fff;
    text-shadow: 0 -1px 1px #8A0609, 1px 0 1px #8A0609, 0 1px 1px #8A0609, -1px 0 1px #8A0609; */
	cursor: auto !important;
}
.alternate, .striped>tbody>:nth-child(odd), ul.striped>:nth-child(odd) {
    background-color: #FFF9F9;
}

#loginguard-right-bar div {
    margin: 0;
}
#loginguard-right-bar a {
    text-decoration: none; 
}
#loginguard-right-bar img{
    margin-top: 10px;
	margin-bottom: 5px;
}
#loginguard-right-bar h3 {
    margin: 0;
    font-size: 16px;
	color: #8A0609;
}
.postbox-right {
    min-width: 255px;
    border: 1px solid #e5e5e5;
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.04);
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    background: #fff;
	min-width: 0px !important;
}
.postbox-right .inside {
    padding: 0 12px 12px;
    line-height: 1.4em;
    font-size: 13px;
}
#loginguard-right-bar .lz-right-ul{
	padding-left: 15px !important;
}

#loginguard-right-bar .lz-right-ul li{
	
	list-style: square !important;
	color: #8A0609;
}

#loginguard-right-bar .button-primary{
margin-left: -4px;
}
.wp-core-ui  .button-primary {
    font-size: 13px;
    padding: 0 10px 1px;
	
    border-radius: 0;
    background: #c7393b;
    border-color: #c7393b #8A0609 #8A0609;
    -webkit-box-shadow: 0 1px 0 #8A0609;
    box-shadow: 0 1px 0 #8A0609;
    color: #fff;
    text-decoration: none;
    text-shadow: 0 -1px 1px #8A0609, 1px 0 1px #8A0609, 0 1px 1px #8A0609, -1px 0 1px #8A0609;
}

.wp-core-ui .active .button {
    font-size: 13px;
    padding: 0 10px 1px;
	
    border-radius: 0;
    background: #c7393b;
    border-color: #c7393b #8A0609 #8A0609;
    -webkit-box-shadow: 0 1px 0 #8A0609;
    box-shadow: 0 1px 0 #8A0609;
    color: #fff;
    text-decoration: none;
    text-shadow: 0 -1px 1px #8A0609, 1px 0 1px #8A0609, 0 1px 1px #8A0609, -1px 0 1px #8A0609;
}


.wp-core-ui .button-primary.focus, .wp-core-ui .button-primary.hover, .wp-core-ui .button-primary:focus, .wp-core-ui .button-primary:hover {
    background: #ef4446;
    border-color: #8A0609;
    color: #fff;
}
.wp-core-ui .button-primary.active, .wp-core-ui .button-primary.active:focus, .wp-core-ui .button-primary.active:hover, .wp-core-ui .button-primary:active {
    background: #c7393b;
    border-color: #8A0609;
    -webkit-box-shadow: inset 0 2px 0 #8A0609;
    box-shadow: inset 0 2px 0 #8A0609;
    vertical-align: top;
}
.wp-core-ui .button-primary.focus, .wp-core-ui .button-primary:focus {
    -webkit-box-shadow: 0 1px 0 #c7393b, 0 0 2px 1px #c7393b;
    box-shadow: 0 1px 0 #c7393b, 0 0 2px 1px #c7393b;
}
.tablenav .tablenav-pages a:focus, .tablenav .tablenav-pages a:hover {
    border-color: #8A0609;
    color: #fff;
    background: #C7393D;
    -webkit-box-shadow: none;
    box-shadow: none;
    outline: 0;
}
.welcome-panel{
	margin: 0px;
	padding: 10px;
}


.form-table label{
	font-weight:bold;
}

.exp{
	font-size:12px;
}


/****  TABS  ****/

.lg-tabs {
    margin: 0;
}
.lg-tabs li {
    display: inline-block;
}

</style>
<?php
	
	echo '<div style="margin: 10px 20px 0 2px;">	
<div class="metabox-holder columns-2">
<div class="postbox-container">	
<div id="top-sortables" class="meta-box-sortables ui-sortable">
	
	<table cellpadding="2" cellspacing="1" width="100%" class="fixed" border="0">
		<tr>
			<td valign="top"><h1 style="color: #8A0609; padding-left: 5px;">'.$title.'</h1></td>
			<td align="right"><a target="_blank" class="button button-primary" href="https://wordpress.org/support/view/plugin-reviews/loginguard">Review Loginguard</a></td>
			<td align="right" width="40"><a target="_blank" href="https://twitter.com/dailyguard_io"><img src="'.LOGINGUARD_URL.'/twitter.png" /></a></td>
			<td align="right" width="40"><a target="_blank" href="https://www.facebook.com/DailyGuard/"><img src="'.LOGINGUARD_URL.'/facebook.png" /></a></td>
		</tr>
	</table>
	<hr />
	
	<!--Main Table-->
	<table cellpadding="8" cellspacing="1" width="100%" class="fixed">
	<tr>
		<td valign="top">';
		
}

// The Loginguard Theme footer
function loginguard_page_footer(){
	
	echo '</td>
	<td width="200" valign="top" id="loginguard-right-bar">';
	
	if(!defined('LOGINGUARD_PREMIUM')){
		
		echo '
		<div class="postbox-right" >
			<center><a href="https://dailyguard.io" target="_blank"><img src="'.LOGINGUARD_URL.'/DailyGuard-plugin-logo.png" /></a></center>
			<div class="inside">				
				<h3>Wordpress Backup and Protection Services</h3>			
				<ul class="lz-right-ul">
					<li>Daily Backup</li>
					<li>Plugin Auto-Updates</li>
					<li>Core Auto-Updates</li>
					<li>Themes Auto-Updates</li>
					<li>Daily Malware Scans</li>
					<li>Emergency Recovery</li>
					<li>Infected Website Cleanups</li>
					<li>And many more ...</li>
				</ul>
				
				<center><a class="button button-primary" href="https://dailyguard.io" target="_blank"><i>Starting only from 9.99$/Mo</i></a></center>
			</div>
		</div>';
		
	}else{
	
		echo '
		<div class="postbox" style="min-width:0px !important;">
			<h2 class="hndle ui-sortable-handle">
				<span>Recommendations</span>
			</h2>
			<div class="inside">
				<i>We recommed that you enable atleast one of the following security features</i>:<br>
				<ul class="lz-right-ul">
					<li>Rename Login Page</li>
					<li>Login Challenge Question</li>
					<li>reCAPTCHA</li>
					<li>Two Factor Auth - Email</li>
					<li>Two Factor Auth - App</li>
					<li>Change \'admin\' Username</li>
				</ul>
			</div>
		</div>';
	}
	
	echo '</td>
	</tr>
	</table>
	<br />
	<div style="width:50%;background:#FFF;padding:15px; margin: 0 10px;">
		
		
		<b>Let your friends know that you have secured your website :</b>
		<form method="get" action="http://twitter.com/intent/tweet" id="tweet" onsubmit="return dotweet(this);">
			<textarea name="text" cols="45" row="3" style="resize:none;width: 70%;">I just secured my @WordPress site against #bruteforce using @loginguard</textarea>
			&nbsp; &nbsp; <input type="submit" value="Tweet!" class="button button-primary" onsubmit="return false;" id="twitter-btn" style="margin-top:0px;"/>
		</form>
		
	</div>
	<br />
	
	<script>
	function dotweet(ele){
		window.open(jQuery("#"+ele.id).attr("action")+"?"+jQuery("#"+ele.id).serialize(), "_blank", "scrollbars=no, menubar=no, height=400, width=500, resizable=yes, toolbar=no, status=no");
		return false;
	}
	</script>
	
	<hr />
	<a href="http://loginguard.com" target="_blank">LoginGuard</a> v'.LOGINGUARD_VERSION.'. You can report any bugs <a href="http://wordpress.org/support/plugin/loginguard" target="_blank">here</a>.

</div>	
</div>
</div>
</div>';

}

// The Loginguard Admin Options Page
function loginguard_page_dashboard(){
	
	global $loginguard, $loginguard_error, $loginguard_env;

	// Is there a license key ?
	if(isset($_POST['save_lz'])){
	
		$license = loginguard_optpost('loginguard_license');
		
		// Check if its a valid license
		if(empty($license)){
			$loginguard_error['lic_invalid'] = __('The license key was not submitted', 'loginguard');
			return loginguard_page_dashboard_T();
		}
		
		$resp = wp_remote_get(LOGINGUARD_API.'license.php?license='.$license);
		
		if(is_array($resp)){
			$json = json_decode($resp['body'], true);
			//print_r($json);
		}
		
		// Save the License
		if(empty($json)){
		
			$loginguard_error['lic_invalid'] = __('The license key is invalid', 'loginguard');
			return loginguard_page_dashboard_T();
			
		}else{
			
			update_option('loginguard_license', $json);
			
			// Mark as saved
			$GLOBALS['loginguard_saved'] = true;
		}
		
	}
	
	
	// Is there a IP Method ?
	if(isset($_POST['save_loginguard_ip_method'])){
		
		$ip_method = (int) loginguard_optpost('loginguard_ip_method');
		
		if($ip_method >= 0 && $ip_method <= 2){
			update_option('loginguard_ip_method', $ip_method);
		}
		
	}
	
	loginguard_page_dashboard_T();
	
}

// The Loginguard Admin Options Page - THEME
function loginguard_page_dashboard_T(){
	
	global $loginguard, $loginguard_error, $loginguard_env;

	loginguard_page_header('LoginGuard Dashboard');
?>
	
	<?php	
	// echo '<script src="https://api.loginguard.com/'.(defined('LOGINGUARD_PREMIUM') ? 'news_security.js' : 'news.js').'"></script>';

	// Saved ?
	if(!empty($GLOBALS['loginguard_saved'])){
		echo '<div id="message" class="updated"><p>'. __('The settings were saved successfully', 'loginguard'). '</p></div><br />';
	}
	
	// Any errors ?
	if(!empty($loginguard_error)){
		loginguard_report_error($loginguard_error);echo '<br />';
	}
	
	?>	
	
	<div class="postbox">
	
		 
		
		<h2 class="hndle ui-sortable-handle" style="display: none;">
			<span><?php echo __('Getting Started', 'loginguard'); ?></span>
		</h2>
		
		<div class="inside">
		
		<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field('loginguard-options'); ?>
		<table class="form-table">
			<tr>
				<td width="25%" scope="row" valign="top">
				<?php echo '<img src="'.LOGINGUARD_URL.'/secure-agent.png" style="width: 170px;"/>';?>
				</td>
				<td width="50%" scope="row" valign="top" colspan="2" style="line-height:150%">
					
					<i>Welcome to LoginGuard Security. <br />By default the <b>Brute Force Protection</b> is immediately enabled. <br />You should start by going over the default settings and tweaking them as per your needs.</i>
					<hr />
					<a class="button button-primary" href="https://dailyguard.io/wp-admin/admin.php?page=loginguard_brute_force" >Brute Force Settings</a>
					<a class="button button-primary" href="https://dailyguard.io/wp-admin/admin.php?page=loginguard_country_blocks" >Country Block</a>
					<?php 
					if(defined('LOGINGUARD_PREMIUM')){
						echo '<br><i>In the Premium version of LoginGuard you have many more features. We recommend you enable features like <b>reCAPTCHA, Two Factor Auth or Email based PasswordLess</b> login. These features will improve your websites security.</i>';
					} 
					?>
				</td>
				<td width="25%" scope="row" valign="top">
					
				</td>
			</tr>
		</table>
		</form>
		
		</div>
	</div>
	
	
	
	

<?php
	
	loginguard_page_footer();

}

// The Loginguard Admin Options Page
function loginguard_page_brute_force(){

	global $wpdb, $wp_roles, $loginguard;
	 
	if(!current_user_can('manage_options')){
		wp_die('Sorry, but you do not have permissions to change settings.');
	}

	/* Make sure post was from this page */
	if(count($_POST) > 0){
		check_admin_referer('loginguard-options');
	}
	
	// BEGIN THEME
	loginguard_page_header('LoginGuard - Brute Force Settings');
	
	// Load the blacklist and whitelist
	$loginguard['blacklist'] = get_option('loginguard_blacklist');
	$loginguard['whitelist'] = get_option('loginguard_whitelist');
	
	if(isset($_POST['save_lz'])){
		
		$max_retries = (int) loginguard_optpost('max_retries');
		$lockout_time = (int) loginguard_optpost('lockout_time');
		$max_lockouts = (int) loginguard_optpost('max_lockouts');
		$lockouts_extend = (int) loginguard_optpost('lockouts_extend');
		$reset_retries = (int) loginguard_optpost('reset_retries');
		$notify_email = (int) loginguard_optpost('notify_email');
		
		$lockout_time = $lockout_time * 60;
		$lockouts_extend = $lockouts_extend * 60 * 60;
		$reset_retries = $reset_retries * 60 * 60;
		
		if(empty($error)){
			
			$option['max_retries'] = $max_retries;
			$option['lockout_time'] = $lockout_time;
			$option['max_lockouts'] = $max_lockouts;
			$option['lockouts_extend'] = $lockouts_extend;
			$option['reset_retries'] = $reset_retries;
			$option['notify_email'] = $notify_email;
			
			// Save the options
			update_option('loginguard_options', $option);
			
			$saved = true;
			
		}else{
			loginguard_report_error($error);
		}
	
		if(!empty($notice)){
			loginguard_report_notice($notice);	
		}
			
		if(!empty($saved)){
			echo '<div id="message" class="updated"><p>'
				. __('The settings were saved successfully', 'loginguard')
				. '</p></div><br />';
		}
	
	}
	
	// Delete a Blackist IP range
	if(isset($_GET['bdelid'])){
		
		$delid = (int) loginguard_optreq('bdelid');
		
		// Unset and save
		$blacklist = $loginguard['blacklist'];
		unset($blacklist[$delid]);
		update_option('loginguard_blacklist', $blacklist);
		
		echo '<div id="message" class="updated fade"><p>'
			. __('The Blacklist IP range has been deleted successfully', 'loginguard')
			. '</p></div><br />';
			
	}
	
	// Delete a Whitelist IP range
	if(isset($_GET['delid'])){
		
		$delid = (int) loginguard_optreq('delid');
		
		// Unset and save
		$whitelist = $loginguard['whitelist'];
		unset($whitelist[$delid]);
		update_option('loginguard_whitelist', $whitelist);
		
		echo '<div id="message" class="updated fade"><p>'
			. __('The Whitelist IP range has been deleted successfully', 'loginguard')
			. '</p></div><br />';
			
	}
	
	// Reset All Logs
	if(isset($_POST['loginguard_reset_all_ip'])){
	
		$result = $wpdb->query("DELETE FROM `".$wpdb->prefix."loginguard_logs` 
							WHERE `time` > 0");
			
		echo '<div id="message" class="updated fade"><p>'
					. __('All the IP Logs have been cleared', 'loginguard')
					. '</p></div><br />';
	}
	
	// Reset Logs
	if(isset($_POST['loginguard_reset_ips']) && is_array($_POST['loginguard_reset_ips'])){

		$ips = $_POST['loginguard_reset_ips'];
		
		foreach($ips as $ip){
			if(!loginguard_valid_ip($ip)){
				$error[] = 'The IP - '.$ip.' is invalid !';
			}
		}
		
		if(count($ips) < 1){
			$error[] = 'There are no IPs submitted';
		}
		
		// Should we start deleting logs
		if(empty($error)){
			
			$result = $wpdb->query("DELETE FROM `".$wpdb->prefix."loginguard_logs` 
							WHERE `ip` IN ('".implode("', '", $ips)."')");
		
			if(empty($error)){
				
				echo '<div id="message" class="updated fade"><p>'
						. __('The selected IP Logs have been reset', 'loginguard')
						. '</p></div><br />';
				
			}
			
		}
		
		if(!empty($error)){
			loginguard_report_error($error);echo '<br />';
		}
		
	}
	
	if(isset($_POST['blacklist_iprange'])){

		$start_ip = loginguard_optpost('start_ip');
		$end_ip = loginguard_optpost('end_ip');
		
		if(empty($start_ip)){
			$error[] = 'Please enter the Start IP';
		}
		
		// If no end IP we consider only 1 IP
		if(empty($end_ip)){
			$end_ip = $start_ip;
		}
				
		if(!loginguard_valid_ip($start_ip)){
			$error[] = 'Please provide a valid start IP';
		}
		
		if(!loginguard_valid_ip($end_ip)){
			$error[] = 'Please provide a valid end IP';			
		}
		
		// Regular ranges will work
		if(ip2long($start_ip) > ip2long($end_ip)){
			
			// BUT, if 0.0.0.1 - 255.255.255.255 is given, it will not work
			if(ip2long($start_ip) >= 0 && ip2long($end_ip) < 0){
				// This is right
			}else{
				$error[] = 'The End IP cannot be smaller than the Start IP';
			}
			
		}
		
		if(empty($error)){
			
			$blacklist = $loginguard['blacklist'];
			
			foreach($blacklist as $k => $v){
				
				// This is to check if there is any other range exists with the same Start or End IP
				if(( ip2long($start_ip) <= ip2long($v['start']) && ip2long($v['start']) <= ip2long($end_ip) )
					|| ( ip2long($start_ip) <= ip2long($v['end']) && ip2long($v['end']) <= ip2long($end_ip) )
				){
					$error[] = 'The Start IP or End IP submitted conflicts with an existing IP range !';
					break;
				}
				
				// This is to check if there is any other range exists with the same Start IP
				if(ip2long($v['start']) <= ip2long($start_ip) && ip2long($start_ip) <= ip2long($v['end'])){
					$error[] = 'The Start IP is present in an existing range !';
					break;
				}
				
				// This is to check if there is any other range exists with the same End IP
				if(ip2long($v['start']) <= ip2long($end_ip) && ip2long($end_ip) <= ip2long($v['end'])){
					$error[] = 'The End IP is present in an existing range!';
					break;
				}
				
			}
			
			$newid = ( empty($blacklist) ? 0 : max(array_keys($blacklist)) ) + 1;
		
			if(empty($error)){
				
				$blacklist[$newid] = array();
				$blacklist[$newid]['start'] = $start_ip;
				$blacklist[$newid]['end'] = $end_ip;
				$blacklist[$newid]['time'] = time();
				
				update_option('loginguard_blacklist', $blacklist);
				
				echo '<div id="message" class="updated fade"><p>'
						. __('Blacklist IP range added successfully', 'loginguard')
						. '</p></div><br />';
				
			}
			
		}
		
		if(!empty($error)){
			loginguard_report_error($error);echo '<br />';
		}
		
	}
	
	if(isset($_POST['whitelist_iprange'])){

		$start_ip = loginguard_optpost('start_ip_w');
		$end_ip = loginguard_optpost('end_ip_w');
		
		if(empty($start_ip)){
			$error[] = 'Please enter the Start IP';
		}
		
		// If no end IP we consider only 1 IP
		if(empty($end_ip)){
			$end_ip = $start_ip;
		}
				
		if(!loginguard_valid_ip($start_ip)){
			$error[] = 'Please provide a valid start IP';
		}
		
		if(!loginguard_valid_ip($end_ip)){
			$error[] = 'Please provide a valid end IP';			
		}
			
		if(ip2long($start_ip) > ip2long($end_ip)){
			
			// BUT, if 0.0.0.1 - 255.255.255.255 is given, it will not work
			if(ip2long($start_ip) >= 0 && ip2long($end_ip) < 0){
				// This is right
			}else{
				$error[] = 'The End IP cannot be smaller than the Start IP';
			}
			
		}
		
		if(empty($error)){
			
			$whitelist = $loginguard['whitelist'];
			
			foreach($whitelist as $k => $v){
				
				// This is to check if there is any other range exists with the same Start or End IP
				if(( ip2long($start_ip) <= ip2long($v['start']) && ip2long($v['start']) <= ip2long($end_ip) )
					|| ( ip2long($start_ip) <= ip2long($v['end']) && ip2long($v['end']) <= ip2long($end_ip) )
				){
					$error[] = 'The Start IP or End IP submitted conflicts with an existing IP range !';
					break;
				}
				
				// This is to check if there is any other range exists with the same Start IP
				if(ip2long($v['start']) <= ip2long($start_ip) && ip2long($start_ip) <= ip2long($v['end'])){
					$error[] = 'The Start IP is present in an existing range !';
					break;
				}
				
				// This is to check if there is any other range exists with the same End IP
				if(ip2long($v['start']) <= ip2long($end_ip) && ip2long($end_ip) <= ip2long($v['end'])){
					$error[] = 'The End IP is present in an existing range!';
					break;
				}
				
			}
			
			$newid = ( empty($whitelist) ? 0 : max(array_keys($whitelist)) ) + 1;
			
			if(empty($error)){
				
				$whitelist[$newid] = array();
				$whitelist[$newid]['start'] = $start_ip;
				$whitelist[$newid]['end'] = $end_ip;
				$whitelist[$newid]['time'] = time();
				
				update_option('loginguard_whitelist', $whitelist);
				
				echo '<div id="message" class="updated fade"><p>'
						. __('Whitelist IP range added successfully', 'loginguard')
						. '</p></div><br />';
				
			}
			
		}
		
		if(!empty($error)){
			loginguard_report_error($error);echo '<br />';
		}
	}
					
	// Count the Results
	$tmp = loginguard_selectquery("SELECT COUNT(*) AS num FROM `".$wpdb->prefix."loginguard_logs`");
	//print_r($tmp);
	
	// Which Page is it
	$loginguard_env['res_len'] = 10;
	$loginguard_env['cur_page'] = loginguard_get_page('lzpage', $loginguard_env['res_len']);
	$loginguard_env['num_res'] = $tmp['num'];
	$loginguard_env['max_page'] = ceil($loginguard_env['num_res'] / $loginguard_env['res_len']);
	
	// Get the logs
	$result = loginguard_selectquery("SELECT * FROM `".$wpdb->prefix."loginguard_logs` 
							ORDER BY `time` DESC 
							LIMIT ".$loginguard_env['cur_page'].", ".$loginguard_env['res_len']."", 1);
	//print_r($result);
	
	$loginguard_env['cur_page'] = ($loginguard_env['cur_page'] / $loginguard_env['res_len']) + 1;
	$loginguard_env['cur_page'] = $loginguard_env['cur_page'] < 1 ? 1 : $loginguard_env['cur_page'];
	$loginguard_env['next_page'] = ($loginguard_env['cur_page'] + 1) > $loginguard_env['max_page'] ? $loginguard_env['max_page'] : ($loginguard_env['cur_page'] + 1);
	$loginguard_env['prev_page'] = ($loginguard_env['cur_page'] - 1) < 1 ? 1 : ($loginguard_env['cur_page'] - 1);
	
	// Reload the settings
	$loginguard['blacklist'] = get_option('loginguard_blacklist');
	$loginguard['whitelist'] = get_option('loginguard_whitelist');
	
	?>

	

	
	
	
	
	<ul class="lg-tabs">
		<li class="active"><a id="tab1" class="tbtn button " href="javascript:void(0)" onclick="openTab('Failed-Logs')">Failed Login Attempts Logs</a></li>
		<li><a id="tab2" class="tbtn button " href="javascript:void(0)" onclick="openTab('Brute-Force-settings')">Brute Force Settings</a></li>
		<li><a id="tab3" class="tbtn button " href="javascript:void(0)" onclick="openTab('Blacklist-IP')">Blacklist IP</a></li>
		<li><a id="tab4" class="tbtn button " href="javascript:void(0)" onclick="openTab('Whitelist-IP')">Whitelist IP</a></li>
		<li><a id="tab5" class="tbtn button " href="javascript:void(0)" onclick="openTab('System-Information')">System Information</a></li>
	</ul>


	
	<div id="Failed-Logs" class="postbox tab">
	
		
		
		<h2 class="hndle ui-sortable-handle">
			<?php echo __('<span>Failed Login Attempts Logs</span> &nbsp; (Past '.($loginguard['reset_retries']/60/60).' hours)','loginguard'); ?>
		</h2>
		
		<script>
		function yesdsd(){
			window.location = '<?php echo menu_page_url('loginguard_brute_force', false);?>&lzpage='+jQuery("#current-page-selector").val();
			return false;
		}
		</script>
		
		<form method="get" onsubmit="return yesdsd();">
			<div class="tablenav">
				<p class="tablenav-pages" style="margin: 5px 10px" align="right">
					<span class="displaying-num"><?php echo $loginguard_env['num_res'];?> items</span>
					<span class="pagination-links">
						<a class="first-page" href="<?php echo menu_page_url('loginguard_brute_force', false).'&lzpage=1';?>"><span class="screen-reader-text">First page</span><span aria-hidden="true">«</span></a>
						<a class="prev-page" href="<?php echo menu_page_url('loginguard_brute_force', false).'&lzpage='.$loginguard_env['prev_page'];?>"><span class="screen-reader-text">Previous page</span><span aria-hidden="true">‹</span></a>
						<span class="paging-input">
							<label for="current-page-selector" class="screen-reader-text">Current Page</label>
							<input class="current-page" id="current-page-selector" name="lzpage" value="<?php echo $loginguard_env['cur_page'];?>" size="3" aria-describedby="table-paging" type="text"><span class="tablenav-paging-text"> of <span class="total-pages"><?php echo $loginguard_env['max_page'];?></span></span>
						</span>						
						<a class="next-page" href="<?php echo menu_page_url('loginguard_brute_force', false).'&lzpage='.$loginguard_env['next_page'];?>"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>
						<a class="last-page" href="<?php echo menu_page_url('loginguard_brute_force', false).'&lzpage='.$loginguard_env['max_page'];?>"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></a>
					</span>
				</p>
			</div>
		</form>
		
		<form action="" method="post" enctype="multipart/form-data">
			<?php wp_nonce_field('loginguard-options'); ?>
			<div class="inside">
				<table class="wp-list-table widefat fixed users" border="0">
					<tr>
						<th scope="row" valign="top" style="background:#fbf5f5;" width="20">#</th>
						<th scope="row" valign="top" style="background:#fbf5f5;"><?php echo __('IP','loginguard'); ?></th>
						<th scope="row" valign="top" style="background:#fbf5f5;"><?php echo __('Last Failed Attempt  (DD/MM/YYYY)','loginguard'); ?></th>
						<th scope="row" valign="top" style="background:#fbf5f5;"><?php echo __('Failed Attempts Count','loginguard'); ?></th>
						<th scope="row" valign="top" style="background:#fbf5f5;" width="150"><?php echo __('Lockouts Count','loginguard'); ?></th>
					</tr>
					<?php
					
					if(empty($result)){
						echo '
						<tr>
							<td colspan="4">
								No Logs. You will see logs about failed login attempts here.
							</td>
						</tr>';
					}else{
						foreach($result as $ik => $iv){
							$status_button = (!empty($iv['status']) ? 'disable' : 'enable');
							echo '
							<tr>
								<td>
									<input type="checkbox" value="'.$iv['ip'].'" name="loginguard_reset_ips[]" />
								</td>
								<td>
									'.$iv['ip'].'
								</td>
								<td>
									'.date('d/m/Y H:i:s', $iv['time']).'
								</td>
								<td>
									'.$iv['count'].'
								</td>
								<td>
									'.$iv['lockout'].'
								</td>
							</tr>';
						}
					}
					
					?>
				</table>
			
				<br>
				<input name="loginguard_reset_ip" class="button button-primary action" value="<?php echo __('Remove From Logs', 'loginguard'); ?>" type="submit" />
				&nbsp; &nbsp; 
				<input name="loginguard_reset_all_ip" class="button button-primary action" value="<?php echo __('Clear All Logs', 'loginguard'); ?>" type="submit" />
			</div>
		</form>
	</div>
	
	<div id="Brute-Force-settings" class="postbox tab" style="display:none">
	
				
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Brute Force Settings', 'loginguard'); ?></span>
		</h2>
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		<div class="inside">
		
		<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field('loginguard-options'); ?>
		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="max_retries"><?php echo __('Max Retries','loginguard'); ?></label></th>
				<td>
					<input type="text" size="3" value="<?php echo loginguard_optpost('max_retries', $loginguard['max_retries']); ?>" name="max_retries" id="max_retries" /> <?php echo __('Maximum failed attempts allowed before lockout','loginguard'); ?> <br />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="lockout_time"><?php echo __('Lockout Time','loginguard'); ?></label></th>
				<td>
				<input type="text" size="3" value="<?php echo (!empty($lockout_time) ? $lockout_time : $loginguard['lockout_time']) / 60; ?>" name="lockout_time" id="lockout_time" /> <?php echo __('minutes','loginguard'); ?> <br />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="max_lockouts"><?php echo __('Max Lockouts','loginguard'); ?></label></th>
				<td>
					<input type="text" size="3" value="<?php echo loginguard_optpost('max_lockouts', $loginguard['max_lockouts']); ?>" name="max_lockouts" id="max_lockouts" /> <?php echo __('','loginguard'); ?> <br />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="lockouts_extend"><?php echo __('Extend Lockout','loginguard'); ?></label></th>
				<td>
					<input type="text" size="3" value="<?php echo (!empty($lockouts_extend) ? $lockouts_extend : $loginguard['lockouts_extend']) / 60 / 60; ?>" name="lockouts_extend" id="lockouts_extend" /> <?php echo __('hours. Extend Lockout time after Max Lockouts','loginguard'); ?> <br />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="reset_retries"><?php echo __('Reset Retries','loginguard'); ?></label></th>
				<td>
					<input type="text" size="3" value="<?php echo (!empty($reset_retries) ? $reset_retries : $loginguard['reset_retries']) / 60 / 60; ?>" name="reset_retries" id="reset_retries" /> <?php echo __('hours','loginguard'); ?> <br />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="notify_email"><?php echo __('Email Notification','loginguard'); ?></label></th>
				<td>
					<?php echo __('after ','loginguard'); ?>
					<input type="text" size="3" value="<?php echo (!empty($notify_email) ? $notify_email : $loginguard['notify_email']); ?>" name="notify_email" id="notify_email" /> <?php echo __('lockouts <br />0 to disable email notifications','loginguard'); ?>
				</td>
			</tr>
		</table><br />
		<input name="save_lz" class="button button-primary action" value="<?php echo __('Save Settings','loginguard'); ?>" type="submit" />
		</form>
	
		</div>
	</div>
	
	<div id="Blacklist-IP" class="postbox tab" style="display:none">
	
		
		
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Blacklist IP','loginguard'); ?></span>
		</h2>
		
		<div class="inside">
		
		<?php echo __('Enter the IP you want to blacklist from login','loginguard'); ?>
	
		<form action="" method="post">
		<?php wp_nonce_field('loginguard-options'); ?>
		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="start_ip"><?php echo __('Start IP','loginguard'); ?></label></th>
				<td>
					<input type="text" size="25" value="<?php echo(loginguard_optpost('start_ip')); ?>" name="start_ip" id="start_ip"/> <?php echo __('Start IP of the range','loginguard'); ?> <br />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="end_ip"><?php echo __('End IP (Optional)','loginguard'); ?></label></th>
				<td>
					<input type="text" size="25" value="<?php echo(loginguard_optpost('end_ip')); ?>" name="end_ip" id="end_ip"/> <?php echo __('End IP of the range. <br />If you want to blacklist single IP leave this field blank.','loginguard'); ?> <br />
				</td>
			</tr>
		</table><br />
		<input name="blacklist_iprange" class="button button-primary action" value="<?php echo __('Add Blacklist IP Range','loginguard'); ?>" type="submit" />		
		</form>
		</div>
		
		<table class="wp-list-table fixed striped users" border="0" width="95%" cellpadding="10" align="center">
			<tr>
				<th scope="row" valign="top" style="background:#fbf5f5;"><?php echo __('Start IP','loginguard'); ?></th>
				<th scope="row" valign="top" style="background:#fbf5f5;"><?php echo __('End IP','loginguard'); ?></th>
				<th scope="row" valign="top" style="background:#fbf5f5;"><?php echo __('Date (DD/MM/YYYY)','loginguard'); ?></th>
				<th scope="row" valign="top" style="background:#fbf5f5;" width="100"><?php echo __('Options','loginguard'); ?></th>
			</tr>
			<?php
				if(empty($loginguard['blacklist'])){
					echo '
					<tr>
						<td colspan="4">
							No Blacklist IPs. You will see blacklisted IP ranges here.
						</td>
					</tr>';
				}else{
					foreach($loginguard['blacklist'] as $ik => $iv){
						echo '
						<tr>
							<td>
								'.$iv['start'].'
							</td>
							<td>
								'.$iv['end'].'
							</td>
							<td>
								'.date('d/m/Y', $iv['time']).'
							</td>
							<td>
								<a class="submitdelete" href="admin.php?page=loginguard_brute_force&bdelid='.$ik.'" onclick="return confirm(\'Are you sure you want to delete this IP range ?\')">Delete</a>
							</td>
						</tr>';
					}
				}
			?>
		</table>
		<br />
		
	</div>
	
	<div id="Whitelist-IP" class="postbox tab" style="display:none">
	
		
		
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('Whitelist IP', 'loginguard'); ?></span>
		</h2>
		
		<div class="inside">
		
		<?php echo __('Enter the IP you want to whitelist for login','loginguard'); ?>
		<form action="" method="post">
		<?php wp_nonce_field('loginguard-options'); ?>
		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label for="start_ip_w"><?php echo __('Start IP','loginguard'); ?></label></th>
				<td>
					<input type="text" size="25" value="<?php echo(loginguard_optpost('start_ip_w')); ?>" name="start_ip_w" id="start_ip_w"/> <?php echo __('Start IP of the range','loginguard'); ?> <br />
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label for="end_ip_w"><?php echo __('End IP (Optional)','loginguard'); ?></label></th>
				<td>
					<input type="text" size="25" value="<?php echo(loginguard_optpost('end_ip_w')); ?>" name="end_ip_w" id="end_ip_w"/> <?php echo __('End IP of the range. <br />If you want to whitelist single IP leave this field blank.','loginguard'); ?> <br />
				</td>
			</tr>
		</table><br />
		<input name="whitelist_iprange" class="button button-primary action" value="<?php echo __('Add Whitelist IP Range','loginguard'); ?>" type="submit" />
		</form>
		</div>
		
		<table class="wp-list-table fixed striped users" border="0" width="95%" cellpadding="10" align="center">
		<tr>
			<th scope="row" valign="top" style="background:#fbf5f5;"><?php echo __('Start IP','loginguard'); ?></th>
			<th scope="row" valign="top" style="background:#fbf5f5;"><?php echo __('End IP','loginguard'); ?></th>
			<th scope="row" valign="top" style="background:#fbf5f5;"><?php echo __('Date (DD/MM/YYYY)','loginguard'); ?></th>
			<th scope="row" valign="top" style="background:#fbf5f5;" width="100"><?php echo __('Options','loginguard'); ?></th>
		</tr>
		<?php
			if(empty($loginguard['whitelist'])){
				echo '
				<tr>
					<td colspan="4">
						No Whitelist IPs. You will see whitelisted IP ranges here.
					</td>
				</tr>';
			}else{
				foreach($loginguard['whitelist'] as $ik => $iv){
					echo '
					<tr>
						<td>
							'.$iv['start'].'
						</td>
						<td>
							'.$iv['end'].'
						</td>
						<td>
							'.date('d/m/Y', $iv['time']).'
						</td>
						<td>
							<a class="submitdelete" href="admin.php?page=loginguard_brute_force&delid='.$ik.'" onclick="return confirm(\'Are you sure you want to delete this IP range ?\')">Delete</a>
						</td>
					</tr>';
				}
			}
		?>
		</table>
		<br />
	
	</div>
	
	<div id="System-Information" class="postbox tab" style="display:none">
	
		
		
		<h2 class="hndle ui-sortable-handle">
			<span><?php echo __('System Information', 'loginguard'); ?></span>
		</h2>
		
		<div class="inside">
		
		<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field('loginguard-options'); ?>
		<table class="wp-list-table fixed striped users" cellspacing="1" border="0" width="95%" cellpadding="10" align="center">
		<?php
			echo '
			<tr>				
				<th align="left" width="25%">'.__('LoginGuard Version', 'loginguard').'</th>
				<td>'.LOGINGUARD_VERSION.(defined('LOGINGUARD_PREMIUM') ? ' (Security PRO Version)' : '').'</td>
			</tr>';
			
			if(defined('LOGINGUARD_PREMIUM')){
			echo '
			<tr>			
				<th align="left" valign="top">'.__('LoginGuard License', 'loginguard').'</th>
				<td align="left">
					'.(empty($loginguard['license']) ? '<span style="color:red">Unlicensed</span> &nbsp; &nbsp;' : '').' 
					<input type="text" name="loginguard_license" value="'.(empty($loginguard['license']) ? '' : $loginguard['license']['license']).'" size="30" placeholder="e.g. WXCSE-SFJJX-XXXXX-AAAAA-BBBBB" style="width:300px;" /> &nbsp; 
					<input name="save_lz" class="button button-primary" value="Update License" type="submit" />';
					
					if(!empty($loginguard['license'])){
						
						$expires = $loginguard['license']['expires'];
						$expires = substr($expires, 0, 4).'/'.substr($expires, 4, 2).'/'.substr($expires, 6);
						
						echo '<div style="margin-top:10px;">License Active : '.(empty($loginguard['license']['active']) ? '<span style="color:red">No</span>' : 'Yes').' &nbsp; &nbsp; &nbsp; 
						License Expires : '.($loginguard['license']['expires'] <= date('Ymd') ? '<span style="color:red">'.$expires.'</span>' : $expires).'
						</div>';
					}
					
					
				echo 
				'</td>
			</tr>';
			}
			
			echo '<tr>
				<th align="left">'.__('URL', 'loginguard').'</th>
				<td>'.get_site_url().'</td>
			</tr>
			<tr>				
				<th align="left">'.__('Path', 'loginguard').'</th>
				<td>'.ABSPATH.'</td>
			</tr>
			<tr>				
				<th align="left">'.__('Server\'s IP Address', 'loginguard').'</th>
				<td>'.$_SERVER['SERVER_ADDR'].'</td>
			</tr>
			<tr>				
				<th align="left">'.__('Your IP Address', 'loginguard').'</th>
				<td>'.loginguard_getip().'
					<div style="float:right">
						Method : 
						<select name="loginguard_ip_method" style="font-size:11px; width:150px">
							<option value="0" '.loginguard_POSTselect('loginguard_ip_method', 0, (@$loginguard['ip_method'] == 0)).'>REMOTE_ADDR</option>
							<option value="1" '.loginguard_POSTselect('loginguard_ip_method', 1, (@$loginguard['ip_method'] == 1)).'>HTTP_X_FORWARDED_FOR</option>
							<option value="2" '.loginguard_POSTselect('loginguard_ip_method', 2, (@$loginguard['ip_method'] == 2)).'>HTTP_CLIENT_IP</option>
						</select>
						<input name="save_loginguard_ip_method" class="button button-primary" value="Save" type="submit" />
					</div>
				</td>
			</tr>
			<tr>				
				<th align="left">'.__('wp-config.php is writable', 'loginguard').'</th>
				<td>'.(is_writable(ABSPATH.'/wp-config.php') ? '<span style="color:red">Yes</span>' : '<span style="color:green">No</span>').'</td>
			</tr>';
			
			if(file_exists(ABSPATH.'/.htaccess')){
				echo '
			<tr>				
				<th align="left">'.__('.htaccess is writable', 'loginguard').'</th>
				<td>'.(is_writable(ABSPATH.'/.htaccess') ? '<span style="color:red">Yes</span>' : '<span style="color:green">No</span>').'</td>
			</tr>';
			
			}
			
		?>
		</table>
		</form>
		
		</div>
		
		
		
		<div class="inside">
		
		<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field('loginguard-options'); ?>
		<table class="wp-list-table fixed striped users" border="0" width="95%" cellpadding="10" align="center">
			
			
			<?php
			
			echo '
			<tr>
				<th style="background:#fbf5f5;text-align: left;">'.__(' File Permissions - Relative Path', 'loginguard').'</th>
				<th style="width:10%; background:#fbf5f5;">'.__('Suggested', 'loginguard').'</th>
				<th style="width:10%; background:#fbf5f5;">'.__('Actual', 'loginguard').'</th>
			</tr>';
			
			$wp_content = basename(dirname(dirname(dirname(__FILE__))));
			
			$files_to_check = array('/' => '0755',
								'/wp-admin' => '0755',
								'/wp-includes' => '0755',
								'/wp-config.php' => '0444',
								'/'.$wp_content => '0755',
								'/'.$wp_content.'/themes' => '0755',
								'/'.$wp_content.'/plugins' => '0755',
								'.htaccess' => '0444');
			
			$root = ABSPATH;
			
			foreach($files_to_check as $k => $v){
				
				$path = $root.'/'.$k;
				$stat = @stat($path);
				$suggested = $v;
				$actual = substr(sprintf('%o', $stat['mode']), -4);
				
				echo '
			<tr>
				<td>'.$k.'</td>
				<td>'.$suggested.'</td>
				<td><span '.($suggested != $actual ? 'style="color: red;"' : '').'>'.$actual.'</span></td>
			</tr>';
				
			}
			
			?>
		</table>
		</form>
		
		</div>
		
		
		
		
	</div>
	
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	<script>
	function openTab(TabName) {
		var i;
		var x = document.getElementsByClassName("tab");
		for (i = 0; i < x.length; i++) {
		   x[i].style.display = "none";  
		}
		document.getElementById(TabName).style.display = "block";
			
	}	
	
	$(document).ready(function(){
    $(".lg-tabs a").click(function(){
        $(this).tab('show');
    });
	});
	
	</script>
	



	
	
<?php

loginguard_page_footer();

}

// The Loginguard Admin Options Page
function loginguard_page_country_blocks(){

	global $wpdb, $wp_roles, $loginguard;
	 
	if(!current_user_can('manage_options')){
		wp_die('Sorry, but you do not have permissions to change settings.');
	}

	/* Make sure post was from this page */
	if(count($_POST) > 0){
		check_admin_referer('loginguard-options');
	}
	
	// BEGIN THEME
	loginguard_page_header('LoginGuard - Country Block');
	
	// Load the blacklist and whitelist
	$loginguard['blocked_countries'] = get_option('loginguard_blocked_countries');
	
	
	if(isset($_POST['save_cb'])){
		
		$blocked_countries = $_POST['countryblock'];
		
                
             
		if(empty($error)){
			
			$option['blocked_countries'] = implode(",",$blocked_countries);
			
                        
			// Save the options
			update_option('loginguard_blocked_countries', $option['blocked_countries']);
			
			$saved = true;
			
		}else{
			loginguard_report_error($error);
		}
	
		if(!empty($notice)){
			loginguard_report_notice($notice);	
		}
			
		if(!empty($saved)){
			echo '<div id="message" class="updated"><p>'
				. __('The settings were saved successfully', 'loginguard')
				. '</p></div><br />';
		}
	
	}

  
   
        
        
	// Reload the settings
	$loginguard['blocked_countries'] = get_option('loginguard_blocked_countries');
        
        
       // echo $loginguard['blocked_countries'];
        

	
	?>

        <script type="text/javascript">
            
            
            
            
            
            jQuery(window).ready(function(){
                
                
                
                
             var country_list=jQuery("#blockedList").val();   
                
               
               
               jQuery(".loginguardCountryCheckbox").each(function(i, obj) {



               var thisVal=   jQuery(this).val();
               
               
               
             if (country_list.indexOf(thisVal) >= 0){
                 
                 jQuery(this).prop("checked",true);
                 
             }



                   });
               
               
               
                
                
            })
            
            
            
            </script>
        
	
	<div id="" class="postbox">
	
		
		
	<h2 class="hndle ui-sortable-handle">
		<span><?php echo __('Blacklist Country','loginguard'); ?></span>
	</h2>
	
	<h3> Select which countries to block</h3> 

	<form action="" method="post">
	<?php wp_nonce_field('loginguard-options'); ?>
            <input type="hidden" name="save_cb" value="1">
            
            <input type="hidden" id="blockedList" value="<?php echo $loginguard['blocked_countries'];?>">
	<div id="loginguardBulkBlockingContainer" style="margin-bottom: 10px;padding:15px;">
			<a href="#" onclick="jQuery('.loginguardCountryCheckbox').prop('checked', true); return false;">Select All</a>&nbsp;&nbsp;
			<a href="#" onclick="jQuery('.loginguardCountryCheckbox').prop('checked', false); return false;">Deselect All</a>&nbsp;&nbsp;
			</br></br>
		<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tbody>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" name="countryblock[]" id="loginguardCountryCheckbox_AF" type="checkbox" value="AF">&nbsp;Afghanistan&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" name="countryblock[]" id="loginguardCountryCheckbox_AX" type="checkbox" value="AX">&nbsp;Aland Islands&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" name="countryblock[]" id="loginguardCountryCheckbox_AL" type="checkbox" value="AL">&nbsp;Albania&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" name="countryblock[]" id="loginguardCountryCheckbox_DZ" type="checkbox" value="DZ">&nbsp;Algeria&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" name="countryblock[]" id="loginguardCountryCheckbox_AS" type="checkbox" value="AS">&nbsp;American Samoa&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_AD" type="checkbox" value="AD">&nbsp;Andorra&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_AO" type="checkbox" value="AO">&nbsp;Angola&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_AI" type="checkbox" value="AI">&nbsp;Anguilla&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_AQ" type="checkbox" value="AQ">&nbsp;Antarctica&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_AG" type="checkbox" value="AG">&nbsp;Antigua and Barbuda&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_AR" type="checkbox" value="AR">&nbsp;Argentina&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_AM" type="checkbox" value="AM">&nbsp;Armenia&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_AW" type="checkbox" value="AW">&nbsp;Aruba&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_AU" type="checkbox" value="AU">&nbsp;Australia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_AT" type="checkbox" value="AT">&nbsp;Austria&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_AZ" type="checkbox" value="AZ">&nbsp;Azerbaijan&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BS" type="checkbox" value="BS">&nbsp;Bahamas&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BH" type="checkbox" value="BH">&nbsp;Bahrain&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BD" type="checkbox" value="BD">&nbsp;Bangladesh&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BB" type="checkbox" value="BB">&nbsp;Barbados&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BY" type="checkbox" value="BY">&nbsp;Belarus&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BE" type="checkbox" value="BE">&nbsp;Belgium&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BZ" type="checkbox" value="BZ">&nbsp;Belize&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BJ" type="checkbox" value="BJ">&nbsp;Benin&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BM" type="checkbox" value="BM">&nbsp;Bermuda&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BT" type="checkbox" value="BT">&nbsp;Bhutan&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BO" type="checkbox" value="BO">&nbsp;Bolivia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BQ" type="checkbox" value="BQ">&nbsp;Bonaire, Saint Eustatius and Saba&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BA" type="checkbox" value="BA">&nbsp;Bosnia and Herzegovina&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BW" type="checkbox" value="BW">&nbsp;Botswana&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BV" type="checkbox" value="BV">&nbsp;Bouvet Island&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BR" type="checkbox" value="BR">&nbsp;Brazil&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_IO" type="checkbox" value="IO">&nbsp;British Indian Ocean Territory&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BN" type="checkbox" value="BN">&nbsp;Brunei Darussalam&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BG" type="checkbox" value="BG">&nbsp;Bulgaria&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BF" type="checkbox" value="BF">&nbsp;Burkina Faso&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BI" type="checkbox" value="BI">&nbsp;Burundi&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_KH" type="checkbox" value="KH">&nbsp;Cambodia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CM" type="checkbox" value="CM">&nbsp;Cameroon&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CA" type="checkbox" value="CA">&nbsp;Canada&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CV" type="checkbox" value="CV">&nbsp;Cape Verde&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_KY" type="checkbox" value="KY">&nbsp;Cayman Islands&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CF" type="checkbox" value="CF">&nbsp;Central African Republic&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_TD" type="checkbox" value="TD">&nbsp;Chad&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CL" type="checkbox" value="CL">&nbsp;Chile&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CN" type="checkbox" value="CN">&nbsp;China&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CX" type="checkbox" value="CX">&nbsp;Christmas Island&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CC" type="checkbox" value="CC">&nbsp;Cocos (Keeling) Islands&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CO" type="checkbox" value="CO">&nbsp;Colombia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_KM" type="checkbox" value="KM">&nbsp;Comoros&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CG" type="checkbox" value="CG">&nbsp;Congo&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CD" type="checkbox" value="CD">&nbsp;Congo, The Democratic Republic of the&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CK" type="checkbox" value="CK">&nbsp;Cook Islands&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CR" type="checkbox" value="CR">&nbsp;Costa Rica&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CI" type="checkbox" value="CI">&nbsp;Cote dIvoire&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_HR" type="checkbox" value="HR">&nbsp;Croatia&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CU" type="checkbox" value="CU">&nbsp;Cuba&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CW" type="checkbox" value="CW">&nbsp;Curacao&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CY" type="checkbox" value="CY">&nbsp;Cyprus&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CZ" type="checkbox" value="CZ">&nbsp;Czech Republic&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_DK" type="checkbox" value="DK">&nbsp;Denmark&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_DJ" type="checkbox" value="DJ">&nbsp;Djibouti&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_DM" type="checkbox" value="DM">&nbsp;Dominica&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_DO" type="checkbox" value="DO">&nbsp;Dominican Republic&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_EC" type="checkbox" value="EC">&nbsp;Ecuador&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_EG" type="checkbox" value="EG">&nbsp;Egypt&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SV" type="checkbox" value="SV">&nbsp;El Salvador&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GQ" type="checkbox" value="GQ">&nbsp;Equatorial Guinea&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_ER" type="checkbox" value="ER">&nbsp;Eritrea&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_EE" type="checkbox" value="EE">&nbsp;Estonia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_ET" type="checkbox" value="ET">&nbsp;Ethiopia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_EU" type="checkbox" value="EU">&nbsp;Europe&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_FK" type="checkbox" value="FK">&nbsp;Falkland Islands (Malvinas)&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_FO" type="checkbox" value="FO">&nbsp;Faroe Islands&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_FJ" type="checkbox" value="FJ">&nbsp;Fiji&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_FI" type="checkbox" value="FI">&nbsp;Finland&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_FR" type="checkbox" value="FR">&nbsp;France&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GF" type="checkbox" value="GF">&nbsp;French Guiana&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_PF" type="checkbox" value="PF">&nbsp;French Polynesia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_TF" type="checkbox" value="TF">&nbsp;French Southern Territories&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GA" type="checkbox" value="GA">&nbsp;Gabon&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GM" type="checkbox" value="GM">&nbsp;Gambia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GE" type="checkbox" value="GE">&nbsp;Georgia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_DE" type="checkbox" value="DE">&nbsp;Germany&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GH" type="checkbox" value="GH">&nbsp;Ghana&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GI" type="checkbox" value="GI">&nbsp;Gibraltar&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GR" type="checkbox" value="GR">&nbsp;Greece&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GL" type="checkbox" value="GL">&nbsp;Greenland&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GD" type="checkbox" value="GD">&nbsp;Grenada&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GP" type="checkbox" value="GP">&nbsp;Guadeloupe&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GU" type="checkbox" value="GU">&nbsp;Guam&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GT" type="checkbox" value="GT">&nbsp;Guatemala&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GG" type="checkbox" value="GG">&nbsp;Guernsey&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GN" type="checkbox" value="GN">&nbsp;Guinea&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GW" type="checkbox" value="GW">&nbsp;Guinea-Bissau&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GY" type="checkbox" value="GY">&nbsp;Guyana&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_HT" type="checkbox" value="HT">&nbsp;Haiti&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_HM" type="checkbox" value="HM">&nbsp;Heard Island and McDonald Islands&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_VA" type="checkbox" value="VA">&nbsp;Holy See (Vatican City State)&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_HN" type="checkbox" value="HN">&nbsp;Honduras&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_HK" type="checkbox" value="HK">&nbsp;Hong Kong&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_HU" type="checkbox" value="HU">&nbsp;Hungary&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_IS" type="checkbox" value="IS">&nbsp;Iceland&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_IN" type="checkbox" value="IN">&nbsp;India&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_ID" type="checkbox" value="ID">&nbsp;Indonesia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_IR" type="checkbox" value="IR">&nbsp;Iran, Islamic Republic of&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_IQ" type="checkbox" value="IQ">&nbsp;Iraq&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_IE" type="checkbox" value="IE">&nbsp;Ireland&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_IM" type="checkbox" value="IM">&nbsp;Isle of Man&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_IL" type="checkbox" value="IL">&nbsp;Israel&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_IT" type="checkbox" value="IT">&nbsp;Italy&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_JM" type="checkbox" value="JM">&nbsp;Jamaica&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_JP" type="checkbox" value="JP">&nbsp;Japan&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_JE" type="checkbox" value="JE">&nbsp;Jersey&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_JO" type="checkbox" value="JO">&nbsp;Jordan&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_KZ" type="checkbox" value="KZ">&nbsp;Kazakhstan&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_KE" type="checkbox" value="KE">&nbsp;Kenya&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_KI" type="checkbox" value="KI">&nbsp;Kiribati&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_KP" type="checkbox" value="KP">&nbsp;Korea, Democratic Peoples Republic of&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_KR" type="checkbox" value="KR">&nbsp;Korea, Republic of&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_KW" type="checkbox" value="KW">&nbsp;Kuwait&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_KG" type="checkbox" value="KG">&nbsp;Kyrgyzstan&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_LA" type="checkbox" value="LA">&nbsp;Lao Peoples Democratic Republic&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_LV" type="checkbox" value="LV">&nbsp;Latvia&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_LB" type="checkbox" value="LB">&nbsp;Lebanon&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_LS" type="checkbox" value="LS">&nbsp;Lesotho&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_LR" type="checkbox" value="LR">&nbsp;Liberia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_LY" type="checkbox" value="LY">&nbsp;Libyan Arab Jamahiriya&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_LI" type="checkbox" value="LI">&nbsp;Liechtenstein&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_LT" type="checkbox" value="LT">&nbsp;Lithuania&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_LU" type="checkbox" value="LU">&nbsp;Luxembourg&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MO" type="checkbox" value="MO">&nbsp;Macao&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MK" type="checkbox" value="MK">&nbsp;Macedonia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MG" type="checkbox" value="MG">&nbsp;Madagascar&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MW" type="checkbox" value="MW">&nbsp;Malawi&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MY" type="checkbox" value="MY">&nbsp;Malaysia&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MV" type="checkbox" value="MV">&nbsp;Maldives&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_ML" type="checkbox" value="ML">&nbsp;Mali&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MT" type="checkbox" value="MT">&nbsp;Malta&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MH" type="checkbox" value="MH">&nbsp;Marshall Islands&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MQ" type="checkbox" value="MQ">&nbsp;Martinique&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MR" type="checkbox" value="MR">&nbsp;Mauritania&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MU" type="checkbox" value="MU">&nbsp;Mauritius&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_YT" type="checkbox" value="YT">&nbsp;Mayotte&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MX" type="checkbox" value="MX">&nbsp;Mexico&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_FM" type="checkbox" value="FM">&nbsp;Micronesia, Federated States of&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MD" type="checkbox" value="MD">&nbsp;Moldova, Republic of&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MC" type="checkbox" value="MC">&nbsp;Monaco&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MN" type="checkbox" value="MN">&nbsp;Mongolia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_ME" type="checkbox" value="ME">&nbsp;Montenegro&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MS" type="checkbox" value="MS">&nbsp;Montserrat&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MA" type="checkbox" value="MA">&nbsp;Morocco&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MZ" type="checkbox" value="MZ">&nbsp;Mozambique&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MM" type="checkbox" value="MM">&nbsp;Myanmar&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_NA" type="checkbox" value="NA">&nbsp;Namibia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_NR" type="checkbox" value="NR">&nbsp;Nauru&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_NP" type="checkbox" value="NP">&nbsp;Nepal&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_NL" type="checkbox" value="NL">&nbsp;Netherlands&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_NC" type="checkbox" value="NC">&nbsp;New Caledonia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_NZ" type="checkbox" value="NZ">&nbsp;New Zealand&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_NI" type="checkbox" value="NI">&nbsp;Nicaragua&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_NE" type="checkbox" value="NE">&nbsp;Niger&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_NG" type="checkbox" value="NG">&nbsp;Nigeria&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_NU" type="checkbox" value="NU">&nbsp;Niue&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_NF" type="checkbox" value="NF">&nbsp;Norfolk Island&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MP" type="checkbox" value="MP">&nbsp;Northern Mariana Islands&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_NO" type="checkbox" value="NO">&nbsp;Norway&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_OM" type="checkbox" value="OM">&nbsp;Oman&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_PK" type="checkbox" value="PK">&nbsp;Pakistan&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_PW" type="checkbox" value="PW">&nbsp;Palau&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_PS" type="checkbox" value="PS">&nbsp;Palestinian Territory&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_PA" type="checkbox" value="PA">&nbsp;Panama&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_PG" type="checkbox" value="PG">&nbsp;Papua New Guinea&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_PY" type="checkbox" value="PY">&nbsp;Paraguay&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_PE" type="checkbox" value="PE">&nbsp;Peru&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_PH" type="checkbox" value="PH">&nbsp;Philippines&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_PN" type="checkbox" value="PN">&nbsp;Pitcairn&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_PL" type="checkbox" value="PL">&nbsp;Poland&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_PT" type="checkbox" value="PT">&nbsp;Portugal&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_PR" type="checkbox" value="PR">&nbsp;Puerto Rico&nbsp;&nbsp;&nbsp;</td>
			</tr>
			
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_QA" type="checkbox" value="QA">&nbsp;Qatar&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_RE" type="checkbox" value="RE">&nbsp;Reunion&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_RO" type="checkbox" value="RO">&nbsp;Romania&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_RU" type="checkbox" value="RU">&nbsp;Russian Federation&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_RW" type="checkbox" value="RW">&nbsp;Rwanda&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_BL" type="checkbox" value="BL">&nbsp;Saint Bartelemey&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SH" type="checkbox" value="SH">&nbsp;Saint Helena&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_KN" type="checkbox" value="KN">&nbsp;Saint Kitts and Nevis&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_LC" type="checkbox" value="LC">&nbsp;Saint Lucia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_MF" type="checkbox" value="MF">&nbsp;Saint Martin&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_PM" type="checkbox" value="PM">&nbsp;Saint Pierre and Miquelon&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_VC" type="checkbox" value="VC">&nbsp;Saint Vincent and the Grenadines&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_WS" type="checkbox" value="WS">&nbsp;Samoa&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SM" type="checkbox" value="SM">&nbsp;San Marino&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_ST" type="checkbox" value="ST">&nbsp;Sao Tome and Principe&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SA" type="checkbox" value="SA">&nbsp;Saudi Arabia&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SN" type="checkbox" value="SN">&nbsp;Senegal&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_RS" type="checkbox" value="RS">&nbsp;Serbia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SC" type="checkbox" value="SC">&nbsp;Seychelles&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SL" type="checkbox" value="SL">&nbsp;Sierra Leone&nbsp;&nbsp;&nbsp;</td>
			</tr>
			
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SG" type="checkbox" value="SG">&nbsp;Singapore&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SX" type="checkbox" value="SX">&nbsp;Sint Maarten&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SK" type="checkbox" value="SK">&nbsp;Slovakia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SI" type="checkbox" value="SI">&nbsp;Slovenia&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SB" type="checkbox" value="SB">&nbsp;Solomon Islands&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SO" type="checkbox" value="SO">&nbsp;Somalia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_ZA" type="checkbox" value="ZA">&nbsp;South Africa&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GS" type="checkbox" value="GS">&nbsp;South Georgia and the South Sandwich Islands&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_ES" type="checkbox" value="ES">&nbsp;Spain&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_LK" type="checkbox" value="LK">&nbsp;Sri Lanka&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SD" type="checkbox" value="SD">&nbsp;Sudan&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SR" type="checkbox" value="SR">&nbsp;Suriname&nbsp;&nbsp;&nbsp;</td>
				
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SJ" type="checkbox" value="SJ">&nbsp;Svalbard and Jan Mayen&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SZ" type="checkbox" value="SZ">&nbsp;Swaziland&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SE" type="checkbox" value="SE">&nbsp;Sweden&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_CH" type="checkbox" value="CH">&nbsp;Switzerland&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_SY" type="checkbox" value="SY">&nbsp;Syrian Arab Republic&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_TW" type="checkbox" value="TW">&nbsp;Taiwan&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_TJ" type="checkbox" value="TJ">&nbsp;Tajikistan&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_TZ" type="checkbox" value="TZ">&nbsp;Tanzania, United Republic of&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_TH" type="checkbox" value="TH">&nbsp;Thailand&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_TL" type="checkbox" value="TL">&nbsp;Timor-Leste&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_TG" type="checkbox" value="TG">&nbsp;Togo&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_TK" type="checkbox" value="TK">&nbsp;Tokelau&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_TO" type="checkbox" value="TO">&nbsp;Tonga&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_TT" type="checkbox" value="TT">&nbsp;Trinidad and Tobago&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_TN" type="checkbox" value="TN">&nbsp;Tunisia&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_TR" type="checkbox" value="TR">&nbsp;Turkey&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_TM" type="checkbox" value="TM">&nbsp;Turkmenistan&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_TC" type="checkbox" value="TC">&nbsp;Turks and Caicos Islands&nbsp;&nbsp;&nbsp;</td>
			
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_TV" type="checkbox" value="TV">&nbsp;Tuvalu&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_UG" type="checkbox" value="UG">&nbsp;Uganda&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_UA" type="checkbox" value="UA">&nbsp;Ukraine&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_AE" type="checkbox" value="AE">&nbsp;United Arab Emirates&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_GB" type="checkbox" value="GB">&nbsp;United Kingdom&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_US" type="checkbox" value="US">&nbsp;United States&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_UM" type="checkbox" value="UM">&nbsp;United States Minor Outlying Islands&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_UY" type="checkbox" value="UY">&nbsp;Uruguay&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_UZ" type="checkbox" value="UZ">&nbsp;Uzbekistan&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_VU" type="checkbox" value="VU">&nbsp;Vanuatu&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_VE" type="checkbox" value="VE">&nbsp;Venezuela&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_VN" type="checkbox" value="VN">&nbsp;Vietnam&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_VG" type="checkbox" value="VG">&nbsp;Virgin Islands, British&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_VI" type="checkbox" value="VI">&nbsp;Virgin Islands, U.S.&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_WF" type="checkbox" value="WF">&nbsp;Wallis and Futuna&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_EH" type="checkbox" value="EH">&nbsp;Western Sahara&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_YE" type="checkbox" value="YE">&nbsp;Yemen&nbsp;&nbsp;&nbsp;</td>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" id="loginguardCountryCheckbox_ZM" type="checkbox" value="ZM">&nbsp;Zambia&nbsp;&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td style=""><input class="loginguardCountryCheckbox" name="countryblock[]" name="countryblock[]" id="loginguardCountryCheckbox_ZW" type="checkbox" value="ZW">&nbsp;Zimbabwe&nbsp;&nbsp;&nbsp;</td>
				<td style=""></td>
				<td style=""></td>
				<td style=""></td>
			</tr>
			</tbody></table>
		
		
		
		
		
		
		
		
		
		
		
		
		
		
		
            <br><br>
            <input name="blacklist_iprange" class="button button-primary action" value="<?php echo __('Block This Countries','loginguard'); ?>" type="submit" />		
		</form> 
		<br>
            
            
    </div>
</div>
	
	
        
	
<?php

loginguard_page_footer();

}


// Sorry to see you going
register_uninstall_hook(LOGINGUARD_FILE, 'loginguard_deactivation');

function loginguard_deactivation(){

global $wpdb;

	$sql = array();
	$sql[] = "DROP TABLE ".$wpdb->prefix."loginguard_logs;";

	foreach($sql as $sk => $sv){
		$wpdb->query($sv);
	}

	delete_option('loginguard_version');
	delete_option('loginguard_options');
	delete_option('loginguard_last_reset');
	delete_option('loginguard_whitelist');
	delete_option('loginguard_blacklist');

}

