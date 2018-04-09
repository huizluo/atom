<?php

$config['cache'] = array();
$config['cache']['drivers'] = array('file', 'db', 'memcache');
$config['cache']['default'] = 'file';
$config['cache']['ttl'] = 3600;

$config['cache']['db']['name'] = '';
$config['cache']['db']['host'] = '';
$config['cache']['db']['user'] = '';
$config['cache']['db']['pass'] = '';

$config['cache']['file']['dir'] = APP_PATH . '/cache/data/';

$config['cache']['memcache']['server'] = '';
$config['cache']['memcache']['port'] = '';