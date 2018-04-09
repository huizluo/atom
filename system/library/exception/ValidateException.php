<?php
namespace system\library\exception;

/**
 * ValidateException Class
 *
 * 验证异常类，增加了错误的对象，可以配合Html页面内容进行控制将错误显示在哪个元素的后面。
 *
 * @package		AtomCode
 * @subpackage	core
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */
class ValidateException extends \Exception {

    protected $object;

    public function __construct($message, $code, $object = '') {
        parent::__construct($message, $code);
        $this->object = $object;
    }

    /**
     * 取得出错的对象名
     *
     * @return string
     */
    public function getObject() {
        return $this->object;
    }
}