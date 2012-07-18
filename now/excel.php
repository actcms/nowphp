<?php
namespace now;

/**
 * 最最简单的excel辅助操作
 * @author      欧远宁
 * @version     1.0.0
 * @copyright 	CopyRight By 欧远宁
 * @package     lib
 */
final class excel {
	
	/**
	 * 写开始
	 */
	private static function _write_bof(){
		echo pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0);
	}
	
	/**
	 * 写结束
	 */
	private static function _write_eof(){
		echo pack("ss", 0x0A, 0x00);
	}
	
	/**
	 * 写数字
	 * @param int $row
	 * @param int $col
	 * @param int $val
	 */
	private static function _write_num($row, $col, $val){
		echo pack("sssss", 0x203, 14, $row, $col, 0x0);
		echo pack("d", $val);
	}
	
	/**
	 * 写字符串
	 * @param int $row
	 * @param int $col
	 * @param string $val
	 */
	private static function _write_str($row, $col, $val){
		$val = iconv('utf-8', 'gbk', $val);
		$len = strlen($val);
		echo pack("ssssss", 0x204, 8 + $len, $row, $col, 0x0, $len);
		echo $val;
	}
	
	/**
	 * 输出excel
	 * @author 欧远宁
	 * @param array $data
	 */
	public static function write($head, $data){
		self::_write_bof();
		$arr = array();
		$i = 0;
		$row = 0;
		foreach($head as $k=>$v){
			self::_write_str($row, $i, $v);
			$arr[] = $k;
			$i = $i + 1;
		}
		$row = $row + 1;
		$max = count($arr);
		foreach($data as $tmp){
			for ($i=0; $i<$max; $i++){
				self::_write_str($row, $i, $tmp[$arr[$i]]);
			}
			$row = $row + 1;
		}
		self::_write_eof();
	}
}