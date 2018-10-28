# Vernam in images

This is an implementation of the Vernam cipher in images.

Encryption:
The given secret message is transformed in a string of bytes and a random key with the same length is generated. The message is then encrypted with this operation: message XOR key = encryptedMessage. The key and the encryptedMessage are then writen on the chosen original image to generate key.png and encryptedMessage.png.

Decryption:
The binary encryptedMessage and the key are extracted from their respective image (encryptedMessage.png and key.png). To retrieve the original message this operation is performed: encryptedMessage XOR key = message. The message is then converted back into ascii characters and displayed. 

## Getting started

First, clone the repository in yourDirectory:

```
cd yourDirectory
git clone https://github.com/laurent-pck/Vernam_In_Images.git
```

### Encrypt

Go in the Vernam_In_Images directory

```
cd Vernam_In_Images
```

Create an original_image directory

```
mkdir original_image
```

Copy your original image, let's say a nice koala, in the original_image directory. This image must be a png image.

```
cp /home/user/Images/koala.png original_image/koala.png
```

Write the message you want to encrypt in message.txt. The message can contain printable ascii characters only (char code 32 to 126 inclusive). By default, message.txt contains a list of all these characters. Moreover, eight times the number of characters in the message must be smaller than the total number of pixels in the original image.

```
echo -n "This is my secret message" > message.txt
```

Launch the encrypt command

```
php src/encrypt.php
```

Your key.png and encryptedMessage.png images were generated in the encrypted_images directory, you should see them by executing

```
ls encrypted_images
```

### Decrypt

Go in the Vernam_In_Images directory

```
cd Vernam_In_Images
```

Create an images_to_decrypt directory

```
mkdir images_to_decrypt
```

Put your key image and your encryptedMessage image in this directory

```
mv /home/user/Images/key.png images_to_decrypt/key.png
mv /home/user/Images/encryptedMessage.png images_to_decrypt/encryptedMessage.png
```

Launch the decrypt command

```
php src/decrypt.php
```

The original message is echoed in the terminal.

### Prerequisites

PHP >= 5.5.0 has to be installed on your machine with cli enabled for it. You will also need the GD lib wih it, it may already be in your PHP version.
