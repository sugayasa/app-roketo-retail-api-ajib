<?php
namespace App\Libraries;

class OneMsgIO
{
    private function getJsonDataConfig()
    {
        return [
            "token"     =>  ONEMSGIO_TOKEN
        ];
    }

    public function sendMessageTemplate($templateName, $templateLanguageCode, $phoneNumber, $params)
    {
        $jsonDataTemplate   =   [
            "namespace" =>  ONEMSGIO_NAMESPACE,
            "template"  =>  $templateName,
            "language"  =>  [
                "policy"    =>  "deterministic",
                "code"      =>  $templateLanguageCode
            ],
            "params"    =>  $params,
            "phone"     =>  $phoneNumber
        ];

        return $this->execOneMsgAPI($jsonDataTemplate, 'sendTemplate');
    }

    public function sendMessage($phoneNumber, $message)
    {
        $jsonDataTemplate   =   [
            "phone" =>  $phoneNumber,
            "body"  =>  $message
        ];

        return $this->execOneMsgAPI($jsonDataTemplate, 'sendMessage');
    }
    
    private function execOneMsgAPI($jsonDataParam, $endPoint = 'sendMessage')
    {
        $logger     =   \Config\Services::logger();
        $ch         =   curl_init();
        $jsonData   =   array_merge($this->getJsonDataConfig(), $jsonDataParam);
        curl_setopt($ch, CURLOPT_URL, ONEMSGIO_CHANNEL_URL.$endPoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        try {
            $response   =   curl_exec($ch);
            $response   =   json_decode($response, true);
            $isSent     =   $response['sent'];

            curl_close($ch);
            if(!$isSent) {
                $errorMsg  =   $response['error'];
                $errorCode =   'E0000';

                switch(true){
                    case stripos($errorMsg, 'Recipient is not a valid WhatsApp user') !== false:
                        $errorCode = 'E0001';
                        break;
                    //invalid namespace
                    case stripos($errorMsg, 'The namespace provided') !== false:
                        $errorCode = 'E0002';
                        break;
                    case stripos($errorMsg, 'header: Template does not contain title component, no parameters allowed') !== false:
                        $errorCode = 'E1001';
                        break;
                    //unmatched body parameter
                    case stripos($errorMsg, 'body: number of localizable_params') !== false:
                        $errorCode = 'E1002';
                        break;
                    //unmatched body parameter
                    case stripos($errorMsg, 'cannot have new-line/tab characters or more than 4 consecutive spaces') !== false:
                        $errorCode = 'E1012';
                        break;
                    case stripos($errorMsg, 'buttons: Template does not contain button components, no parameters allowed') !== false:
                        $errorCode = 'E1003';
                        break;
                    case stripos($errorMsg, 'unexpected technical error, please, contact support') !== false:
                        $errorCode = 'E9999';
                        break;
                    default:
                        $errorCode = 'E0000';
                        break;
                }

                return ['isSent' => false, 'errorCode' => $errorCode, 'errorMsg' => $errorMsg];
            }

            return ['isSent' => true, 'idMessage' => $response['id']];
        } catch (\Throwable $th) {
            $logger->info('Error:' . curl_error($ch));
            $logger->info('Error:' . json_encode($th));
            return ['isSent' => false, 'errorCode' => 'E0000', 'errorMsg' => 'CURL'.curl_error($ch)];
        }
        curl_close($ch);
    }

    public function getListOfTemplates()
    {
        $ch         =   curl_init();
        $jsonData   =   $this->getJsonDataConfig();
        curl_setopt($ch, CURLOPT_URL, ONEMSGIO_CHANNEL_URL."templates");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonData));

        try {
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                return [];
            } else {
                return json_decode($response, true);
            }
        } catch (\Throwable $th) {
            return [];
        }
        curl_close($ch);
    }

    public function generateMessageFromTemplateAndParam($templateName, $templateList, $templateParam)
    {
        if(isset($templateList) && is_array($templateList) && count($templateList) > 0){
            foreach($templateList['templates'] as $templateObject){
                $templateListName   =    $templateObject['name'];

                if($templateListName == $templateName){
                    $arrMessageComponent        =   [
                        "header"    =>  "",
                        "body"      =>  "",
                        "footer"    =>  ""
                    ];
                    $templateComponents =   $templateObject['components'];

                    if(isset($templateComponents) && is_array($templateComponents) && count($templateComponents) > 0){
                        foreach($templateComponents as $component){
                            $componentType  =   $component['type'];
                            $componentText  =   isset($component['text']) && $component['text'] != '' ? $component['text'] : '';

                            if($componentType == 'HEADER') $arrMessageComponent['header']   =   $this->composeComponentTextWithParam('header', $templateParam, $componentText);
                            if($componentType == 'BODY') $arrMessageComponent['body']       =   $this->composeComponentTextWithParam('body', $templateParam, $componentText);
                            if($componentType == 'FOOTER') $arrMessageComponent['footer']   =   $this->composeComponentTextWithParam('footer', $templateParam, $componentText);
                        }
                    }

                    return $arrMessageComponent;
                }
            }
        }

        return false;
    }

    private function composeComponentTextWithParam($type, $templateParam, $componentText)
    {
        $arrParameters      =   $this->getTemplateParameters($templateParam, $type);

        if(isset($arrParameters) && is_array($arrParameters) && count($arrParameters) > 0){
            foreach($arrParameters as $index => $parameter){
                $placeholder    =   "{{".($index + 1)."}}";
                $componentText  =   str_replace($placeholder, $parameter, $componentText);
            }
        }

        return $componentText;
    }

    private function getTemplateParameters($templateParam, $type){
        $arrParameters  =   [];
        if(isset($templateParam) && is_array($templateParam) && count($templateParam) > 0){
            foreach($templateParam as $templateParamArray){
                $templateParamType  =   $templateParamArray['type'];

                if($templateParamType == $type){
                    $templateParameters =   $templateParamArray['parameters'];

                    if(isset($templateParameters) && is_array($templateParameters) && count($templateParameters) > 0){
                        foreach($templateParameters as $parameters){
                            $arrParameters[]    =   $parameters['text'];
                        }
                    }
                }
            }
        }

        return $arrParameters;
    }

    public function generateParametersTemplate($templateParameters){
        $parameters =   [];
        foreach($templateParameters as $keyParameters){
            $parameters[]   =   [
                "type"  =>  "text",
                "text"  =>  $keyParameters
            ];
        }
        return $parameters;
    }
}