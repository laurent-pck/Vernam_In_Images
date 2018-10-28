<?php

class ImagesVernam
{
	public function __construct()
	{
		return $this;
	}

	/**
	 * Creates a key image and an encrypted message image, both based on the original image given.
	 *
	 * @param string $message (the message to encrypt)
	 * @param string $originalImagePath (absolute path to original image)
	 * @return bool (true on succes, throw exception otherwise)
	 */
	public function encrypt(string $message, string $originalImagePath)
	{
		// check if the message is an ascii string
		if (! $this->isAscii($message)) {
			throw new Exception("The message is not ascii (char codes 32 to 126 inclusive).");
		}

		$encryptedImagesDirPath = __DIR__ . '/../encrypted_images';

		if (!file_exists($encryptedImagesDirPath)) {
		    mkdir($encryptedImagesDirPath, 0664, true);
		}

		$keyImagePath = $encryptedImagesDirPath . '/key.png';
		$encryptedMessageImagePath = $encryptedImagesDirPath . '/encryptedMessage.png';

		copy($originalImagePath, $keyImagePath);
		copy($originalImagePath, $encryptedMessageImagePath);

		$keyImage = imagecreatefrompng($keyImagePath);
		$encryptedMessageImage = imagecreatefrompng($encryptedMessageImagePath);

		// We need to have truecolor images to keep exact value of rgb for each pixel. 
		if(!imageistruecolor($keyImage)) {
			imagepalettetotruecolor($keyImage);
		}

		if(!imageistruecolor($encryptedMessageImage)) {
			imagepalettetotruecolor($encryptedMessageImage);
		}

		if(!(imageistruecolor($keyImage) && imageistruecolor($encryptedMessageImage))) {
			throw new Exception("The images could not be converted to truecolor images. Try with an other original image.");
		}

		$binaryMessage = $this->stringToBinary($message);
		$key = $this->generateKey(strlen($binaryMessage));
		$encryptedMessage = $this->xorOperation($binaryMessage, $key);

		$keyImage = $this->putBinaryInImage($key, $keyImage);
		$encryptedMessageImage = $this->putBinaryInImage($encryptedMessage, $encryptedMessageImage);

		imagepng($keyImage, $encryptedImagesDirPath . '/key.png');
		imagepng($encryptedMessageImage, $encryptedImagesDirPath . '/encryptedMessage.png');

		imagedestroy($keyImage);
		imagedestroy($encryptedMessageImage);

		return true;
	}

	/**
	 * Retrieve the original message from the key image and the encrypted message image.
	 *
	 * @param string $keyImagePath (absolute path)
	 * @param string $encryptedMessageImagePath (absolute path)
	 * @return string (original message)
	 */
	public function decrypt($keyImagePath, $encryptedMessageImagePath)
	{
		$keyImage = imagecreatefrompng($keyImagePath);
		$encryptedMessageImage = imagecreatefrompng($encryptedMessageImagePath);

		if (! $this->sameImages($keyImage, $encryptedMessageImage)) {
			throw new Exception('Images does not have the same dimensions.');
		}

		$binaryOriginal = $this->imageXorOperation($keyImage, $encryptedMessageImage);
		$stringOriginal = $this->binaryToString($binaryOriginal);

		imagedestroy($keyImage);
		imagedestroy($encryptedMessageImage);

		return $stringOriginal;
	}

	/**
	 * Verify if a string is ascii (char codes from 32 to 126 inclusive).
	 *
	 * @param string $test
	 * @return bool
	 */
	private function isAscii(string $test)
	{
		if (preg_match('/[^\x20-\x7e]/', $test)) {
			return false;
		}

		return true;
	}

	/**
	 * Transform an ascii string in a bytes string.
	 *
	 * @param string $input (ascii string)
	 * @return string (bytes)
	 */
	private function stringToBinary(string $input)
	{
		$length = strlen($input);
		$binary = '';

		for ($i=0; $i<$length; $i++) {
			$char = substr($input, $i); 
			$code = ord($char);
			$binCode = base_convert($code, 10, 2);
			$diff = 8 - strlen($binCode);
			$add = substr("00000000", 0, $diff);
			$binary .= ($add . $binCode);
		}

		return $binary;
	}

	/**
	 * Transform a bytes string in an ascii string.
	 *
	 * @param string $input (bytes)
	 * @return string (ascii string)
	 */
	private function binaryToString(string $input)
	{
		$length = strlen($input);
		$result = '';

		for($i=0; $i<$length; $i+=8) {
			$byte = substr($input, $i, 8);
			$code = base_convert($byte, 2, 10);
			$char = chr((int)$code);
			$result .= $char; 
		}

		return $result;
	}

	/**
	 * Generates a key of $length random bits.
	 *
	 * @param int $length
	 * @return string (bits)
	 */
	private function generateKey(int $length)
	{
		$key = '';

		for ($i=0; $i<$length; $i++) { 
			$key .= (string)rand(0, 1);
		}

		return $key;
	}

	/**
	 * Makes a xor operation with two string of bits of equal length.
	 *
	 * @param string $message (bits)
	 * @param string $key (bits)
	 * @return string (xor result)
	 */
	private function xorOperation(string $message, string $key)
	{
		if (($length = strlen($message)) !== strlen($key)) {
			return false;
		}

		$result = '';

		for ($i=0; $i<$length; $i++) { 
			$m = substr($message, $i, 1);
			$k = substr($key, $i, 1);
			$xorResult = (boolval($m) xor boolval($k));
			$bit = $xorResult ? '1' : '0';
			$result .= $bit;
		}

		return $result;
	}

	/**
	 * Put binary data in an image (add one or zero to corresponding pixel).
	 *
	 * @param string $binary (bits)
	 * @param resource $image
	 * @return resource (image with data in it)
	 */
	private function putBinaryInImage(string $binary, $image)
	{
		$lengthStr = strlen($binary);
		$width = imagesx($image);
		$height = imagesy($image);
		$lengthImg =  $width * $height;

		if ($lengthStr > $lengthImg) {
			throw new Exception("Eight times string length is bigger than the number of pixels in the original image. An image with minimum " . $lengthStr . " pixels is needed.");
		}

		for ($i=0; $i<$lengthStr; $i++) { 
			$bit = substr($binary, $i, 1);

			$xCoord = $i % $width;
			$yCoord = (int)floor($i / $width);

			$colorIndex = imagecolorat($image, $xCoord, $yCoord);
			$rgba = imagecolorsforindex($image, $colorIndex);

			if ($bit == '1') {
				// if we are not at the max value, we add one
				if($rgba['red'] != 255) {
					$rgba['red'] += 1;
				}
				// if we are at the max value, we substract one before adding one (= do nothing)
			} else {
				// if we are at the max value, we substract one before adding zero (= substract one)
				if($rgba['red'] == 255) {
					$rgba['red'] -= 1;
				}
			}

			$newRgba = imagecolorallocate($image, $rgba['red'], $rgba['green'], $rgba['blue']);
			imagesetpixel($image, $xCoord, $yCoord, $newRgba);
		}

		return $image;
	}

	/**
	 * Verify if two images have the same dimensions.
	 *
	 * @param resource $imageOne
	 * @param resource $imageTwo
	 * @return bool (true if the images have the same dimensions, false otherwise)
	 */
	private function sameImages($imageOne, $imageTwo)
	{
		$widthOne = imagesx($imageOne);
		$heightOne = imagesy($imageOne);
		$widthTwo = imagesx($imageTwo);
		$heightTwo = imagesy($imageTwo);

		if ($widthOne == $widthTwo && $heightOne == $heightTwo) {
			return true;
		}

		return false;
	}

	/**
	 * Computes the xor results for two string of bits hidden in two images.
	 *
	 * @param resource $keyImg
	 * @param resource $messageImg
	 * @return string (bits)
	 */
	private function imageXorOperation($keyImg, $messageImg)
	{
		$width = imagesx($keyImg);
		$height = imagesy($keyImg);

		$size = $width * $height;

		$result = "";
		$nullByteTest = "";

		for ($i=0; $i<$size ; $i++) { 
			$xCoord = $i % $width;
			$yCoord = (int)floor($i / $width);

			$keyIndex = imagecolorat($keyImg, $xCoord, $yCoord);
			$keyRgba = imagecolorsforindex($keyImg, $keyIndex);
			$keyR = $keyRgba['red'];

			$messageIndex = imagecolorat($messageImg, $xCoord, $yCoord);
			$messageRgba = imagecolorsforindex($messageImg, $messageIndex);
			$messageR = $messageRgba['red'];

			// We have four cases, they are summarized bellow (b is background value):
			// key, message, difference, xorResult
			// 1) b+1, b+0, 1, 1
			// 2) b+1, b+1, 0, 0
			// 3) b+0, b+0, 0, 0
			// 4) b+0, b+1, -1, 1
			// The xor result is the absolute value of the difference.
			$difference = $keyR - $messageR;
			$xorResult = abs($difference);

			// If we have a null byte, we consider that it is the end of the string (with reason).
			if ($i%8 == 0) {
				if ($nullByteTest == '00000000') {
					// Null byte is taken out of the result befor return
					$result = substr($result, 0, -8);
					return $result;
				}

				$nullByteTest = "";
			}

			$nullByteTest .= (string)$xorResult;
			$result .= (string)$xorResult;
		}

		return $result;
	}
}