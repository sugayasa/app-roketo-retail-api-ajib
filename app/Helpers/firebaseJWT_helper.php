<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Config\Services;

if(!function_exists('encodeJWTToken')){
    function encodeJWTToken($tokenPayload){
        $jwtSecretToken =   getenv('JWT_SECRET_TOKEN');
        $jwtEncodeString=   JWT::encode($tokenPayload, $jwtSecretToken, 'HS256');
        $encrypter      =   Services::encrypter();

        return urlsafe_b64encode($encrypter->encrypt($jwtEncodeString));
    }
}

if(!function_exists('decodeJWTToken')){
    function decodeJWTToken($token){
        $jwtSecretToken =   getenv('JWT_SECRET_TOKEN');
        $encrypter      =   Services::encrypter();
        $base64Decode   =   urlsafe_b64decode($token);
        $tokenDecrypt   =   $encrypter->decrypt($base64Decode);

        return JWT::decode($tokenDecrypt, new Key($jwtSecretToken, 'HS256'));
    }
}