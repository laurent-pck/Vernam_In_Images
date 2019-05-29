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
     * @return bool (true on succes, Exception otherwise)
     */
    public function encrypt(string $message, string $originalImagePath)
    {
        // check if the message is an ascii string
        if (!$this->isAscii($message)) {
            throw new Exception("The message is not ascii (char codes 32 to 126 inclusive).");
        }

        $encryptedImagesDirPath = __DIR__ . '/../encrypted_images';

        if (!file_exists($encryptedImagesDirPath)) {
            mkdir($encryptedImagesDirPath, 0777, true);
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
     * Generate a key.png image for each image in the given directory.
     * The length of the key is the number of pixels in the image.
     *
     * @param string $originalImagesDirPath (absolute path)
     * @return bool (true on success, Exception otherwise)
     */
    public function generateKeys(string $originalImagesDirPath)
    {
        return true;
    }

    /**
     * Encrypt a message with a previously made image key.
     * The original image is needed to retrieve the binary key.
     *
     * @param string $message (the message to encrypt)
     * @param string $keyImagePath (absolute path)
     * @param string $originalImagePath (absolute path)
     * @return bool (true on success, Exception otherwise)
     */
    public function encryptWithKey(string $message, string $keyImagePath, string $originalImagePath)
    {
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
            $key .= (string)random_int(0, 1);
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
     * An even pixel value means 0, an odd 1
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

        if ($lengthStr > 3 * $lengthImg) {
            throw new Exception("Eight times string length is bigger than three times the number of pixels in the original image. An image with minimum " . (int)floor($lengthStr / 3) . " pixels is needed.");
        }

        for ($i=0; $i<$lengthStr; $i++) { 
            $bit = substr($binary, $i, 1);

            $channelNb = (int)floor($i / $lengthImg);
            $channelNbColor = array(
                0 => 'red',
                1 => 'green',
                2 => 'blue',
            );
            $channelColor = $channelNbColor[$channelNb];

            $xCoord = $i % $width;
            $yCoord = (int)floor($i / $width) - ($height * $channelNb);

            $colorIndex = imagecolorat($image, $xCoord, $yCoord);
            $rgba = imagecolorsforindex($image, $colorIndex);

            if ($bit == '1') {
                // if value is even, change it
                if ($rgba[$channelColor] % 2 === 0) {
                    $rgba[$channelColor] += 1;
                }
            } else {
                // if value is odd, change it
                if ($rgba[$channelColor] % 2 !== 0) {
                    if ($rgba[$channelColor] === 255) {
                        $rgba[$channelColor] -= 1;
                    } else {
                        $rgba[$channelColor] += 1;
                    }
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

        for ($i=0; $i<3*$size ; $i++) {
            $channelNb = (int)floor($i / $size);
            $channelNbColor = array(
                0 => 'red',
                1 => 'green',
                2 => 'blue',
            );
            $channelColor = $channelNbColor[$channelNb];

            $xCoord = $i % $width;
            $yCoord = (int)floor($i / $width) - ($height * $channelNb);

            $keyIndex = imagecolorat($keyImg, $xCoord, $yCoord);
            $keyRgba = imagecolorsforindex($keyImg, $keyIndex);
            $keyVal = $keyRgba[$channelColor];

            $messageIndex = imagecolorat($messageImg, $xCoord, $yCoord);
            $messageRgba = imagecolorsforindex($messageImg, $messageIndex);
            $messageVal = $messageRgba[$channelColor];

            // The keyVal or messageVal mod 2 gives the binary value
            $xorResult = (boolval($keyVal % 2) xor boolval($messageVal % 2));
            $xorResult = $xorResult ? '1' : '0';

            // If we have a null byte, we consider that it is the end of the string (with reason).
            if ($i%8 == 0) {
                if ($nullByteTest == '00000000') {
                    // Null byte is taken out of the result befor return
                    $result = substr($result, 0, -8);
                    return $result;
                }
                $nullByteTest = "";
            }
            $nullByteTest .= $xorResult;
            $result .= $xorResult;
        }
        return $result;
    }
}
