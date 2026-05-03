<?php

use Hashids\Hashids;

helper(['standardResponse']);
if(!function_exists('hashidEncode')){
    function hashidEncode($idPayload, $keyDefault = false){
        $hashKey    =   getHashKey($keyDefault);
        $hashids    =   new Hashids($hashKey);

        try {
            return $hashids->encode($idPayload);
        } catch (\Throwable $th) {
            return false;
        }
    }
}

if(!function_exists('hashidDecode')){
    function hashidDecode($idPayload, $keyDefault = false){
        $hashKey    =   getHashKey($keyDefault);
        $hashids    =   new Hashids($hashKey);

        try {
            $decodeResult   =   $hashids->decode($idPayload);
            $idReturn       =   is_array($decodeResult) ? $decodeResult[0] : $decodeResult;
            return $idReturn;
        } catch (\Throwable $th) {
            return false;
        }
    }
}

if(!function_exists('getHashKey')){
    function getHashKey($keyDefault){
        $request        =   \Config\Services::request();
        $requestParam   =   null;
        
        try {
            $requestParam   =   json_decode($request->getBody());
        } catch (\Throwable $th) {
        }

        if($keyDefault) return $_ENV['HASHID_KEY'];

        if(isset($requestParam->hardwareID) && $requestParam->hardwareID != ""){
            $hardwareID =   $requestParam->hardwareID;
        } else {
            $header     =   $request->getServer('HTTP_AUTHORIZATION');
            $token      =   $header != "" ? explode(' ', $header)[1] : "";

            if(isset($token) && $token != ""){
                try {
                    $dataDecode =   decodeJWTToken($token);
                    $hardwareID =   $dataDecode->hardwareID;
                } catch (\Throwable $th) {
                    return throwResponseUnauthorized('[E-AUTH-001.2.0] Invalid Token | HID');
                }
            } else {
                return throwResponseUnauthorized('[E-AUTH-001.2.0] Invalid Token | HID');
            }
        }

        $hashKey        =   strtoupper(substr($hardwareID, 3, 16));

        return $hashKey;
    }
}

if(!function_exists('encodeDatabaseObjectResultKey')){
    function encodeDatabaseObjectResultKey($databaseObjectResult, $keyField, $keyDefault = false){
        $keyField   =   is_array($keyField) ? $keyField : [$keyField];
        if(!is_null($databaseObjectResult) && $databaseObjectResult && count($databaseObjectResult) > 0){
            foreach($databaseObjectResult as $keyUserObjectResult){
                foreach($keyField as $field){
                    $keyUserObjectResult->$field    =   hashidEncode($keyUserObjectResult->$field, $keyDefault);
                }
			}
            return $databaseObjectResult;
        }
    }
}

if(!function_exists('encodeDataArrayKey')){
    function encodeDataArrayKey($dataArray, $keyField, $keyDefault = false){
        $keyField   =   is_array($keyField) ? $keyField : [$keyField];
        if(!is_null($dataArray) && $dataArray && count($dataArray) > 0){
            foreach($dataArray as &$keyArray){
                foreach($keyField as $field){
                    $keyArray[$field]    =   hashidEncode($keyArray[$field], $keyDefault);
                }
			}
            return $dataArray;
        }
    }
}