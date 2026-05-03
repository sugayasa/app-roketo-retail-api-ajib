<?php

namespace App\Controllers\ERP\Master;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\MainOperation;
use App\Models\ERP\Master\GudangModel;

class Gudang extends ResourceController
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
        $gudangModel    =   new GudangModel();
        $mainOperation  =   new MainOperation();
        $searchKeyword  =   $this->request->getVar('searchKeyword');
        $dataPerPage    =   $this->request->getVar('dataPerPage');
        $pageNumber     =   $this->request->getVar('pageNumber');
        $baseData       =	$gudangModel->getListGudang($searchKeyword);
        $totalNumberData=   $baseData->countAllResults(false);
        $pageProperty   =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $listData   =   $baseData->orderBy('NAMA', 'ASC')->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $listData   =   encodeDatabaseObjectResultKey($listData, ['IDGUDANG', 'IDUSERADMINKEPALAGUDANG']);
            
            foreach($listData as $data) {
                $data->LOGO =   [$data->LOGOURL, $data->LOGO];
                unset($data->LOGOURL);
            }

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $listData,
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty
            ];
            return throwResponseNotFound('Tidak ada data yang ditemukan', $dataReturn);
        }
    }

    public function uploadImageLogoPerusahaan()
    {
        $imageLogo          =   $this->request->getFile('imageLogo');
        $allowedExtensions  =   ['png', 'jpg', 'jpeg'];

        if ($imageLogo && !$imageLogo->hasMoved()) {
            $extension = strtolower($imageLogo->getExtension());
            if (!in_array($extension, $allowedExtensions)) {
                return $this->failValidationErrors('Ekstensi file tidak diizinkan. Hanya png, jpg dan jpeg yang diperbolehkan.');
            }
        }
        
        if ($imageLogo && $imageLogo->isValid() && !$imageLogo->hasMoved()) {
            $imageLogoName    =   "Logo_".$imageLogo->getRandomName();
            $imageLogo->move(PATH_STORAGE_LOGO_PERUSAHAAN, $imageLogoName);

            return $this->setResponseFormat('json')->respond([
                'message'   =>  'Image logo berhasil diunggah',
                'urlLogo'   =>  URL_LOGO_PERUSAHAAN.$imageLogoName,
                'imageName' =>  $imageLogoName
            ]);
        }
    }

    public function saveData()
    {
        helper(['form']);
        $idGudang    =   $this->request->getVar('idGudang');
        $idGudang    =   $idGudang != "" ? hashidDecode($idGudang) : 0;
        $validation =   $idGudang == 0 ? $this->parametersValidatorGudang() : $this->parametersValidatorGudang(true, $idGudang);

        if($validation !== true) return $this->fail($validation);
        return $idGudang == 0 ? $this->insertData() : $this->updateData($idGudang);
    }

    private function insertData()
    {
        $mainOperation  =   new MainOperation();
        $arrInsertData  =   $this->generateArrayInsertUpdate();
        $procInsertData =   $mainOperation->insertDataTable('m_gudang', $arrInsertData);

        if(!$procInsertData['status']) return switchMySQLErrorCode($procInsertData['errCode']);
        $idGudang=   $procInsertData['insertID'];
        return throwResponseOK(
            'Data gudang telah disimpan',
            ['idGudang'  =>  hashidEncode($idGudang)]
        );
    }

    private function updateData($idGudang)
    {
        $mainOperation  =   new MainOperation();
        $arrUpdateData  =   $this->generateArrayInsertUpdate();
        $procUpdateData =   $mainOperation->updateDataTable('m_gudang', $arrUpdateData, ['IDGUDANG' => $idGudang]);

        if(!$procUpdateData['status']) return switchMySQLErrorCode($procUpdateData['errCode']);
        return throwResponseOK(
            'Data gudang telah diperbarui',
            ['idGudang'  =>  hashidEncode($idGudang)]
        );
    }

    private function parametersValidatorGudang($isUpdate = false, $idGudang = null)
    {
        $rules  =   [
            'namaPerusahaan'            =>  ['label' => 'Nama Perusahaan', 'rules' => 'required|alpha_numeric_punct|min_length[1]|max_length[100]'],
            'idUserAdminKepalaGudang'   =>  ['label' => 'Kepala Gudang', 'rules' => 'required|alpha_numeric'],
            'kodeGudang'                =>  ['label' => 'Kode Gudang', 'rules' => 'required|alpha_numeric|min_length[1]|max_length[10]'],
            'namaGudang'                =>  ['label' => 'Nama Gudang', 'rules' => 'required|alpha_numeric_space|min_length[3]|max_length[50]'],
            'alamatGudang'              =>  ['label' => 'Alamat Gudang', 'rules' => 'required|alpha_numeric_space|min_length[20]|max_length[150]'],
            'kota'                      =>  ['label' => 'Kota', 'rules' => 'required|alpha_numeric_punct|min_length[10]|max_length[75]'],
            'propinsi'                  =>  ['label' => 'Propinsi', 'rules' => 'required|alpha_numeric_punct|min_length[10]|max_length[75]'],
            'logo'                      =>  ['label' => 'Logo', 'rules' => 'permit_empty|alpha_numeric_punct|min_length[8]|max_length[100]'],
        ];

        $messages   =   [
            'idUserAdminKepalaGudang'   => [
                'required'      => 'Harap pilih kepala gudang terlebih dahulu',
                'alpha_numeric' => 'Data kiriman tidak lengkap, silakan periksa kembali'
            ],
            'logo'  => [
                'alpha_numeric_punct'   => 'File logo yang anda pilih tidak valid',
                'min_length'            => 'File logo yang anda pilih tidak valid',
                'max_length'            => 'File logo yang anda pilih tidak valid'
            ]
        ];

        if($isUpdate) {
            $rules['kodeGudang']['rules']           .=  '|is_unique[m_gudang.KODE, IDGUDANG, '.$idGudang.']';
            $rules['namaGudang']['rules']           .=  '|is_unique[m_gudang.NAMA, IDGUDANG, '.$idGudang.']';
            $rules['idGudang']['rules']             =   'required|alpha_numeric';
            $messages['idGudang']['required']       =   'Data kiriman tidak lengkap, silakan periksa kembali';
            $messages['idGudang']['alpha_numeric']  =   'Data kiriman tidak lengkap, silakan periksa kembali';
        } else {
            $rules['kodeGudang']['rules']           .=  '|is_unique[m_gudang.KODE]';
            $rules['namaGudang']['rules']           .=  '|is_unique[m_gudang.NAMA]';
        }

        if(!$this->validate($rules, $messages)) return $this->validator->getErrors();
        return true;
    }

    private function generateArrayInsertUpdate(): array
    {
        $idUserAdmin    =   $this->request->getVar('idUserAdmin');
        $idUserAdmin    =   $idUserAdmin != "" ? hashidDecode($idUserAdmin) : 0;
        $namaPerusahaan =   $this->request->getVar('namaPerusahaan');
        $kodeGudang     =   $this->request->getVar('kodeGudang');
        $namaGudang     =   $this->request->getVar('namaGudang');
        $alamatGudang   =   $this->request->getVar('alamatGudang');
        $kota           =   $this->request->getVar('kota');
        $propinsi       =   $this->request->getVar('propinsi');
        $logo           =   $this->request->getVar('logo');

        return [
            'IDUSERADMINKEPALAGUDANG'   =>  $idUserAdmin,
            'NAMAPERUSAHAAN'            =>  $namaPerusahaan,
            'KODE'                      =>  $kodeGudang,
            'NAMA'                      =>  $namaGudang,
            'ALAMAT'                    =>  $alamatGudang,
            'KOTA'                      =>  $kota,
            'PROVINSI'                  =>  $propinsi,
            'LOGO'                      =>  $logo
        ];
    }
}