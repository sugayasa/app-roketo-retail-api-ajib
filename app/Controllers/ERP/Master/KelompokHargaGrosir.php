<?php

namespace App\Controllers\ERP\Master;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\MainOperation;
use App\Models\ERP\Master\KelompokHargaGrosirModel;

class KelompokHargaGrosir extends ResourceController
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
        $kelompokHargaGrosirModel   =   new KelompokHargaGrosirModel();
        $mainOperation              =   new MainOperation();
        $searchKeyword              =   $this->request->getVar('searchKeyword');
        $dataPerPage                =   $this->request->getVar('dataPerPage');
        $pageNumber                 =   $this->request->getVar('pageNumber');
        $baseData                   =   $kelompokHargaGrosirModel->like('KELOMPOKHARGAGROSIR', $searchKeyword)->orLike('DESKRIPSI', $searchKeyword);
        $totalNumberData            =   $baseData->countAllResults(false);
        $pageProperty               =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $listData   =   $baseData->orderBy('KELOMPOKHARGAGROSIR', 'ASC')->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $listData   =   encodeDatabaseObjectResultKey($listData, ['IDKELOMPOKHARGAGROSIR']);
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

    public function saveData()
    {
        helper(['form']);
        $idKelompokHargaGrosir  =   $this->request->getVar('idKelompokHargaGrosir');
        $idKelompokHargaGrosir  =   $idKelompokHargaGrosir != "" ? hashidDecode($idKelompokHargaGrosir) : 0;
        $validation             =   $idKelompokHargaGrosir == 0 ? $this->parametersValidatorKelompokHargaGrosir() : $this->parametersValidatorKelompokHargaGrosir(true, $idKelompokHargaGrosir);

        if($validation !== true) return $this->fail($validation);
        return $idKelompokHargaGrosir == 0 ? $this->insertData() : $this->updateData($idKelompokHargaGrosir);
    }

    private function insertData()
    {
        $mainOperation  =   new MainOperation();
        $arrInsertData  =   $this->generateArrayInsertUpdate();
        $procInsertData =   $mainOperation->insertDataTable('m_kelompokhargagrosir', $arrInsertData);

        if(!$procInsertData['status']) return switchMySQLErrorCode($procInsertData['errCode']);
        $idKelompokHargaGrosir     =   $procInsertData['insertID'];
        return throwResponseOK(
            'Data kelompok harga grosir telah disimpan',
            ['idKelompokHargaGrosir'  =>  hashidEncode($idKelompokHargaGrosir)]
        );
    }

    private function updateData($idKelompokHargaGrosir)
    {
        $mainOperation  =   new MainOperation();
        $arrUpdateData  =   $this->generateArrayInsertUpdate();
        $procUpdateData =   $mainOperation->updateDataTable('m_kelompokhargagrosir', $arrUpdateData, ['IDKELOMPOKHARGAGROSIR' => $idKelompokHargaGrosir]);

        if(!$procUpdateData['status']) return switchMySQLErrorCode($procUpdateData['errCode']);
        return throwResponseOK(
            'Data kelompok harga grosir telah diperbarui',
            ['idKelompokHargaGrosir'    =>  hashidEncode($idKelompokHargaGrosir)]
        );
    }

    private function parametersValidatorKelompokHargaGrosir($isUpdate = false, $idKelompokHargaGrosir = null)
    {
        $rules  =   [
            'kelompokHargaGrosir'   =>  ['label' => 'Nama Kelompok Harga', 'rules' => 'required|regex_match[/^[a-zA-Z0-9\s\.\,\!\?\:\;\-\(\)]+$/]|min_length[3]|max_length[50]'],
            'deskripsi'             =>  ['label' => 'Deskripsi', 'rules' => 'permit_empty|regex_match[/^[a-zA-Z0-9\s\.\,\!\?\:\;\-\(\)]+$/]|min_length[10]|max_length[150]'],
            'status'                =>  ['label' => 'Status', 'rules' => 'required|in_list[0,1]']
        ];

        $messages   =   [
            'status'    => [
                'in_list'   => 'Harap pilih status yang valid'
            ]
        ];

        if($isUpdate) {
            $rules['kelompokHargaGrosir']['rules']              .=  '|is_unique[m_kelompokhargagrosir.KELOMPOKHARGAGROSIR, IDKELOMPOKHARGAGROSIR, '.$idKelompokHargaGrosir.']';
            $messages['idKelompokHargaGrosir']['required']      =   'Data kiriman tidak lengkap, silakan periksa kembali';
            $messages['idKelompokHargaGrosir']['alpha_numeric'] =   'Data kiriman tidak lengkap, silakan periksa kembali';
        } else {
            $rules['kelompokHargaGrosir']['rules']              .=  '|is_unique[m_kelompokhargagrosir.KELOMPOKHARGAGROSIR]';
        }

        if(!$this->validate($rules, $messages)) return $this->validator->getErrors();
        return true;
    }

    private function generateArrayInsertUpdate(): array
    {
        $kelompokHargaGrosir=   $this->request->getVar('kelompokHargaGrosir');
        $deskripsi          =   $this->request->getVar('deskripsi');
        $status             =   $this->request->getVar('status');
        $arrInsertUpdate    =   [
            'KELOMPOKHARGAGROSIR'   =>  $kelompokHargaGrosir,
            'DESKRIPSI'             =>  $deskripsi,
            'STATUS'                =>  $status
        ];

        return $arrInsertUpdate;
    }
}