<?php

require_once(__DIR__ . '/ImagesVernam.php');

$originalImageDirPath = __DIR__ . '/../original_image';
$messagePath = __DIR__ . '/../message.txt';

$message = file_get_contents($messagePath);

if ($message === false) {
	throw new Exception("Cannot read message in " . $messagePath . ".");
}

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

$originalImagePath = $originalImageDirPath . '/' . $originalImageName;

$vernam = new ImagesVernam();

try {

	$vernam->encrypt($message, $originalImagePath);
	echo('DONE');

} catch (Exception $e) {

	echo $e->getMessage();

}
