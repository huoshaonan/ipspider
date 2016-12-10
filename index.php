<?php

header('content-type:text/html;charset=utf8');
define('BASE_PATH', dirname(__FILE__));
define('CONTROLLER_PATH',BASE_PATH.'/controller');

require_once CONTROLLER_PATH.'/spider.php';
require_once BASE_PATH.'/model/ipmodel.php';
require_once BASE_PATH.'/library/function.php';
require_once BASE_PATH.'/library/curl.php';
require_once BASE_PATH.'/controller/myPthreads.php';

set_time_limit(0);

//兼容cli模式下运行
if (PHP_SAPI === 'cli') {

	$controller = @$argv['1'];
	$action     = @$argv['2'];
	$params     = @$argv['3'];
} else {

	$controller = @$_GET['c'];
	$action     = @$_GET['m'];
	$params     = @$_GET['p'];
}

$file = CONTROLLER_PATH.'/'.$controller.'.php';
if (!file_exists($file)) {
	throw new Exception("Undefined Controller", 1);
}

require_once $file;
if (!$action) {
	$action = 'index';
}

$obj = new $controller();
$obj->$action($params);