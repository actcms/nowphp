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
     * 此次请求的方法
     * @var string
     */
    protected $method = '';
    
    /**
     * 构造函数
     * @author 欧远宁
     */
    public function __construct($method){
        $this->method = $method;
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
     * //返回json<br/>
     * $this->_ret(<br/>
     * array(<br/>
     *   'msg'=>'hi',<br/>
     *   'data'=>array('aa','bb');<br/>
     * )<br/>
     * );<br/><br/>
     * 
     * //根据模版返回<br/>
     * $this->_ret(array('name'=>'aaaa'), 'tpl', 'index');<br/>
     * 
     * //直接下载
	 * $this->_ret(array(
	 *		'name'=>'毕业证号导入模板.xls',
	 *		'path'=>OP.'static/excel/import_grad_num.xls',
	 *		'type'=>'application/vnd.ms-excel;charset=gb2312'
	 *		),'down');
     */
    protected final function _ret($data=array(), $type='json', $para=null){
        inject::cmd_after($this->method, $data, $type, $para);
        if ($type == 'json'){
            view::json($data);
        } else if ($type == 'jsonp'){
            view::jsonp($para, $data);
        } else if ($type == 'tpl'){
            view::tpl($para, $data);
        } else if ($type == 'amf') {
            view::amf($data);
        } else if ($type == 'excel'){
        	view::excel($para['name'], $para['head'], $data);
        } else if ($type == 'down') {
        	if (isset($data['type'])){
        		view::down($data['name'], $data['path'], $data['type']);
        	} else {
        		view::down($data['name'], $data['path']);
        	}
       } else if ($type == 'redirect'){
       		view::redirect($data);
       }
    }
    
    /**
     * 进行用户传入信息的验证，如果无法通过验证，将会抛出异常。包含了一下的验证方式<br/>
     * uuid		检查uuid<br/>
     * ip		检查IP<br/>
     * mail		检查email<br/>
     * url      检查URL<br/>
     * zip      检查邮编
     * passwod  检查密码
     * phone    电话号码<br/>
     * mobile   手机号
     * date     日期，格式为yyyy-mm-dd<br/>
     * datetime 时间，格式为yyyy-mm-dd hh:ii:ss<br/>
     * timestamp 时间戳类型<br/>
     * qq       QQ号<br/>
     * chinese-min-[max]   都是汉字<br/>
     * alpha-min-[max]     都是字母<br/>
     * alnum-min-[max]     字母和数字<br/>
     * num-min-[max]       数字<br/>
     * int-min-[max]       整数<br/>
     * str-min-[max]       字符串，一个汉字当作一个字符串<br/>
     * str2-min-[max]      字符串，一个汉字根据gbk编码计算字符串长度，一般是2个<br/>
     * @author 欧远宁
     * @param string $str 需要验证的字符串
     * @param string $val 验证规则
     * @param bool $throw 是否直接抛出异常，默认为直接抛出异常，FALSE则在第一次出错的时候，返回FALSE。
     * @throws err 抛出系统验证异常，我们一般认为只有黑客才会传入无法通过验证的异常
     */
    protected final function _val($str, $val='', $throw=TRUE){
        if (is_array($str)){
            foreach($str as $k=>$v){
                if (!fun::val_str($k,$v)){
                	if ($throw){
                    	throw new err('The value of field ['.$k.'] , ['.$v.'] can not be verified');
                	} else {
                		return FALSE;
                	}
                }
            }
        } else {
            if (!fun::val_str($str, $val)){
				if ($throw){
              	 	throw new err('The value of ['.$str.'] can not be verified');
				} else {
					return FALSE;
				}
            }
        }
        return TRUE;
    }
    
    /**
     * 检验对应的VO
     * @author 欧远宁
     * @param string $mdl 模块名
     * @param string $tbl 表名
     * @param array  $val 需要检验的数据
     * @param bool $throw 是否直接抛出异常，默认为直接抛出异常，FALSE则在第一次出错的时候，返回FALSE。
     * @throws err 抛出系统验证异常，我们一般认为只有黑客才会传入无法通过验证的异常
     */
    protected final function _valvo($mdl, $tbl, $val, $throw=TRUE){
        $check = $GLOBALS['cfg'][$mdl]['schema'][$tbl]['check'];
        foreach($val as $k=>$v){
            if (key_exists($k, $check) && $check[$k] != ''){
                if (!fun::val_str($v, $check[$k])){
                	if ($throw){
                		throw new err('The value of field ['.$k.'] , ['.$v.'] can not be verified');
                	} else {
                		return FALSE;
                	}
                }
            }
        }
        return TRUE;
    }
    
    /**
     * 剔除空字段内容,返回都是有数据的字段
     * @author 欧远宁
     * @param array $para	输入参数
     * @return 去掉空输入之后的数据
     */
    protected final function _remove_empty($para){
    	$ret = array();
    	foreach($para as $k=>$v){
    		if (!empty($v)){
    			$ret[$k] = $v;
    		}
    	}
    	return $ret;
    }
    
    /**
     * 检查必须字段
     * @author 欧远宁
     * @param array $val     传递过来的数据内容
     * @param array $fields  必须字段列表
     * @param bool $throw 是否直接抛出异常，默认为直接抛出异常，FALSE则在第一次出错的时候，返回FALSE。
     */
    protected final function _required($val, $fields, $throw=TRUE){
        if (is_array($fields)){
            foreach($fields as $field){
                if (!key_exists($field, $val)){
                	if ($throw){
                    	throw new err($field.' is required');
                	} else {
                		return FALSE;
                	}
                }
            }
        } else {
            if (!key_exists($fields, $val)){
                if ($throw){
               		throw new err($fields.' is required');
                } else {
                	return FALSE;
                }
            }
        }
        return TRUE;
    }
    
}