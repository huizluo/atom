<?php

$config['email']['driver'] = 'smtp'; //mail, sendmail, or smtp
//$config['email']['smtp_host'] = '219.232.254.26';
$config['email']['smtp_host']	=	'192.168.0.26';	
$config['email']['smtp_user'] = '';
$config['email']['smtp_pass'] = '';

$config['email']['smtp_port'] = '25';
$config['email']['smtp_timeout'] = '30';

$config['email']['wordwrap'] = true;
$config['email']['wrapchars'] = 76;
$config['email']['mailtype'] = "html"; //text 或 html
$config['email']['charset'] = "gb2312";
