<?php

require_once(__DIR__ . '/ImagesVernam.php');

$imagesToDecryptDirPath = __DIR__ . '/../images_to_decrypt';

$keyImagePath = $imagesToDecryptDirPath . '/' . 'key.png';
$encryptedMessageImagePath = $imagesToDecryptDirPath . '/' . 'encryptedMessage.png';

$vernam = new ImagesVernam();

try {

    $message = $vernam->decrypt($keyImagePath, $encryptedMessageImagePath);
    echo($message);

} catch (Exception $e) {

    echo $e->getMessage();

}


