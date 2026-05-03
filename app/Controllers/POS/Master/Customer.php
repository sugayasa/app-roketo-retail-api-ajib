<?php

namespace App\Controllers\POS\Master;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\MainOperation;
use App\Models\POS\Master\CustomerModel;

class Customer extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    use ResponseTrait;
    protected $userData, $idToko, $currentDateTime, $request;
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger) {
        parent::initController($request, $response, $logger);

        try {
            $this->userData         =   $request->userData;
            $this->idToko           =   $request->userData->idToko;
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
        $customerModel  =   new CustomerModel();
        $mainOperation  =   new MainOperation();
        $searchKeyword  =   $this->request->getVar('searchKeyword');
        $dataPerPage    =   $this->request->getVar('dataPerPage');
        $pageNumber     =   $this->request->getVar('pageNumber');
        $baseData       =	$customerModel
                            ->select('IDCUSTOMER, NAMA, ALAMAT, TELPON, CATATAN, EDITABLE')
                            ->where('IDTOKO', $this->idToko)
                            ->groupStart()
                            ->like('NAMA', $searchKeyword)
                            ->orLike('ALAMAT', $searchKeyword)
                            ->orLike('TELPON', $searchKeyword)
                            ->orLike('CATATAN', $searchKeyword)
                            ->groupEnd();
        $totalNumberData=   $baseData->countAllResults(false);
        $pageProperty   =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $listData   =   $baseData->orderBy('NAMA', 'ASC')->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $listData   =   encodeDatabaseObjectResultKey($listData, ['IDCUSTOMER']);
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
        $idCustomer =   $this->request->getVar('idCustomer');
        $idCustomer =   $idCustomer != "" ? hashidDecode($idCustomer) : 0;
        $validation =   $idCustomer == 0 ? $this->parametersValidatorCustomer() : $this->parametersValidatorCustomer(true, $idCustomer);

        if($validation !== true) return $this->fail($validation);
        return $idCustomer == 0 ? $this->insertData() : $this->updateData($idCustomer);
    }

    private function insertData()
    {
        $mainOperation  =   new MainOperation();
        $arrInsertData  =   $this->generateArrayInsertUpdate(true);
        $procInsertData =   $mainOperation->insertDataTable('m_customer', $arrInsertData);

        if(!$procInsertData['status']) return switchMySQLErrorCode($procInsertData['errCode']);
        $idCustomer     =   $procInsertData['insertID'];
        return throwResponseOK(
            'Data customer telah disimpan',
            ['idCustomer'  =>  hashidEncode($idCustomer)]
        );
    }

    private function updateData($idCustomer)
    {
        $mainOperation  =   new MainOperation();
        $arrUpdateData  =   $this->generateArrayInsertUpdate();
        $procUpdateData =   $mainOperation->updateDataTable('m_customer', $arrUpdateData, ['IDCUSTOMER' => $idCustomer]);

        if(!$procUpdateData['status']) return switchMySQLErrorCode($procUpdateData['errCode']);
        return throwResponseOK(
            'Data customer telah diperbarui',
            ['idCustomer'   =>  hashidEncode($idCustomer)]
        );
    }

    private function parametersValidatorCustomer($isUpdate = false, $idCustomer = null)
    {
        $rules  =   [
            'namaCustomer'      =>  ['label' => 'Nama Customer', 'rules' => 'required|alpha_numeric_space|min_length[3]|max_length[50]'],
            'alamatCustomer'    =>  ['label' => 'Alamat Customer', 'rules' => 'required|alpha_numeric_space|min_length[10]|max_length[150]'],
            'telponCustomer'    =>  ['label' => 'Telpon Customer', 'rules' => 'required|regex_match[/^\+?[0-9]{10,13}$/]|min_length[8]|max_length[20]'],
            'catatan'           =>  ['label' => 'Catatan', 'rules' => 'permit_empty|alpha_numeric_space|max_length[500]']
        ];

        $messages   =   [];

        if($isUpdate) {
            $rules['telponCustomer']['rules']       .=  '|is_unique[m_customer.TELPON, IDCUSTOMER, '.$idCustomer.']';
            $rules['idCustomer']['rules']           =   'required|alpha_numeric';
            $messages['idCustomer']['required']     =   'Data kiriman tidak lengkap, silakan periksa kembali';
            $messages['idCustomer']['alpha_numeric']=   'Data kiriman tidak lengkap, silakan periksa kembali';
        } else {
            $rules['telponCustomer']['rules']       .=  '|is_unique[m_customer.TELPON]';
        }

        if(!$this->validate($rules, $messages)) return $this->validator->getErrors();
        return true;
    }

    private function generateArrayInsertUpdate($isInsert = false): array
    {
        $namaCustomer   =   $this->request->getVar('namaCustomer');
        $alamatCustomer =   $this->request->getVar('alamatCustomer');
        $telponCustomer =   $this->request->getVar('telponCustomer');
        $catatan        =   $this->request->getVar('catatan');
        $arrInsertUpdate=   [
            'IDTOKO'    =>  $this->idToko,
            'NAMA'      =>  $namaCustomer,
            'ALAMAT'    =>  $alamatCustomer,
            'TELPON'    =>  $telponCustomer,
            'CATATAN'   =>  $catatan
        ];

        if($isInsert) $arrInsertUpdate['INPUTTANGGALWAKTU'] =   $this->currentDateTime;
        return $arrInsertUpdate;
    }
}