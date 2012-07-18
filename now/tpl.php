<?php
namespace now;

/**
 * 使用php自带语法的模版，请尽量不要使用后台模版，尽量使用前端的js模版
 * 只支持<!--{ }-->  和 {{ }}这2个标签，其他一律不支持了
 * @author      欧远宁
 * @version     1.0.0
 * @copyright   CopyRight By 欧远宁
 * @package     now
 */
final class tpl {
    
    /**
     * 根据对应数据显示页面内容
     * @static
     * @author 欧远宁
     * @param string $page
     * @param array $data
     */
    public static function get_tpl($page, $data=array()){
        $path = $GLOBALS['cfg']['cfg']['tpl'].$page.'.html';
        $tpl = file_get_contents($path);
        $tpl = str_replace(
                array(
                '<!--{', '}-->', '{{', '}}'
                ), 
                array(
                "\nEF;\n", ";\$str.=<<<EF\n", "\nEF;\n\$str.=", ";\n\$str.=<<<EF\n",
                ), $tpl);
        
       $tpl = "\$str=<<<EF\n".$tpl."\nEF;\nreturn \$str;";
       $fun = create_function('$data', $tpl);
        return $fun($data);
    }
}