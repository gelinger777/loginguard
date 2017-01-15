<?php
/**
 * @package loginguard
 * @version 1.0.0
 */
/*
Plugin Name: LoginGuard by DailyGuard.io
Plugin URI: http://wordpress.org/extend/plugins/loginguard/
Description: LoginGuard protects you from brute-force attacks and let's you allow access to the page from only defined countries.
Version: 1.3.0
Author: Dailyguard.io Team
Author URI: http://www.dailyguard.io
License: GPLv3 or later
*/

/*
Copyright (C) 2013  Raj Kothari (email : support@dailyguard.io)
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if(!function_exists('add_action')){
	echo 'You are not allowed to access this page directly.';
	exit;
}





// Is the premium plugin active ?
if(defined('LOGINGUARD_VERSION')){
	return;
}

$plugin_loginguard = plugin_basename(__FILE__);
define('LOGINGUARD_FILE', __FILE__);
define('LOGINGUARD_API', 'http://dailyguard.io/api.php');

include_once(dirname(__FILE__).'/init.php');

