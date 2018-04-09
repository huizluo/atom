<?php

define('RAND_NUM', 1);
define('RAND_ALPHA', 2);
define('RAND_U_ALPHA', 4);
define('RAND_SYMBOL', 8);

define('LOG_FILE', 'file');
define('LOG_SYS', 'sys');
define('LOG_DEFAULT', 'default');

/**
 * AtomCode
 *
 * 通用文件，提供框架公用函数，此文件内的函数会优先加载，命名时请不要与此文件中的函数或类进行冲突，否则将会引发致命错误！
 *
 * @package		AtomCode
 * @subpackage	core
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/user_guide/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */

/**
 * 将当前 PHP 版本与给出的版本进行比较
 *
 * 目前主要用于测试 PHP 版本是否大于5.3，因为本框架仅支持 PHP5.3 以上版本
 *
 * @access	public
 * @param	string
 * @return	bool	TRUE 表示当前版本比给出的版本大
 */
function is_php($version = '5.3.0') {
    static $_is_php = array();
    $version = (string)$version;

    if (!isset($_is_php[$version])) {
        $_is_php[$version] = (version_compare(PHP_VERSION, $version) < 0) ? FALSE : TRUE;
    }

    return $_is_php[$version];
}

/**
 * 测试文件或文件夹是否可写
 *
 * 在 windows 平台下，is_writable() 函数根据只读属性返回是否可写，而在 Unix 下，如果打开了 safe_mode
 * 也会返回不正确的是否可写。所以我们根据平台判断和真实以写模式打开来判断是否真的可写。
 *
 * @access	private
 * @return	boolean
 */
function is_really_writable($file) {
    // 是否为 Unix 下的非 safe_mode
    if (DIRECTORY_SEPARATOR == '/' && @ini_get("safe_mode") == FALSE) {
        return is_writable($file);
    }

    // For windows servers and safe_mode "on" installations we'll actually
    // write a file then read it.  Bah...
    if (is_dir($file)) {
        $file = rtrim($file, '/') . '/' . md5(mt_rand(1, 100) . mt_rand(1, 100));

        if (($fp = @fopen($file, 'ab')) === FALSE) {
            return FALSE;
        }

        fclose($fp);
        @chmod($file, 0777);
        @unlink($file);
        return TRUE;
    } elseif (!is_file($file) || ($fp = @fopen($file, 'ab')) === FALSE) {
        return FALSE;
    }

    fclose($fp);
    return TRUE;
}

/**
 * 自动加载类
 *
 * 在 AtomCode 中，你不需要使用 include 或 require 来包含文件，框架将帮助你自动将文件包含进来。但是要求你按照一定的规范将文件放在特定的目录下。<br>
 * 目前支持的类后缀有： Model, Helper, Block, Render，不在此后缀列表中的将会以通用类的方式查找（目录为：library）。<br>
 *
 * 如果使用了命名空间，则命名空间将作为子目录包含于文件路径中。<br />
 * 比如定义： <br />
 * namespace my;<br />
 * class Simple extends \Singleton {} <br />
 *
 * 文件存放路径则应该是 APP_PATH/library/my/Simple.php
 *
 * 查找的顺序是首先尝试加载应用自己定义的类，如果不存在，再去查找系统目录下的相应文件。所以，如果要覆盖系统类的实现，只需要建立一个同名类即可。
 *
 * @param String $name 类名，有后缀的需要包含后缀
 * @param String $dir 指定目录，仅可指定子目录，框架会自动附加上应用或系统的路径
 * @return boolean 类是否加载成功或者已存在
 */
function load_class($name, $dir = '') {
    static $autoload_dirs = array('Model' => 'model', 'Helper' => 'helper', 'Block' => 'block', 'Render' => 'render', 'Driver' => 'library');

    if (class_exists($name, FALSE)) {
        return true;
    }

    $t = explode('\\', $name);
    $class_name = array_pop($t);
    $dir_name = implode(DIRECTORY_SEPARATOR, $t);
    $sub_dir = '';

    if ($dir) {
        $guess_dir = $dir;
    } else {
        $guess_dir = 'library';
        foreach ($autoload_dirs as $suffix => $dir) {
            if (substr($name, -strlen($suffix)) == $suffix) {
                if ($suffix == 'Driver') {
                    preg_match_all('/([A-Z][a-z0-9_]*)/', $name, $matches);
                    $sub_dir = DIRECTORY_SEPARATOR . strtolower(implode(DIRECTORY_SEPARATOR, array_slice($matches[0], 0, -2)));
                }
                $guess_dir = $dir;
                break;
            }
        }
    }

    $dir_name && $guess_dir = $dir_name;
    if ($sub_dir) {
        $guess_dir .= DIRECTORY_SEPARATOR . 'driver' . $sub_dir;
    }

    //var_dump(ROOT_PATH);

    $file_path = ROOT_PATH . $guess_dir . DIRECTORY_SEPARATOR . $class_name . '.php';

    if (!file_exists($file_path)) {
        return false;
    }

    require $file_path;

    return class_exists($name, FALSE);

}

/**
 * 取得配置信息，如果不存在则返回默认值。
 *
 * @param String $name 配置名，为 $config 数组的第一维键名
 * @param Mixed $default 默认值
 * @return array $config
 */
function &get_config($name = '', $default = '') {
    global $config;
    if (!$name) {
        return $config;
    } else {
        if (!array_key_exists($name, $config)) {
            $config[$name] = $default;
        }
        return $config[$name];
    }
}

/**
 * 加载配置文件
 *
 * 尝试加载与配置名同名的配置文件，成功则返回相应配置，否则返回 FALSE
 *
 * 加载配置文件的目录为 config 目录，如果在入口文件中指定了 ENV 常量，则会加载相应子目录中
 * 的配置，所以在测试环境中，请使用在入口文件中定义测试配置子目录，保证上线后不会发生配置错
 * 误的情况。生产环境中的入口文件请在部署时自动覆盖，即可将内外网环境进行隔离，避免问题的发
 * 生。
 *
 * ENV常量定义示例：
 *
 * <code>
 * <?php
 * define('ENV', 'test');
 * </code>
 *
 * @param string $name 配置名
 * @param bool | Mixed $default 配置默认值
 * @return  bool | Mixed 配置信息
 */
function load_config($name, $default = NULL) {
    static $loaded_config = array();
    global $config;

    if (isset($loaded_config[$name])) {
        return $loaded_config[$name] ? $config[$name] : FALSE;
    }

    $loaded_config[$name] = FALSE;

    $sub_dir = (defined('ENV') && ENV) ? ENV . '/' : '';

    foreach (array(APP_PATH . '/config/' . $sub_dir, BASE_PATH . '/config/') as $dir) {
        $dir .=  $name . '.php';

        if (file_exists($dir)) {
            include $dir;
            $loaded_config[$name] = true;
            break;
        }
    }

    return array_key_exists($name, $config) ? $config[$name] : $default;
}

/**
 * 错误日志接口
 *
 * 此函数被用于快速记录错误信息，日志记录内容受限于配置 log_threshold, Configuration: {@example ../config/config.php 56 19}
 *
 * @access	public
 * @param enum{'error', 'debug', 'warning', 'notice'} $level 错误级别
 * @param string $message 错误信息
 * @param boolean $php_error 是否是 PHP 错误
 * @return	void
 */
function log_message($message, $level = 'error', $php_error = FALSE) {
    $config = get_config();
    if ($config['log_threshold'] == 0) {
        return;
    }

    $_log = & \system\library\log\Log::instance();
    $_log->write($message, $level, $php_error ? 'php' : 'log');
}

/**
 * 记录日志到系统日志
 *
 * @param int $priority
 * @param string $message
 * @param int $dest
 */
function aclog($priority, $message, $dest) {
    $dest = ($dest == LOG_DEFAULT ? (get_config('log_destination') == 'console' ? LOG_CONS : LOG_FILE) : $dest);

    if ($dest == LOG_FILE) {
        $_levels = array(LOG_ERR => 'error', LOG_DEBUG => 'debug', LOG_INFO => 'info');
         log_message($message, $_levels[$priority]);
    } else {
         ac_syslog($priority, $message, $dest);
    }
}

/**
 * 记录到系统日志
 *
 * @param int $priority
 * @param string $message
 * @param int $dest
 */
function ac_syslog($priority, $message, $dest) {
    $log_opt = LOG_PID;

    if ($dest == LOG_CONS || $dest == LOG_PERROR) {
        $log_opt |= $dest;
    }

    openlog("atomcode", $log_opt, LOG_USER);
    syslog($priority, $message);
    closelog();
}

/**
 * 记录调试信息
 *
 * @param string $message
 * @param int $dest
 */
function log_d($message, $dest = LOG_DEFAULT) {
    aclog(LOG_DEBUG, $message, $dest);
}

/**
 * 记录信息
 *
 * @param string $message
 * @param int $dest
 */
function log_i($message, $dest = LOG_DEFAULT) {
     aclog(LOG_INFO, $message, $dest);
}

/**
 * 记录错误信息
 *
 * @param string $message
 * @param int $dest
 */
function log_e($message, $dest = LOG_DEFAULT) {
    aclog(LOG_ERR, $message, $dest);
}

function println($message, $delay = FALSE) {
    if ($delay) {
        echo $message . (IS_CLI ? "\n" : "<br />\n");
    } else {
        echo $message . (IS_CLI ? "\n" : "<br />\n");
    }
}
/**
 * 快速显示错误页面
 *
 * 此函数显示的是 PHP 错误类型。使用的错误页面模板是 error_general 模板
 *
 * @param string $message 错误信息
 * @param integer $status_code 同 HTTP RESPONSE CODE, {@link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html}
 * @param string $heading 页面标题
 * @access	public
 * @return	void
 */
function show_error($message, $title = '', $config = array()) {
    $_error = & \system\library\Error::instance();
    echo $_error->show_error($message, $title, $config);
}

/**
 * 显示 404 页面
 *
 * 如果PHP在处理的过程中，发现参数所要求的页面无法展示，即可显示此页面。
 *
 * @param string $page 未能显示的页面
 * @param boolean $log_error 是否错误记录日志，TRUE 表示框架将会尝试记下此条日志。记录与否请参考： {@link log_message()}
 * @see log_message()
 * @access	public
 * @return	void
 */
function show_404($page = '') {
    $_error = & \system\library\Error::instance();
    $_error->show_404($page);
    exit();
}

/**
 * 输出 HTTP 响应状态码
 *
 * 为了配合服务器实现与静态页面相同的效果，PHP可以输出不同的状态码，使浏览器有相应的响应。
 *
 * @access	public
 * @param	int		 HTTP RESPONSE CODE, {@link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html}
 * @param	string
 * @return	void
 */
function set_status_header($code = 200, $text = '') {
    $stati = array(200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 304 => 'Not Modified', 305 => 'Use Proxy', 307 => 'Temporary Redirect', 400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Requested Range Not Satisfiable', 417 => 'Expectation Failed', 500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported');

    if ($code == '' || !is_numeric($code)) {
        show_error('Status codes must be numeric', 500);
    }

    if (isset($stati[$code]) && $text == '') {
        $text = $stati[$code];
    }

    if ($text == '') {
        show_error('No status text available.  Please check your status code number or supply your own message text.', 500);
    }

    $server_protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : FALSE;

    if (substr(php_sapi_name(), 0, 3) == 'cgi') {
        header("Status: {$code} {$text}", TRUE);
    } elseif ($server_protocol == 'HTTP/1.1' || $server_protocol == 'HTTP/1.0') {
        header($server_protocol . " {$code} {$text}", TRUE, $code);
    } else {
        header("HTTP/1.1 {$code} {$text}", TRUE, $code);
    }
}

/**
 * 错误钩子
 *
 * 用于自动处理 PHP 运行过程中产生的错误信息。
 *
 * @param string $severity
 * @param string $message
 * @param string $filepath
 * @param string $line
 */
function _error_handler($severity, $message, $filepath, $line) {
    // We don't bother with "strict" notices since they tend to fill up
    // the log file with excess information that isn't normally very helpful.
    // For example, if you are running PHP 5 and you use version 4 style
    // class functions (without prefixes like "public", "private", etc.)
    // you'll get notices telling you that these have been deprecated.
    if ($severity == E_STRICT) {
        return;
    }

    $_error = & \system\library\Error::instance();
    $config = get_config();

    // Should we display the error? We'll get the current error_reporting
    // level and add its bits with the severity bits to find out.
    if (($severity & error_reporting()) == $severity) {
        $_error->show_php_error($severity, $message, $filepath, $line);
    }

    // Should we log the error?  No?  We're done...
    if ($config['log_threshold'] == 0) {
        return;
    }

    $_error->log_exception($severity, $message, $filepath, $line);
}

/**
 * 异常钩子
 *
 * 当系统抛出未捕获的异常时，由此函数负责捕获，并显示异常调试页面（在测试模式下有效）
 *
 * @param Exception $excetion
 */
function _exception_handler($excetion) {
    $error = & \system\library\Error::instance();
    $error->debug($excetion, TRUE);
}

/**
 * Remove Invisible Characters
 *
 * This prevents sandwiching null characters
 * between ascii characters, like Java\0script.
 *
 * @access	public
 * @param	string
 * @return	string
 */
function remove_invisible_characters($str, $url_encoded = TRUE) {
    $non_displayables = array();

    // every control character except newline (dec 10)
    // carriage return (dec 13), and horizontal tab (dec 09)


    if ($url_encoded) {
        $non_displayables[] = '/%0[0-8bcef]/'; // url encoded 00-08, 11, 12, 14, 15
        $non_displayables[] = '/%1[0-9a-f]/'; // url encoded 16-31
    }

    $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S'; // 00-08, 11, 12, 14-31, 127


    do {
        $str = preg_replace($non_displayables, '', $str, -1, $count);
    } while ($count);

    return $str;
}

/**
 * 取得用户的IP地址
 *
 * 如果使用代理上网，则会尽可能的找到用户的真实IP，而不是用户代理的IP。<br>
 * 如果用户使用 3G，或 uniwap 等方式上网，则取到的是运营商的代理服务器IP，而无法取得用户的真实IP
 *
 * @param boolean
 * @return string|long
 */
function get_ip($long = FALSE) {
    static $ip = 0;

    if ($ip === 0) {
        if ($_SERVER["HTTP_CLIENT_IP"] && strcasecmp($_SERVER["HTTP_CLIENT_IP"], "unknown")) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif ($_SERVER["HTTP_X_FORWARDED_FOR"] && strcasecmp($_SERVER["HTTP_X_FORWARDED_FOR"], "unknown")) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } elseif (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")) {
            $ip = getenv("REMOTE_ADDR");
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = "unknown";
        }
    }

    if ($long) {
        return $ip == 'unknown' ? 0 : ip2long($ip);
    }

    return $ip;
}

/**
 * 按照站点URL规则生成干净的URL
 *
 * 按照推荐的URL规则，URL一般是 http://www.example.com/class/action?query=value#mask 这样的形式
 *
 * 要生成这样的URL，以以下方式调用
 * <code>
 * get_clean_url('class/action', 'query=value', 'mask');
 * </code>
 *
 * @see get_url()
 * @param string $method
 * @param string | Array $get
 * @param string $hash
 * @return string url
 */
function get_clean_url($method, $get = array(), $hash = '') {
    $get && is_string($get) && parse_str($get, $get);
    $get || $get = array();
    $method = trim($method, ' /');

    if (get_config('uri_protocol') == 'QUERY_STRING') {
        $p = explode('/', $method);
        if (count($p) === 3) {
            $get[get_config('directory_trigger')] = array_shift($p);
        }

        $get[get_config('controller_trigger')] = $p[0] ? $p[0] : '';
        $get[get_config('function_trigger')] = $p[1] ? $p[1] : '';
        $method = '';
    }

    $prefix = preg_replace('~([^:])/{2,}~', '$1/', get_config('base_url') . get_config('index_page') . ($method ? '/' . $method : ''));

    return $prefix . (count($get) ? (strpos($prefix, '?') ? '&' : '?') . http_build_query($get) : '') . ($hash ? '#' . $hash : '');
}

/**
 * 按照站点URL规则生成干净的URL
 *
 * 用法和 {@link get_clean_url()} 一样，但是会自动附加上当前URL中的查询语句
 *
 * @see get_clean_url()
 * @param string $method
 * @param string | Array $get
 * @param string $hash
 * @return string url
 */
function get_url($method, $get = array(), $hash = '') {
    $get && is_string($get) && parse_str($get, $get);
    $get || $get = array();
    $get = array_merge($_GET, $get);
    return get_clean_url($method, $get, $hash);
}

/**
 * 取得随机数
 *
 * 根据传入的规则，生成一定长度的随机数，可以使用以下几种类型之一或几种的组合:
 * - 数字({@link RAND_NUM})
 * - 小写英文({@link RAND_ALPHA})
 * - 大写英文({@link RAND_U_ALPHA})
 * - 标点符号({@link RAND_SYMBOL})
 *
 * 在非单独使用数字的情况下，数字部分会去掉0和1；英文部分不包括： l o z I O Z 这几个字母。
 *
 * @param integer $length
 * @param integer $reg
 * @return string
 */

function get_random($length, $reg = RAND_NUM) {
    $rand_num = ($reg == RAND_NUM ? '23456789' : '2345678901');
    $rand_alpha = 'abcdefghijkmnpqrstuvwxy';
    $rand_u_alpha = 'ABCDEFGHJKLMNPQRSTUVWXY';
    $rand_symbol = '`~!@#$%^&*()-_=+\\|,.<>/?';

    $rstr = '';
    if ($reg & RAND_NUM) {
        $rstr .= $rand_num;
    }
    if ($reg & RAND_ALPHA) {
        $rstr .= $rand_alpha;
    }
    if ($reg & RAND_U_ALPHA) {
        $rstr .= $rand_u_alpha;
    }
    if ($reg & RAND_SYMBOL) {
        $rstr .= $rand_symbol;
    }
    $rlen = strlen($rstr);
    $return = '';

    for($i = 0; $i < $length; $i++) {
        $return .= $rstr{rand(0, $rlen - 1)};
    }

    return $return;
}

/**
 * 设置语言
 *
 * 设置加载语言包时要加载的语言目录。
 *
 * @param string $language 语言，在 language 目录下的目录名
 * @return null
 */
function set_language($language = '') {
    $l = & get_config('language');
    $l = $language ? $language : get_config('default_language');
}

/**
 * 加载语言包文件
 *
 * @param string $packages 语言包，可以使用逗号(,)分隔一次加载多个
 * @param string $language 语言
 * @return null
 */
function load_lang($packages, $language = '') {
    static $loaded_lang = array();
    global $__LANGUAGE_PACKAGE; //全局语言变量
    $_LANG = array();

    $package_arr = explode(',', $packages);
    $lang = $language ? $language : get_config('language');
    $language = $lang;

    $default_lang = get_config('default_language');

    // 语言选择
    do {
        foreach ($package_arr as $key => $package) {
            if (isset($loaded_lang[$lang][$package])) {
                continue;
            }

            $loaded_lang[$lang][$package] = FALSE;
            foreach (array(APP_PATH, BASE_PATH) as $dir) {
                $file = $dir . '/language/' . $lang . '/' . $package . '.php';

                if (file_exists($file)) {
                    include $file;
                    $loaded_lang[$lang][$package] = TRUE;
                    $__LANGUAGE_PACKAGE[$lang][$package] = $_LANG;
                    $_LANG = array();
                    break;
                }
            }
        }

        if ($lang != $language && $loaded_lang[$lang][$package]) {
            $loaded_lang[$language][$package] = TRUE;
            $__LANGUAGE_PACKAGE[$language][$package] = & $__LANGUAGE_PACKAGE[$lang][$package];
        }

        if ($loaded_lang[$lang][$package] || $lang == $default_lang) {
            break;
        }

        $lang = $default_lang;
    } while (TRUE);
}

/**
 * 直接翻译语言
 *
 * - 如果指定了语言包，则在特定语言包中寻找语言对象，否则会遍历所有语言包
 * - 如果指定了语言，则在特定的语言中寻找语言对象，否则会遍历所有语言
 * - 如果指定了语言，则可指明如果未找到是否可以使用默认语言
 *
 * @param string $msg
 * @param string $package
 * @param string $language
 * @return Mixed 翻译内容，可以是字符串或数组或msg本身
 */
function lang($msg, $package = '', $language = '') {
    global $__LANGUAGE_PACKAGE;

    if ($package) {
        load_lang($package, $language);
    }

    // 全部指定
    if ($package && $language) {
        if (isset($__LANGUAGE_PACKAGE[$language][$package][$msg])) {
            return $__LANGUAGE_PACKAGE[$language][$package][$msg];
        }

        if (isset($__LANGUAGE_PACKAGE[get_config('default_language')][$package][$msg])) {
            return $__LANGUAGE_PACKAGE[get_config('default_language')][$package][$msg];
        }
    }

    // 仅指定语言
    if (!$package && $language && $__LANGUAGE_PACKAGE[$language]) {
        foreach ($__LANGUAGE_PACKAGE[$language] as $pkg => $_) {
            if (isset($__LANGUAGE_PACKAGE[$language][$pkg][$msg])) {
                return $__LANGUAGE_PACKAGE[$language][$pkg][$msg];
            }

            if (isset($__LANGUAGE_PACKAGE[get_config('default_language')][$pkg][$msg])) {
                return $__LANGUAGE_PACKAGE[get_config('default_language')][$pkg][$msg];
            }
        }
    }

    // 仅指定语言包
    if ($package && !$language) {
        foreach ($__LANGUAGE_PACKAGE as $lang => $_) {
            if (isset($__LANGUAGE_PACKAGE[$lang][$package][$msg])) {
                return $__LANGUAGE_PACKAGE[$lang][$package][$msg];
            }
        }
    }

    // 什么都没指定
    if (!$package && !$language) {
        foreach ($__LANGUAGE_PACKAGE as $lang => &$pkg) {
            if (!is_array($pkg)) {
                continue;
            }

            foreach ($pkg as $key => $value) {
                if (isset($__LANGUAGE_PACKAGE[$lang][$key][$msg])) {
                    return $__LANGUAGE_PACKAGE[$lang][$key][$msg];
                }
            }
        }
    }

    return $msg;
}

/**
 * 带参数翻译语言
 *
 * 参数可以在翻译后的语句中被替换为值。
 * <code>
 * $source = "my name is {name}";
 * // $dest = "my name is eachcan";
 * $dest = elang($source, array("name" => "eachcan"));
 * </code>
 *
 * @see lang
 * @param string $msg 要翻译的语句
 * @param string $param_arr 加入的参数
 * @param string $package 语言包
 * @param string $language 语言
 * @return
 */
function elang($msg, $param_arr = array(), $package = '', $language = '') {
    global $__LANGUAGE_PACKAGE;

    $msg = lang($msg, $package, $language);

    foreach ($param_arr as $key => $val) {
        $msg = str_replace('{' . $key . '}', $val, $msg);
    }

    return $msg;
}

/**
 * 跳转到目标页面
 *
 * 自动发送 301 转向的 Http 状态码。如果是站内地址，可以直接写 get_url 兼容的URL地址。
 * <code>
 * redirect("welcome/index?id=1");
 * </code>
 *
 * @see set_status_header()
 * @param string $url
 * @param boolean $clean
 */
function redirect($url, $clean = TRUE) {
    if (strncmp($url, 'http://', 7) != 0 && strncmp($url, 'https://', 8) != 0) {
        $url = $clean ? get_clean_url($url) : get_url($url);
    }

    set_status_header(301);

    if (TEST_MODEL) {
        header('location:' . $url);
    } else {
        @header('location:' . $url);
    }
    exit();
}

/**
 * 取得当前URL
 *
 * @return string 当前URL
 */
function current_url() {
    $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    return $url;
}

/**
 * 取得来源页面地址
 *
 * @param string $url
 * @return string referer
 */
function referer($url = '') {
    $uri = $_SERVER['REQUEST_URI'];

    if ($_POST['referer']) {
        return $_POST['referer'];
    }

    if ($_GET['referer']) {
        return $_GET['referer'];
    }

    if ($_SERVER['HTTP_REFERER']) {
        $cur_url = current_url();
        if (!is_same_url($cur_url, $_SERVER['HTTP_REFERER'])) {
            return $_SERVER['HTTP_REFERER'];
        }
    }

    return $url;
}

/**
 * 判断是否为相同的URL
 *
 * 根据当前的URL规则，判断两个URL中包含的目录、类、方法是否相同。
 *
 * @param string $url1
 * @param string $url2
 * @return string
 */
function is_same_url($url1, $url2) {
    $c = parse_url($url1);
    $d = parse_url($$url2);
    $up = get_config('uri_protocol');
    $same_q = TRUE;
    if ($up == 'QUERY_STRING') {
        parse_str($c['query'], $e);
        parse_str($d['query'], $f);

        if ($e[get_config('controller_trigger')] != $f[get_config('controller_trigger')]) {
            $same_q = FALSE;
        }
        if ($e[get_config('function_trigger')] != $f[get_config('function_trigger')]) {
            $same_q = FALSE;
        }
        if ($e[get_config('directory_trigger')] != $f[get_config('directory_trigger')]) {
            $same_q = FALSE;
        }
    }

    if ($same_q) {
        return $c['host'] == $d['host'] && $c['path'] == $d['path'];
    }

    return $same_q;
}

/**
 * 取得当前执行路径
 *
 * 执行路径包括：目录、类、方法，组成了URL，比如：welcome/index
 * @return string
 */
function current_act() {
    global $__DIR, $__CLASS, $__METHOD;
    $class = strtolower(substr($__CLASS, 0, -10));

    return str_replace('\\', '/', $__DIR) . $class . '/' . $__METHOD;
}

function set_style($style) {
    $l = & get_config('view_path');
    $style = preg_replace('/\W/', '', $style);
    $l = $style ? $style : get_config('default_view_path');
}

/**
 * 加密函数
 * @param string $string
 * @param enum $operation
 * @param string $key
 * @param timestamp $expiry
 * @return string
 */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
    $ckey_length = 6;
    $key = md5($key ? $key : $GLOBALS['']);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);

    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    $string_length = strlen($string);

    $result = '';

    $box = range(0, 255);
    $rndkey = array();
    for($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;

        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }

    if($operation == 'DECODE') {
        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc.str_replace('=', '', base64_encode($result));
    }
}


