<?php if (!defined("ERROR_PAGE_ID")) { define('ERROR_PAGE_ID', TRUE); 
function __shutdown_function_callback() {
	echo "</body></html>";
}
register_shutdown_function("__shutdown_function_callback");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $title; ?></title>
<style type="text/css">
body,td,th {
	font-family: "微软雅黑", "Arial Black";
	font-size: 12px;
}
h1, h2, hr, p { margin:0; padding:2px;}
h1 { font-size:large; color:#FF9900;}
h2 { font-size:larger; padding-top: 6px;}
hr { width:350px; border:none; border-top:2px solid #FF9900;}
ul, li { margin: 0; padding:0; list-style: none;}
ul {padding-left: 12px;}
</style>
</head>

<body>
<?php } else { echo "<br /><br /><br />";} ?>
<h1><?php echo $title; ?></h1>
<hr />
<?php if (is_string($message)) {?>
<p><b>Error: </b><?php echo $message; ?> </p>
<p>&nbsp;</p>
<?php } elseif (is_array($message)) {
	echo "<p><b>Error:</b><br /><ul>"; 
	echo implode('<br />', $message);
	echo "</ul></p>";
 } ?>
<?php if (is_string($exception)) {?>
<p><b>Exception: </b><?php echo $exception; ?> </p>
<p>&nbsp;</p>
<?php } elseif (is_array($exception)) {
	echo "<p><b>Exception:</b><br />"; 
	if (!function_exists('__function_error_show_msg_item')) {
		function __function_error_show_msg_item($config) {
			echo '<ul>' . PHP_EOL;
			foreach ($config as $key => $config2) {
				if (is_array($config2)) {
					echo "<li># $key :</li>";
					__function_error_show_msg_item($config2);
				} else {
					echo "<li>$key: " . (is_bool($config2) ? ($config2 ? "TRUE" : "FALSE") : $config2) . "</li>";
				}
			}
			echo '</ul>';
		}
	}
	echo __function_error_show_msg_item($exception);
 } ?>
<?php if (TEST_MODE && $config && is_array($config)) { ?>
<h2>Configuration:</h2>
<?php 
	if (!function_exists('__function_error_show_config_item')) {
		function __function_error_show_config_item($config) {
			echo '<ul>' . PHP_EOL;
			foreach ($config as $key => $config2) {
				if (is_array($config2)) {
					echo "<li>$key :</li>";
					__function_error_show_config_item($config2);
				} else {
					echo "<li>$key: " . (is_bool($config2) ? ($config2 ? "TRUE" : "FALSE") : $config2) . "</li>";
				}
			}
			echo '</ul>';
		}
	}
	__function_error_show_config_item($config);
}
?>