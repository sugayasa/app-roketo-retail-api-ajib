<?php

if(!function_exists('switchMySQLErrorCode')){
    function switchMySQLErrorCode($errorCode, $httpResponse = true){
       switch($errorCode){
            case 0		:	$msgError   =   lang("Helpers.Database.ErrorCode0");
                            return $httpResponse ? throwResponseNotModified($msgError) : $msgError;
                            break;
            case 1062	:	$msgError   =   lang("Helpers.Database.ErrorCode1062");
                            return $httpResponse ? throwResponseConlflict($msgError) : $msgError;
                            break;
            case 1054	:	$msgError   =   lang("Helpers.Database.ErrorCode1054");
                            return $httpResponse ? throwResponseInternalServerError($msgError) : $msgError;
                            break;
            case 1329	:	$msgError   =   lang("Helpers.Database.ErrorCode1329");
                            return $httpResponse ? throwResponseInternalServerError($msgError) : $msgError;
                            break;
            default		:	$msgError   =   lang("Helpers.Database.ErrorCodeDefault");
                            return $httpResponse ? throwResponseInternalServerError($msgError) : $msgError;
                            break;
        }
    }
}