<?php
class Helper_Image {
	
	/**
	 * get real image extention by image content
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
	 * thumbnail
	 *
	 * @param unknown $file        	
	 * @param unknown $thumb        	
	 * @param unknown $width        	
	 * @param unknown $height        	
	 * @param string $ratio        	
	 * @param number $quality        	
	 * @return boolean
	 */
	static function thumb($file, $thumb, $width, $height, $ratio = true, $quality = 100) {
		if (! file_exists ( $file )) {
			user_error ( $file . ' was not found', E_USER_WARNING );
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
				user_error ( 'Can\'t create thumb,no appropriate function', E_USER_WARNING );
			}
			if (exif_imagetype ( $file ) == IMAGETYPE_JPEG && function_exists ( 'imagejpeg' )) {
				imagejpeg ( $ni, $thumb );
			} elseif (exif_imagetype ( $file ) == IMAGETYPE_PNG && function_exists ( 'imagepng' )) {
				imagepng ( $ni, $thumb );
			} else {
				user_error ( 'no function find to create type:' . exif_imagetype ( $file ) . ' image', E_USER_WARNING );
			}
		} else {
			copy ( $file, $thumb );
		}
		imagedestroy ( $im );
		imagedestroy ( $ni );
		return file_exists ( $thumb );
	}
	
	/**
	 * add watermark to image
	 *
	 * @param unknown $source        	
	 * @param unknown $water        	
	 * @param string $savename        	
	 * @param number $alpha        	
	 * @return boolean
	 */
	static function water($source, $water, $savename = null, $alpha = 80) {
		if (! file_exists ( $source ) || ! file_exists ( $water )) {
			return false;
		}
		// image info
		$sInfo = self::getImageInfo ( $source );
		$wInfo = self::getImageInfo ( $water );
		
		// if image size less than watermark image, then return
		if ($sInfo ["width"] < $wInfo ["width"] || $sInfo ['height'] < $wInfo ['height']) {
			return false;
		}
		
		// create image
		$sCreateFun = "imagecreatefrom" . $sInfo ['type'];
		$sImage = $sCreateFun ( $source );
		$wCreateFun = "imagecreatefrom" . $wInfo ['type'];
		$wImage = $wCreateFun ( $water );
		
		// set image color mixture mode
		imagealphablending ( $wImage, true );
		
		// position, default bottom right corner
		$posY = $sInfo ["height"] - $wInfo ["height"];
		$posX = $sInfo ["width"] - $wInfo ["width"];
		
		// generate mixed image
		// ImageAlphaBlending($sImage, true);
		// imagecopymerge($sImage, $wImage, $posX, $posY, 0, 0, $wInfo['width'], $wInfo['height'], $alpha);
		// imagecopyresampled($sImage, $wImage, $posX, $posY, 0, 0, $wInfo['width'], $wInfo['height'],$sInfo['width'],$sInfo['height']);
		imagecopy ( $sImage, $wImage, $posX, $posY, 0, 0, $wInfo ['width'], $wInfo ['height'] );
		// output image
		$ImageFun = 'Image' . $sInfo ['type'];
		// use original image filename if not specified
		if (! $savename) {
			$savename = $source;
			unlink ( $source );
		}
		// save image
		$ImageFun ( $sImage, $savename );
		imagedestroy ( $sImage );
	}
}