<?php
define('OP', dirname(__FILE__) . DIRECTORY_SEPARATOR);    //定义系统根目录
define('DS', DIRECTORY_SEPARATOR);                        //定义文件系统分隔符
require_once(OP.'ver.php');
require_once(OP.'now'.DS.'control.php');
spl_autoload_register(array('now\control', 'autoload'));  //动态加载类
use now\control as ctl;
ctl::execute();