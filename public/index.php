<?php

define("TEST_MODE", true);

define("SELF", __FILE__);

define("DOC_ROOT", dirname(__FILE__));
define("APP_PATH", realpath(dirname(__FILE__) . "/../application"));


define('ENV', '');
/**
 * 定义渲染引擎，如果不定义则默认为Html, 否则使用相应的引擎进行渲染输出
 * 允许的值请参考文档
 * @var String
 */
define('RENDER', 'Html');

require '../vendor/autoload.php';
require '../system/atom.php';


