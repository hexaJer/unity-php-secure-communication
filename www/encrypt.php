<?php
/**
 * test with curl:
 *
 *  // Generate keypair : return XML public key
 *  curl -c cookies.txt -d "keygen=1" http://exemple.com//encrypt.php
 *
 *  // Test encrypt/decrypt : return encrypted and decrypted `my text to encode`
 *  curl -b cookies.txt -d "test=my text to encode" http://exemple.com//encrypt.php
 *
 *  // Test encrypt : return encrypted
 *  curl -b cookie.txt -d "encrypt=my text to encode" http://exemple.com//encrypt.php > encrypted.txt; cat encrypted.txt
 *
 *  // Test decrypt : return decrypted `my text to encode`
 *  curl -b cookie.txt -d "decrypt=`cat encrypted.txt`" http://exemple.com//encrypt.php
 */

include 'vendor/autoload.php';
use phpseclib\Crypt\RSA;

function generateKeyPair(){
    if (!isset($_SESSION['publickey'])){
        $rsa = new RSA();
        $rsa->setPrivateKeyFormat(RSA::PRIVATE_FORMAT_XML);
        $rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_XML);
        $keys = $rsa->createKey();
        $_SESSION['privatekey'] = $keys['privatekey'];
        $_SESSION['publickey'] = $keys['publickey'];
    }
    return $_SESSION['publickey'];
}

function encrypt($cleartext){
    $rsa = new RSA();
    $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
    $rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_XML);
    $rsa->loadKey($_SESSION['publickey']);
    $bytesCipherText = $rsa->encrypt($cleartext);
    return rawurlencode(base64_encode($bytesCipherText));
}

function decrypt($encrypted){
    $rsa = new RSA();
    $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
    $rsa->setPrivateKeyFormat(RSA::PRIVATE_FORMAT_XML);
    $rsa->loadKey($_SESSION['privatekey']);
    $bytesCipherText = base64_decode(rawurldecode($encrypted));
    return $rsa->decrypt($bytesCipherText);
}

if (isset($_POST['session_id'])) {
    session_id($_POST['session_id']);
}
session_start();

if (isset($_POST['keygen'])) {
    echo generateKeyPair();
    exit();
}

if (isset($_POST['encrypt'])) {
    echo encrypt($_POST['encrypt']);
    exit();
}

if (isset($_POST['decrypt'])) {
    echo decrypt($_POST['decrypt']);
    exit();
}

if (isset($_POST['test'])) {
    generateKeyPair();
    $ciphertext = encrypt($_POST['test']);
    echo "encrypted: $ciphertext\n\n";
    $clearText = decrypt($ciphertext);
    echo "decrypted: $clearText\n";
    exit();
}
