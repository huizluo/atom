<?php
namespace system\library\cache;

use system\library\cache\driver\CacheFileDriver;
use system\library\exception\ValidateException;


class Cache {

	private static $config = array();

	private static $instances;

	/**
	 * @return Cache
	 */
	public static function &instance($driver = '') {
		if (!self::$config) {
			self::$config = load_config('cache');
		}
		
		if (!self::$config) {
			throw new ValidateException("Configure for cache is not found", 0);
		}
		
		if (!$driver) {
			$driver = self::$config['default'];
		}
		
		if (!is_array(self::$config['drivers']) || !in_array($driver, self::$config['drivers'])) {
			$driver = 'file';
		}
		
		if (!self::$instances[$driver]) {
			$class = 'Cache' . ucfirst($driver) . 'Driver';
			self::$instances[$driver] = new $class();
			self::$instances[$driver]->setOption('ttl', self::$config['ttl']);
			self::$instances[$driver]->setOptions(self::$config[$driver]);
		}
		
		return self::$instances[$driver];
	}

	/**
	 * @return CacheFileDriver
	 */
	public static function file() {
		return self::instance('file');
	}
}
