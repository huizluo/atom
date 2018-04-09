<?php
namespace system\library;

/**
 * Uri Class
 *
 * URI解析类，可以尝试从QueryString中解析兼容模式的URI,
 *
 * @package		AtomCode
 * @subpackage	library
 * @category	library
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 2.0
 * @filesource
 */
class Uri {

	private static $instance;

	public $segments;

	private $uri_string;
	
	const controller_suffix = 'Controller';

	/**
	 * Constructor
	 *
	 * 初始化 Uri 类，
	 *
	 * @access	public
	 */
	private function __construct() {
		$this->parseUri();
		log_message("Uri Class Initialized", 'debug');
	}

	/**
	 * 
	 * @return Uri
	 */
	public static function &instance() {
		if (!isset(self::$instance)) {
			self::$instance = new Uri();
		}
		
		return self::$instance;
	}

	/**
	 * Detects the URI
	 *
	 * This function will detect the URI automatically and fix the query string
	 * if necessary.
	 *
	 * @access	private
	 * @return	string
	 */
	private function detectUri() {
		if (!isset($_SERVER['REQUEST_URI'])) {
			return '';
		}
		
		$uri = $_SERVER['REQUEST_URI'];
		if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0) {
			$uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
		} elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0) {
			$uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
		}
		
		// This section ensures that even on servers that require the URI to be in the query string (Nginx) a correct
		// URI is found, and also fixes the QUERY_STRING server var and $_GET array.
		$uri = ltrim($uri, '?/');
		$parts = preg_split('#\?|&#i', $uri, 2);
		$uri = $parts[0];
		if (isset($parts[1])) {
			$_SERVER['QUERY_STRING'] = $parts[1];
			parse_str($_SERVER['QUERY_STRING'], $_GET);
		} else {
			$_SERVER['QUERY_STRING'] = '';
			$_GET = array();
		}
		
		if ($uri == '/' || empty($uri)) {
			return '/';
		}
		
		$uri = parse_url($uri, PHP_URL_PATH);
		
		// Do some final cleaning of the URI and return it
		return str_replace(array('//', '../'), '/', trim($uri, '/'));
	}

	/**
	 * Parse cli arguments
	 *
	 * Take each command line argument and assume it is a URI segment.
	 *
	 * @access	private
	 * @return	string
	 */
	private function detectCliUri() {
		$args = array_slice($_SERVER['argv'], 1);
		$uri = '';
		$query = '';
		
		if (isset($args[0])) {
			if (strpos($args[0], '?') === FALSE && strpos($args[0], '=') === FALSE) {
				$uri = array_shift($args);
			}
		}
		
		if (isset($args[0])) {
			if (strpos($args[0], '?') === 0) {
				$query = array_shift($args);
				$_SERVER['QUERY_STRING'] = str_replace(array('?', '/'), '&', substr($query, 1));
				parse_str($_SERVER['QUERY_STRING'], $_GET);
			}
		}
		
		if (count($args)) {
			parse_str(implode('&', $args), $_POST);
		}
		
		return $uri;
	}

	private function detectPathInfo() {
		$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
		return ltrim($path, '/');
	}

	public function parseUri() {
		$this->segments = array();
		
		if (IS_CLI) {
			$this->setUri($this->detectCliUri());
		} else {
			$uri = $this->detectUri();
			if (!$uri) {
				$uri = $this->detectPathInfo();
			}
			$this->setUri($uri);
		}
		
		$segments = explode("/", $this->removeUrlSuffix($this->uri_string, get_config('url_suffix')));
		foreach ($segments as $seg) {
			if ($seg) {
				$this->segments[] = $this->filterUri($seg);
			}
		}
	}

	private function setUri($uri) {
		if (preg_match('/^[\w\-\.\/]+$/', $uri)) {
			$uri = preg_replace('/\.+/', '.', $uri);
			$this->uri_string = $uri;
		} else {
			$this->uri_string = '';
		}
	}

	public function getUriString() {
		return $this->uri_string;
	}

	public function segment($n) {
		return $this->segments[$n];
	}

	/**
	 * Filter segments for malicious characters
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	public function filterUri($str) {
		// Convert programatic characters to entities
		$bad = array('$', '(', ')', '%28', '%29');
		$good = array('&#36;', '&#40;', '&#41;', '&#40;', '&#41;');
		
		return str_replace($bad, $good, $str);
	}

	/**
	 * Remove the suffix from the URL if needed
	 *
	 * @access	private
	 * @return	void
	 */
	private function removeUrlSuffix($uri, $suffix) {
		if ($suffix != "") {
			return preg_replace("|" . preg_quote($suffix) . "$|", "", $this->uri_string);
		}
		
		return $uri;
	}
}