<?php
//-----------------------------------------------
// Define root folder and load base
//-----------------------------------------------
if (!defined('MAESTRANO_ROOT')) {
  define("MAESTRANO_ROOT", realpath(dirname(__FILE__) . '/../../'));
}
require MAESTRANO_ROOT . '/app/init/base.php';

//-----------------------------------------------
// Require your app specific files here
//-----------------------------------------------
define('APP_DIR', realpath(MAESTRANO_ROOT . '/../'));
chdir(APP_DIR);

// Need to define _PS_ADMIN_DIR before loading config.inc
// so that application switches to admin mode
$admin_dirs = glob("admin*");
define('_PS_ADMIN_DIR_', $admin_dirs[0]);

// Require the config file
require_once APP_DIR . '/config/config.inc.php';

//-----------------------------------------------
// Perform your custom preparation code
//-----------------------------------------------
// If you define the $opts variable then it will
// automatically be passed to the MnoSsoUser object
// for construction
// e.g:
$opts = array();
$opts['db_connection'] = Db::getInstance();

// Set after sso path
MaestranoService::setAfterSsoSignInPath('/' . _PS_ADMIN_DIR_ . '/index.php');
