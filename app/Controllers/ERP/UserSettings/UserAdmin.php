<?php

namespace App\Controllers\ERP\UserSettings;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\MainOperation;
use App\Models\ERP\UserSettings\UserAdminModel;

class UserAdmin extends ResourceController
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
            $this->userData         =   $request->userData;
            $this->currentDateTime  =   $request->currentDateTime;
        } catch (\Throwable $th) {
        }
    }

    public function index()
    {
        return $this->failForbidden('[E-AUTH-000] Forbidden Access');
    }

    public function getList()
    {
        $userAdminModel     =   new UserAdminModel();
        $searchKeyword      =   $this->request->getVar('searchKeyword');
        $listUserAdmin      =	$userAdminModel->getListUserAdmin($searchKeyword);

        if($listUserAdmin){
            foreach($listUserAdmin as $keyUserAdmin){
                $idUserAdmin            =   $keyUserAdmin->IDUSERADMIN;
                $dataApplicationLevel   =   $userAdminModel->getUserAdminApplicationLevel($idUserAdmin);
                if($dataApplicationLevel){
                    if(count($dataApplicationLevel) > 0){
                        foreach($dataApplicationLevel as $keyDataApplicationLevel){
                            $keyDataApplicationLevel->IDAPPLICATIONTYPE =   hashidEncode($keyDataApplicationLevel->IDAPPLICATIONTYPE);
                            $keyDataApplicationLevel->IDUSERADMINLEVEL  =   $keyDataApplicationLevel->IDUSERADMINLEVEL == 'NULL' ? 'NULL' : hashidEncode($keyDataApplicationLevel->IDUSERADMINLEVEL);
                            $keyDataApplicationLevel->IDGUDANG          =   $keyDataApplicationLevel->IDGUDANG == 0 ? '' : hashidEncode($keyDataApplicationLevel->IDGUDANG);
                            $keyDataApplicationLevel->IDTOKO            =   $keyDataApplicationLevel->IDTOKO == 0 ? '' : hashidEncode($keyDataApplicationLevel->IDTOKO);
                        }
                    }
                    $keyUserAdmin->USERADMINAPPLICATIONLEVEL=   $dataApplicationLevel;
                } else {
                    $keyUserAdmin->USERADMINAPPLICATIONLEVEL=   [];
                }
            }

            $listUserAdmin  =   encodeDatabaseObjectResultKey($listUserAdmin, ['IDUSERADMIN']);
            return $this->setResponseFormat('json')
                        ->respond([
                            "listUserAdmin" =>  $listUserAdmin
                        ]);
        } else {
            return throwResponseNotFound('No data found based on the applied filter');
        }
    }

    public function saveData()
    {
        helper(['form']);
        $idUserAdmin    =   $this->request->getVar('idUserAdmin');
        $idUserAdmin    =   $idUserAdmin != "" ? hashidDecode($idUserAdmin) : 0;

        return $idUserAdmin == 0 ? $this->insertDataUserAdmin() : $this->updateDataUserAdmin($idUserAdmin);
    }

    private function insertDataUserAdmin()
    {
        $rules  =   [
            'name'                                      =>  ['label' => 'Nama', 'rules' => 'required|alpha_numeric_space'],
            'email'                                     =>  ['label' => 'Email', 'rules' => 'required|valid_email|is_unique[m_useradmin.EMAIL]'],
            'username'                                  =>  ['label' => 'Username', 'rules' => 'required|alpha_numeric|min_length[5]|is_unique[m_useradmin.USERNAME]'],
            'status'                                    =>  ['label' => 'Status', 'rules' => 'required|in_list[0,1]'],
            'newPassword'                               =>  ['label' => 'Password Baru', 'rules' => 'required|alpha_numeric|min_length[6]'],
            'repeatPassword'                            =>  ['label' => 'Pengulangan Password', 'rules' => 'required|alpha_numeric|min_length[6]'],
            'listApplicationLevel'                      =>  ['label' => 'Level Aplikasi', 'rules' => 'required|is_array'],
            'listApplicationLevel.*.idApplicationType'  =>  ['label' => 'Tipe Aplikasi', 'rules' => 'required|alpha_numeric'],
            'listApplicationLevel.*.idUserAdminLevel'   =>  ['label' => 'Level Admin Pengguna', 'rules' => 'permit_empty|alpha_numeric'],
            'listApplicationLevel.*.isAllowApplication' =>  ['label' => 'Izin Aplikasi', 'rules' => 'required|in_list[0,1]']
        ];

        $messages   =   [
            'email'     =>  ['is_unique'=> 'Alamat email ini sudah terdaftar, silakan masukkan alamat email lain'],
            'username'  =>  ['is_unique'=> 'Username ini sudah digunakan, silakan pilih username lain'],
            'status'    =>  ['in_list'  => 'Status yang dipilih tidak valid, silakan pilih status yang sesuai'],
            'listApplicationLevel'  =>  [
                'required' => 'Harap pilih minimal satu aplikasi yang diizinkan untuk user admin ini',
                'is_array' => 'Data kiriman tidak lengkap, silakan periksa kembali'
            ],
            'listApplicationLevel.*.idApplicationType'  =>  [
                'required'      =>  'Data tipe aplikasi tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data tipe aplikasi tidak valid, silakan periksa kembali'
            ],
            'listApplicationLevel.*.idUserAdminLevel'  =>  [
                'required'      =>  'Data level admin pengguna tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data level admin pengguna tidak valid, silakan periksa kembali'
            ],
            'listApplicationLevel.*.isAllowApplication'  =>  [
                'in_list'       =>  'Data izin aplikasi pengguna tidak valid, silakan periksa kembali'
            ]
        ];
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $name                   =   $this->request->getVar('name');
        $email                  =   $this->request->getVar('email');
        $username               =   $this->request->getVar('username');
        $status                 =   $this->request->getVar('status');
        $newPassword            =   $this->request->getVar('newPassword');
        $repeatPassword         =   $this->request->getVar('repeatPassword');
        $listApplicationLevel   =   $this->request->getVar('listApplicationLevel');

        if($newPassword != $repeatPassword) return throwResponseNotAcceptable("Pengulangan password yang Anda masukkan tidak cocok");

        $arrInsertData      =   [
            'NAME'      =>  $name,
            'EMAIL'     =>  $email,
            'USERNAME'  =>  $username,
            'PASSWORD'  =>  password_hash($newPassword, PASSWORD_DEFAULT),
            'STATUS'    =>  $status
        ];

        $mainOperation  =   new MainOperation();
        $procInsertData =   $mainOperation->insertDataTable('m_useradmin', $arrInsertData);

        if(!$procInsertData['status']) return switchMySQLErrorCode($procInsertData['errCode']);

        $idUserAdmin    =   $procInsertData['insertID'];
        if(count($listApplicationLevel) > 0){
            foreach($listApplicationLevel as $keyApplicationLevel){
                $idApplicationType          =   hashidDecode($keyApplicationLevel->idApplicationType);
                $idUserAdminLevel           =   hashidDecode($keyApplicationLevel->idUserAdminLevel);
                $idGudang                   =   $keyApplicationLevel->idGudang == "" ? 0 : hashidDecode($keyApplicationLevel->idGudang);
                $idToko                     =   $keyApplicationLevel->idToko == "" ? 0 : hashidDecode($keyApplicationLevel->idToko);
                $isAllowApplication         =   $keyApplicationLevel->isAllowApplication;
                $arrInsertApplicationLevel  =   [
                    'IDAPPLICATIONTYPE' =>  $idApplicationType,
                    'IDUSERADMIN'       =>  $idUserAdmin,
                    'IDUSERADMINLEVEL'  =>  $idUserAdminLevel,
                    'IDGUDANG'          =>  $idGudang,
                    'IDTOKO'            =>  $idToko
                ];
                if(intval($isAllowApplication) == 1) $mainOperation->insertDataTable('m_useradminapplicationlevel', $arrInsertApplicationLevel);
            }
        }

        return throwResponseOK(
            'Data user admin baru telah ditambahkan',
            ['idUserAdmin'  =>  hashidEncode($idUserAdmin)]
        );
    }

    private function updateDataUserAdmin($idUserAdmin)
    {
        helper(['form']);
        $userAdminModel     =   new UserAdminModel();

        $rules      =   [
            'idUserAdmin'                               =>  ['label' => 'ID User Admin', 'rules' => 'required|alpha_numeric'],
            'name'                                      =>  ['label' => 'Nama', 'rules' => 'required|alpha_numeric_space'],
            'email'                                     =>  ['label' => 'Email', 'rules' => 'required|valid_email|is_unique[m_useradmin.EMAIL, IDUSERADMIN, '.$idUserAdmin.']'],
            'username'                                  =>  ['label' => 'Username', 'rules' => 'required|alpha_numeric|min_length[5]|is_unique[m_useradmin.USERNAME, IDUSERADMIN, '.$idUserAdmin.']'],
            'status'                                    =>  ['label' => 'Status', 'rules' => 'required|in_list[0,1]'],
            'listApplicationLevel'                      =>  ['label' => 'Level Aplikasi Pengguna', 'rules' => 'required|is_array'],
            'listApplicationLevel.*.idApplicationType'  =>  ['label' => 'Tipe Aplikasi', 'rules' => 'required|alpha_numeric'],
            'listApplicationLevel.*.idUserAdminLevel'   =>  ['label' => 'Level Admin Pengguna', 'rules' => 'permit_empty|alpha_numeric'],
            'listApplicationLevel.*.isAllowApplication' =>  ['label' => 'Izin Aplikasi', 'rules' => 'required|in_list[0,1]']
        ];

        $messages   =   [
            'idUserAdmin'   => [
                'required'      => 'Invalid data sent',
                'alpha_numeric' => 'Invalid data sent'
            ],
            'email'     =>  ['is_unique' => 'Alamat email ini sudah terdaftar, silakan masukkan alamat email lain'],
            'username'  =>  ['is_unique' => 'Username ini sudah digunakan, silakan pilih username lain'],
            'status'    =>  ['in_list'  => 'Status yang dipilih tidak valid, silakan pilih status yang sesuai'],
            'listApplicationLevel'  =>  [
                'required' => 'Harap pilih minimal satu aplikasi yang diizinkan untuk user admin ini',
                'is_array' => 'Data kiriman tidak lengkap, silakan periksa kembali'
            ],
            'listApplicationLevel.*.idApplicationType'  =>  [
                'required'      =>  'Data tipe aplikasi tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data tipe aplikasi tidak valid, silakan periksa kembali'
            ],
            'listApplicationLevel.*.idUserAdminLevel'  =>  [
                'required'      =>  'Data level admin pengguna tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data level admin pengguna tidak valid, silakan periksa kembali'
            ],
            'listApplicationLevel.*.isAllowApplication'  =>  [
                'in_list'       =>  'Data izin aplikasi pengguna tidak valid, silakan periksa kembali'
            ]
        ];
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $name                   =   $this->request->getVar('name');
        $email                  =   $this->request->getVar('email');
        $username               =   $this->request->getVar('username');
        $status                 =   $this->request->getVar('status');
        $newPassword            =   $this->request->getVar('newPassword');
        $repeatPassword         =   $this->request->getVar('repeatPassword');
        $listApplicationLevel   =   $this->request->getVar('listApplicationLevel');

        $arrUpdateUserAdmin =   [
            'NAME'      =>  $name,
            'EMAIL'     =>  $email,
            'USERNAME'  =>  $username,
            'STATUS'    =>  $status
        ];

        if($newPassword != "" || $repeatPassword != ""){
			if($newPassword == "") return throwResponseNotAcceptable("Harap masukkan password baru");
            if($repeatPassword == "") return throwResponseNotAcceptable("Harap masukkan pengulangan password baru");
            if($newPassword != $repeatPassword) return throwResponseNotAcceptable("Pengulangan password yang Anda masukkan tidak cocok");
			
			$arrUpdateUserAdmin['PASSWORD'] =   password_hash($newPassword, PASSWORD_DEFAULT);
        }
        
        $mainOperation          =   new MainOperation();
        $procUpdateData         =   $mainOperation->updateDataTable('m_useradmin', $arrUpdateUserAdmin, ['IDUSERADMIN' => $idUserAdmin]);
        $totalUpdateAppTypeLevel=   0;

        if(count($listApplicationLevel) > 0){
            $arrIdApplicationType   =   $this->getAddIdApplicationType($listApplicationLevel);
            $dataApplicationLevelDB =   $userAdminModel->getUserAdminApplicationLevel($idUserAdmin);

            foreach($dataApplicationLevelDB as $keyApplicationLevelDB){
                $idApplicationTypeDB    =   $keyApplicationLevelDB->IDAPPLICATIONTYPE;
                if(!in_array($idApplicationTypeDB, $arrIdApplicationType)) $mainOperation->deleteDataTable('m_useradminapplicationlevel', ['IDAPPLICATIONTYPE' => $idApplicationTypeDB, 'IDUSERADMIN' => $idUserAdmin]);
                $totalUpdateAppTypeLevel++;
            }

            foreach($listApplicationLevel as $keyApplicationLevel){
                $idApplicationType          =   hashidDecode($keyApplicationLevel->idApplicationType);
                $idUserAdminLevel           =   hashidDecode($keyApplicationLevel->idUserAdminLevel);
                $isAllowApplication         =   $keyApplicationLevel->isAllowApplication;
                $isApplicationLevelExist    =   $userAdminModel->isApplicationLevelExist($idUserAdmin, $idApplicationType);
                $idGudang                   =   $keyApplicationLevel->idGudang == "" ? 0 : hashidDecode($keyApplicationLevel->idGudang);
                $idToko                     =   $keyApplicationLevel->idToko == "" ? 0 : hashidDecode($keyApplicationLevel->idToko);
                $arrInsUpdApplicationLevel  =   [
                    'IDAPPLICATIONTYPE'  =>  $idApplicationType,
                    'IDUSERADMIN'        =>  $idUserAdmin,
                    'IDUSERADMINLEVEL'   =>  $idUserAdminLevel,
                    'IDGUDANG'           =>  $idGudang,
                    'IDTOKO'             =>  $idToko
                ];
            
                if(!$isApplicationLevelExist && $isAllowApplication == "1") {
                    $procInsertData =   $mainOperation->insertDataTable('m_useradminapplicationlevel', $arrInsUpdApplicationLevel);
                    if($procInsertData['status']) $totalUpdateAppTypeLevel++;
                } else if($isApplicationLevelExist && $isAllowApplication == "1") {
                    $procUpdateData =   $mainOperation->updateDataTable('m_useradminapplicationlevel', $arrInsUpdApplicationLevel, ['IDUSERADMINAPPLICATIONLEVEL' => $isApplicationLevelExist['IDUSERADMINAPPLICATIONLEVEL']]);
                    if($procUpdateData['status']) $totalUpdateAppTypeLevel++;
                } else {
                    $procDeleteData =   $mainOperation->deleteDataTable('m_useradminapplicationlevel', ['IDAPPLICATIONTYPE' => $idApplicationType, 'IDUSERADMIN' => $idUserAdmin]);
                    if($procDeleteData['status']) $totalUpdateAppTypeLevel++;
                }
            }
        }

        if(!$procUpdateData['status'] && $totalUpdateAppTypeLevel <= 0) return switchMySQLErrorCode($procUpdateData['errCode']);
        return throwResponseOK(
            'Data user admin telah diperbarui',
            ['idUserAdmin'  =>  hashidEncode($idUserAdmin)]
        );
    }

    private function getAddIdApplicationType($listApplicationLevel)
    {
        $arrIdApplicationType = [];
        if(count($listApplicationLevel) > 0){
            foreach($listApplicationLevel as $keyApplicationLevel){
                $idApplicationType  =   hashidDecode($keyApplicationLevel->idApplicationType);
                if(!in_array($idApplicationType, $arrIdApplicationType)) $arrIdApplicationType[] = $idApplicationType;
            }
        }
        return $arrIdApplicationType;
    }
}