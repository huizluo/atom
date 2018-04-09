<?php
namespace system\library;

/**
 * XML Class
 *
 * XML处理类
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
class Xml {

	private $parser;

	private $document;

	private $parent;

	private $stack;

	private $last_opened_tag;

	/**
	 * 构造函数
	 * 可通过指定编码进行解析不同的 XML 文档
	 * @param string $charset utf-8 | gbk
	 */
	public function __construct($charset = 'utf-8') {
		$this->parser = xml_parser_create($charset);
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, 'open', 'close');
		xml_set_character_data_handler($this->parser, 'data');
	}

	public static function toArray(&$xml) {
		$xml_parser = new Xml();
		$data = $xml_parser->parse($xml);
		
		$arr = Xml::xml_format_array($data);
		unset($xml_parser);
		return $arr;
	}

	public static function fromArray(&$data, $htmlon = 0, $level = 1) {
		$space = str_repeat("\t", $level);
		$cdatahead = $htmlon ? '<![CDATA[' : '';
		$cdatafoot = $htmlon ? ']]>' : '';
		
		$s = '';
		
		if (!empty($data)) {
			foreach ($data as $key => $val) {
				if (!is_array($val)) {
					$val = "$cdatahead$val$cdatafoot";
					if (is_numeric($key)) {
						$s .= "$space<item_$key>$val</item_$key>";
					} elseif ($key === '') {
						$s .= '';
					} else {
						$s .= "$space<$key>$val</$key>";
					}
				} else {
					if (is_numeric($key)) {
						$s .= "$space<item_$key>" . Xml::fromArray($val, $htmlon, $level + 1) . "$space</item_$key>";
					} elseif ($key === '') {
						$s .= '';
					} else {
						$s .= "$space<$key>" . Xml::fromArray($val, $htmlon, $level + 1) . "$space</$key>";
					}
				}
			}
		}
		$s = preg_replace("/([\x01-\x09\x0b-\x0c\x0e-\x1f])+/", ' ', $s);
		return ($level == 1 ? "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?><root>" : '') . $s . ($level == 1 ? '</root>' : '');
	}

	public static function xml_format_array($arr, $level = 0) {
		foreach ((array) $arr as $key => $val) {
			if (is_array($val)) {
				$val = Xml::xml_format_array($val, $level + 1);
			}
			if (is_string($key) && strpos($key, 'item_') === 0) {
				$arr[intval(substr($key, 5))] = $val;
				unset($arr[$key]);
			} else {
				$arr[$key] = $val;
			}
		}
		return $arr;
	}

	/**
	 * 施放解析器
	 */
	public function destruct() {
		xml_parser_free($this->parser);
	}
	
	public function __destruct() {
		$this->destruct();
	}

	/**
	 * 开始解析XML内容
	 * @param string $data
	 */
	public function parse(&$data) {
		$this->document = array();
		$this->stack = array();
		$this->parent = &$this->document;
		return xml_parse($this->parser, $data, true) ? $this->document : xml_error_string(xml_get_error_code($this->parser)) . " Line: " . xml_get_current_line_number($this->parser);
	}

	/**
	 * 回调函数，开始标签
	 * @param Resource $parser
	 * @param string $tag
	 * @param array $attributes
	 */
	public function open(&$parser, $tag, $attributes) {
		$this->data = '';
		$this->last_opened_tag = $tag;
		if (is_array($this->parent) and array_key_exists($tag, $this->parent)) {
			if (is_array($this->parent[$tag]) and array_key_exists(0, $this->parent[$tag])) {
				$key = $this->count($this->parent[$tag]);
			} else {
				if (array_key_exists($tag . ' attr', $this->parent)) {
					$arr = array(
						'0 attr' => &$this->parent[$tag . ' attr'], &$this->parent[$tag]
					);
					unset($this->parent[$tag . ' attr']);
				} else {
					$arr = array(
						&$this->parent[$tag]
					);
				}
				$this->parent[$tag] = &$arr;
				$key = 1;
			}
			$this->parent = &$this->parent[$tag];
		} else {
			$key = $tag;
		}
		if ($attributes) {
			$this->parent[$key . ' attr'] = $attributes;
		}
		$this->parent = &$this->parent[$key];
		$this->stack[] = &$this->parent;
	}

	/**
	 * 回调函数，处理CData数据
	 * @param Resource $parser
	 * @param String $data
	 */
	public function data(&$parser, $data) {
		if ($this->last_opened_tag != NULL) $this->data .= ViewHelper::txt2html($data);
	}

	/**
	 * 回调函数，用于处理闭合标签
	 * @param Resource $parser
	 * @param String $tag
	 */
	public function close(&$parser, $tag) {
		if ($this->last_opened_tag == $tag) {
			$this->parent = $this->data;
			$this->last_opened_tag = NULL;
		}
		array_pop($this->stack);
		if ($this->stack) $this->parent = &$this->stack[count($this->stack) - 1];
	}

	/**
	 * 计算数组中值的个数
	 * @param unknown_type $array
	 */
	public function count(&$array) {
		return is_array($array) ? count(array_filter(array_keys($array), 'is_numeric')) : 0;
	}
}
// End Xml Class

/* End of file Xml.php */
/* Location: ./system/library/Xml.php */