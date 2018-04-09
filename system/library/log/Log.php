<?php
namespace system\library\log;
/**
 * Log
 *
 * 日志类，可以定义记录日志的类型
 *
 * @package AtomCode
 * @subpackage library
 * @category library
 * @author Eachcan<eachcan@gmail.com>
 * @license http://digglink.com/user_guide/license.html
 * @link http://digglink.com
 * @since Version 2.0
 * @filesource
 */
use system\library\Singleton;

class Log extends Singleton {

	protected $_log_path;

	protected $_threshold = 0;

	protected $_date_fmt = 'Y-m-d H:i:s';

	protected $_enabled = TRUE;

	protected $_levels = array('ERROR' => '1', 'DEBUG' => '2', 'INFO' => '3', 'ALL' => '4');

	/**
	 *
	 * @return Log
	 */
	public static function &instance() {
		return self::getInstance();
	}

	/**
	 * Constructor
	 */
	protected function __construct() {
		$this->initialize();
	}

	protected function initialize() {
		if (is_numeric(get_config('log_threshold'))) {
			$this->_threshold = get_config('log_threshold');
		}
		
		if (!$this->_threshold) {
			$this->_enabled = FALSE;
			return;
		}
		
		$this->_log_path = get_config('log_path');
		if (!$this->_log_path) {
			$this->_log_path = APP_PATH . '/logs/';
		}
		
		if (!is_dir($this->_log_path) || !is_really_writable($this->_log_path)) {
			$this->_enabled = FALSE;
			return ;
		}
		
		$this->_date_fmt = get_config('log_date_format', $this->_date_fmt);
	}
	
	public function write($msg, $level = 'error', $type = 'log') {
		if ($this->_enabled === FALSE) {
			return FALSE;
		}
		
		$level = strtoupper($level);
		
		if (!isset($this->_levels[$level]) || ($this->_levels[$level] > $this->_threshold)) {
			return FALSE;
		}
		
		$filepath = $this->_log_path . $type . '-' . $level . '-' . date('Y-m-d') . '.php';
		$message = '';
		
		if (!$fp = fopen($filepath, 'ab')) {
			return FALSE;
		}
		
		$message .= $level . ' - ' . date($this->_date_fmt) . ' --> ' . $msg . "\n";
		
		flock($fp, LOCK_EX);
		fwrite($fp, $message);
		flock($fp, LOCK_UN);
		fclose($fp);
		
		@chmod($filepath, 0777);
		return TRUE;
	}
}