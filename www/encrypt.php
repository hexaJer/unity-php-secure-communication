<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * test with web browser
 *
 *  go to `http://exemple.com//encrypt.php?test=my text to encode`
 *
 *
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

function isJsonRequest(){
    $accept = strtolower(str_replace(' ', '', $_SERVER['HTTP_ACCEPT']));
    $accept = explode(',', $accept);
    return $accept[0] == "application/json";
}

function response($data, $message=null){
    $response = array(
        "data" => $data
    );
    if ($message) {
        $response["message"] = $message;
    }
    if (isJsonRequest()){
        header('Content-Type: application/json');
        echo json_encode($response);
        return;
    }
    echo $data;
}

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

function encrypt($clearText){
    $rsa = new RSA();
    $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
    $rsa->setPublicKeyFormat(RSA::PUBLIC_FORMAT_XML);
    $rsa->loadKey($_SESSION['publickey']);
    $bytesCipherText = $rsa->encrypt($clearText);
    return rawurlencode(base64_encode($bytesCipherText));
}

function decrypt($encrypted){
    $rsa = new RSA();
    $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
    $rsa->setPrivateKeyFormat(RSA::PRIVATE_FORMAT_XML);
    $rsa->loadKey($_SESSION['privatekey']);
    $bytesCipherText = base64_decode(rawurldecode($encrypted));
    $clearText = $rsa->decrypt($bytesCipherText);
    return $clearText;
}

if (isset($_POST['session_id'])) {
    session_id($_POST['session_id']);
}
session_start();

if (isset($_POST['keygen'])) {
    response(generateKeyPair());
    exit();
}

if (isset($_POST['encrypt'])) {
    response(encrypt($_POST['encrypt']));
    exit();
}

if (isset($_POST['decrypt'])) {
    $clearText = decrypt($_POST['decrypt']);
    response($clearText);
    exit();
}

if (isset($_REQUEST['test'])) {
    generateKeyPair();
    $ciphertext = encrypt($_REQUEST['test']);
    echo "<pre>\n";
    echo "encrypted: $ciphertext\n";
    $clearText = decrypt($ciphertext);
    echo "decrypted: $clearText\n";
    echo "</pre>\n";
    exit();
}
