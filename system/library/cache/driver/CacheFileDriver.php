<?php
namespace system\library\cache\driver;

use system\library\CacheDriver;

class CacheFileDriver implements CacheDriver {

    private $dir, $ttl;

    public function __construct() {
        $this->dir = APP_PATH . '/cache/data/';
    }

    /* (non-PHPdoc)
     * @see CacheDriver::get()
     */
    public function get($id) {
        $file = $this->dir . base64_encode($id) . '.php';
        if (!file_exists($file)) {
            return NULL;
        }

        $content = file_get_contents($file);
        $arr = @unserialize($content);
        if ($arr['e'] !== -1 && time() > $arr['e']) {
            @unlink($file);
            return NULL;
        }

        return $arr['d'];
    }

    /* (non-PHPdoc)
     * @see CacheDriver::set()
     */
    public function set($id, $content, $ttl = NULl) {
        $file = $this->dir . base64_encode($id) . '.php';
        if (is_null($ttl)) $ttl = $this->ttl;
        $arr['e'] = $ttl == -1 ? -1 : time() + $ttl;
        $arr['d'] = $content;
        if (!file_exists(dirname($file))) {
            if (!@mkdir(dirname($file), 0777, TRUE)) {
                if (TEST_MODE) show_error("CacheFileDriver mkdir failure, may have no permission on dir :" . dirname($file));
            }
        }

        if (@file_put_contents($file, serialize($arr)) !== FALSE) {
            return true;
        } else {
            if (TEST_MODE) show_error("CacheFileDriver write file failure, may have no permission on file :" . $file);
            return false;
        }
    }

    /* (non-PHPdoc)
     * @see CacheDriver::delete()
     */
    public function delete($id) {
        $file = $this->dir . base64_encode($id) . '.php';
        return !file_exists($file) || @unlink($file);
    }

    /* (non-PHPdoc)
     * @see CacheDriver::setOption()
     */
    public function setOption($name, $value) {
        switch ($name) {
            case "dir":
                $this->setDir($value);
                break;
            case 'ttl':
                $this->ttl = $value;
                break;
        }
    }

    /* (non-PHPdoc)
     * @see CacheDriver::setOptions()
     */
    public function setOptions($arr) {
        foreach ($arr as $key => $value) {
            $this->setOption($key, $value);
        }
    }

    public function setDir($path) {
        $this->dir = rtrim($path, ' /\\') . DIRECTORY_SEPARATOR;
    }
}