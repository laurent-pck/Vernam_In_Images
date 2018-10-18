<?php

class ImagesVernam
{
	public function encrypt(string $message)
	{
		$originalImageDirPath = __DIR__ . 'original_image';

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

		$originalImage = imagecreatefromjpeg($originalImageDirPath . $originalImageName);

		

	}
}
