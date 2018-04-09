<?php
namespace system\library;

/**
 * Benchmark Class
 * 
 * 你可以使用这个类标记你程序的执行过程，并可以计算两个标记之间的时间
 * 内存占用可以在运行过程中取得
 * 
 * 本类运行依赖于配置 enable_benchmark 位置配置文件中
 * 
 * @package		AtomCode
 * @subpackage	library
 * @category	library
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/user_guide/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */
class Benchmark {
	
	private $marker = array();
	private static $instance;
	
	private function __construct() {
		$this->mark(0);
	}
	
	/**
	 * @return Benchmark
	 */
	public static function &instance() {
		if (!isset(self::$instance)) {
			self::$instance = new Benchmark();
		}
		
		return self::$instance;
	}

	/**
	 * 加入一个标记
	 *
	 * 注意：标记名不能重复，如果重复则以最后一次为准
	 *
	 * @access	public
	 * @param	string	$name	标记名
	 * @return	void
	 */
	public function mark($name) {
		$this->marker[$name] = microtime(true);
	}

	/**
	 * 计算两个标记之间的时间
	 * 
	 * 返回值规则如下：
	 * 1. 不存在的标记被置为空标记
	 * 2. 第一个标记为空则认为是开始标记
	 * 3. 第二个标记为空则认为是结束标记
	 * 4. 返回为两个标记时间间隔
	 *
	 * @access	public
	 * @param	string	第一个标记
	 * @param	string	第二个标记
	 * @param	integer	精度，即小数位数
	 * @return	mixed
	 */
	public function elapsedTime($point1 = '', $point2 = '', $precision = 4) {
		if (!isset($this->marker[$point1])) {
			reset($this->marker);
			$point1 = key($this->marker);
		}
		
		if (!isset($this->marker[$point2])) {
			end($this->marker);
			$point2 = key($this->marker);
		}
		
		return round($this->marker[$point2] - $this->marker[$point1], $precision);
	}

	/**
	 * 内存占用量
	 *
	 * @access	public
	 * @return	string
	 */
	public function memoryUsage() {
		return memory_get_usage();
	}

	/**
	 * 从程序开始到现在的执行时间
	 * 
	 * @see Benchmark::elapsedTime()
	 * @param float $decimals
	 */
	public function execTime($decimals = 4) {
		return microtime(true) - reset($this->marker);
	}
	
	/**
	 * 取得用户组和用户ID
	 */
	public function getUid() {
		return getmygid() . ':' . getmyuid();
	}
	
	/**
	 * 取得运行模式
	 * 
	 */
	public function getRunMode() {
		return php_sapi_name();
	}
}
// END Benchmark class

/* End of file Benchmark.php */
/* Location: ./system/library/Benchmark.php */