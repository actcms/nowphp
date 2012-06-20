<?php
error_reporting(E_ALL);    //返回所有错误
//error_reporting(0);      //不返回任何错误

mb_internal_encoding('utf-8');                            //设置mbstring的编码
define('OP', dirname(__FILE__) . DIRECTORY_SEPARATOR);    //定义系统根目录
define('DS', DIRECTORY_SEPARATOR);                        //定义文件系统分隔符
date_default_timezone_set('Asia/Shanghai');               //设定时区
setlocale(LC_ALL, 'zh_CN.utf-8');                         //设置本地化区域及字符集

require_once(OP.'now'.DS.'control.php');
spl_autoload_register(array('now\control', 'autoload'));  //动态加载类
use now\control as ctl;
ctl::execute();