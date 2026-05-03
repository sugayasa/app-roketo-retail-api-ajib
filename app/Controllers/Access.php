<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\AccessModel;
use App\Models\MainOperation;
use CodeIgniter\I18n\Time;

class Access extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    use ResponseTrait;
    protected $userData, $currentDateTime, $request;
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger) {
        parent::initController($request, $response, $logger);

        try {
            $this->request          =   $request;
            $this->userData         =   $request->userData;
            $this->currentDateTime  =   $request->currentDateTime;
        } catch (\Throwable $th) {
        }
    }

    public function index()
    {
        return $this->failForbidden('[E-AUTH-000] '.lang("Controllers.Global.forbiddenAccess"));
    }

    public function check()
    {
        helper(['firebaseJWT', 'hashid']);

        $rules  =   [
            'hardwareID'        =>  ['label' => 'Hardware ID', 'rules' => 'required|alpha_numeric_punct|min_length[10]'],
            'userTimeZoneOffset'=>  ['label' => lang("Controllers.Access.validationLabel.userTimeZoneOffset"), 'rules' => 'required'],
        ];

        if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $hardwareID             =   strtoupper($this->request->getVar('hardwareID'));
        $idApplicationTypeParam =   $this->request->getVar('idApplicationType');
        $idApplicationTypeParam =   isset($idApplicationTypeParam) && $idApplicationTypeParam != "" ? hashidDecode($idApplicationTypeParam) : 0;
        $username               =   $this->request->getVar('username');
        $userTimeZoneOffset     =   $this->request->getVar('userTimeZoneOffset');
        $header                 =   $this->request->getServer('HTTP_AUTHORIZATION');
        $explodeHeader          =   $header != "" ? explode(' ', $header) : [];
        $token                  =   is_array($explodeHeader) && isset($explodeHeader[1]) && $explodeHeader[1] != "" ? $explodeHeader[1] : "";
        $timeCreate             =   Time::now(APP_TIMEZONE)->toDateTimeString();
        $statusCode             =   401;
        $responseMsg            =   lang("Controllers.Access.check.enterUsernamePassword");
        $captchaCode            =   generateRandomCharacter(4, 3);
        $idUserAdmin            =   0;

        $userDetails    =   [
            "applicationType"   =>  '',
            "username"          =>  '',
            "initialName"       =>  '',
            "name"              =>  '',
            "email"             =>  '',
            "userLevelName"     =>  '',
            "officeName"        =>  ''
        ];

        $accessModel    =   new AccessModel(); 
        $applicationList=   [];
        $menuList       =   [];
        $tokenPayload   =   [
            "idUserAdmin"       =>  0,
            "idApplicationType" =>  0,
            "idUserAdminLevel"  =>  0,
            "idGudang"          =>  0,
            "idToko"            =>  0,
            "username"          =>  "",
            "userLevelName"     =>  "",
            "name"              =>  "",
            "email"             =>  "",
            "captchaCode"       =>  $captchaCode,
            "hardwareID"        =>  $hardwareID,
            "userTimeZoneOffset"=>  $userTimeZoneOffset,
            "timeCreate"        =>  $timeCreate
        ];

        if(isset($token) && $token != ""){
            $defaultToken   =   encodeJWTToken($tokenPayload);
            try {
                $dataDecode             =   decodeJWTToken($token);
                $idUserAdmin            =   intval($dataDecode->idUserAdmin);
                $idApplicationTypeToken =   intval($dataDecode->idApplicationType);
                $hardwareIDToken        =   $dataDecode->hardwareID;
                $timeCreateToken        =   $dataDecode->timeCreate;
                $idApplicationTypeCheck =   $idApplicationTypeParam != 0 ? $idApplicationTypeParam : $idApplicationTypeToken;

                if($idUserAdmin != 0 && $idApplicationTypeCheck != 0){
                    $userAdminDataDB    =   $accessModel
                                            ->from('m_useradmin AS A')
                                            ->select("A.*, B.IDAPPLICATIONTYPE, F.APPLICATIONTYPESHORT, B.IDUSERADMINLEVEL, B.IDGUDANG, B.IDTOKO, C.LEVELNAME,
                                                    CASE
                                                        WHEN B.IDAPPLICATIONTYPE = 2 THEN CONCAT('Gudang ', IFNULL(D.NAMA, ''))
                                                        WHEN B.IDAPPLICATIONTYPE = 3 THEN CONCAT('Toko ', IFNULL(E.NAMA, ''))
                                                        ELSE 'Kantor Pusat'
                                                    END AS OFFICENAME")
                                            ->join('m_useradminapplicationlevel AS B', 'A.IDUSERADMIN = B.IDUSERADMIN AND B.IDAPPLICATIONTYPE = ' . $idApplicationTypeCheck, 'LEFT')
                                            ->join('m_useradminlevel AS C', 'B.IDUSERADMINLEVEL = C.IDUSERADMINLEVEL', 'LEFT')
                                            ->join('m_gudang AS D', 'B.IDGUDANG = D.IDGUDANG', 'LEFT')
                                            ->join('m_toko AS E', 'B.IDTOKO = E.IDTOKO', 'LEFT')
                                            ->join('a_applicationtype AS F', 'B.IDAPPLICATIONTYPE = F.IDAPPLICATIONTYPE', 'LEFT')
                                            ->where("A.IDUSERADMIN", $idUserAdmin)
                                            ->first();
                    if(!$userAdminDataDB || is_null($userAdminDataDB)) return throwResponseUnauthorized(lang("Controllers.Access.check.userNotRegisteredLogin"), ['token'=>$defaultToken, 'errorCode'=>'[E-AUTH-001.1.0]']);

                    $hardwareIDDB   =   $userAdminDataDB['HARDWAREID'];
                    $usernameDB     =   $userAdminDataDB['USERNAME'];

                    if($hardwareID == $hardwareIDDB && $hardwareID == $hardwareIDToken){
                        $timeCreateToken    =   Time::parse($timeCreateToken, APP_TIMEZONE);
                        $minutesDifference  =   $timeCreateToken->difference(Time::now(APP_TIMEZONE))->getMinutes();

                        if($minutesDifference > MAX_INACTIVE_SESSION_MINUTES) return throwResponseForbidden(lang("Controllers.Access.check.sessionEndLoginFirst"), ['token'=>$defaultToken, 'errorCode'=>'[E-AUTH-001.1.1]']);
                        $accessModel->update($idUserAdmin, ['DATETIMELOGIN' => $timeCreate]);

                        $userDetails    =   [
                            "applicationType"   =>  $userAdminDataDB['APPLICATIONTYPESHORT'],
                            "username"          =>  $userAdminDataDB['USERNAME'],
                            "initialName"       =>  getInitialsName($userAdminDataDB['NAME']),
                            "name"              =>  $userAdminDataDB['NAME'],
                            "email"             =>  $userAdminDataDB['EMAIL'],
                            "userLevelName"     =>  $userAdminDataDB['LEVELNAME'],
                            "officeName"        =>  $userAdminDataDB['OFFICENAME']
                        ];

                        if(isset($username) && $username != null){
                            if($username != $usernameDB) {
                                $accessModel->where('HARDWAREID', $hardwareID)->set('HARDWAREID', 'null', false)->update();
                                return throwResponseConlflict(lang("Controllers.Access.check.invalidUserCredentials"), ['token'=>$defaultToken, 'errorCode'=>'[E-AUTH-001.1.2]']);
                            }
                        }

                        $tokenPayload['idUserAdmin']        =   $idUserAdmin;
                        $tokenPayload['idApplicationType']  =   $userAdminDataDB['IDAPPLICATIONTYPE'];
                        $tokenPayload['idUserAdminLevel']   =   $userAdminDataDB['IDUSERADMINLEVEL'];
                        $tokenPayload['idGudang']           =   $userAdminDataDB['IDGUDANG'];
                        $tokenPayload['idToko']             =   $userAdminDataDB['IDTOKO'];
                        $tokenPayload['username']           =   $userAdminDataDB['USERNAME'];
                        $tokenPayload['userLevelName']      =   $userAdminDataDB['LEVELNAME'];
                        $tokenPayload['name']               =   $userAdminDataDB['NAME'];
                        $tokenPayload['initialName']        =   getInitialsName($userAdminDataDB['NAME']);
                        $tokenPayload['email']              =   $userAdminDataDB['EMAIL'];
                        $applicationList                    =   $this->getApplicationList($idUserAdmin);
                        $menuList                           =   $this->getMenuList($userAdminDataDB['IDUSERADMINLEVEL']);
                        $statusCode                         =   200;
                        $responseMsg                        =   lang("Controllers.Access.check.loginSuccessfullContinue");
                    } else {
                        if(!is_null($hardwareIDDB)) return throwResponseUnauthorized(lang("Controllers.Access.check.hardwareIdChangedLogin"), ['token'=>$defaultToken, 'errorCode'=>'[E-AUTH-001.1.3]']);
                        if(is_null($hardwareIDDB)) return throwResponseUnauthorized(lang("Controllers.Access.check.enterUsernamePassword"), ['token'=>$defaultToken, 'errorCode'=>'[E-AUTH-001.1.3]']);
                    }
                }
            } catch (\Throwable $th) {
                return throwResponseUnauthorized(lang("Controllers.Access.check.invalidToken"), ['token'=>$defaultToken, 'errorCode'=>'[E-AUTH-001.2.0]', 'th'=>$th->getMessage()]);
            }
        }

        $newToken       =   encodeJWTToken($tokenPayload);
        $arrResponse    =   [
            'token'             =>  $newToken,
            'userDetails'       =>  $userDetails,
            'applicationList'   =>  $applicationList,
            'menuList'          =>  $menuList,
            'baseURLFotoBarang' =>  URL_FOTO_BARANG,
            'messages'          =>  ["accessMessage" =>  $responseMsg]
        ];

        if($idUserAdmin != 0 && $idUserAdmin !== null) {
            $mainOperation              =   new MainOperation(); 
            $idTokoTokenPayload         =   intval($tokenPayload['idToko']);
            $arrIdKategoriBarang        =   $idApplicationTypeParam == 3 ? $mainOperation->getArrIdKategoriBarangToko($idTokoTokenPayload) : [];
            $arrResponse['optionHelper']=   $this->getDataOption($arrIdKategoriBarang);
            $arrResponse['arrIdKategoriBarang']=   $arrIdKategoriBarang;
        } else {
            $dataApplicationType        =   encodeDatabaseObjectResultKey($accessModel->getDataApplicationType(), 'ID');
            $arrResponse['optionHelper']=   ['dataApplicationType' => $dataApplicationType];
        }

        return $this->setResponseFormat('json')->respond($arrResponse)->setStatusCode($statusCode);
    }

    private function getApplicationList($idUserAdmin)
    {
        $accessModel        =   new AccessModel();
        $applicationList    =   $accessModel->getApplicationList($idUserAdmin);

        if(!is_null($applicationList) && count($applicationList) > 0) return encodeDatabaseObjectResultKey($applicationList, 'ID');
        return [];
    }

    private function getMenuList($idUserAdminLevel)
    {
        $accessModel        =   new AccessModel();
        $dataUserAdminMenu  =   $accessModel->getUserAdminMenu($idUserAdminLevel);

        if(!is_null($dataUserAdminMenu) && count($dataUserAdminMenu) > 0) {
            foreach($dataUserAdminMenu as $keyUserAdminMenu){
                $idMenuAdmin=   $keyUserAdminMenu->IDMENUADMIN;
                $permissions=   $accessModel->getUserAdminMenuPermissions($idUserAdminLevel, $idMenuAdmin);

                if($permissions){
                    foreach($permissions as $permission){
                        $permission->ALLOW  =   intval($permission->ALLOW) == 1 ? true : false;
                    }
                } else {
                    $permissions =   [];
                }

                $keyUserAdminMenu->PERMISSIONS  =   $permissions;
                unset($keyUserAdminMenu->IDMENUADMIN);
            }
        }

        return $dataUserAdminMenu;
    }

    public function captcha($token = '')
    {
        if(!$token || $token == "") $this->returnBlankCaptcha();
        helper(['firebaseJWT']);

        try {
            $dataDecode     =   decodeJWTToken($token);
            $captchaCode    =   $dataDecode->captchaCode;
            $codeLength     =   strlen($captchaCode);

            generateCaptchaImage($captchaCode, $codeLength);
        } catch (\Throwable $th) {
            $this->returnBlankCaptcha();
        }
    }

    public function login()
    {
        helper(['form']);
        $rules  =   [
            'idApplicationType' =>  ['label' => 'Id Application Type', 'rules' => 'required|alpha_numeric'],
            'username'          =>  ['label' => 'Username', 'rules' => 'required|min_length[5]'],
            'password'          =>  ['label' => lang("Controllers.Access.validationLabel.password"), 'rules' => 'required|min_length[5]'],
            'captcha'           =>  ['label' => lang("Controllers.Access.validationLabel.captcha"), 'rules' => 'required|alpha_numeric|exact_length[4]']
        ];

        $messages   =   [
            'idApplicationType' =>  [
                'required'      =>  'Tipe aplikasi yang dipilih tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Tipe aplikasi yang dipilih tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $accessModel        =   new AccessModel();
        $idApplicationType  =   $this->request->getVar('idApplicationType');
        $username           =   $this->request->getVar('username');
        $password           =   $this->request->getVar('password');
        $captcha            =   $this->request->getVar('captcha');
        $captchaToken       =   $this->userData->captchaCode;
        $idApplicationType  =   $idApplicationType != "" ? hashidDecode($idApplicationType) : 0;

        if($captcha != $captchaToken) return throwResponseNotAcceptable(lang("Controllers.Access.login.captchaDoesNotMatch"));
        $dataUserAdmin      =   $accessModel->select("*")->from("m_useradmin", true)->where("USERNAME", $username)->where("STATUS", 1)->first();
        if(!$dataUserAdmin) return throwResponseNotFound(lang("Controllers.Access.login.noMatchingUsername"));
        
        $idUserAdmin        =   $dataUserAdmin['IDUSERADMIN'];
        $dataLevelAdmin     =   $accessModel
                                ->select("A.IDUSERADMINLEVEL, E.APPLICATIONTYPESHORT, B.LEVELNAME, A.IDGUDANG, A.IDTOKO,
                                    CASE
                                        WHEN A.IDAPPLICATIONTYPE = 2 THEN CONCAT('Gudang ', IFNULL(C.NAMA, ''))
                                        WHEN A.IDAPPLICATIONTYPE = 3 THEN CONCAT('Toko ', IFNULL(D.NAMA, ''))
                                        ELSE 'Kantor Pusat'
                                    END AS OFFICENAME")
                                ->from('m_useradminapplicationlevel AS A', true)
                                ->join('m_useradminlevel AS B', 'A.IDUSERADMINLEVEL = B.IDUSERADMINLEVEL', 'LEFT')
                                ->join('m_gudang AS C', 'A.IDGUDANG = C.IDGUDANG', 'LEFT')
                                ->join('m_toko AS D', 'A.IDTOKO = D.IDTOKO', 'LEFT')
                                ->join('a_applicationtype AS E', 'A.IDAPPLICATIONTYPE = E.IDAPPLICATIONTYPE', 'LEFT')
                                ->where("A.IDUSERADMIN", $idUserAdmin)
                                ->where("A.IDAPPLICATIONTYPE", $idApplicationType)
                                ->first();
        if(!$dataLevelAdmin) return throwResponseNotFound("Tipe aplikasi yang dipilih tidak valid, silakan periksa kembali");

        $passwordVerify     =   password_verify($password, $dataUserAdmin['PASSWORD']);
        if(!$passwordVerify) return throwResponseNotAcceptable(lang("Controllers.Access.login.incorrectPassword"));

        $idUserAdminLevel   =   $dataLevelAdmin['IDUSERADMINLEVEL'];
        $name               =   $dataUserAdmin['NAME'];
        $email              =   $dataUserAdmin['EMAIL'];
        $currentDateTime    =   $this->currentDateTime;
        $hardwareID         =   $this->userData->hardwareID;
        
        $dataUpdateUserAdmin=   [
            'HARDWAREID'    =>   $hardwareID,
            'DATETIMELOGIN' =>   $currentDateTime    
        ];

        $accessModel    =   new AccessModel();
        $accessModel->setHardwareIdNull($hardwareID);
        $accessModel->update($idUserAdmin, $dataUpdateUserAdmin);

        $userDetails    =   [
            "applicationType"   =>  $dataLevelAdmin['APPLICATIONTYPESHORT'],
            "username"          =>  $username,
            "initialName"       =>  getInitialsName($name),
            "name"              =>  $name,
            "email"             =>  $email,
            "userLevelName"     =>  $dataLevelAdmin['LEVELNAME'],
            "officeName"        =>  $dataLevelAdmin['OFFICENAME']
        ];
        
        $tokenUpdate    =   array_merge([
            "idUserAdmin"       =>  $idUserAdmin,
            "idApplicationType" =>  $idApplicationType,
            "idUserAdminLevel"  =>  $idUserAdminLevel,
            "idGudang"          =>  $dataLevelAdmin['IDGUDANG'],
            "idToko"            =>  $dataLevelAdmin['IDTOKO']
        ], $userDetails);
        
        $menuList       =   $this->getMenuList($idUserAdminLevel);
        return $this->setResponseFormat('json')
                    ->respond([
                        'tokenUpdate'   =>  $tokenUpdate,
                        'userDetails'   =>  $userDetails,
                        'menuList'      =>  $menuList,
                        'message'       =>  lang("Controllers.Access.login.loginSuccessfullContinue")
                    ]);		
    }

    public function logout($token = false)
    {
        if(!$token || $token == "") return throwResponseUnauthorized(lang("Controllers.Access.logout.invalidToken"), ['errorCode'=>'[E-AUTH-001.1]']);
        helper(['firebaseJWT']);

        try {
            $dataDecode         =   decodeJWTToken($token);
            $idUserAdmin        =   $dataDecode->idUserAdmin;
            $hardwareID         =   $dataDecode->hardwareID;
            $accessModel        =   new AccessModel();
            $userAdminDataDB    =   $accessModel->where("IDUSERADMIN", $idUserAdmin)->first();

            if(!$userAdminDataDB || is_null($userAdminDataDB)) return throwResponseOK(lang("Controllers.Access.logout.logoutSuccessfull"));

            $hardwareIDDB       =   $userAdminDataDB['HARDWAREID'];

            if($hardwareID == $hardwareIDDB) $accessModel->where('HARDWAREID', $hardwareID)->set('HARDWAREID', 'null', false)->update();
            return throwResponseOK(lang("Controllers.Access.logout.logoutSuccessfull"));
        } catch (\Throwable $th) {
            return throwResponseUnauthorized(lang("Controllers.Access.logout.invalidToken"), ['errorCode'=>'[E-AUTH-001.2]', 'errorMessage'=>$th->getMessage()]);
        }
    }

    private function returnBlankCaptcha()
    {
        $img    =   imagecreatetruecolor(120, 20);
        $bg     =   imagecolorallocate ( $img, 255, 255, 255 );
        imagefilledrectangle($img, 0, 0, 120, 20, $bg);
        
        ob_start();
        imagejpeg($img, "blank.jpg", 100);
        $contents = ob_get_contents();
        ob_end_clean();

        $dataUri = "data:image/jpeg;base64," . base64_encode($contents);
        echo $dataUri;
    }

    private function getDataOption($arrIdKategoriBarang = [])
    {
        $accessModel            =   new AccessModel();
        $dataApplicationType    =   encodeDatabaseObjectResultKey($accessModel->getDataApplicationType(), 'ID');
        $dataMetodeBayar        =   encodeDatabaseObjectResultKey($accessModel->getDataMetodeBayar(), 'ID');
        $dataCaraPelunasan      =   encodeDatabaseObjectResultKey($accessModel->getDataCaraPelunasan(), 'ID');
        $dataUserAdminLevel     =   encodeDatabaseObjectResultKey($accessModel->getDataUserAdminLevel(), ['IDAPPLICATIONTYPE', 'ID']);
        $dataUserAdminGudang    =   encodeDatabaseObjectResultKey($accessModel->getDataUserAdminGudang(), ['ID']);
        $dataUserAdminToko      =   encodeDatabaseObjectResultKey($accessModel->getDataUserAdminToko(), ['ID']);
        $dataBarangSatuan       =   encodeDatabaseObjectResultKey($accessModel->getDataBarangSatuan(), ['ID']);
        $dataBarangMerk         =   encodeDatabaseObjectResultKey($accessModel->getDataBarangMerk(), ['ID']);
        $dataBarangKategori     =   encodeDatabaseObjectResultKey($accessModel->getDataBarangKategori($arrIdKategoriBarang), ['ID']);
        $dataBarangAtribut      =   encodeDatabaseObjectResultKey($accessModel->getDataBarangAtribut(), ['ID']);
        $dataBarangSKU          =   encodeDatabaseObjectResultKey($accessModel->getDataBarangSKU($arrIdKategoriBarang), ['ID']);
        $dataProdusenDistributor=   encodeDatabaseObjectResultKey($accessModel->getDataProdusenDistributor(), ['ID']);
        $dataGudang             =   encodeDatabaseObjectResultKey($accessModel->getDataGudang(), ['ID']);
        $dataToko               =   encodeDatabaseObjectResultKey($accessModel->getDataToko(), ['ID']);
        $dataKelompokHargaGrosir=   encodeDatabaseObjectResultKey($accessModel->getDataKelompokHargaGrosir(), ['ID']);

        return [
            "dataApplicationType"       =>  $dataApplicationType,
            "dataMetodeBayar"           =>  $dataMetodeBayar,
            "dataCaraPelunasan"         =>  $dataCaraPelunasan,
            "dataUserAdminLevel"        =>  $dataUserAdminLevel == null ? [] : $dataUserAdminLevel,
            "dataUserAdminGudang"       =>  $dataUserAdminGudang == null ? [] : $dataUserAdminGudang,
            "dataUserAdminToko"         =>  $dataUserAdminToko == null ? [] : $dataUserAdminToko,
            "dataBarangSatuan"          =>  $dataBarangSatuan == null ? [] : $dataBarangSatuan,
            "dataBarangMerk"            =>  $dataBarangMerk == null ? [] : $dataBarangMerk,
            "dataBarangKategori"        =>  $dataBarangKategori == null ? [] : $dataBarangKategori,
            "dataBarangAtribut"         =>  $dataBarangAtribut == null ? [] : $dataBarangAtribut,
            "dataBarangSKU"             =>  $dataBarangSKU == null ? [] : $dataBarangSKU,
            "dataProdusenDistributor"   =>  $dataProdusenDistributor == null ? [] : $dataProdusenDistributor,
            "dataGudang"                =>  $dataGudang == null ? [] : $dataGudang,
            "dataToko"                  =>  $dataToko == null ? [] : $dataToko,
            "dataKelompokHargaGrosir"   =>  $dataKelompokHargaGrosir == null ? [] : $dataKelompokHargaGrosir,
            "optionProdusenDistributor" =>  OPTION_PRODUSEN_DISTRIBUTOR,
            "optionHours"	            =>  OPTION_HOURS,
            "optionMinutes"             =>  OPTION_MINUTES,
            "optionMinuteInterval"	    =>  OPTION_MINUTEINTERVAL,
            "optionMonth"	            =>  OPTION_MONTH,
            "optionYear"	            =>  OPTION_YEAR
        ];
    }

    public function getDataOptionByKey($keyName, $optionName = false, $keyword = false)
    {
        $accessModel    =   new AccessModel();
        $optionName     =   $optionName != false ? $optionName : 'randomOption';
        $dataOption     =   [];
        $arrEncodeKey   =   ['ID'];

        switch($keyName){
            default :
                break;
        }

        $dataOption     =   encodeDatabaseObjectResultKey($dataOption, $arrEncodeKey);
        return $this->setResponseFormat('json')
                ->respond([
                    "dataOption"    =>  $dataOption,
                    "optionName"    =>  $optionName
                ]);
    }

    public function detailProfile()
    {
        $accessModel        =   new AccessModel();
        $idUserAdmin        =   $this->userData->idUserAdmin;
        $idApplicationType  =   $this->userData->idApplicationType;
        $detailUserAdmin    =   $accessModel->getUserAdminDetail($idUserAdmin, $idApplicationType);

        if(is_null($detailUserAdmin)) return throwResponseNotFound(lang("Controllers.Access.detailProfile.userDetailsNotFound"));
        unset($detailUserAdmin['IDUSERADMINLEVEL']);
        return $this->setResponseFormat('json')
                    ->respond([
                        "detailUserAdmin"   =>  $detailUserAdmin
                     ]);
    }

    public function saveDetailProfile()
    {
        helper(['form']);
        $idUserAdmin    =   $this->userData->idUserAdmin;
        $rules          =   [
            'username'  =>  ['label' => 'Username', 'rules' => 'required|alpha_numeric|min_length[4]|is_unique[m_useradmin.USERNAME, IDUSERADMIN, '.$idUserAdmin.']'],
            'name'      =>  ['label' => lang("Controllers.Access.validationLabel.name"), 'rules' => 'required|alpha_numeric_space|min_length[4]'],
            'email'     =>  ['label' => lang("Controllers.Access.validationLabel.email"), 'rules' => 'required|valid_email']
        ];

        if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $accessModel        =   new AccessModel();
        $username           =   $this->request->getVar('username');
        $name               =   $this->request->getVar('name');
        $email              =   $this->request->getVar('email');
        $currentPassword    =   $this->request->getVar('currentPassword');
        $newPassword        =   $this->request->getVar('newPassword');
        $repeatPassword     =   $this->request->getVar('repeatPassword');
        $relogin            =   false;

        $arrUpdateUserAdmin =   [
            'NAME'      =>  $name,
            'USERNAME'  =>  $username,
            'EMAIL'     =>  $email
        ];

        if($currentPassword != "" || $newPassword != "" || $repeatPassword != ""){
			if($currentPassword == "") return throwResponseNotAcceptable(lang("Controllers.Access.saveDetailProfile.pleaseEnterOldPassword"));
			if($newPassword == "") return throwResponseNotAcceptable(lang("Controllers.Access.saveDetailProfile.pleaseEnterNewPassword"));
            if($repeatPassword == "") return throwResponseNotAcceptable(lang("Controllers.Access.saveDetailProfile.pleaseEnterNewPasswordRepetition"));
			if($newPassword != $repeatPassword) return throwResponseNotAcceptable(lang("Controllers.Access.saveDetailProfile.passwordRepetitionNotMatch"));
			
            $dataUserAdmin  =   $accessModel->where("IDUSERADMIN", $idUserAdmin)->first();
            if(!$dataUserAdmin) return $this->failNotFound(lang("Controllers.Access.saveDetailProfile.userDataNotFoundTryAgain"));
            $passwordVerify =   password_verify($currentPassword, $dataUserAdmin['PASSWORD']);
            if(!$passwordVerify) return $this->fail(lang("Controllers.Access.saveDetailProfile.oldPasswordIncorrect"));
			
			$arrUpdateUserAdmin['PASSWORD'] =	password_hash($newPassword, PASSWORD_DEFAULT);
            $relogin                        =   true;
		}

        $accessModel->update($idUserAdmin, $arrUpdateUserAdmin);
        $tokenUpdate    =   [
            "username"  =>  $username,
            "name"      =>  $name,
            "email"     =>  $email
        ];

        return $this->setResponseFormat('json')->respond([
            "message"       =>  lang("Controllers.Access.saveDetailProfile.userDataUpdated"),
            "relogin"       =>  $relogin,
            "tokenUpdate"   =>  $tokenUpdate
        ]);
    }
}