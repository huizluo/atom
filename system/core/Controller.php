<?php
namespace system\core;
/**
 * Controller Class
 *
 * 控制器是MVC框架的主要逻辑处理部分，与业务逻辑有直接的关系。通过继承控制器类，即可被框架
 * 自动根据URL规则进行调用。
 * 
 * 控制器还有渲染引擎选择的作用，在没有指定渲染引擎时，选择使用入口指定的渲染引擎，否则使用
 * 指定的渲染引擎。
 *
 * @package		AtomCode
 * @subpackage	core
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */
class Controller {

	private static $instance;

	private $values;

	private $config;

	/**
	 * Constructor
	 */
	public function __construct() {
		self::$instance = & $this;
		$this->config = & get_config();
		$this->values = array();
		log_message('debug', "Controller Class Initialized");
	}

	/**
	 * 取得当前正在运行的控制器实例
	 * 
	 * 此方法主要用于 Hook，可以在任意一个类中使用。需要注意的是，在控制器被实例化之前，调
	 * 用此方法会返回 null
	 * 
	 * @return Controller 正在运行的控制器实例
	 */
	public static function &instance() {
		return self::$instance;
	}

	/**
	 * 为模板变量赋值
	 * 
	 * 赋值将会传递给渲染引擎，用于输出
	 * 
	 * @param String $name
	 * @param String $value
	 */
	public function _assign($name, $value = '') {
		if (is_array($name)) {
			$this->values = array_merge($this->values, $name);
		} else {
			$this->values[$name] = $value;
		}
	}

	/**
	 * 是否为POST类型
	 * 
	 * @return boolean
	 */
	protected function isPost() {
		return strtolower($_SERVER['REQUEST_METHOD']) == 'post';
	}

	/**
	 * 是否是Ajax调用
	 * 
	 * 判断的依据是通过浏览器 XMLHttpRequest 组件访问或在URL中包含 ajax 参数或在表单中包含 ajax
	 * 字段即被认为是 Ajax 提交。
	 */
	protected function isAjax() {
		return $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' || $_GET['ajax'] || $_POST['ajax'];
	}

	/**
	 * 最终输出
	 * 
	 * 此处会选择一个渲染引擎，将结果格式化后输出到浏览器，一般在控制器方法执行完毕后返回此方法的结果即
	 * 可。示例：
	 * <code>
	 * return $this->_display('welcome/index');
	 * </code>
	 * 同时可以指定一个渲染引擎，以便只用于输出特定格式的数据
	 * @param String $view_name 视图名，具体含义与渲染引擎有关。<br>对于 HtmlRender ，此参数表示模板名；对于 XmlRender 来说，此参数可选： atom, rss, csv, xml, 或者其他支持格式
	 * @param String $render 渲染引擎名，与入口文件定义不同的是，此处需要传递完整的类名，比如：HtmlRender，注意区分大小写
	 */
	public function _display($view_name, $render = '') {
		if ($render) {
			$r = new $render();
			$r->setEnv(array($view_name, $this->values));
			return $r;
		} else {
			return array($view_name, $this->values);
		}
	}

	protected function startTask($task, $instance_count = 1) {
		$task = base64_encode($task);
		if (!file_exists(APP_PATH . '/cache/task')) mkdir(APP_PATH . '/cache/task', 0777, TRUE);
		
		while ($instance_count--) {
			$file = APP_PATH . '/cache/task/.task_' . $task . '.' . $instance_count;
			$this->task = fopen($file, 'w+');
			if (flock($this->task, LOCK_EX | LOCK_NB)) {
				return TRUE;
			} else {
				fclose($this->task);
			}
		}
		
		return false;
	}

	protected function endTask() {
		if (is_resource($this->task)) {
			fclose($this->task);
		}
	}
}