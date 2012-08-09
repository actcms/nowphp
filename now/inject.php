<?php
namespace now;

use now\err as err;

/**
 * 进行分库，分表处理
 * @author      欧远宁
 * @version     1.0.0
 * @copyright   CopyRight By 欧远宁
 * @package     now
 */
final class inject {
    
    /**
     * 得到某个库某个表的分库分表信息
     * @static
     * @author 欧远宁
     * @param string $mdl 模块名
     * @param string $tbl 表名
     * @return array('db'=>'库后缀','tbl'=>'表后缀')
     */
    public static function split($mdl, $tbl){
        $cfg = fun::get_cfg('cfg');
        if (!key_exists('dbsplit', $cfg)){
            return array('','');
        }
                
        $db_suf = call_user_func($cfg['dbsplit'], $mdl);
        $tmp = $mdl.$tbl;
        $tbl_suf = call_user_func($cfg['dbsplit'], $mdl, $tbl);
        
        return array('db'=>$db_suf, 'tbl'=>$tbl_suf);
    }
    
    /**
     * 进行命令执行前的拦截操作
     * @static
     * @author 欧远宁
     * @param string $cmd  命令名
     * @param string $para 请求的参数
     */
    public static function cmd_before($cmd, $para){
    	$cfg = fun::get_cfg('cfg');
    	if (key_exists('cmd_inject', $cfg)){
    		call_user_func(array($cfg['cmd_inject'], 'before'), $cmd, $para);
    	}
    }
    
    /**
     * 进行命令执行后结果的拦截操作
     * @static
     * @author 欧远宁
     * @param string $cmd  命令名
     * @param string $data 返回的结果
     * @param string $type 返回类型
     * @param string $para 返回的参数
     */
    public static function cmd_after($cmd, $data=array(), $type='json', $para=null){
    	$cfg = fun::get_cfg('cfg');
    	if (key_exists('cmd_inject', $cfg)){
    		call_user_func(array($cfg['cmd_inject'], 'after'), $cmd, $data, $type, $para);
    	}
    }
    
    /**
     * 进行命令执行的异常拦截操作
     * @static
     * @author 欧远宁
     * @param string $cmd  命令名
     * @param string $code 错误码
     * @param string $msg  错误内容
     */
    public static function cmd_error($cmd, $code, $msg){
    	$cfg = fun::get_cfg('cfg');
    	if (key_exists('cmd_inject', $cfg)){
    		call_user_func(array($cfg['cmd_inject'], 'error'), $cmd, $code, $msg);
    	}
    }
}