<?php
namespace system\library\cookie;

class Cookie {
    private static $config;

    public static function init() {
        if (!self::$config) {
            self::$config = get_config('cookie');
        }
    }

    public static function get($key) {
        self::init();
        return self::decode($_COOKIE[self::$config['prefix'] . $key]);
    }

    public static function set($key, $value, $lifetime = 0, $path = NULL, $domain = NULL, $secure = FALSE) {
        self::init();
        $lifetime = $lifetime == 0 ? 0 : $lifetime + time();
        if (!$path) $path = self::$config['path'];
        if (!$domain) $domain = self::$config['domain'];
        if (!$secure) $secure = self::$config['secure'];

        return setcookie(self::$config['prefix'] . $key, self::encode($value), $lifetime, $path, $domain, $secure);
    }

    public static function del($key) {
        return self::set(self::$config['prefix'] . $key, '', -100);
    }

    public static function encode($value) {
        return self::$config['encrypt'] ? authcode($value, 'ENCODE', self::$config['key']) : $value;
    }

    public static function decode($value) {
        return self::$config['encrypt'] ? authcode($value, 'DECODE', self::$config['key']) : $value;
    }
}