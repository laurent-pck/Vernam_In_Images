<?php

require_once(__DIR__ . '/ImagesVernam.php');

$vernam = new ImagesVernam();

$vernam->encrypt("Hello World!");

echo("DONE");