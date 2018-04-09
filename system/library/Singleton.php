<?php
namespace system\library;
/***
 * 单例
 * **/
abstract class Singleton {

    protected static $instances;

    private final function __clone() {}

    /**
     * @final
     * @return self
     */
    public static function &instance() {
        return self::getInstance();
    }

    /**
     * @return self
     */
    protected static final function &getInstance() {
        $class = get_called_class();

        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }
        return self::$instances[$class];
    }
}