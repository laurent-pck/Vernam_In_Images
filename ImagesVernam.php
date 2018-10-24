<?php

class ImagesVernam
{
	public function __construct()
	{
		return $this;
	}

	public function encrypt(string $message)
	{
		// check if the message is an ascii string
		if (! $this->isAscii($message)) {
			return false;
		}

		$originalImageDirPath = __DIR__ . '/original_image';
		$encryptedImagesDirPath = __DIR__ . '/encrypted_images';

		$originalImageDir = opendir($originalImageDirPath);

		if ($originalImageDir === false) {
			throw new Exception("Cannot open directory " . $originalImageDirPath . ".");
		}

		$originalImageName = null;

		while(($file = readdir($originalImageDir)) !== false) {
			if ($file == '.' || $file == '..') {
				continue;
			}

			$originalImageName = $file;
			break;
		}

		closedir($originalImageDir);

		if($originalImageName === false) {
			throw new Exception("No image in original_image directory. Please add one.");
		}

		$originalImage = imagecreatefromjpeg($originalImageDirPath . '/' . $originalImageName);

		$binaryMessage = $this->stringToBinary($message);
		$key = $this->generateKey(strlen($binaryMessage));
		$encryptedMessage = $this->xorOperation($binaryMessage, $key);

		$keyImage = $this->putBinaryInImage($key, $originalImage);
		$encryptedMessageImage = $this->putBinaryInImage($encryptedMessage, $originalImage);

		imagejpeg($keyImage, $encryptedImagesDirPath . '/key.jpeg');
		imagejpeg($encryptedMessageImage, $encryptedImagesDirPath . '/encryptedMessage.jpeg');

		return true;
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
			return false;
		}

		for ($i=0; $i<$lengthStr; $i++) { 
			$bit = substr($binary, $i, 1);

			$xCoord = $i % $width;
			$yCoord = floor($i / $width);

			$index = imagecolorat($image, $xCoord, $yCoord);
			$rgba = imagecolorsforindex($image, $index);

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

			$newIndex = imagecolorallocatealpha($image,
				$rgba['red'],
				$rgba['green'],
				$rgba['blue'],
				$rgba['alpha']
			);

			imagesetpixel($image, $xCoord, $yCoord, $newIndex);
		}

		return $image;
	}
}