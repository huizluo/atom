<?php
namespace system;


use system\library\session\Session;
use system\library\Benchmark;
use system\library\Uri;
use system\library\Input;
use system\library\render\Render;


if (!defined("SELF") || !defined("APP_PATH")) {
    exit('Lost basic defination.');
}
if (!defined("TEST_MODE")) {
    define('TEST_MODE', FALSE);
}
if (!defined('RENDER')) {
    define('RENDER', 'Html');
}
if (TEST_MODE) {
    error_reporting(E_ALL & ~E_NOTICE);
} else {
    error_reporting(0);
}

define('BASE_PATH', pathinfo(__FILE__, PATHINFO_DIRNAME));
define('VERSION', '2.0');
define('TIMESTAMP', time());
define('IS_CLI', PHP_SAPI == 'cli');
define('IS_WIN', strpos(PHP_OS, 'WIN') !== false);
define('EXT', '.php');
define('CONTROLLER_SUFFIX', 'Controller');
define('DS', DIRECTORY_SEPARATOR);
define('LIB_PATH', BASE_PATH . '/library' . DS);

defined('APP_PATH') or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . DS);
defined('ROOT_PATH') or define('ROOT_PATH', dirname(realpath(APP_PATH)) . DS);
defined('EXTEND_PATH') or define('EXTEND_PATH', ROOT_PATH . 'extend' . DS);
defined('VENDOR_PATH') or define('VENDOR_PATH', ROOT_PATH . 'vendor' . DS);
defined('RUNTIME_PATH') or define('RUNTIME_PATH', ROOT_PATH . 'runtime' . DS);


require (BASE_PATH . '/core/common.php');
if (!is_php()) {
    exit('Atom need php5.6 or newer.');
}


spl_autoload_register('load_class');
set_error_handler('_error_handler');
set_exception_handler('_exception_handler');

@ini_set('magic_quotes_runtime', 0);

load_config('config');

// Benchmark start
if (get_config('enable_benchmark')) {
    $BM = & Benchmark::instance();
    $BM->mark('app_start');
}

if (IS_CLI) {
    chdir(dirname(SELF));
} else {
    header('X-Powered-By: Atom v' . VERSION);
}


if (function_exists("set_time_limit") == TRUE && @ini_get("safe_mode") == 0 && PHP_SAPI != 'cli') {
    @set_time_limit(60);
}
if (get_config('time_zone')) {
    date_default_timezone_set(get_config('time_zone'));
}

// Route
$URI = & Uri::instance();

$segments = $URI->segments;
// Language
if (get_config('language_decision') && ($__key = get_config('language_decision_key')) !== '') {
    switch (get_config('language_decision')) {
        case 'cookie':
            set_language($_COOKIE[$__key]);
            break;
        case 'session':
            Session::start();
            set_language($_SESSION[$__key]);
            break;
        case 'segment':
            $__lang_segment = $URI->segment($__key);
            if (in_array($__lang_segment, get_config('languages'))) {
                set_language($URI->segment($__key));
                unset($segments[$__key]);
            }
            unset($__lang_segment);
            break;
        default:
            set_language($_GET[$__key]);
    }
    unset($__key);
}
// view path
if (get_config('view_path_decision') && ($__key = get_config('view_path_decision_key')) !== '') {
    switch (get_config('view_path_decision')) {
        case 'cookie':
            $__vp = $_COOKIE[$__key];
            break;
        case 'session':
            $__vp = $_SESSION[$__key];
            break;
        case 'segment':
            $__vp = $URI->segment($__key);
            if (is_dir(APP_PATH . '/view/' . $__vp)) {
                unset($segments[$__key]);
            } else {

            }
            break;
        default:
            $__vp = $_GET[$__key];
            break;
    }
    $__vp = preg_replace('/\W/', '', $__vp);
    if ($__vp)
        $config['view_path'] = $__vp;
    unset($__key);
    unset($__vp);
}

// Reset Input
Input::instance();

//require BASE_PATH . '/core/Controller.php';
//require BASE_PATH . '/core/Model.php';

// Load the local application controller
$segments = array_values($segments);

if ($segments) {
    if (is_dir(APP_PATH . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments))) {
        $__DIR = implode(DIRECTORY_SEPARATOR, $segments);
        $__CLASS = 'Index';
        $__METHOD = 'index';
        unset($segments);
    }
}

if ($segments) {
    $segments_0 = array_pop($segments);
    $__CLASS = str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $segments_0)));
    if (is_dir(APP_PATH . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments))) {
        $__DIR = implode(DIRECTORY_SEPARATOR, $segments);
        $__METHOD = 'index';
        unset($segments);
    }
}

if ($segments) {
    $__METHOD = $segments_0;
    $__CLASS = str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', array_pop($segments))));
    if (is_dir(APP_PATH . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments))) {
        $__DIR = implode(DIRECTORY_SEPARATOR, $segments);
        unset($segments);
    }
}

unset($segments_0);

if (!$__CLASS) {
    $__CLASS = 'Index';
}

if (!$__METHOD) {
    $__METHOD = 'index';
}

if ($segments) {
    show_404();
}

$__CLASS .= CONTROLLER_SUFFIX;

$__DIR && $__DIR .= DIRECTORY_SEPARATOR;


// Note: The Router class automatically validates the controller path using the router->_validate_request().
// If this include fails it means that the default controller in the Routes.php file is not resolving to something valid.
$__CTRL = APP_PATH . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR . $__DIR . $__CLASS . EXT;


if (!file_exists($__CTRL)) {
    show_404();
}else{
    include ($__CTRL);
}


unset($__CTRL);

/*
 * ------------------------------------------------------
 *  Security check
 * ------------------------------------------------------
 *
 *  None of the functions in the app controller or the
 *  loader class can be called via the URI, nor can
 *  controller functions that begin with an underscore
 */
$__CLASS = 'app\\controller\\' . $__CLASS;

if (!class_exists($__CLASS) or strncmp($__METHOD, '_', 1) == 0 or in_array(strtolower($__METHOD), array_map('strtolower', get_class_methods('system\\core\\Controller')))) {
    show_404();
}

if (get_config('enable_benchmark')) {
    $BM->mark('controller_execution_time_( ' . $__CLASS . ' / ' . $__METHOD . ' )_start');
}
$__CTRL = new $__CLASS();

if (method_exists($__CTRL, '_remap')) {
    $__VIEW = $__CTRL->_remap($__METHOD);
} else {
    if (!in_array(strtolower($__METHOD), array_map('strtolower', get_class_methods($__CTRL)))) {
        show_404();
    }

    // Call the requested method.
    // Any URI segments present (besides the class/function) will be passed to the method for convenience
    $__VIEW = $__CTRL->$__METHOD();
}

//Benchmark end
if (get_config('enable_benchmark')) {
    $BM->mark('app_end');
}

if ($__VIEW) {
    if (is_string($__VIEW)) {
        echo $__VIEW;
    } elseif ($__VIEW instanceof Render) {
        $__VIEW->display();
    } else {
        $r = RENDER . 'Render';
        $__RENDER = new $r();
        $__RENDER->setEnv($__VIEW);
        $__RENDER->display($__VIEW);
    }
}
