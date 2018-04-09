<?php
namespace system\library\session;

/**
 * Session Class
 *
 * Session类，用于保存在线状态，配置信息存在于config
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
class Session {
    private static $instance;
    private static $started = FALSE;
    private static $allowedDriver = array('cookie', 'database', 'memcache');

    public static function isStarted() {
        return self::$started;
    }

    /**
     * @return SessionDriver
     */
    public static function start() {
        if (self::$started) {
            return self::$instance;
        }

        $config = get_config('session');

        if (in_array($config['driver'], self::$allowedDriver)) {
            switch ($config['driver']) {
                case 'cookie':
                    self::$instance = & SessionCookieDriver::instance();
                    break;
                case 'database':
                    self::$instance = & SessionDatabaseDriver::instance();
                    break;
                case 'memcache':
                    self::$instance = & SessionMemcacheDriver::instance();
                    break;
            }

            session_set_save_handler(array(&self::$instance, 'open'), array(&self::$instance, 'close'), array(&self::$instance, 'read'), array(&self::$instance, 'write'), array(&self::$instance, 'destroy'), array(&self::$instance, 'gc'));
        }
        session_name('ATOMCODEUID');
        session_save_path(APP_PATH . '/cache');
        session_start();
        self::$started = TRUE;

        return self::$instance;
    }
}

/**
 * Session 驱动的接口类，定义Session驱动所需要的方法
 *
 */
interface SessionDriver {
    /**
     * 打开Session, 取出Session数据
     * @param String $save_path session保存的路径
     * @param String $sess_name
     * @return Bool 是否成功打开
     */
    public function open($save_path, $sess_name);
    /**
     * 关闭Session，如果有打开的句柄，在此处关闭
     */
    public function close();
    /**
     * 读取Session
     * @param String $sess_id Session Id
     * @return String 为序列化状态
     */
    public function read($sess_id);
    /**
     * 写入Session，为序列化状态
     * @param String $sess_id Session Id
     * @param String $sess_data 为序列化状态字符串
     * @return Bool 是否写入成功
     */
    public function write($sess_id, $sess_data);
    /**
     * 清除当前用户Session值
     * @param String $sess_id Session Id
     * @return Bool 是否清除成功
     */
    public function destroy($sess_id);
    /**
     * 清除过期用户的Session值
     * @param unknown_type $max_life_time
     */
    public function gc($max_life_time);
}

/**
 * 将Session加密存储在Cookie中的方式来保存Session，要求在输出前设置完成
 */
class SessionCookieDriver implements SessionDriver {
    private static $instance;

    /**
     *
     * @return SessionCookieDriver
     */
    public static function &instance() {
        if (!isset(self::$instance)) {
            self::$instance = new SessionCookieDriver();
        }

        return self::$instance;
    }
    /* (non-PHPdoc)
     * @see SessionDriver::open()
     */
    public function open($save_path, $sess_name) {

    }

    /* (non-PHPdoc)
     * @see SessionDriver::close()
     */
    public function close() {

    }

    /* (non-PHPdoc)
     * @see SessionDriver::read()
     */
    public function read($sess_id) {

    }

    /* (non-PHPdoc)
     * @see SessionDriver::write()
     */
    public function write($sess_id, $sess_data) {

    }

    /* (non-PHPdoc)
     * @see SessionDriver::destroy()
     */
    public function destroy($sess_id) {

    }

    /* (non-PHPdoc)
     * @see SessionDriver::gc()
     */
    public function gc($max_life_time) {

    }

    public function __destruct() {
        Model::closeAllLinks();
    }
}

/**
 * 将Session存储在数据库中，并对部分字段进行索引
 * 需要SessionModel和数据库的支持
 *
 */
class SessionDatabaseDriver implements SessionDriver {
    private static $instance;

    /**
     *
     * @return SessionDatabaseDriver
     */
    public static function &instance() {
        if (!isset(self::$instance)) {
            self::$instance = new SessionDatabaseDriver();
        }

        return self::$instance;
    }
    /* (non-PHPdoc)
     * @see SessionDriver::open()
     */
    public function open($save_path, $sess_name) {
        return true;
    }

    /* (non-PHPdoc)
     * @see SessionDriver::close()
     */
    public function close() {
        return true;
    }

    /* (non-PHPdoc)
     * @see SessionDriver::read()
     */
    public function read($sess_id) {
        $session = SessionModel::instance();
        $sess = $session->read($sess_id);
        if ($sess) {
            return $sess['data'];
        }
        return '';
    }

    /* (non-PHPdoc)
     * @see SessionDriver::write()
     */
    public function write($sess_id, $sess_data) {
        $session = SessionModel::instance();
        return $session->write($sess_id, $sess_data);
    }

    /* (non-PHPdoc)
     * @see SessionDriver::destroy()
     */
    public function destroy($sess_id) {
        $session = SessionModel::instance();
        return $session->destroy($sess_id);
    }

    /* (non-PHPdoc)
     * @see SessionDriver::gc()
     */
    public function gc($max_life_time) {
        $session = SessionModel::instance();
        return $session->gc($max_life_time);
    }

    public function __destruct() {
        Model::closeAllLinks();
    }
}

/**
 * 将Session数据存储在Memcache中
 *
 */
class SessionMemcacheDriver implements SessionDriver {
    private static $instance;

    /**
     *
     * @return SessionMemcacheDriver
     */
    public static function &instance() {
        if (!isset(self::$instance)) {
            self::$instance = new SessionMemcacheDriver();
        }

        return self::$instance;
    }
    /* (non-PHPdoc)
     * @see SessionDriver::open()
     */
    public function open($save_path, $sess_name) {

    }

    /* (non-PHPdoc)
     * @see SessionDriver::close()
     */
    public function close() {

    }

    /* (non-PHPdoc)
     * @see SessionDriver::read()
     */
    public function read($sess_id) {

    }

    /* (non-PHPdoc)
     * @see SessionDriver::write()
     */
    public function write($sess_id, $sess_data) {

    }

    /* (non-PHPdoc)
     * @see SessionDriver::destroy()
     */
    public function destroy($sess_id) {

    }

    /* (non-PHPdoc)
     * @see SessionDriver::gc()
     */
    public function gc($max_life_time) {

    }

    public function __destruct() {
        Model::closeAllLinks();
    }
}