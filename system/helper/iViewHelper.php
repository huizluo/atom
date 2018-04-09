<?php
namespace system\helper;
/**
 * iViewHelper Class
 * 
 * 模板解析引擎需要的修饰函数，如果要实现更多的方法，请继承本类，并实现新的方法
 * 
 * @see			ViewHelper
 * @package		AtomCode
 * @subpackage	helper
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */

abstract class iViewHelper {

	/**
	 * 截取字符串
	 * @param string $val
	 * @param string $begin
	 * @param string $len
	 */
	public static function substr($val, $begin, $len = null) {
		return substr($val, $begin, $len);
	}

	/**
	 * 截取中文字符串
	 * @param string $val
	 * @param string $begin
	 * @param string $len
	 */
	public static function csubstr($val, $begin, $len = null, $charset = '') {
		if (!$charset)
			$charset = get_config('charset', 'utf-8');
		
		return mb_substr($val, $begin, $len, $charset);
	}

	/**
	 * 截短字符串，超过长度后显示附加字符串
	 * 
	 * @param string $val
	 * @param int $len
	 * @param string $ellipse
	 */
	public static function shorten($val, $len, $ellipse = '...') {
		if (mb_strlen($val, get_config('charset')) > $len) {
			return mb_substr($val, 0, $len, get_config('charset')) . '...';
		} else {
			return $val;
		}
	}

	/**
	 * 格式化日期 
	 * 
	 * @param int $time
	 * @param string $format
	 */
	public static function date($time, $format = 'Y-m-d H:i:s') {
		return date($format, $time);
	}

	/**
	 * 全部转换为大写
	 * 
	 * @param string $str
	 */
	public static function upper($str) {
		return strtoupper($str);
	}

	/**
	 * 全部转换为小写
	 * 
	 * @param string $string
	 */
	public static function lower($string) {
		return strtolower($string);
	}

	/**
	 * 字符串替换
	 * @param string $src
	 * @param string $search
	 * @param string $replace
	 */
	public static function replace($src, $search, $replace) {
		return str_replace($search, $replace, src);
	}

	/**
	 * 如果值为空则使用此值
	 * 
	 * 相当于 smarty 中的 default
	 * @param string $str
	 * @param string $instead
	 */
	public static function ifempty($str, $instead) {
		if (empty($str)) {
			return $instead;
		}
		
		return $str;
	}

	/**
	 * 将HTML内容以文本方式显示
	 * 
	 * 并不是去队HTML标签，而是以查看源代码方式显示
	 * 
	 * @see iViewHelper::txt2html()
	 * @param string $string
	 * @param boolean $simple 非简单模式将会把特殊字符转换为 &#xxx; 方式
	 */
	public static function html2txt($string, $simple = false) {
		if ($simple) {
			return str_replace(array('<', '>'), array('&lt;', '&gt;'), $string);
		} else {
			return htmlspecialchars($string);
		}
	}

	/**
	 * 将文本内容转为HTML
	 * 
	 * 与 {@link iViewHelper::html2txt()} 相反，将原本被转义的 HTML 转换为真正的HTML内容。
	 * @param string $string
	 */
	public static function txt2html($string) {
		return htmlspecialchars_decode($string);
	}

	/**
	 * 取得当前时间
	 */
	public static function time() {
		return TIMESTAMP;
	}

	/**
	 * 生成 HTML select 中的 option 列表
	 * Enter description here ...
	 * @param array $option
	 * @param string|int $selected_value
	 */
	public static function mkoptions($option, $selected_value) {
		$html = '';
		if (!$option || !is_array($option))
			return '';
		
		$first_item = reset($option);
		$complex = is_array($first_item);
		if ($complex) {
			if (isset($first_item[0]) && isset($first_item[1])) {
				$value_key = 0;
				$text_key = 1;
			} else {
				$free_key = array();
				foreach (array_keys($first_item) as $value) {
					if (strpos($value, 'id') !== FALSE) {
						if (!isset($value_key))
							$value_key = $value;
					} elseif (strpos($value, 'name')) {
						if (!isset($text_key))
							$text_key = $value;
					} else {
						if (count($free_key) < 2)
							array_push($free_key, $value);
					}
				}
				
				if (!isset($value_key))
					$value_key = array_shift($free_key);
				if (!isset($text_key))
					$text_key = array_shift($free_key);
				if (!$value_key || !$text_key)
					return '';
			}
			
			foreach ($option as $item) {
				$html .= '<option value="' . $item[$value_key] . '"' . ($item[$value_key] == $selected_value ? ' selected="selected"' : '') . '>' . $item[$text_key] . '</option>';
			}
		} else {
			foreach ($option as $value => $text) {
				$html .= '<option value="' . $value . '"' . ($value == $selected_value ? ' selected="selected"' : '') . '>' . $text . '</option>';
			}
		}
		
		return $html;
	}

	/**
	 * 取得JSON化后的字符串
	 * @param mixed $value
	 */
	public static function json($value) {
		return Json::encode($value);
	}

	/**
	 * 将值转化为可阅读的格式
	 * 
	 * var: 将使用 var_export 进行输出数据
	 * json: 将值转化为  json　格式字符串输出
	 * html: 将值先使用 var_export 输出，再高亮代码
	 * 
	 * @param mixed $value
	 * @param enum $type var, json, html
	 */
	public static function readable($value, $type = 'var') {
		if ($type == 'json') {
			self::json($value);
		} elseif ($type == 'html') {
			return highlight_string(var_export($value, TRUE), TRUE);
		} else {
			return var_export($value, TRUE);
		}
	
	}

	/**
	 * 将值乘以另一个值
	 * @param number $a
	 * @param number $b
	 */
	public static function multiple($a, $b) {
		return $a * $b;
	}

	/**
	 * 将值除以另一个值
	 * @param number $a
	 * @param number $b
	 */
	public static function divide($a, $b) {
		return $a / $b;
	}

	/**
	 * 将值加上另一个值
	 * @param number $a
	 * @param number $b
	 */
	public static function plus($a, $b) {
		return $a + $b;
	}

	/**
	 * 将值减去另一个值
	 * @param number $a
	 * @param number $b
	 */
	public static function minus($a, $b) {
		return $a - $b;
	}

	/**
	 * 取值的相反数
	 * @param number $a
	 */
	public static function negative($a) {
		return -$a;
	}

	/**
	 * 取值的倒数
	 * @param number $a
	 */
	public static function inserse($a) {
		return 1 / $a;
	}

	/**
	 * 取值的绝对值
	 * @param number $a
	 * @param number $b
	 */
	public static function abs($a) {
		return abs($a);
	}

	public static function mod($a, $b) {
		return fmod($a, $b);
	}

	public static function ceil($a) {
		return ceil($a);
	}

	public static function floor($a) {
		return floor($a);
	}

	public static function round($a) {
		return round($a);
	}

	/**
	 * 将值转换为两位小数的值，或转换为指定格式
	 * 
	 * @param unknown_type $a
	 * @param unknown_type $format
	 */
	public static function money($a, $format = '%.2f') {
		return sprintf($format, $a);
	}

	/**
	 * 将值使用  number_format 进行转换
	 * 
	 * @param number $a
	 * @param int $decimals
	 */
	public static function number($a, $decimals = 0) {
		if (!is_numeric($a)) {
			return '';
		}
		
		return number_format($a, $decimals);
	}

	/**
	 * 将值赋给另一个变量
	 * @param mixed $a
	 * @param mixed $b
	 */
	public static function reassign($a, &$b) {
		$b = $a;
		
		return '';
	}

	/**
	 * 如果是POST方法则返回给出的值
	 * 
	 * @param unknown_type $a
	 * @param unknown_type $b
	 */
	public static function ifpost($a, $b) {
		if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
			return $b;
		} else {
			return $a;
		}
	}

	/**
	 * 由 $a 决定是使用 $b 还是 $c
	 * 
	 * @param unknown_type $a
	 * @param unknown_type $b
	 * @param unknown_type $c
	 */
	public static function choose($a, $b, $c) {
		if ($a) {
			return $b;
		} else {
			return $c;
		}
	}
}