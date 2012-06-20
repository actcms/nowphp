<?php
namespace now;

use now\err as err;
use now\fun as fun;
use now\view as view;

/**
 * 所有前段请求处理类的基类
 * @author      欧远宁
 * @version     1.0.0
 * @copyright   CopyRight By 欧远宁
 * @package     now
 */
abstract class cmd {
    
    /**
     * 模块名
     * @var string
     */
    protected $mdl = '';
    
    /**
     * 构造函数
     * @author 欧远宁
     */
    public function __construct($mdl){
        $this->mdl = $mdl;
    }
    
    /**
     * 析构函数
     * @author 欧远宁
     */
    public function __destruct(){
    }
    
    /**
     * 设置返回结果
     * @author 欧远宁
     * @param array $data 返回的数据
     * @param string $type 返回的类型，默认为json。其他还有：tpl,jsonp,amf
     * @param any $para 对应的参数
     * @example
     * //返回json
     * $this->_ret(
     * array(
     *   'msg'=>'hi',
     *   'data'=>array('aa','bb');
     * )
     * );
     * 
     * //根据模版返回
     * $this->_ret(array('name'=>'aaaa'), 'tpl', 'index');
     */
    protected function _ret($data=array(), $type='json', $para=null){
        if ($type == 'json'){
            view::json($data);
        } else if ($type == 'jsonp'){
            view::jsonp($para, $data);
        } else if ($type == 'tpl'){
            view::tpl($para, $data);
        } else if ($type == 'amf') {
            view::amf($data);
        }
    }
    
    /**
     * 进行用户传入信息的验证，如果无法通过验证，将会抛出异常。包含了一下的验证方式
     * uuid
     * ip
     * mail
     * url
     * zip
     * passwod
     * phone
     * mobile
     * date
     * datetime
     * qq
     * chinese-[min]-[max]
     * alpha-[min]-[max]
     * alnum-[min]-[max]
     * int-[min]-[max]
     * str-[min]-[max]
     * str2-[min]-[max]
     * @author 欧远宁
     * @param string $str 需要验证的字符串
     * @param string $val 验证规则
     * @throws err 抛出系统验证异常，我们一般认为只有黑客才会传入无法通过验证的异常
     */
    protected function _val($str, $val=''){
        if (is_array($str)){
            foreach($str as $k=>$v){
                if (!fun::val_str($k,$v)){
                    throw new err('The value of field ['.$k.'] , ['.$v.'] can not be verified');
                }
            }
        } else {
            if (!fun::val_str($str, $val)){
               throw new err('The value of ['.$str.'] can not be verified');
            }
        }
    }
    
    /**
     * 检验对应的VO
     * @author 欧远宁
     * @param string $mdl 模块名
     * @param string $tbl 表名
     * @param array  $val 需要检验的数据
     * @throws err 抛出系统验证异常，我们一般认为只有黑客才会传入无法通过验证的异常
     */
    protected function _valvo($mdl, $tbl, $val){
        $check = $GLOBALS['cfg'][$mdl]['schema'][$tbl]['check'];
        foreach($val as $k=>$v){
            if (key_exists($k, $check) && $check[$k] != ''){
                if (!fun::val_str($v, $check[$k])){
                    throw new err('The value of field ['.$k.'] , ['.$v.'] can not be verified');
                }
            }
        }
    }
    
    /**
     * 检查必须字段
     * @author 欧远宁
     * @param array $val     传递过来的数据内容
     * @param array $fields  必须字段列表
     */
    protected function _required($val, $fields){
        if (is_array($fields)){
            foreach($fields as $field){
                if (!key_exists($field, $val)){
                    throw new err($field.' is required');
                }
            }
        } else {
            if (!key_exists($fields, $val)){
                throw new err($fields.' is required');
            }
        }
    }
    
}