<?php

namespace App\Controllers\ERP\UserSettings;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\MainOperation;
use App\Models\ERP\UserSettings\LevelMenuModel;

class LevelMenu extends ResourceController
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
        $levelMenuModel     =   new LevelMenuModel();
        $searchKeyword      =   $this->request->getVar('searchKeyword');
        $idApplicationType  =   $this->request->getVar('idApplicationType');
        $idApplicationType  =   isset($idApplicationType) && !empty($idApplicationType) && $idApplicationType != '' ? hashidDecode($idApplicationType) : '';
        $listUserLevel      =	$levelMenuModel->getList($searchKeyword, $idApplicationType);

        if($listUserLevel){
            $listUserLevel  =   encodeDatabaseObjectResultKey($listUserLevel, ['IDUSERADMINLEVEL', 'IDAPPLICATIONTYPE']);
            return $this->setResponseFormat('json')
                        ->respond([
                            "listUserLevel"    =>  $listUserLevel
                        ]);
        } else {
            return throwResponseNotFound('No data found based on the applied filter');
        }
    }

    public function getDetail()
    {
        helper(['form']);
        $rules  =   [
            'idUserAdminLevel'  =>    ['label' => 'Id user level', 'rules' => 'required|alpha_numeric'],
            'idApplicationType' =>    ['label' => 'Id Application Type', 'rules' => 'required|alpha_numeric']
        ];

        $messages   =   [
            'idUserAdminLevel'    => [
                'required'      =>  'Data kiriman tidak valid',
                'alpha_numeric' =>  'Data kiriman tidak valid'
            ],
            'idApplicationType' => [
                'required'      =>  'Data kiriman tidak valid',
                'alpha_numeric' =>  'Data kiriman tidak valid'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $levelMenuModel     =   new LevelMenuModel();
        $idUserAdminLevel   =   $this->request->getVar('idUserAdminLevel');
        $idUserAdminLevel   =   hashidDecode($idUserAdminLevel);
        $idApplicationType  =   $this->request->getVar('idApplicationType');
        $idApplicationType  =   hashidDecode($idApplicationType);
        $detailMenuLevel    =	$levelMenuModel->getDetail($idUserAdminLevel, $idApplicationType);

        if($detailMenuLevel){
            foreach($detailMenuLevel as $keyMenuLevel){
                $idMenuAdmin        =   $keyMenuLevel->IDMENUADMIN;
                $detailPermissions  =   $levelMenuModel->getUserAdminMenuPermissions($idUserAdminLevel, $idMenuAdmin);

                if(!is_null($detailPermissions) && !empty($detailPermissions)) {
                    foreach($detailPermissions as $detailPermission){
                        $detailPermission->ALLOW  =   intval($detailPermission->ALLOW) == 1 ? true : false;
                    }

                    $keyMenuLevel->PERMISSIONS = encodeDatabaseObjectResultKey($detailPermissions, 'IDMENUADMINPERMISSION');
                } else {
                    $keyMenuLevel->PERMISSIONS = [];
                }
            }

            $detailMenuLevel    =   encodeDatabaseObjectResultKey($detailMenuLevel, ['IDMENUADMIN', 'IDMENULEVELADMIN']);
            return $this->setResponseFormat('json')
                        ->respond([
                            "detailMenuLevel"    =>  $detailMenuLevel
                        ]);
        } else {
            return throwResponseNotFound('No data found based on level user selected');
        }
    }

    public function addLevel()
    {
        helper(['form']);
        $rules      =   [
            'idApplicationType' =>  ['label' => 'Application Type', 'rules' => 'required|alpha_numeric'],
            'userLevelName'     =>  ['label' => 'Level Name', 'rules' => 'required|regex_match[/^[a-zA-Z0-9~!#$%&*_\-\+=|:., ]+$/]|min_length[5]|max_length[50]'],
            'description'       =>  ['label' => 'Description', 'rules' => 'required|regex_match[/^[a-zA-Z0-9~!#$%&*_\-\+=|:., ]+$/]|max_length[255]']
        ];

        if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $levelMenuModel     =   new LevelMenuModel();
        $idApplicationType  =   $this->request->getVar('idApplicationType');
        $idApplicationType  =   hashidDecode($idApplicationType);
        $userLevelName      =   $this->request->getVar('userLevelName');
        $description        =   $this->request->getVar('description');
        $isLevelAdminExist  =	$levelMenuModel->isUserLevelAdminExist($userLevelName);

        if(!$isLevelAdminExist){
            $arrInsertData   =   [
                'IDAPPLICATIONTYPE' =>  $idApplicationType,
                'LEVELNAME'         =>  $userLevelName,
                'DESCRIPTION'       =>  $description,
                'ISSUPERADMIN'      =>  0
            ];

            $mainOperation          =   new MainOperation();
            $procInsertLevelUser    =   $mainOperation->insertDataTable('m_useradminlevel', $arrInsertData);

            if(!$procInsertLevelUser['status']) return switchMySQLErrorCode($procInsertLevelUser['errCode']);
            return throwResponseOK(
                'New user level has been successfully added',
                ['idUserAdminLevel' =>  hashidEncode($procInsertLevelUser['insertID'])]
            );
        } else {
            return throwResponseNotAcceptable('Admin user level with name `'.$userLevelName.'` already exists, please use another name.');
        }
    }

    public function saveLevelDetailAndMenuList()
    {
        helper(['form']);
        $rules      =   [
            'idUserAdminLevel'  =>  ['label' => 'Id User Admin Level', 'rules' => 'required|alpha_numeric'],
            'userAdminLevelName'=>  ['label' => 'Level Name', 'rules' => 'required|regex_match[/^[a-zA-Z0-9~!#$%&*_\-\+=|:., ]+$/]|min_length[5]|max_length[50]'],
            'description'       =>  ['label' => 'Description', 'rules' => 'required|regex_match[/^[a-zA-Z0-9~!#$%&*_\-\+=|:., ]+$/]|max_length[255]'],
            'userAdminLevelMenu'=>  ['label' => 'User level menu', 'rules' => 'required|is_array'],
        ];

        $messages   =   [
            'idUserAdminLevel'  =>  [
                'required'      => 'Invalid data sent',
                'alpha_numeric' => 'Invalid data sent'
            ],
            'userAdminLevelMenu'=>  [
                'required'  => 'Invalid data sent',
                'is_array'  => 'Invalid data sent'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation      =   new MainOperation();
        $levelMenuModel     =   new LevelMenuModel();
        $idUserAdminLevel   =   $this->request->getVar('idUserAdminLevel');
        $userAdminLevelName =   $this->request->getVar('userAdminLevelName');
        $description        =   $this->request->getVar('description');
        $idUserAdminLevel   =   hashidDecode($idUserAdminLevel);
        $userAdminLevelMenu =   $this->request->getVar('userAdminLevelMenu');
        $isLevelAdminExist  =	$levelMenuModel->isUserLevelAdminExist($userAdminLevelName, $idUserAdminLevel);
        $arrDataLog         =   [];

        if($isLevelAdminExist) return throwResponseNotAcceptable('User admin level dengan nama `'.$userAdminLevelName.'` sudah ada, silakan gunakan nama lain.');

        $arrUpdateUserAdminLevel =   [
            'LEVELNAME'     =>  $userAdminLevelName,
            'DESCRIPTION'   =>  $description
        ];

        $mainOperation->updateDataTable('m_useradminlevel', $arrUpdateUserAdminLevel, ['IDUSERADMINLEVEL' => $idUserAdminLevel]);

        foreach($userAdminLevelMenu as $keyUserAdminLevelMenu) {
            $idMenuAdmin        =   isset($keyUserAdminLevelMenu->idMenuAdmin) && $keyUserAdminLevelMenu->idMenuAdmin != '' ? hashidDecode($keyUserAdminLevelMenu->idMenuAdmin) : 0;
            $idMenuLevelAdmin   =   isset($keyUserAdminLevelMenu->idMenuLevelAdmin) && $keyUserAdminLevelMenu->idMenuLevelAdmin != '' ? hashidDecode($keyUserAdminLevelMenu->idMenuLevelAdmin) : 0;
            $isMenuOpen         =   isset($keyUserAdminLevelMenu->isMenuOpen) && $keyUserAdminLevelMenu->isMenuOpen != '' ? $keyUserAdminLevelMenu->isMenuOpen : 0;
            $permissions        =   isset($keyUserAdminLevelMenu->permissions) && is_array($keyUserAdminLevelMenu->permissions) ? $keyUserAdminLevelMenu->permissions : [];
            $arrDataLog[]       =   [
                'idMenuAdmin'       =>  $idMenuAdmin,
                'idMenuLevelAdmin'  =>  $idMenuLevelAdmin,
                'isMenuOpen'        =>  $isMenuOpen
            ];

            if(!$isMenuOpen || $isMenuOpen == 0){
                if($idMenuLevelAdmin != 0) $mainOperation->deleteDataTable('m_menuleveladmin', ['IDMENULEVELADMIN' => $idMenuLevelAdmin]);
            } else {
                $arrInsertUpdateMenuLevel   =   [
                    'IDUSERADMINLEVEL'  =>  $idUserAdminLevel,
                    'IDMENUADMIN'       =>  $idMenuAdmin
                ];

                if($idMenuLevelAdmin != 0) $mainOperation->updateDataTable('m_menuleveladmin', $arrInsertUpdateMenuLevel, ['IDMENULEVELADMIN' => $idMenuLevelAdmin]);
                if($idMenuLevelAdmin == 0) $mainOperation->insertDataTable('m_menuleveladmin', $arrInsertUpdateMenuLevel);
            }

            if(count($permissions) > 0){
                foreach($permissions as $keyPermission){
                    $idMenuAdminPermission      =   isset($keyPermission->idMenuAdminPermission) && $keyPermission->idMenuAdminPermission != '' ? hashidDecode($keyPermission->idMenuAdminPermission) : 0;
                    $allow                      =   isset($keyPermission->allow) && $keyPermission->allow != '' ? intval($keyPermission->allow) : 0;
                    $isMenuLevelPermissionExist =   $levelMenuModel->isMenuLevelPermissionExist($idMenuAdminPermission, $idUserAdminLevel);
                    $arrMenuLevelPermission     =   [
                        'IDMENUADMINPERMISSION' =>  $idMenuAdminPermission,
                        'IDUSERADMINLEVEL'      =>  $idUserAdminLevel,
                        'ALLOW'                 =>  $allow
                    ];

                    if($isMenuLevelPermissionExist) $mainOperation->updateDataTable('m_menuleveladminpermission', $arrMenuLevelPermission, ['IDMENULEVELADMINPERMISSION' => $isMenuLevelPermissionExist['IDMENULEVELADMINPERMISSION']]);
                    if(!$isMenuLevelPermissionExist) $mainOperation->insertDataTable('m_menuleveladminpermission', $arrMenuLevelPermission);
                }
            }
        }

        return throwResponseOK(
            'User level detail & menu access has been successfully updated',
            [
                'idUserLevel'   =>  $idUserAdminLevel,
                'arrUpdateUserAdminLevel'   =>  $arrUpdateUserAdminLevel,
                'arrDataLog'    =>  $arrDataLog
            ]
        );
    }
}