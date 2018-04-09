<?php
namespace system\library\database\driver;
/**
 *
 */

class DbMysqliDriver extends DbDriver
{
    const LOW_PRIORITY = 'LOW_PRIORITY';
    const HIGH_PRIORITY = 'HIGH_PRIORITY';
    const QUICK = 'QUICK';
    const IGNORE = 'IGNORE';
    const DELAYED = 'DELAYED';
    const DISTINCT = 'DISTINCT';
    const STRAIGHT_JOIN = 'STRAIGHT_JOIN';
    const SQL_SMALL_RESULT = 'SQL_SMALL_RESULT';
    const SQL_BIG_RESULT = 'SQL_BIG_RESULT';
    const SQL_BUFFER_RESULT = 'SQL_BUFFER_RESULT';
    const SQL_CACHE = 'SQL_CACHE';
    const SQL_NO_CACHE = 'SQL_NO_CACHE';
    const SQL_CALC_FOUND_ROWS = 'SQL_CALC_FOUND_ROWS';
    const WITH_CONSISTENT_SNAPSHOT = 'WITH CONSISTENT SNAPSHOT';
    const READ_WRITE = 'READ WRITE';
    const READ_ONLY = 'READ ONLY';
    const AND_CHAIN = 'AND CHAIN';
    const AND_NO_CHAIN = 'AND NO CHAIN';
    const RELEASE = 'RELEASE';
    const NO_RELEASE = 'NO RELEASE';
    const WITH_ROLLUP = 'WITH ROLLUP';

    protected $SELECT_OPTION = array('HIGH_PRIORITY', 'DISTINCT', 'SQL_SMALL_RESULT', 'SQL_BIG_RESULT', 'SQL_BUFFER_RESULT', 'SQL_CACHE', 'SQL_NO_CACHE', 'SQL_CALC_FOUND_ROWS');
    protected $DELETE_OPTION = array('LOW_PRIORITY', 'QUICK', 'IGNORE');
    protected $INSERT_OPTION = array('LOW_PRIORITY', 'DELAYED', 'IGNORE', 'HIGH_PRIORITY');
    protected $REPLACE_OPTION = array('LOW_PRIORITY', 'DELAYED');
    protected $UPDATE_OPTION = array('LOW_PRIORITY', 'DELAYED');

    public function __construct()
    {
        $this->protect_start = '`';
        $this->protect_end = '`';
    }

    /**
     * @param $config
     * @return mixed
     */
    public function connect($config)
    {
        if ($config['pconnect']) {
            $link = mysqli_connect('p:'.$config['host'] , $config['user'], $config['pass'],$config['name'],$config['port']);
        } else {
            $link = mysqli_connect($config['host'] , $config['user'], $config['pass'],$config['name'],$config['port']);
        }
        if (!$link) {
            error_log(mysqli_errno($link),0,APP_PATH . '/log/dberr.log',null);
            $this->showError(0, "Can't connect to database");
        }
        if (!mysqli_set_charset($link,$config['charset'])) {
            $this->showError(0, "Unknown charset: " . $config['charset']);
        }

        return $link;
    }

    /**
     * @param DbData $data
     * @param $link
     * @return mixed
     */
    public function getSql($data, $link)
    {
        $method = $data->queryType;
        if ($method) {
            $method = "get" . $method . "Sql";
            if (method_exists($this, $method))return $this->$method($data, $link);
        }

        return "";
    }

    /**
     * @param string $sql
     * @param $link
     * @return mixed
     */
    public function query($sql, $link)
    {
        $result = mysqli_query($link,$sql);
        if ($result === FALSE) {
            $this->showError(mysqli_errno($link), mysqli_error($link), $sql);
        }

        return $result;
    }

    /**
     * @param $result
     * @return mixed
     */
    public function wrapResult($result)
    {
        if ($result === TRUE || $result === FALSE || $result === NULL) {
            return $result;
        }

        $array = array();
        while (($row = mysqli_fetch_assoc($result)) !== NULL) {
            $array[] = $row;
        }

        return $array;
    }

    public function escape($str, $link) {
        return mysqli_real_escape_string($link,$str);
    }
    /**
     * @param $link
     * @return mixed
     */
    public function lastId($link)
    {
        return mysqli_insert_id($link);
    }

    /**
     * @param $auto
     * @param $link
     * @return mixed
     */
    public function setAutoCommit($auto, $link)
    {
        $sql = "SET autocommit=" . ($auto ? 1 : 0);
        return $this->query($sql, $link);
    }

    public function commit($option, $link) {
        $opt1 = $this->getOptions(array(self::AND_CHAIN, self::AND_NO_CHAIN), $option, 1);
        $opt2 = $this->getOptions(array(self::RELEASE, self::NO_RELEASE), $option, 1);

        $sql = "COMMIT" . ($opt1 ? ' ' . $opt1[0] : '') . ($opt2 ? ' ' . $opt2[0] : '');

        return $this->query($sql, $link);
    }

    public function rollback($option, $link) {
        $opt1 = $this->getOptions(array(self::AND_CHAIN, self::AND_NO_CHAIN), $option, 1);
        $opt2 = $this->getOptions(array(self::RELEASE, self::NO_RELEASE), $option, 1);

        $sql = "ROLLBACK" . ($opt1 ? ' ' . $opt1[0] : '') . ($opt2 ? ' ' . $opt2[0] : '');

        return $this->query($sql, $link);
    }

    /**
     * @param $option
     * @param $link
     * @return mixed
     */
    public function startTrans($option, $link)
    {
        $option = $this->getOptions(array(self::WITH_CONSISTENT_SNAPSHOT, self::READ_WRITE, self::READ_ONLY), $option);
        $sql = "START TRANSACTION " . implode(',', $option);

        return $this->query($sql, $link);
    }


    /**
     * @param $link
     * @return mixed
     */
    public function affectedRows($link)
    {
        return mysqli_affected_rows($link);
    }

    /**
     * @param $link
     * @return mixed
     */
    public function foundRows($link)
    {
        $sql = "SELECT FOUND_ROWS() as total";
        $result = $this->wrapResult($this->query($sql, $link));
        return $result[0]['total'];
    }

    /**
     * @param $link
     * @return mixed
     */
    public function version($link)
    {
        $sql = "SELECT VERSION() as ver";
        $result = $this->query($sql, $link);
        $result2 = $this->wrapResult($result);

        return $result2[0]['ver'];
    }


    /**
     * @return mixed
     */
    public function driver()
    {
        return 'mysqli';
    }

    /**
     *
     * @param DbData $data
     * @param Mysqli $link
     * @return String
     */
    public function getSelectSql($data, $link) {
        $sql = "SELECT";
        $sql .= $this->getOptionSql($this->SELECT_OPTION, $data->options);
        $sql .= $this->getColumnsSql($data->selects);
        $sql .= $this->getFromSql($data);
        $sql .= $this->getJoinSql($data, $link);
        $sql .= $this->getWhereSql($data, $link);
        $sql .= $this->getGroupbySql($data);
        $sql .= $this->getHavingSql($data, $link);
        $sql .= $this->getOrderbySql($data);
        $sql .= $this->getLimitSql($data);

        return $sql;
    }

    /**
     * @param Mysqli $link
     * @param DbData $data
     * @return String
     */
    public function getDeleteSql($data, $link) {
        $sql = "DELETE";
        $sql .= $this->getOptionSql($this->DELETE_OPTION, $data->options);
        $sql .= $this->getDeletetableSql($data);
        $sql .= $this->getFromSql($data);
        $sql .= $this->getJoinSql($data, $link);
        $sql .= $this->getWhereSql($data, $link);
        if (!$data->joins) {
            $sql .= $this->getOrderbySql($data);
            $sql .= $this->getLimitSql($data);
        }

        return $sql;
    }

    /**
     * @param Mysqli $link
     * @param DbData $data
     * @return String
     */
    public function getInsertSql($data, $link) {
        $sql = "INSERT";
        $sql .= $this->getOptionSql($this->INSERT_OPTION, $data->options);
        $sql .= $this->getIntoSql($data);
        $sql .= $this->getValuesSql($data, $link);
        $sql .= $this->getDuplicateSql($data, $link);

        return $sql;
    }

    /**
     * @param Mysqli $link
     * @param DbData $data
     * @return String
     */
    public function getReplaceSql($data, $link) {
        $sql = "REPLACE";
        $sql .= $this->getOptionSql($this->REPLACE_OPTION, $data->options);
        $sql .= $this->getIntoSql($data);
        $sql .= $this->getValuesSql($data, $link);

        return $sql;
    }

    public function getUpdateSql($data, $link) {
        $sql = "UPDATE";
        $sql .= $this->getOptionSql($this->UPDATE_OPTION, $data->options);
        $sql .= ' ' . $this->protect_start . $data->table . $this->protect_end . ($data->alias ? ' AS ' . $this->protect_start . $data->alias . $this->protect_end : '');
        $sql .= $this->getUpdateItemSql($data, $link);
        $sql .= $this->getWhereSql($data, $link);
        $sql .= $this->getOrderbySql($data);
        $sql .= $this->getLimitSql($data);

        return $sql;
    }

    /**
     * @param DbIndexHint $hint
     * @return mixed
     */
    public function parseIndexHint($hint)
    {
        if ($hint && get_class($hint) == 'DbIndexHint') {
            if (!in_array($hint->hint['method'], array('USE', 'IGNORE', 'FORCE'))) {
                $hint->hint['method'] = 'USE';
            }
            if (!in_array($hint->hint['for'], array('JOIN', 'ORDER BY', 'GROUP BY'))) {
                $hint->hint['for'] = '';
            } else {
                $hint->hint['for'] = ' FOR ' . $hint->hint['for'];
            }

            return $hint->hint['method'] . $hint->hint['for'] . '(' . (is_array($hint->hint['index']) ? implode(',', $hint->hint['index']) : $hint->hint['index']) . ')';
        }

        return '';
    }

    /**
     * @param $link
     * @return mixed
     */
    public function close($link)
    {
        return is_resource($link) ? mysqli_close($link) : TRUE;
    }
}