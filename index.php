<?php
/**
 * 主入口 
 * @author 欧远宁
 */
define('OP', dirname(__FILE__) . DIRECTORY_SEPARATOR);    //定义系统根目录
define('DS', DIRECTORY_SEPARATOR);                        //定义文件系统分隔符
define('TIME', time());   								  //定义版本
require_once(OP.'cfg'.DS.'const.php');
require_once(OP.'now'.DS.'control.php');
spl_autoload_register(array('now\control', 'autoload'));  //动态加载类

use now\fun;
$now_cfg = fun::get_cfg('now');
mb_internal_encoding($now_cfg['encoding']);				//设置mbstring的编码
date_default_timezone_set($now_cfg['timezone']);		//设定时区
setlocale(LC_ALL, $now_cfg['locale']);					//设置本地化区域及字符集

function _get_ver($arr) {
	$ip = $_SERVER['SERVER_ADDR'];
	foreach($arr as $k=>$v) {
		foreach($v as $_ip){
			if ( strpos($_ip, $ip) !== FALSE ){
				return $k;
			}
		}
	}
	return 'dev';
}

define('VER', _get_ver($now_cfg['ver_ip']));   //定义版本
if (VER == 'pro') {     //正式版
	error_reporting(0); //不返回错误
} else { //其他版
	error_reporting(E_ALL); //返回所有错误
}
fun::init_cfg();
use now\control;
control::execute();