<?php

namespace App\Controllers\ERP\Master\DataDasarBarang;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\MainOperation;
use App\Models\ERP\Master\DataDasarBarang\MerkModel;

class Merk extends ResourceController
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
        $merkModel      =   new MerkModel();
        $mainOperation  =   new MainOperation();
        $searchKeyword  =   $this->request->getVar('searchKeyword');
        $dataPerPage    =   $this->request->getVar('dataPerPage');
        $pageNumber     =   $this->request->getVar('pageNumber');
        $baseData       =	$merkModel->like('NAMAMERK', $searchKeyword)->like('KODEMERK', $searchKeyword)->orLike('DESKRIPSI', $searchKeyword);
        $totalNumberData=   $baseData->countAllResults(false);
        $pageProperty   =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $listData   =   $baseData->orderBy('NAMAMERK', 'ASC')->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $listData   =   encodeDatabaseObjectResultKey($listData, ['IDBARANGMERK']);
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
        $idBarangMerk   =   $this->request->getVar('idBarangMerk');
        $idBarangMerk   =   $idBarangMerk != "" ? hashidDecode($idBarangMerk) : 0;
        $validation     =   $idBarangMerk == 0 ? $this->parametersValidatorMerk() : $this->parametersValidatorMerk(true, $idBarangMerk);

        if($validation !== true) return $this->fail($validation);
        return $idBarangMerk == 0 ? $this->insertData() : $this->updateData($idBarangMerk);
    }

    private function insertData()
    {
        $mainOperation  =   new MainOperation();
        $arrInsertData  =   $this->generateArrayInsertUpdate();
        $procInsertData =   $mainOperation->insertDataTable('m_barangmerk', $arrInsertData);

        if(!$procInsertData['status']) return switchMySQLErrorCode($procInsertData['errCode']);
        $idBarangMerk=   $procInsertData['insertID'];
        return throwResponseOK(
            'Data merk barang telah disimpan',
            ['idBarangMerk'  =>  hashidEncode($idBarangMerk)]
        );
    }

    private function updateData($idBarangMerk)
    {
        $mainOperation  =   new MainOperation();
        $arrUpdateData  =   $this->generateArrayInsertUpdate();
        $procUpdateData =   $mainOperation->updateDataTable('m_barangmerk', $arrUpdateData, ['IDBARANGMERK' => $idBarangMerk]);

        if(!$procUpdateData['status']) return switchMySQLErrorCode($procUpdateData['errCode']);
        return throwResponseOK(
            'Data merk barang telah diperbarui',
            ['idBarangMerk'  =>  hashidEncode($idBarangMerk)]
        );
    }

    private function parametersValidatorMerk($isUpdate = false, $idMerk = null)
    {
        $rules  =   [
            'kodeMerk'  =>  ['label' => 'Kode Merk', 'rules' => 'required|alpha_numeric|min_length[3]|max_length[8]'],
            'namaMerk'  =>  ['label' => 'Nama Merk', 'rules' => 'required|alpha_numeric_space|min_length[3]|max_length[50]'],
            'deskripsi' =>  ['label' => 'Deskripsi', 'rules' => 'required|regex_match[/^[a-zA-Z0-9\s\p{P}]+$/u]|max_length[255]'],
            'status'    =>  ['label' => 'Status', 'rules' => 'required|in_list[0,1]']
        ];

        $messages   =   [];

        if($isUpdate) {
            $rules['kodeMerk']['rules']           .=  '|is_unique[m_barangmerk.KODEMERK, IDBARANGMERK, '.$idMerk.']';
            $rules['namaMerk']['rules']           .=  '|is_unique[m_barangmerk.NAMAMERK, IDBARANGMERK, '.$idMerk.']';
            $rules['idMerk']['rules']             =   'required|alpha_numeric';
            $messages['idMerk']['required']       =   'Data kiriman tidak lengkap, silakan periksa kembali';
            $messages['idMerk']['alpha_numeric']  =   'Data kiriman tidak lengkap, silakan periksa kembali';
        } else {
            $rules['kodeMerk']['rules']           .=  '|is_unique[m_barangmerk.KODEMERK]';
            $rules['namaMerk']['rules']           .=  '|is_unique[m_barangmerk.NAMAMERK]';
        }

        if(!$this->validate($rules, $messages)) return $this->validator->getErrors();
        return true;
    }

    private function generateArrayInsertUpdate(): array
    {
        $kodeMerk   =   $this->request->getVar('kodeMerk');
        $namaMerk   =   $this->request->getVar('namaMerk');
        $deskripsi  =   $this->request->getVar('deskripsi');
        $status     =   $this->request->getVar('status');

        return [
            'KODEMERK'  =>  $kodeMerk,
            'NAMAMERK'  =>  $namaMerk,
            'DESKRIPSI' =>  $deskripsi,
            'STATUS'    =>  $status
        ];
    }
}