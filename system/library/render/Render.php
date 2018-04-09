<?php
namespace system\library\render;
/**
 * Render Class
 *
 * 渲染引擎
 *
 * @package		AtomCode
 * @subpackage	core
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */
abstract class Render {

    protected $view, $values;

    /**
     * 输入渲染内容
     */
    public abstract function display();

    /**
     * 获取要显示的内容
     * @param Array $values
     */
    public function setEnv($values) {
        $this->view = $values[0];
        $this->values = $values[1];

        if (!is_array($this->values)) {
            $this->values = array();
        }
    }
}