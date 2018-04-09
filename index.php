<?php
/**
 *
 * 是否测试模式，控制错误输出和允许调试、测试、记录日志, 不定义则表示 TEST_MODEL 为 FALSE
 * @var Boolean
 */
define("TEST_MODE", TRUE);
/**
 * 文件本身 *必须
 * @var String
 */
define("SELF", __FILE__);

/**
 * 应用的路径 *必须
 * @var String
 */
define("DOC_ROOT", dirname(__FILE__));
define("APP_PATH", realpath(dirname(__FILE__) . "/../application"));

/**
 * 定义环境，如果不定义则使用 /application/config/目录下配置，否则使用 /application/config/ENV/目录下配置
 * @var String
 */
define('ENV', '');
/**
 * 定义渲染引擎，如果不定义则默认为Html, 否则使用相应的引擎进行渲染输出
 * 允许的值请参考文档
 * @var String
 */
define('RENDER', 'Html');

require '../vendor/autoload.php';
require '../system/atom.php';


