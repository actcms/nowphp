<?php
namespace now;

/**
 * 框架的异常
 * @author      欧远宁
 * @version     1.0.0
 * @copyright   CopyRight By 欧远宁
 * @package     now
 */
class err extends \Exception {

    /**
     * 构造函数
     * @author 欧远宁
     * @param $msg  string 错误信息
     * @param $code int    错误码
     */
    public function __construct($msg, $code = 0) {
        parent::__construct($msg, $code);
    }

    /**
     * 打印的错误信息
     */
    public function __toString() {
        return sprintf('%s [ %s ]: %s ~ %s [ %d ]',get_class($this), $this->getCode(), strip_tags($this->getMessage()), $this->getFile(), $this->getLine());
    }

}