<?php
namespace system\library\database\driver;
/*
 * 数据库抽象
 * **/
use system\library\database\DbDriver;

class DbMysqlDriver extends DbDriver {
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

	public function __construct() {
		$this->protect_start = '`';
		$this->protect_end = '`';
	}

	public function connect($config) {
		if ($config['mode'] == 'master/slave') {
			return array('m' => null, 's' => null);
		}
		
		if ($config['pconnect']) {
			$link = mysql_pconnect($config['host'] . ($config['port'] ? ':' . $config['port'] : ''), $config['user'], $config['pass']);
		} else {
			$link = mysql_connect($config['host'] . ($config['port'] ? ':' . $config['port'] : ''), $config['user'], $config['pass'], TRUE);
		}
		if (!$link) {
			$this->showError(0, "Can't connect to database");
		}
		
		if (!mysql_select_db($config['name'], $link)) {
			$this->showError(0, "Can't select database: " . $config['name']);
		}
		
		if (!mysql_set_charset($config['charset'], $link)) {
			$this->showError(0, "Unknown charset: " . $config['charset']);
		}
		
		mysql_query("set time_zone='" .  date('P') . "'", $link);
		
		return $link;
	}

	/** (non-PHPdoc)
	 * @see DbDriver::getSql()
	 * @param DbData $data
	 */
	public function getSql($data, $link) {
		$method = $data->queryType;
		if ($method) {
			$method = "get" . $method . "Sql";
			if (method_exists($this, $method))return $this->$method($data, $link);
		}
		
		return "";
	}

	/* (non-PHPdoc)
	 * @see DbDriver::query()
	 */
	public function query($sql, $link) {
		$result = mysql_query($sql, $link);
		if ($result === FALSE) {
			$this->showError(mysql_errno($link), mysql_error($link), $sql);
		}
		
		return $result;
	}

	/* (non-PHPdoc)
	 * @see DbDriver::wrapResult()
	 */
	public function wrapResult($result) {
		if ($result === TRUE || $result === FALSE || $result === NULL) {
			return $result;
		}
		
		$array = array();
		while (($row = mysql_fetch_assoc($result)) !== FALSE) {
			$array[] = $row;
		}
		
		return $array;
	}
	
	public function escape($str, $link) {
		return mysql_real_escape_string($str, $link);
	}

	/* (non-PHPdoc)
	 * @see DbDriver::lastId()
	 */
	public function lastId($link) {
		return mysql_insert_id($link);
	
	}

	public function setAutoCommit($auto, $link) {
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

	public function startTrans($option, $link) {
		$option = $this->getOptions(array(self::WITH_CONSISTENT_SNAPSHOT, self::READ_WRITE, self::READ_ONLY), $option);
		$sql = "START TRANSACTION " . implode(',', $option);
		
		return $this->query($sql, $link);
	}

	/* (non-PHPdoc)
	 * @see DbDriver::affectedRows()
	 */
	public function affectedRows($link) {
		return mysql_affected_rows($link);
	}

	/* (non-PHPdoc)
	 * @see DbDriver::foundRows()
	 */
	public function foundRows($link) {
		$sql = "SELECT FOUND_ROWS() as total";
		$result = $this->wrapResult($this->query($sql, $link));
		return $result[0]['total'];
	}

	/* (non-PHPdoc)
	 * @see DbDriver::version()
	 */
	public function version($link) {
		$sql = "SELECT VERSION() as ver";
		$result = $this->query($sql, $link);
		$result2 = $this->wrapResult($result);
		
		return $result2[0]['ver'];
	}

	/* (non-PHPdoc)
	 * @see DbDriver::driver()
	 */
	public function driver() {
		return 'mysql';
	}

	/**
	 * 
	 * @param DbData $data
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
	 * 
	 * @param DbData $data
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
	 * 
	 * @param DbData $data
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
	 * 
	 * @param DbData $data
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
	 * (non-PHPdoc)
	 * @see DbDriver::parseIndexHint()
	 */
	public function parseIndexHint($hint) {
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
	
	public function close($link) {
		return is_resource($link) ? mysql_close($link) : TRUE;
	}
}