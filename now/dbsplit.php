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
final class dbsplit {
    
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
}