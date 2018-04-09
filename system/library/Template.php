<?php
namespace system\library;
/**
 * Template Class
 *
 * 模板编译类，本类可以一般通过 {@see Render} 类调用，如果直接调用，则会输出为解析后的模板而不能解析为HTML
 * 
 * 支持的解析规则：
 * 
 * <b>变量:</b>
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
class Template  {
	/**
	 * @var Template
	 */
	private static $instance;
	private $srcFile, $regx, $source, $data;
	private $tpl_base_path, $tpl_ext;
	
	private function __construct() {
		$this->source = '';
		# match bracket portion of variable
		$this->regx['bracket_var'] = '\[\$?[\w\.]+\]|\.\$?[\w\.]+';
		# match number
		$this->regx['number'] = '(?:\-?\d+(?:\.\d+)?)';
		# match "helo\"helo", or single quote
		$this->regx['dbquote'] = '"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"';
		$this->regx['sgquote'] = '\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'';
		$this->regx['quote'] = '(?:' . $this->regx['dbquote'] . '|' . $this->regx['sgquote'] . ')';
		
		# match a.b
		$this->regx['cnt'] = '\w+\.\w+';
		
		# match $var.var[num]
		$this->regx['var'] = '\$\w+(?:' . $this->regx['bracket_var'] . ')*';
		# match |func:param
		$this->regx['modifier'] = '(?:\|\w+(?::(?:' . $this->regx['number'] . '|' . $this->regx['var'] . '|' . $this->regx['quote'] . '|true|false))*)';
		# match $var.var or 123 or "string"
		$this->regx['value'] = '(?:' . $this->regx['var'] . '|' . $this->regx['number'] . '|' . $this->regx['quote'] . ')';
		# match abc=$var, efg=0, hig="string"
		$this->regx['param'] = '(?:\s+\w+\s*=\s*' . $this->regx['value'] . ')';
		
		#variable for perl regular
		# {$varible|modifier:parm .. }
		$this->preg['var'] = "~\\{(" . $this->regx['var'] . ')(' . $this->regx['modifier'] . "*)\\}~";
		$this->preg['lang'] = "~\\{lang(" . $this->regx['param'] . "*)\\s*\\}~";
		$this->preg['if'] = "~\\{(if|elseif)\\s+(.+?)\\}~";
		$this->preg['for'] = "~\\{foreach\\s+(" . $this->regx['var'] . ")(?:\\s+(\\$[a-zA-Z_]\\w+))(?:\\s+(\\$[a-zA-Z_]\\w+))?\\s*\\}~";
		$this->preg['url'] = "~\\{url(" . $this->regx['param'] . "*)\\s*\\}~";
		$this->preg['include'] = "~\\{include(" . $this->regx['param'] . "*)\\s*\\}~";
		$this->preg['block'] = "~\\{(\\w+)\\.(\\w+)(" . $this->regx['param'] . "*)\\s*\\}~";
	
		$this->tpl_base_path = trim(get_config('view_path', ''), ' /\\');
		$this->tpl_ext = trim(get_config('view_ext', '.php'));
		
		if (strlen($this->tpl_base_path)) {
			$this->tpl_base_path .= '/';
		}
		if (!$this->tpl_ext) {
			$this->tpl_ext = '.php';
		}
	}
	
	/**
	 * @param $src_path
	 * @return Template
	 */
	public static function &instance($src_file = '') {
		if (!isset(self::$instance)) {
			self::$instance = new Template();
		}
		
		if ($src_file) {
			self::$instance->setSourceFile($src_file);
		}
		
		return self::$instance;
	}
	
	/**
	 * 待解析内容
	 * @param String $source
	 */
	public function setSource($source) {
		$this->source = $source;
		$this->srcFile = '';
	}
	
	/**
	 * 设置源文件
	 * @param unknown_type $source_file
	 */
	public function setSourceFile($source_file) {
		$view_file = $this->getView($source_file);
		if (!file_exists($view_file)) {
			show_error('Can not find view file:' . $source_file, 500, 'Server Side Error!');
		}
		
		$this->srcFile = $view_file;
		$this->source = file_get_contents($view_file);
	}
	
	/**
	 * 编译模板
	 */
	public function compile() {
		$this->data = $this->source;
		$this->data = preg_replace_callback($this->preg['var'], array(&$this, 'parseVar'), $this->data);
		$this->data = preg_replace_callback($this->preg['if'], array(&$this, 'parseIf'), $this->data);
		$this->data = preg_replace_callback($this->preg['for'], array(&$this, 'parseForeach'), $this->data);
		$this->data = $this->parseSingleTag($this->data);
		$this->data = preg_replace_callback($this->preg['url'], array(&$this, 'parseUrl'), $this->data);
		$this->data = preg_replace_callback($this->preg['include'], array(&$this, 'parseInclude'), $this->data);
		$this->data = preg_replace_callback($this->preg['block'], array(&$this, 'parseBlock'), $this->data);
		$this->data = preg_replace_callback($this->preg['lang'], array(&$this, 'parseLang'), $this->data);
	}
	
	/**
	 * 将解析结果保存到目标文件
	 * @param String $dest_path
	 */
	public function save($dest_path) {
		$path = dirname($dest_path);

		if (!file_exists($dest_path)) {
			@mkdir($path, 0777, TRUE);
			@touch($path);
			@chmod($dest_path, 0600);
		}
		
		file_put_contents($dest_path, $this->data);
	}
	
	private function parseVar($matches) {
		$var = $matches[1];
		$modifiers = explode('|', trim($matches[2], '| '));
		$var = $this->_parseVar($var);
		
		foreach ($modifiers as $modifier) {
			if (!$modifier) continue;
			$var = $this->_parseModifier($var, $modifier);
		}
		return  '<?php echo ' . $var . '; ?>';
	}
	
	private function parseIf($matches) {
		return '<?php ' . (trim($matches[1]) == 'if' ? '' : '} ') . $matches[1] . '(' . $this->_parseVar($matches[2]) . ') { ?>';
	}
	
	private function parseForeach($matches) {
		$src_var = $this->_parseVar($matches[1]);
		return '<?php if (!is_array(' . $src_var . ') && !is_object(' . $src_var . ')) ' . $src_var . ' = array(); foreach (' . $src_var . ' as ' . $matches[2] . (isset($matches[3]) ? ' => ' . $matches[3] : '') . ') { ?>';
	}
	
	private function parseUrl($matches) {
		$params = $this->getParams($matches[1]);
		$func = 'get_url';
		if ($params['clean']) {
			$func = 'get_clean_url';
		}
		
		$php_string = "<?php\n";
		$php_string .= "\$__obj = explode('?', " . $this->_parseStringVar($params['url']) . ');';
		if ($params['output']) {
			$php_string .= $params['output'] . " = ";
		} else {
			$php_string .= 'echo ';
		}
		
		$php_string .= $func . '($__obj[0], $__obj[1], "' . $params['hash'] . '");?>';
		
		return $php_string;
	}
	
	private function parseBlock($matches) {
		$class = ucfirst($matches[1]) . 'Block';
		$method = $matches[2];
		$params = $this->getParams($matches[3]);
		
		
		$php_string = "<?php\n";
		$php_string .= "\$__obj = $class::instance();\$__params=array();\n";
		
		foreach ($params as $param_name => $param) {
			$php_string .= "\$__params['$param_name'] = $param;\n";
		}
		
		$php_string .= $params['output'] . " = \$__obj->$method(\$__params);?>";
		
		return $php_string;
	}
	
	private function parseSingleTag($val) {
		return str_replace(array('{else}', '{/if}', '{/foreach}'), array('<?php } else { ?>', '<?php } ?>', '<?php } ?>'), $val);
	}
	
	private function parseInclude($matches) {
		$params = $this->getParams($matches[1]);
		$filename = trim($params['file'], '"\'');
		$php_string = '<?php ' . "\n";
		$php_string .= 'if (!file_exists("' . $this->getCacheFile($filename) . '") || (TEST_MODE && filemtime("' . $this->getCacheFile($filename) . '") < filemtime("' . $this->getView($filename) . '"))) {' . "\n";
		$php_string .= '$__tpl_engine = Template::instance(' . $params['file'] . ');' . "\n";
		$php_string .= '$__tpl_engine->compile();' . "\n";
		$php_string .= '$__tpl_engine->save("' . $this->getCacheFile($filename) . '");' . "\n";
		$php_string .= '}' . "\n";
		foreach ($params as $param_name => $param) {
			$php_string .= "\$$param_name = $param;\n";
		}
		$php_string .= 'include "' . $this->getCacheFile($filename) . "\";\n";
		$php_string .= '?>' . "\n";
		
		return $php_string;
	}
	
	private function parseLang($matches) {
		$params = $this->getParams($matches[1]);
		$lang = $params['lang'];
		$language = $params['language'] ? $params['language'] : '""';
		$package = $params['package'] ? $params['package'] : '""';
		unset($params['lang']);
		unset($params['language']);
		unset($params['package']);
		
		$php_string = "<?php\n";
		
		if (count($params)) {
			$php_string .= "\$__params=array();";
			
			foreach ($params as $param_name => $param) {
				$php_string .= "\$__params['$param_name'] = $param;\n";
			}
			
			if ($params['output']) {
				$php_string .= $params['output'] . " = ";
			} else {
				$php_string .= 'echo ';
			}
			
			$php_string .= 'elang(' . $lang . ', $__params, ' . $package . ', ' . $language . ');?>';
		} else {
			if ($params['output']) {
				$php_string .= $params['output'] . " = ";
			} else {
				$php_string .= 'echo ';
			}
			
			$php_string .= 'lang(' . $lang . ', ' . $package . ', ' . $language . ');?>';
		}
		
		return $php_string;
	}
	
	private function _parseVar($var, $quot = TRUE) {
		$var = preg_replace('/\.(\w+)/', '[$1]', $var);
		
		if ($quot) {
			return preg_replace('/\[([a-zA-Z_]+)\]/', '["$1"]', $var);
		} else {
			return preg_replace('/\[([a-zA-Z_]+)\]/', '[$1]', $var);
		}
	}
	
	private function _parseStringVar($value) {
		if ($value{0} != '"' && $value{0} != "'") {
			return $value;
		}
		
		return $this->_parseVar(str_replace("'", '"', $value), FALSE);
	}
	
	private function _parseValue($val) {
		$val = trim($val);
		
		if (substr($val, 0, 1) == '$') {
			return $this->_parseVar($val);
		} else {
			return $this->_parseStringVar($val);
		}
	}
	
	private function _parseModifier($val, $modifier) {
		$p = explode(':', $modifier);
		$func = array_shift($p);
		
		foreach ($p as &$v) {
			$val .= ', ' . $this->_parseValue($v);
		}
		
		return 'ViewHelper::' . $func . '(' . $val . ')';
	}
	
	private function getParams($param_string) {
		preg_match_all('~' . $this->regx['param'] . '~', $param_string, $matches);
		$params = array();
		foreach ($matches[0] as $match) {
			$kv = explode('=', $match, 2);
			$params[trim($kv[0])] = $this->_parseValue($kv[1]);
		}
		return $params;
	}

	private function getView($view) {
		return str_replace('\\', '/', APP_PATH) . '/view/' . $this->tpl_base_path . $view . $this->tpl_ext;
	}

	private function getCacheFile($view) {
		return str_replace('\\', '/', APP_PATH) . '/cache/view/' . $this->tpl_base_path . $view . $this->tpl_ext;
	}
}