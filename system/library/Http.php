<?php
namespace system\library;

/**
 * Http Class
 *
 * 模拟 HTTP 访问，依赖 CURL {@link http://cn.php.net/manual/zh/book.curl.php} 库
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
class Http {
	/**
	 * Curl句柄
	 * @var CURL
	 */
	private $handle;
	/**
	 * HTTP请求超时时间
	 * @var int
	 */
	private $timeout = 20;
	
	private static $instance;
	
	/**
	 * Constructer
	 */
	private function __construct() {
		$this->handle = curl_init();
		curl_setopt($this->handle, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($this->handle, CURLOPT_AUTOREFERER, TRUE);
		curl_setopt($this->handle, CURLOPT_CONNECTTIMEOUT, $this->timeout);
		curl_setopt($this->handle, CURLOPT_HEADER, 0);
		curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, TRUE);
	}

	/**
	 * 
	 * @return Http
	 */
	public static function &instance() {
		if (!isset(self::$instance)) {
			self::$instance = new Http();
		}
		
		return self::$instance;
	}
	
	/**
	 * 设置来源URL
	 * @param string $url
	 */
	public function setReferer($url) {
		curl_setopt($this->handle, CURLOPT_REFERER, $url);
	}
	
	/**
	 * 设置超时时间
	 * @param int $second
	 */
	public function setTimeout($second=20) {
		$this->timeout = $second;
		curl_setopt($this->handle, CURLOPT_CONNECTTIMEOUT_MS, $this->timeout * 200);
		curl_setopt($this->handle, CURLOPT_TIMEOUT, $this->timeout);
	}
	
	/**
	 * 忽略之前保存的 Cookie
	 */
	public function newSession() {
		curl_setopt($this->handle, CURLOPT_COOKIESESSION, TRUE);
	}
	
	public function setCookie($cookie) {
		curl_setopt($this->handle, CURLOPT_COOKIE, $cookie);
	}
	
	/**
	 * 用Get方法请求数据
	 * @param string $url
	 */
	public function get($url) {
		curl_setopt($this->handle, CURLOPT_HTTPGET, TRUE);
		curl_setopt($this->handle, CURLOPT_URL, $url);
		return curl_exec($this->handle);
	}
	
	/**
	 * 提交数据到指定的URL
	 * @param string $url
	 * @param Array $post_data 类似于 $_GET/$_POST 取得的数据
	 */
	public function post($url, $post_data) {
		curl_setopt($this->handle, CURLOPT_POST, TRUE);
		curl_setopt($this->handle, CURLOPT_URL, $url);
		curl_setopt($this->handle, CURLOPT_POSTFIELDS, $post_data);
		
		return curl_exec($this->handle);
	}
	
	public function set($option, $value) {
		curl_setopt($this->handle, $option, $value);
	}
	
	public function error() {
		return curl_error($this->handle);
	}
}
