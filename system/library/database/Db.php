<?php
namespace system\library\database;
/**
 * Database Class
 *
 * 作为数据库引擎的数据保存基类，封装了所有用户会查询的动作，然后再由各数据库驱动来
 *
 * @package		AtomCode
 * @subpackage	library
 * @category	library
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 2.0
 */
class Db {
    const NO_QUOTE_KEY = 0x01;
    const NO_QUOTE_VALUE = 0x02;
    const NO_ESCAPE_VALUE = 0x04;

    /**
     * 返回数据库驱动
     * @param String $type
     * @return DbDriver
     */
    public function loadDriver($type) {
        $class = "Db" . ucfirst($type) . 'Driver';
        return new $class();
    }
}

abstract class DbDriver {

    /**
     * @var Model
     */
    private $err_obj;

    protected $protect_start, $protect_end;

    abstract public function connect($config);

    /**
     * 根据设定生成SQL语句
     * @param DbData $data
     */
    abstract public function getSql($data, $link);

    /**
     * 查询SQL语句并返回结果
     *
     * @param string $sql
     */
    abstract public function query($sql, $link);

    abstract public function wrapResult($result);

    abstract public function lastId($link);

    abstract public function setAutoCommit($auto, $link);

    abstract public function startTrans($option, $link);

    abstract public function commit($option, $link);

    abstract public function rollback($option, $link);

    abstract public function affectedRows($link);

    abstract public function foundRows($link);

    abstract public function version($link);

    abstract public function escape($str, $link);

    abstract public function driver();

    /**
     *
     * Enter description here ...
     * @param DbIndexHint $hint
     */
    abstract public function parseIndexHint($hint);

    protected function getOptions($options, $option, $limit = 0) {
        if (!($options && $option)) {
            return array();
        }

        $active = array();
        $rows = 0;
        is_string($option) && $option = explode(",", $option);

        foreach ($option as $o) {
            $o = trim($o);
            if (in_array($o, $options)) {
                $active[] = $o;
                $rows++;
                if ($limit && $rows >= $limit) {
                    break;
                }
            }
        }

        return array_values($active);
    }

    public final function setErrorHandler($errobj) {
        $this->err_obj = &$errobj;
    }

    public function showError($errno, $error, $sql = NULL) {
        $this->err_obj->showError($errno, $error, $sql);
    }

    public function getOptionSql($options, $option) {
        $o = $this->getOptions($options, $option);
        return $o ? ' ' . implode(' ', $o) : '';
    }

    public function getColumnsSql($columns) {
        if (!$columns || $columns == array('*')) {
            return ' *';
        }

        $cols = array();

        foreach ($columns as $col_info) {
            $cols[] = $this->getColumnInfo($col_info);
        }

        return $cols ? ' ' . implode(", ", $cols) : '';
    }

    public function getColumnInfo($col) {
        $cols = array();
        if (($col['escape'] & DB::NO_QUOTE_KEY) == DB::NO_QUOTE_KEY) {
            if (is_array($col['col'])) {
                return implode(", ", $col['col']);
            } else {
                return $col['col'];
            }
        } else {
            if (is_array($col['col'])) {
                foreach ($col['col'] as $r) {
                    $cols[] = DbHelper::getProtectedString($r, $this->protect_start, $this->protect_end);
                }

                return implode(", ", $cols);
            } else {
                $cols = DbHelper::getProtectedString($col['col'], $this->protect_start, $this->protect_end);
            }
        }

        return $cols;
    }

    /**
     *
     * @param DbData $data
     */
    public function getFromSql($data) {
        $sql = ' FROM ';
        $tables = array();
        $index = array();
        $row = array();

        if (!$data->subQueryNoTable) {
            $tables[] = $this->protect_start . $data->table . $this->protect_end . ($data->alias ? ' AS ' . $this->protect_start . $data->alias . $this->protect_end : '');
            $index[$this->protect_start . $data->table . $this->protect_end] = $data->index_hint;
        }

        if ($data->froms) {
            foreach ($data->froms as $from) {
                if ($from['table']) {
                    $row = is_array($from['table']) ? $from['table'] : explode(',', $from['table']);

                    foreach ($row as $line) {
                        $tables[] = $this->formatTable($line);
                    }
                    if (count($row) == 1) {
                        $index[$this->protect_start . $line[0] . $this->protect_end] = $from['index_hint'];
                    }
                } elseif ($from['sql']) {
                    $tables[] = '(' . $from['sql'] . ') ' . $from['alias'];
                }
            }
        }

        return ' FROM ' . implode(',', $tables);
    }

    public function formatTable($str) {
        $line = explode(' ', trim($str), 3);
        $alias = strtoupper($line[1]) == 'AS' ? $line[2] : $line[1];
        return $this->protect_start . $line[0] . $this->protect_end . ($alias ? ' AS ' . $this->protect_start . $alias . $this->protect_end : "");
    }

    /**
     * array('table' => $table, 'cond' => $conditions, 'type' => $join_type, 'escape' => $escape, 'index_hint' => $index_hint)
     * @param DbData $data
     */
    public function getJoinSql($data, $link) {
        $str = ' ';
        if ($data->joins) {
            foreach ($data->joins as $join) {
                $join['type'] = strtoupper($join['type']);
                if (strncmp('NATURAL', $join['type'], 7) === 0) {
                    $str .= $join['type'] . ' JOIN ' . $this->formatTable($join['table']) . $this->parseIndexHint($join['index_hint']);
                } else {
                    $str .= $join['type'] . ' JOIN ' . $this->formatTable($join['table']) . $this->parseIndexHint($join['index_hint']) . ($join['cond'] ? ' ON ' . $this->parseWhere($join['cond'], '', $link) : '');
                }
            }
        }

        return $str;
    }

    public function getWhereSql($data, $link) {
        if ($data->wheres) {
            return ' WHERE ' . $this->parseWhere($data->wheres, '', $link);
        }
        return '';
    }

    public function getHavingSql($data, $link) {
        if ($data->havings) {
            return ' HAVING ' . $this->parseWhere($data->havings, '', $link);
        }
        return '';
    }

    /**
     * id=123
     * array('key' => $key, 'value' => $value, 'escape' => $escape, 'logic' => $logic);
     * array('key' => $key, 'sql' => $value, 'escape' => $escape, 'logic' => $logic);
     * Enter description here ...
     * @param mixed $where
     */
    public function parseWhere($where, $org_logic = '', $link) {
        if (!$where) return '';

        if (is_string($where)) {
            return $where;
        }

        if (array_key_exists('sql', $where)) {
            if (is_string($where['key'])) {
                $where['key'] = trim($where['key']);
                if (($oper = $this->hasOperator($where['key'])) == '') {
                    return $this->protectKey($where['key'], $where['escape']) . '=(' . $where['sql'] . ')';
                } else {
                    return $this->protectKey(trim(substr($where['key'], 0, -strlen($oper))), $where['escape']) . $oper . ' (' . $where['sql'] . ')';
                }
            } else {
                return $this->parseWhere($where['key'], $org_logic, $link);
            }
        } elseif (array_key_exists('value', $where)) {
            if (is_string($where['key'])) {
                $where['key'] = trim($where['key']);
                if (($oper = $this->hasOperator($where['key'])) == '') {
                    return $this->protectKey($where['key'], $where['escape']) . '=' . $this->protectValue($where['value'], $where['escape'], $link);
                } else {
                    if ($oper == ' LIKE' || $oper == ' NOT LIKE' || $oper == ' IS' || $oper == ' IS NOT' || $oper == ' IN' || $oper == ' NOT IN') {
                        return $this->protectKey(trim(substr($where['key'], 0, -strlen($oper))), $where['escape']) . $oper . ' ' . $this->protectValue($where['value'], $where['escape'], $link);
                    }

                    return $this->protectKey(trim(substr($where['key'], 0, -strlen($oper))), $where['escape']) . $oper . $this->protectValue($where['value'], $where['escape'], $link);
                }
            } else {
                return $this->parseWhere($where['key'], $org_logic, $link);
            }
        } elseif (array_key_exists('params', $where)) {
            $str = '';
            $str = substr($where['psql'], 0, $pos = strpos($where['psql'], '?'));
            foreach ($where['params'] as $param) {
                $pos1 = strpos($where['psql'], '?', $pos);
                $str .= substr($where['psql'], $pos, $pos1 - $pos - 1);
                $pos = $pos1 + 1;
            }

            return $str;
        }

        $str = '';
        if (!$org_logic) {
            $org_logic = 'AND';
        }

        // only "AND" or "OR"
        $wheres = array();
        if ($where['AND']) {
            foreach ($where['AND'] as $w) {
                $wheres[] = $this->parseWhere($w, 'AND', $link);
            }

            $str .= ($str == '' ? '' : ' ' . $org_logic . ' ') . (count($wheres) > 1 ? '(' : '') . implode(' AND ', $wheres) . (count($wheres) > 1 ? ')' : '');
        }

        $wheres = array();
        if ($where['OR']) {
            foreach ($where['OR'] as $w) {
                $wheres[] = $this->parseWhere($w, 'AND', $link);
            }

            $str .= ($str == '' ? '' : ' ' . $org_logic . ' ') . (count($wheres) > 1 ? '(' : '') . implode(' OR ', $wheres) . (count($wheres) > 1 ? ')' : '');
        }

        return $str;
    }

    protected function getLogic($logic1, $logic2) {
        $logic1 = strtoupper($logic1);
        $logic2 = strtoupper($logic2);

        if ($logic1 == 'AND' || $logic1 == 'OR') {
            return $logic1;
        }

        if ($logic2 == 'AND' || $logic2 == 'OR') {
            return $logic2;
        }

        return 'AND';
    }

    protected function hasOperator($str) {
        $operators = array('<=>', '>=', '<=', '<>', '!=','=', '>', '<', ' IS NOT', ' IS', " LIKE", " NOT LIKE", " IN", " NOT IN");
        $str = strtoupper($str);
        foreach ($operators as $oper) {
            if (strpos($str, $oper)) {
                return $oper;
            }
        }

        return '';
    }

    protected function protectKey($str, $escape = 0) {
        return $escape & DB::NO_QUOTE_KEY ? $str : $this->protect_start . $str . $this->protect_end;
    }

    protected function protectKeyArray($array) {
        foreach ($array as &$item) {
            $item = $this->protectKey($item);
        }

        return implode(',', $array);
    }

    protected function protectValue($str, $escape, $link = NULL) {
        if (is_numeric($str)) {
            return $str;
        }

        if (is_bool($str)) {
            return intval($str);
        }

        if ($str === NULL) {
            return 'NULL';
        }

        if (is_array($str)) {
            $s = array();
            foreach ($str as $st) {
                $s[] = $this->protectValue($st, $escape, $link);
            }

            return ' (' . implode(",", $s) . ')';
        }

        return $escape & DB::NO_QUOTE_VALUE ? $str : '"' . ($escape & DB::NO_ESCAPE_VALUE ? $str : $this->escape($str, $link)) . '"';
    }

    /**
     *
     * array('col' => $columns, 'direction' => $direction, 'option' => $option);
     * @param DbData $data
     */
    protected function getGroupbySql($data) {
        $str = '';
        if ($data->groupBys) {
            foreach ($data->groupBys as $group) {
                $str .= ' ' . $group['col'] . ($group['direction'] ? ' ' . strtoupper($group['direction']) : '') . ($group['option'] ? ' ' . strtoupper($group['option']) : '');
            }
        }

        return $str ? ' GROUP BY ' . $str : '';
    }

    protected function getOrderbySql($data) {
        $str = array();
        if ($data->orderBys) {
            foreach ($data->orderBys as $group) {
                $str[] = DbHelper::getProtectedString($group['col'], $this->protect_start, $this->protect_end) . ($group['direction'] ? ' ' . strtoupper($group['direction']) : '');
            }
        }

        return $str ? ' ORDER BY ' . implode(",", $str) : '';
    }

    /**
     * @param DbData $data
     */
    protected function getLimitSql($data) {
        $str = '';
        if ($data->limit['limit']) {
            $str .= ' LIMIT ' . $data->limit['limit'];
        }

        if ($data->limit['offset']) {
            $str .= ' OFFSET ' . $data->limit['offset'];
        }

        return $str;
    }

    /**
     * @param DbData $data
     */
    protected function getDeletetableSql($data) {
        $str = ' ';
        if ($data->deleteTables) {
            if (is_string($data->deleteTables)) {
                $data->deleteTables = explode(',', $data->deleteTables);
            }

            $tables = array();
            foreach ($data->deleteTables as $table) {
                $tables[] = $this->formatTable($table);
            }
            $str = ' ' . implode(", ", $tables);
        }

        return $str;
    }

    /**
     * @param DbData $data
     */
    protected function getIntoSql($data) {
        return ' INTO ' . $data->table;
    }

    /**
     * @param DbData $data
     */
    protected function getValuesSql($data, $link) {
        $set_sqls = array();
        $keys = array();

        if ($data->msets) {
            foreach ($data->msets['values'] as $set) {
                $keys || $keys = array_keys($set);
                foreach ($set as $k => $value) {
                    $set[$k] = $this->protectValue($value, !in_array($k, $data->msets['reserve_keys']), $link);
                }

                $set_sqls[] = '(' . implode(',', $set) . ')';
            }
        } elseif ($data->sets) {
            foreach ($data->sets as $set) {
                $keys[] = $set['key'];
                $vals[] = $this->protectValue($set['value'], $set['escape'], $link);
            }
            $set_sqls[] = '(' . implode(',', $vals) . ')';
        }

        $sql = '(' . $this->protectKeyArray($keys) . ') VALUES ' . implode(',', $set_sqls);

        return $sql;
    }

    protected function getValueSql($set, $link) {
        return '(' . $this->protectValue($set, TRUE, $link) . ')';
    }

    protected function getDuplicateSql($data, $link) {
        if ($data->sets2) {
            $set_sqls = $this->getPairs($data->sets2, $link);
        }

        return $set_sqls ? ' ON DUPLICATE KEY UPDATE ' . $set_sqls : '';
    }

    protected function getPairs($pairs, $link) {
        foreach ($pairs as $set) {
            $vals[] = $this->protectKey($set['key']) . '=' . $this->protectValue($set['value'], $set['escape'], $link);
        }

        return implode(',', $vals);
    }

    protected function getUpdateItemSql($data, $link) {
        $set_sqls = $this->getPairs($data->sets, $link);

        return ' SET ' . $set_sqls;
    }

    abstract public function close($link);

    public function getLikeValue($value, $side, $escape, $link) {
        $value = $escape & Db::NO_ESCAPE_VALUE == 0 ? $this->escape($value, $link) : $value;
        $value = ($side == 'LEFT' || $side == 'BOTH' ? '%' : '') . $value . ($side == 'RIGHT' || $side == 'BOTH' ? '%' : '');
        if (($escape & Db::NO_QUOTE_VALUE) === 0) $value = '"' . $value . '"';
        return $value;
    }
}

class DbHelper {

    public static function getProtectedString($col_string, $start, $end) {
        return self::protect($col_string, $start, $end);

        $len = strlen($col_string);
        $in_quote = FALSE;
        $quoted_char = '';

        $escaping = FALSE;

        $cols = array();
        $same = ($start == $end);
        $prev_string = '';

        for($i = 0; $i < $len; $i++) {
            $c = $col_string{$i};

            if ($c != ',' || $in_quote) $prev_string .= $c;

            if ($in_quote && $c != $quoted_char || $escaping) {
                $escaping && $escaping = !$escaping;
                $c == '\\' && $escaping = TRUE;

                continue;
            }

            if ($c == '"') {
                $in_quote = !$in_quote;
                $quoted_char = $c;
            } elseif ($c == "'") {
                $in_quote = !$in_quote;
                $quoted_char = $c;
            } elseif ($c == ",") {
                if (!$in_quote) {
                    $cols[] = $prev_string;
                    $prev_string = '';
                } else {
                    $prev_string .= $c;
                }
            }
        }

        if ($prev_string) $cols[] = $prev_string;

        foreach ($cols as &$col) {
            $col = self::protect($col, $start, $end);
        }
        return implode(', ', $cols);
    }

    public static function protect($col, $start, $end) {
        return preg_replace("/(\\b|\\s)(\\w+)(\\b|\\s)/", '\1' . $start . '\2' . $end . '\3', $col);
    }
}

class DbData {

    public $options, $wheres, $froms, $selects, $havings, $orderBys, $groupBys, $limit, $deleteTables, $joins;

    public $queryType, $selectSql, $sets, $msets, $sets2, $table, $alias, $subQueryNoTable = FALSE, $index_hint;
}

class DbIndexHint {

    public $hint;

    public function __construct($method, $for, $index_list) {
        $this->hint = array('method' => $method, 'for' => $for, 'index' => $index_list);
    }
}