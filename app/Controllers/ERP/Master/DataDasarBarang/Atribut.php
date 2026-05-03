<?php

namespace App\Controllers\ERP\Master\DataDasarBarang;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\MainOperation;
use App\Models\ERP\Master\DataDasarBarang\AtributModel;

class Atribut extends ResourceController
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
        $atributModel   =   new AtributModel();
        $mainOperation  =   new MainOperation();
        $searchKeyword  =   $this->request->getVar('searchKeyword');
        $dataPerPage    =   $this->request->getVar('dataPerPage');
        $pageNumber     =   $this->request->getVar('pageNumber');
        $baseData       =	$atributModel->like('NAMAATRIBUT', $searchKeyword)->orLike('KODEATRIBUT', $searchKeyword)->orLike('DESKRIPSI', $searchKeyword);
        $totalNumberData=   $baseData->countAllResults(false);
        $pageProperty   =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $listData   =   $baseData->orderBy('NAMAATRIBUT', 'ASC')->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $listData   =   encodeDatabaseObjectResultKey($listData, ['IDBARANGATRIBUT']);
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
        $idBarangAtribut    =   $this->request->getVar('idBarangAtribut');
        $idBarangAtribut    =   $idBarangAtribut != "" ? hashidDecode($idBarangAtribut) : 0;

        return $idBarangAtribut == 0 ? $this->insertData() : $this->updateData($idBarangAtribut);
    }

    private function insertData()
    {
        $rules  =   [
            'namaAtribut'   =>  ['label' => 'Nama Atribut', 'rules' => 'required|alpha_numeric_space|min_length[3]|max_length[50]|is_unique[m_barangatribut.NAMAATRIBUT]'],
            'kodeAtribut'   =>  ['label' => 'Kode Atribut', 'rules' => 'required|alpha_numeric|min_length[1]|max_length[5]|is_unique[m_barangatribut.KODEATRIBUT]'],
            'deskripsi'     =>  ['label' => 'Deskripsi', 'rules' => 'required|regex_match[/^[a-zA-Z0-9\s\p{P}]+$/u]|max_length[255]'],
            'status'        =>  ['label' => 'Status', 'rules' => 'required|in_list[0,1]']
        ];

        $messages  =   [
            'status'    =>  ['in_list'  => 'Status yang dipilih tidak valid, silakan pilih status yang sesuai']
        ];
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation  =   new MainOperation();
        $arrInsertData  =   $this->generateArrayInsertUpdate();
        $procInsertData =   $mainOperation->insertDataTable('m_barangatribut', $arrInsertData);

        if(!$procInsertData['status']) return switchMySQLErrorCode($procInsertData['errCode']);
        $idBarangAtribut=   $procInsertData['insertID'];
        return throwResponseOK(
            'Data atribut barang telah disimpan',
            ['idBarangAtribut'  =>  hashidEncode($idBarangAtribut)]
        );
    }

    private function updateData($idBarangAtribut)
    {
        $rules      =   [
            'idBarangAtribut'   =>  ['label' => 'ID Barang Atribut', 'rules' => 'required|alpha_numeric'],
            'namaAtribut'       =>  ['label' => 'Nama Atribut', 'rules' => 'required|alpha_numeric_space|min_length[3]|max_length[50]|is_unique[m_barangatribut.NAMAATRIBUT, IDBARANGATRIBUT, '.$idBarangAtribut.']'],
            'kodeAtribut'       =>  ['label' => 'Kode Atribut', 'rules' => 'required|alpha_numeric|min_length[1]|max_length[5]|is_unique[m_barangatribut.KODEATRIBUT, IDBARANGATRIBUT, '.$idBarangAtribut.']'],
            'deskripsi'         =>  ['label' => 'Deskripsi', 'rules' => 'required|regex_match[/^[a-zA-Z0-9\s\p{P}]+$/u]|max_length[255]'],
            'status'            =>  ['label' => 'Status', 'rules' => 'required|in_list[0,1]']
        ];

        $messages   =   [
            'idBarangAtribut'   => [
                'required'      => 'Data kiriman tidak lengkap, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak lengkap, silakan periksa kembali'
            ],
            'status'    =>  ['in_list'  => 'Status yang dipilih tidak valid, silakan pilih status yang sesuai']
        ];
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation  =   new MainOperation();
        $arrUpdateData  =   $this->generateArrayInsertUpdate();
        $procUpdateData =   $mainOperation->updateDataTable('m_barangatribut', $arrUpdateData, ['IDBARANGATRIBUT' => $idBarangAtribut]);

        if(!$procUpdateData['status']) return switchMySQLErrorCode($procUpdateData['errCode']);
        return throwResponseOK(
            'Data atribut barang telah diperbarui',
            ['idBarangAtribut'  =>  hashidEncode($idBarangAtribut)]
        );
    }

    private function generateArrayInsertUpdate(): array
    {
        $namaAtribut    =   $this->request->getVar('namaAtribut');
        $kodeAtribut    =   $this->request->getVar('kodeAtribut');
        $deskripsi      =   $this->request->getVar('deskripsi');
        $status         =   $this->request->getVar('status');

        return [
            'NAMAATRIBUT' =>  $namaAtribut,
            'KODEATRIBUT' =>  $kodeAtribut,
            'DESKRIPSI'   =>  $deskripsi,
            'STATUS'      =>  $status
        ];
    }
}