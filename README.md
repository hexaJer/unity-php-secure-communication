# Unity php secure communication
Example secure RSA communication between Unity and php using phpseclib

## How to
* Use composer for install phpseclib
```
cd www
php composer.phar install
```

* Upload `www` content on your webserver
* Open `MainScene` in Unity Editor
* Select `RSAEncryptionPanel` and change `Script Url` field
* Play
* Click on **Generate KeyPair**
* Type some text in `ClearTextInputField`
* Click on **Encrypt** button
* Click on **Decrypt** button
* You must receive the same text as you typed
