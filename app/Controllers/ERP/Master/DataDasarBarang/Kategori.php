<?php

namespace App\Controllers\ERP\Master\DataDasarBarang;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\MainOperation;
use App\Models\ERP\Master\DataDasarBarang\KategoriModel;

class Kategori extends ResourceController
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
        $kategoriModel  =   new KategoriModel();
        $mainOperation  =   new MainOperation();
        $searchKeyword  =   $this->request->getVar('searchKeyword');
        $dataPerPage    =   $this->request->getVar('dataPerPage');
        $pageNumber     =   $this->request->getVar('pageNumber');
        $baseData       =	$kategoriModel->like('NAMAKATEGORI', $searchKeyword)->like('KODEKATEGORI', $searchKeyword)->orLike('DESKRIPSI', $searchKeyword);
        $totalNumberData=   $baseData->countAllResults(false);
        $pageProperty   =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $listData   =   $baseData->orderBy('NAMAKATEGORI', 'ASC')->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $listData   =   encodeDatabaseObjectResultKey($listData, ['IDBARANGKATEGORI']);
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
        $idBarangKategori   =   $this->request->getVar('idBarangKategori');
        $idBarangKategori   =   $idBarangKategori != "" ? hashidDecode($idBarangKategori) : 0;
        $validation         =   $idBarangKategori == 0 ? $this->parametersValidatorKategori() : $this->parametersValidatorKategori(true, $idBarangKategori);

        if($validation !== true) return $this->fail($validation);
        return $idBarangKategori == 0 ? $this->insertData() : $this->updateData($idBarangKategori);
    }

    private function insertData()
    {
        $mainOperation  =   new MainOperation();
        $arrInsertData  =   $this->generateArrayInsertUpdate();
        $procInsertData =   $mainOperation->insertDataTable('m_barangkategori', $arrInsertData);

        if(!$procInsertData['status']) return switchMySQLErrorCode($procInsertData['errCode']);
        $idBarangKategori=   $procInsertData['insertID'];
        return throwResponseOK(
            'Data kategori barang telah disimpan',
            ['idBarangKategori'  =>  hashidEncode($idBarangKategori)]
        );
    }

    private function updateData($idBarangKategori)
    {
        $mainOperation  =   new MainOperation();
        $arrUpdateData  =   $this->generateArrayInsertUpdate();
        $procUpdateData =   $mainOperation->updateDataTable('m_barangkategori', $arrUpdateData, ['IDBARANGKATEGORI' => $idBarangKategori]);

        if(!$procUpdateData['status']) return switchMySQLErrorCode($procUpdateData['errCode']);
        return throwResponseOK(
            'Data kategori barang telah diperbarui',
            ['idBarangKategori'  =>  hashidEncode($idBarangKategori)]
        );
    }

    private function parametersValidatorKategori($isUpdate = false, $idBarangKategori = null)
    {
        $rules  =   [
            'kodeKategori'  =>  ['label' => 'Kode Kategori', 'rules' => 'required|alpha_numeric|min_length[3]|max_length[8]'],
            'namaKategori'  =>  ['label' => 'Nama Kategori', 'rules' => 'required|alpha_numeric_space|min_length[3]|max_length[50]'],
            'deskripsi'     =>  ['label' => 'Deskripsi', 'rules' => 'required|regex_match[/^[a-zA-Z0-9\s\p{P}]+$/u]|max_length[255]'],
            'status'        =>  ['label' => 'Status', 'rules' => 'required|in_list[0,1]']
        ];

        $messages   =   [
            'status'    =>  ['in_list'  => 'Status yang dipilih tidak valid, silakan pilih status yang sesuai']
        ];

        if($isUpdate) {
            $rules['kodeKategori']['rules']                 .=  '|is_unique[m_barangkategori.KODEKATEGORI, IDBARANGKATEGORI, '.$idBarangKategori.']';
            $rules['namaKategori']['rules']                 .=  '|is_unique[m_barangkategori.NAMAKATEGORI, IDBARANGKATEGORI, '.$idBarangKategori.']';
            $rules['idBarangKategori']['rules']             =   'required|alpha_numeric';
            $messages['idBarangKategori']['required']       =   'Data kiriman tidak lengkap, silakan periksa kembali';
            $messages['idBarangKategori']['alpha_numeric']  =   'Data kiriman tidak lengkap, silakan periksa kembali';
        } else {
            $rules['kodeKategori']['rules']                 .=  '|is_unique[m_barangkategori.KODEKATEGORI]';
            $rules['namaKategori']['rules']                 .=  '|is_unique[m_barangkategori.NAMAKATEGORI]';
        }

        if(!$this->validate($rules, $messages)) return $this->validator->getErrors();
        return true;
    }

    private function generateArrayInsertUpdate(): array
    {
        $kodeKategori   =   $this->request->getVar('kodeKategori');
        $namaKategori   =   $this->request->getVar('namaKategori');
        $deskripsi      =   $this->request->getVar('deskripsi');
        $status         =   $this->request->getVar('status');

        return [
            'KODEKATEGORI'  =>  $kodeKategori,
            'NAMAKATEGORI'  =>  $namaKategori,
            'DESKRIPSI'     =>  $deskripsi,
            'STATUS'        =>  $status
        ];
    }
}