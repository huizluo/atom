<?php

/*
 * Example: http://example.com/
 * If exists, ends with '/'
 */
$config['base_url'] = '';

/*
 * Index file
 * If your server don't support 'rewrite', 'index.php?' is recommend
 */
$config['index_page'] = 'index.php?';

/*
 * URL suffix
 *
 * This option allows you to add a suffix to url path, example: 
 * http://example.com/info/show.html?id=123
 */

$config['url_suffix'] = '';

/*
 * Default language
 *
 * This determines which set of language files should be used. Make sure
 * there is an available translation if you intend to use something other
 * than english.
 *
*/
$config['default_language'] = 'zh';
$config['language'] = $config['default_language'];

/*
 * 支持的语言列表
 * @var Array
 */
$config['languages'] = array('zh');

/*
 * Language decision
 * 
 * AtomCode allows you set page language in cookie/session/segment/query parameters
 * If 'segment' is set as a value, $_SERVER['PATH_INFO'] will affect both uri route
 * and language, for example: /cn/article/show may behalf /cn/ArticleController or 
 * chinese language
 */
$config['language_decision'] = '';
$config['language_decision_key'] = '';

/*
 * 默认输出编码
 *
 * This determines which character set is used by default in various methods
 * that require a character set to be provided.
 *
*/
$config['charset'] = 'UTF-8';

/*
 * Error Logging Threshold
 *
 * If you have enabled error logging, you can set an error threshold to
 * determine what gets logged. Threshold options are:
 * You can enable error logging by setting a threshold over zero. The
 * threshold determines what gets logged. Threshold options are:
 *
 *	0 = Disables logging, Error logging TURNED OFF
 *	1 = Error Messages (including PHP errors)
 *	2 = Debug Messages
 *	3 = Informational Messages
 *	4 = All Messages
 *
 * For a live site you'll usually only enable Errors (1) to be logged otherwise
 * your log files will fill up very fast.
 *
*/
$config['log_threshold'] = 0;

/*
 * Error Logging Directory Path
 *
 * Leave this BLANK unless you would like to set something other than the default
 * application/logs/ folder. Use a full server path with trailing slash.
 *
*/
$config['log_path'] = '';

/*
 * Date Format for Logs
 *
 * Each item that is logged has an associated date. You can use PHP date
 * codes to set your own date formatting
 *
*/
$config['log_date_format'] = 'Y-m-d H:i:s';

/*
 * Log Message Destination
 * 
 * Will wirte log message to file or console.
 */
$config['log_destination'] = 'file';

/*
 * Session Variables
 *
 * sess_driver	Drivers: session, database, memcache
 * 
 * Note:
 * database driver need session table and SessionModel
 *
*/
$config['session']['driver'] = '';
$config['session']['match_ip'] = FALSE;
$config['session']['table'] = '';
$config['session']['expiration'] = 7200;
// memcache configuration
$config['session']['mem_host'] = '';
$config['session']['mem_port'] = '';

/*
 *--------------------------------------------------------------------------
 * Cookie Related Variables
 *--------------------------------------------------------------------------
 *
 * 'prefix' = Set a prefix if you need to avoid collisions
 * 'domain' = Set to .your-domain.com for site-wide cookies
 * 'path'   =  Typically will be a forward slash
 * 'secure' =  Cookies will only be set if a secure HTTPS connection exists.
 *
*/
$config['cookie']['prefix'] = "";
$config['cookie']['domain'] = "";
$config['cookie']['path'] = "/";
$config['cookie']['secure'] = FALSE;
$config['cookie']['encrypt'] = FALSE;
$config['cookie']['key'] = '';

/*
 * Global XSS Filtering
 *
 * Determines whether the XSS filter is always active when GET, POST or
 * COOKIE data is encountered
 *
*/
$config['global_xss_filtering'] = FALSE;

/*
 * Cross Site Request Forgery
 * 
 * Enables a CSRF cookie token to be set. When set to TRUE, token will be
 * checked on a submitted form. If you are accepting user data, it is strongly
 * recommended CSRF protection be enabled.
 *
 * 'csrf_token_name' = The token name
 * 'csrf_cookie_name' = The cookie name
 * 'csrf_expire' = The number in seconds the token should expire.
*/
$config['csrf_protection'] = FALSE;
$config['csrf_token_name'] = 'csrf_test_name';
$config['csrf_cookie_name'] = 'csrf_cookie_name';
$config['csrf_expire'] = 7200;


/*
 * Master Time Reference
 *
 * Options are 'local' or 'gmt'.  This pref tells the system whether to use
 * your server's local time as the master 'now' reference, or convert it to
 * GMT.  See the 'date helper' page of the user guide for information
 * regarding date handling.
 *
*/
$config['time_zone'] = 'Asia/Harbin';

/*
 * Reverse Proxy IPs
 *
 * You need specify a white list when your website is behind proxies, otherwise,
 * get_ip() function will not work properly.
 *
*/
$config['proxy_ips'] = '';
/*
 * View Path
 * 
 * This option tell templete engine the path of views
 * view_ext: the suffix of the view file
 * view_path_decision: same as language_decision
 */
$config['view_path'] = 'default';
$config['view_ext'] = '.tpl';
$config['view_path_decision'] = '';
$config['view_path_decision_key'] = '';

/*
 * whether to enable benchmark
 */
$config['enable_benchmark'] = FALSE;
/* Location: ./application/config/config.php */