<?php

namespace _lib;

use ErrorException;

class UtilImage {

	/**
	 * 获取图片的真实后缀
	 *
	 * @param unknown $file
	 * @return string
	 */
	static function ext($file) {
		if (! file_exists ( $file )) {
			$r = '';
		} else {
			return image_type_to_extension ( exif_imagetype ( $file ), false );
		}
	}

	/**
	 * 生成缩略图
	 *
	 * @param unknown $file
	 * @param unknown $thumb
	 * @param unknown $width
	 * @param unknown $height
	 * @param string $ratio
	 * @param number $quality
	 * @throws ErrorException
	 * @return boolean
	 */
	static function thumb($file, $thumb, $width, $height, $ratio = true, $quality = 100) {
		if (! file_exists ( $file )) {
			throw new ErrorException ( $file . ' was not found' );
		}
		$im = '';
		$imageSize = getimagesize ( $file );
		if ($imageSize) {
			if ($imageSize [2] == 1) { // gif
				if (function_exists ( "imagecreatefromgif" )) {
					$im = imagecreatefromgif ( $file );
				}
			} elseif ($imageSize [2] == 2) { // jpeg
				if (function_exists ( "imagecreatefromjpeg" )) {
					$im = imagecreatefromjpeg ( $file );
				}
			} elseif ($imageSize [2] == 3) { // png
				if (function_exists ( "imagecreatefrompng" )) {
					$im = imagecreatefrompng ( $file );
				}
			}
		}
		$srcWidth = $imageSize [0];
		$srcHeight = $imageSize [1];
		if ($ratio) {
			$srcRatio = $srcWidth / $srcHeight;
			$thumbRatio = $width / $height;
			if ($thumbRatio <= $srcRatio) {
				$height = round ( $width / $srcRatio );
			} else {
				$width = round ( $height * $srcRatio );
			}
		}
		// make thumb
		if ($width < $srcWidth || $height < $srcHeight) {
			if (function_exists ( "imagecreatetruecolor" ) && function_exists ( "imagecopyresampled" ) && $ni = imagecreatetruecolor ( $width, $height )) {
				imagecopyresampled ( $ni, $im, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight );
			} elseif (function_exists ( "imagecreate" ) && function_exists ( "imagecopyresized" ) && $ni = imagecreate ( $width, $height )) {
				imagecopyresized ( $ni, $im, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight );
			} else {
				throw new ErrorException ( 'Can\'t create thumb,no appropriate function' );
			}
			if (exif_imagetype ( $file ) == IMAGETYPE_JPEG && function_exists ( 'imagejpeg' )) {
				imagejpeg ( $ni, $thumb );
			} elseif (exif_imagetype ( $file ) == IMAGETYPE_PNG && function_exists ( 'imagepng' )) {
				imagepng ( $ni, $thumb );
			} else {
				throw new ErrorException ( 'no function find to create type:' . exif_imagetype ( $file ) . ' image' );
			}
		} else {
			copy ( $file, $thumb );
		}
		imagedestroy ( $im );
		imagedestroy ( $ni );
		return file_exists ( $thumb );
	}

	/**
	 * 为图片添加水印
	 *
	 * @param unknown $source
	 * @param unknown $water
	 * @param string $savename
	 * @param number $alpha
	 * @return boolean
	 */
	static function water($source, $water, $savename = null, $alpha = 80) {
		// 检查文件是否存在
		if (! file_exists ( $source ) || ! file_exists ( $water ))
			return false;

			// 图片信息
		$sInfo = self::getImageInfo ( $source );
		$wInfo = self::getImageInfo ( $water );

		// 如果图片小于水印图片，不生成图片
		if ($sInfo ["width"] < $wInfo ["width"] || $sInfo ['height'] < $wInfo ['height'])
			return false;

			// 建立图像
		$sCreateFun = "imagecreatefrom" . $sInfo ['type'];
		$sImage = $sCreateFun ( $source );
		$wCreateFun = "imagecreatefrom" . $wInfo ['type'];
		$wImage = $wCreateFun ( $water );

		// 设定图像的混色模式
		imagealphablending ( $wImage, true );

		// 图像位置,默认为右下角右对齐
		$posY = $sInfo ["height"] - $wInfo ["height"];
		$posX = $sInfo ["width"] - $wInfo ["width"];

		// 生成混合图像
		// ImageAlphaBlending($sImage, true);
		// imagecopymerge($sImage, $wImage, $posX, $posY, 0, 0, $wInfo['width'], $wInfo['height'], $alpha);
		// imagecopyresampled($sImage, $wImage, $posX, $posY, 0, 0, $wInfo['width'], $wInfo['height'],$sInfo['width'],$sInfo['height']);
		imagecopy ( $sImage, $wImage, $posX, $posY, 0, 0, $wInfo ['width'], $wInfo ['height'] );
		// 输出图像
		$ImageFun = 'Image' . $sInfo ['type'];
		// 如果没有给出保存文件名，默认为原图像名
		if (! $savename) {
			$savename = $source;
			@unlink ( $source );
		}
		// 保存图像
		$ImageFun ( $sImage, $savename );
		imagedestroy ( $sImage );
	}
}