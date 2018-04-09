<?php

/**
 * AtomCode
 *
 * A open source application,welcome to join us to develop it.
 *
 * @package		AtomCode
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */

/**
 * Enable multiple database mode
 * 
 * PHP 5-5.2: You must specify the database key in each model of the corresponding table
 * PHP >= 5.3: You can use namespace instead of specify the database variable.
 */
$config['database']['multiple'] = FALSE;
/*
 * default database key name
 */
$config['database']['default'] = 'default';

/*
 * Db mode, none: single db, master/slave
 */
$config['database']['type'] = 'mysql';
$config['database']['host'] = 'localhost';
$config['database']['port'] = '';
$config['database']['user'] = 'root';
$config['database']['pass'] = '';
$config['database']['name'] = '';
$config['database']['charset'] = 'utf8';
$config['database']['table_prefix'] = '';
$config['database']['pconnect'] = FALSE;
$config['database']['db_debug'] = FALSE;
$config['database']['log'] = FALSE;
$config['database']['show_error'] = TRUE;
$config['database']['save_queries'] = FALSE;
/*
$key = 'default';
$config['database']['dbs'][$key]['mode'] = 'none';
$config['database']['dbs'][$key]['type'] = 'mysql';
$config['database']['dbs'][$key]['host'] = 'localhost';
$config['database']['dbs'][$key]['port'] = '';
$config['database']['dbs'][$key]['user'] = '';
$config['database']['dbs'][$key]['pass'] = '';
$config['database']['dbs'][$key]['name'] = '';
$config['database']['dbs'][$key]['charset'] = 'utf8';
$config['database']['dbs'][$key]['table_prefix'] = '';
$config['database']['dbs'][$key]['pconnect'] = FALSE;
$config['database']['dbs'][$key]['cache'] = FALSE;
$config['database']['dbs'][$key]['db_debug'] = FALSE;
$config['database']['dbs'][$key]['save_queries'] = FALSE;
*/