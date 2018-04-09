<?php
namespace system\library;


interface CacheDriver {

    /**
     * 取得缓存
     * @param string $id
     */
    public function get($id);

    /**
     * 保存缓存
     * @param string $id
     * @param string $content
     * @param string $ttl
     */
    public function set($id, $content, $ttl);

    /**
     * 删除缓存
     * @param string $id
     */
    public function delete($id);

    /**
     * 设置选项
     * @param string $name
     * @param string $value
     */
    public function setOption($name, $value);

    /**
     * 批量设置选项
     * @param Array $arr
     */
    public function setOptions($arr);
}