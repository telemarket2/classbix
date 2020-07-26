<?php

//phpinfo();exit;
/**
 * ClassiBase Classifieds Script
 *
 * ClassiBase Classifieds Script by Vepa Halliyev is licensed under a Creative Commons Attribution-Share Alike 3.0 License.
 *
 * @package		ClassiBase Classifieds Script
 * @author		Vepa Halliyev
 * @copyright	Copyright (c) 2009, Vepa Halliyev, veppa.com.
 * @license		http://classibase.com
 * @link		http://classibase.com
 * @since		Version 1.0
 * @filesource
 */
//  Directories --------------------------------------------------------------

define('FROG_ROOT', dirname(__FILE__));
define('CORE_ROOT', FROG_ROOT . '/sys');


define('APP_PATH', CORE_ROOT . '/app');


define('SESSION_LIFETIME', 3600);
define('REMEMBER_LOGIN_LIFETIME', 1209600); // two weeks

define('DEFAULT_CONTROLLER', 'index');
define('DEFAULT_ACTION', 'index');

define('COOKIE_PATH', '/');
define('COOKIE_DOMAIN', null);
define('COOKIE_SECURE', false);

//  Init ---------------------------------------------------------------------
$config_file = CORE_ROOT . '/config.php';
if (!file_exists($config_file))
{
	require 'setup.php';
}
else
{
	require $config_file;
}

define('BASE_URL', URL_PUBLIC . (USE_MOD_REWRITE ? '' : '?'));
define('DIR_CACHE', UPLOAD_ROOT . '/cache/data/'); 

include CORE_ROOT . '/Framework.php';


// TODO: check page cache here if required
// set connections from config file
Record::$__CONNECTIONS__ = $connections;


use_helper('I18n');
// set locale from cookie
// I18n::setLocale('en');
// profile route 
/* Dispatcher::addRoute(array(
  '/profile/:num/' => '/profile/index/$1',
  '/profile/:num/:any/:any/:any' => '/profile/$2/$1/$3/$4', // use this before second statement because it is more specific
  '/profile/:num/:any/:any' => '/profile/$2/$1/$3', // use this before second statement because it is more specific
  '/profile/:num/:any/' => '/profile/$2/$1/',

  )); */


// set locale
Language::setLocaleFromUrl();

// perform init actions
Config::init();

// add theme controller and model folders to autoloader
Theme::setAutoloader();

// dispatch 
// get url without locale 
$url = Language::getCurrentUrl(true);
$arr_url = explode('/', $url);
if ($arr_url[0] === 'admin' || $arr_url[0] === 'post' || $arr_url[0] === 'login')
{
	// ready to go 
	Dispatcher::dispatch($url);
}
// load index
Dispatcher::dispatch('/index/load/' . $url);
