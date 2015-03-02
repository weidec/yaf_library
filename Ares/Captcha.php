<?php

namespace Ares;

class Captcha {
	public $length = 4;
	public $width = 65;
	public $height = 22;
	public $fontSize = 14;
	private $font;
	private $name = '_captcha';
	private $image;
	function __construct() {
		if(''==session_id()){
			session_start();
		}
		$this->font = SYS_PATH . '/resource/fonts/consolas.ttf';
	}
	
	/**
	 * 设置captcha的名字，也是Session的名字
	 * 
	 * @param unknown $name        	
	 * @throws \ErrorException
	 */
	function setName($name) {
		if (empty ( $name ) || ! is_string ( $name )) {
			throw new \ErrorException ( 'Captcha name is invalid' );
		}
		$this->name = $name;
	}
	
	/**
	 * 显示验证码
	 */
	function display() {
		$img = $this->image ();
		header ( "Content-Type:image/gif" );
		imagegif ( $img );
		imagedestroy ( $img );
	}
	
	/**
	 * 获取验证码
	 *
	 * @return string
	 */
	function getCode() {
		if (isset ( $_SESSION [$this->name] )) {
			$code = $_SESSION [$this->name];
			unset ( $_SESSION [$this->name] );
			return $code;
		}
	}
	
	/**
	 * 创建图像
	 *
	 * @return resource
	 */
	private function image() {
		$w = $this->width;
		$h = $this->height;
		$size = $this->fontSize;
		// 产生验证码并写入session
		$str = $this->randCode ();
		$_SESSION [$this->name] = $str;
		$im = imagecreate ( $w, $h );
		imagecolorallocate ( $im, 0xFF, 0xFF, 0xFF ); // 背景色
		$pix = imagecolorallocate ( $im, 0x60, 0x60, 0x60 ); // 杂点色
		for($i = 0; $i < 100; $i ++) {
			imagesetpixel ( $im, mt_rand ( 0, $w ), mt_rand ( 0, $h ), $pix );
		}
		for($i = 0; $i < $this->length; $i ++) {
			// 验证码
			imagettftext ( $im, $size, mt_rand ( - 20, 20 ), $i * $size + 7, $size + 2, imagecolorallocate ( $im, mt_rand ( 0, 200 ), mt_rand ( 0, 200 ), mt_rand ( 0, 200 ) ), $this->font, $str [$i] );
		}
		return $im;
	}
	
	/**
	 * 产生随机串
	 *
	 * @return string
	 */
	private function randCode() {
		$list = array_merge ( range ( 'a', 'z' ), range ( '2', '9' ) );
		// i j l o
		unset ( $list [8], $list [9], $list [11], $list [14] );
		$keys = array_rand ( $list, $this->length );
		$str = '';
		foreach ( $keys as $v ) {
			$str .= $list [$v];
		}
		return $str;
	}
}