<?php
/**
 * 这里设置版本信息，根据请求的HOST来确定当前版本，并使用对应版本的配置
 * @author 欧远宁
 */
mb_internal_encoding('utf-8');                            //设置mbstring的编码
date_default_timezone_set('Asia/Shanghai');               //设定时区
setlocale(LC_ALL, 'zh_CN.utf-8');                         //设置本地化区域及字符集

if ($_SERVER['HTTP_HOST'] == 'localhost') {
	define('VER', 'dev');   //开发版本
	error_reporting(E_ALL); //返回所有错误
} else if ( substr($_SERVER['HTTP_HOST'], 0, 3) == '192' || substr($_SERVER['HTTP_HOST'], 0, 3) == '10.') {
	define('VER', 'test');   //测试版本
	error_reporting(E_ALL); //返回所有错误
} else {
	define('VER', 'pro');   //生产版本
	error_reporting(0);     //不返回所有错误
}
require_once OP.'cfg'.DS.'cfg.php';
require_once OP.'cfg'.DS.'db.php';
$GLOBALS['cfg']['cfg'] = $GLOBALS['cfg']['cfg'][VER];
$GLOBALS['cfg']['db'] = $GLOBALS['cfg']['db'][VER];
