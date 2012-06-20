<?php
namespace now;

use now\err as err;

/**
 * 视图输出类，提供结果集的返回
 * @author      欧远宁
 * @version     1.0.0
 * @copyright   CopyRight By 欧远宁
 * @package     now
 */
final class view {
    
    /**
     * 提供基于flash的AMF3格式的数据返回
     * @static
     * @author 欧远宁
     * @param array $data    返回的数据
     */
    public static function amf($data){
        if (!extension_loaded('amf')){
            throw new err('need amf ext');
        }
        header('Content-Type: application/x-amf');
        echo substr(amf_encode($data,AMF_AMF3|AMF_BIG_ENDIAN|AMFC_ARRAY|AMFR_ARRAY),1);
    }
    
    /**
     * 提供基于Json格式的数据返回
     * @static
     * @author 欧远宁
     * @param array $data    返回的数据
     */
    public static function json($data){
        header('Expires: 0');
        header('Cache-Control: public,must-revalidate,max-age=0,post-check=0,pre-check=0');
        header('Content-type:text/html;charset=utf-8');
        echo json_encode($data);
    }
    
    /**
     * 提供基于Json格式的数据返回供外面的接口使用,返回的数据是json格式的js数据，
     * 可以直接在jquery中使用 $.getScript()方法进行跨域提取
     * @static
     * @author 欧远宁
     * @param string $cb  jsonp的回调函数名称
     * @param array $data 返回的数据
     */
    public static function jsonp($cb, $data=array()){
        header('Expires: 0');
        header('Cache-Control: public,must-revalidate,max-age=0,post-check=0,pre-check=0');
        header('Content-type:text/html;charset=utf-8');
        echo $cb.'('. json_encode($data) .')';
    }
    
    /**
     * 使用模版返回结果内容，尽量不要用这个，可以使用前台模版
     * @static
     * @author 欧远宁
     * @param array $data  返回的数据
     * @param string $page 模版页面名称
     */
    public static function tpl($page, $data=array()){
        $ret = tpl::get_tpl($page, $data);
        header('Expires: 0');
        header('Cache-Control: public,must-revalidate,max-age=0,post-check=0,pre-check=0');
        header('Content-type:text/html;charset=utf-8');
        echo $ret;
    }
}