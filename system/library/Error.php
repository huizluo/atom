<?php
namespace system\library;

/**
 * Error Class
 *
 * 错误处理类，可显示错误，并记录错误日志
 *
 * @package		AtomCode
 * @subpackage	library
 * @category	library
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/user_guide/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource	$Id$
 */
use system\library\exception\ValidateException;

class Error {

	private $action;

	private $severity;

	private $message;

	private $filename;

	private $line;

	private $ob_level;

	private static $instance;

	private $levels = array(E_DEPRECATED => 'Error', E_ERROR => 'Error', E_WARNING => 'Warning', E_PARSE => 'Parsing Error', E_NOTICE => 'Notice', E_CORE_ERROR => 'Core Error', E_CORE_WARNING => 'Core Warning', E_COMPILE_ERROR => 'Compile Error', E_COMPILE_WARNING => 'Compile Warning', E_USER_ERROR => 'User Error', E_USER_WARNING => 'User Warning', E_USER_NOTICE => 'User Notice', E_STRICT => 'Runtime Notice');

	/**
	 * error: E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR
	 * debug: E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING
	 * info: E_NOTICE, E_USER_NOTICE
	 * all: E_STRICT
	 */
	private $levelName = array(E_ERROR => 'error', E_WARNING => 'debug', E_PARSE => 'error', E_NOTICE => 'info', E_CORE_ERROR => 'error', E_CORE_WARNING => 'debug', E_COMPILE_ERROR => 'error', E_COMPILE_WARNING => 'debug', E_USER_ERROR => 'error', E_USER_WARNING => 'debug', E_USER_NOTICE => 'info', E_STRICT => 'all');

	/**
	 * Constructor
	 */
	private function __construct() {}

	/**
	 * 
	 * @return Error
	 */
	public static function &instance() {
		if (!isset(self::$instance)) {
			self::$instance = new Error();
		}
		
		self::$instance->ob_level = ob_get_level();
		return self::$instance;
	}

	/**
	 * Exception Logger
	 *
	 * This function logs PHP generated error messages
	 *
	 * @access	public
	 * @param	string	the error severity
	 * @param	string	the error string
	 * @param	string	the error filepath
	 * @param	string	the error line number
	 * @return	string
	 */
	public function log_exception($severity, $message, $filepath, $line) {
		$levelName = (!isset($this->levelName[$severity])) ? 'all' : $this->levelName[$severity];
		$severity = (!isset($this->levels[$severity])) ? $severity : $this->levels[$severity];
		
		log_message($severity . ' - ' . $message . ' ' . $filepath . ' ' . $line, $levelName, TRUE);
	}

	// --------------------------------------------------------------------
	

	/**
	 * 404 Page Not Found Handler
	 *
	 * @access	public 
	 * @param	string
	 * @return	string
	 */
	public function show_404($page = '') {
		$heading = "404 Page Not Found";
		$message = "The page you requested was not found.<br />$page";
		if (IS_CLI) {
			$this->show_std_error($message, $heading);
		}
		
		echo $this->show_error($message, $heading);
		exit();
	}

	// --------------------------------------------------------------------
	

	/**
	 * General Error Page
	 *
	 * This function takes an error message as input
	 * (either as a string or an array) and displays
	 * it using the specified template.
	 *
	 * @access	public 
	 * @param	string	the heading
	 * @param	string	the message
	 * @param	string	the template name
	 * @return	string
	 */
	public function show_error($message, $title = '', $config = array(), $template = 'error') {
		$title || $title = "Error Reporting";
		
		if (IS_CLI) {
			$this->show_std_error($message, $title, $config);
		}
		
		if (ob_get_level() > $this->ob_level + 1) {
			ob_end_flush();
		}
		
		ob_start();
		include (BASE_PATH . '/error/' . $template . '.php');
		$buffer = ob_get_contents();
		ob_end_clean();
		echo $buffer;
	}

	/**
	 * Native PHP error handler
	 *
	 * @access	private
	 * @param	string	the error severity
	 * @param	string	the error string
	 * @param	string	the error filepath
	 * @param	string	the error line number
	 * @return	string
	 */
	public function show_php_error($severity, $body, $filepath, $line) {
		if (IS_CLI) {
			$this->show_std_error('PHP Error', "Severity:\t$severity\nMessage:\t$body\nFile:\t\t$filepath\nline:\t\t$line");
		}
		$severity = (!isset($this->levels[$severity])) ? $severity : $this->levels[$severity];
		
		$filepath = str_replace("\\", "/", $filepath);
		
		// For safety reasons we do not show the full file path
		if (FALSE !== strpos($filepath, '/')) {
			$x = explode('/', $filepath);
			$filepath = $x[count($x) - 2] . '/' . end($x);
		}
		
		if (ob_get_level() > $this->ob_level + 1) {
			ob_end_flush();
		}
		
		$title = "PHP $severity";
		$message[] = $body;
		$message[] = "File: " . $filepath;
		$message[] = "Line: " . $line;
		
		ob_start();
		include (BASE_PATH . '/error/error.php');
		$buffer = ob_get_contents();
		ob_end_clean();
		echo $buffer;
	}

	/**
	 * 处理异常及调试信息
	 * @param \Exception | Mixed $obj
	 * @param Boolean $is_exception
	 */
	public function debug($obj, $is_exception = FALSE) {
		$is_exception = $is_exception && ($obj instanceof \Exception);
		$is_valid_exception = is_object($obj) && ($obj instanceof ValidateException);
		
		if ($is_exception) {
			$trace = $obj->getTrace();
		} else {
			$trace = debug_backtrace();
			
			$trace[1]['file'] = $trace[0]['file'];
			$trace[1]['line'] = $trace[0]['line'];
			array_shift($trace);
		}
		$title = 'PHP Exception';
		
		if (IS_CLI) {
			$t = array();
			$s = array();
			foreach ($trace as $id => $trace1) {
				$s[] = "Exception $id#:\n";
				foreach ($trace1 as $k => $v) {
					$s[] = "$k: " . var_export($v, TRUE) . "\n";
				}
				
				$t[] = implode("", $s);
			}
			
			$this->show_std_error(implode("\n", $t), $title);
		}
		
		ob_start();
		$exception = $trace;
		include (BASE_PATH . '/error/error.php');
		$buffer = ob_get_contents();
		ob_end_clean();
		echo $buffer;
		
		if ($is_exception) {
			exit();
		}
	}

	private function show_std_error($message, $heading, $config = array()) {
		if (is_array($message)) {
			$msg = '';
			foreach ($message as $k => $v) {
				if ($v == '--') {
					$msg .= "\n";
				}
				$msg .= $k . ":\t" . $v . "\n";
			}
			echo "$heading\n$msg";
		} else {
			echo "$heading\n$message\n";
		}

		if (!function_exists('__function_error_show_config_item_cli')) {
			function __function_error_show_config_item_cli($config, $level = 0) {
				foreach ($config as $key => $config2) {
					if (is_array($config2)) {
						echo "$key :" . PHP_EOL;
						__function_error_show_config_item_cli($config2, $level + 1);
					} else {
						echo str_repeat(" ", $level * 4) . "$key: " . (is_bool($config2) ? ($config2 ? "TRUE" : "FALSE") : $config2) . PHP_EOL;
					}
				}
			}
		}
		
		if ($config && is_array($config)) {
			echo PHP_EOL . "Configure:" . PHP_EOL;
			__function_error_show_config_item_cli($config);
		}
	}
}
// End Error Class

/* End of file Error.php */
/* Location: ./system/library/Error.php */