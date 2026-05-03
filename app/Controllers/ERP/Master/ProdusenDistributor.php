<?php

namespace App\Controllers\ERP\Master;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\MainOperation;
use App\Models\ERP\Master\DataDasarBarang\MerkModel;
use App\Models\ERP\Master\ProdusenDistributorModel;

class ProdusenDistributor extends ResourceController
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
        $produsenDistributorModel   =   new ProdusenDistributorModel();
        $merkModel                  =   new MerkModel();
        $mainOperation              =   new MainOperation();
        $searchKeyword              =   $this->request->getVar('searchKeyword');
        $dataPerPage                =   $this->request->getVar('dataPerPage');
        $pageNumber                 =   $this->request->getVar('pageNumber');
        $baseData                   =   $produsenDistributorModel->like('NAMA', $searchKeyword)->orLike('ALAMAT', $searchKeyword)->orLike('TELPON', $searchKeyword)->orLike('CATATAN', $searchKeyword);
        $totalNumberData            =   $baseData->countAllResults(false);
        $pageProperty               =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $listData   =   $baseData->orderBy('NAMA', 'ASC')->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);

            foreach($listData as $data) {
                $arrIdMerk  =   $data->ARRIDBARANGMERK;
                if($arrIdMerk != null && $arrIdMerk != "") {
                    $arrIdMerk          =   json_decode($data->ARRIDBARANGMERK);
                    $arrIdMerkReturned  =   [];

                    foreach($arrIdMerk as $idMerk) {
                        if($idMerk != 0 && $idMerk != null && $idMerk != false) {
                            $arrIdMerkReturned[]    =   [
                                'IDBARANGMERK'  =>  hashidEncode($idMerk),
                                'NAMAMERK'      =>  $merkModel->select('NAMAMERK')->where('IDBARANGMERK', $idMerk)->get()->getRowObject()->NAMAMERK
                            ];
                        }
                    }

                    $data->ARRIDBARANGMERK  =   is_null($arrIdMerkReturned) || empty($arrIdMerkReturned) ? [] : $arrIdMerkReturned;
                } else {
                    $data->ARRIDBARANGMERK  =   [];
                }
            }

            $listData       =   encodeDatabaseObjectResultKey($listData, ['IDPRODUSENDISTRIBUTOR']);
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
        $idProdusenDistributor  =   $this->request->getVar('idProdusenDistributor');
        $idProdusenDistributor  =   $idProdusenDistributor != "" ? hashidDecode($idProdusenDistributor) : 0;
        $validation             =   $idProdusenDistributor == 0 ? $this->parametersValidatorProdusenDistributor() : $this->parametersValidatorProdusenDistributor(true, $idProdusenDistributor);

        if($validation !== true) return $this->fail($validation);
        return $idProdusenDistributor == 0 ? $this->insertData() : $this->updateData($idProdusenDistributor);
    }

    private function insertData()
    {
        $mainOperation  =   new MainOperation();
        $arrInsertData  =   $this->generateArrayInsertUpdate();
        $procInsertData =   $mainOperation->insertDataTable('m_produsendistributor', $arrInsertData);

        if(!$procInsertData['status']) return switchMySQLErrorCode($procInsertData['errCode']);
        $idProdusenDistributor     =   $procInsertData['insertID'];
        return throwResponseOK(
            'Data produsen/distributor telah disimpan',
            ['idProdusenDistributor'  =>  hashidEncode($idProdusenDistributor)]
        );
    }

    private function updateData($idProdusenDistributor)
    {
        $mainOperation  =   new MainOperation();
        $arrUpdateData  =   $this->generateArrayInsertUpdate();
        $procUpdateData =   $mainOperation->updateDataTable('m_produsendistributor', $arrUpdateData, ['IDPRODUSENDISTRIBUTOR' => $idProdusenDistributor]);

        if(!$procUpdateData['status']) return switchMySQLErrorCode($procUpdateData['errCode']);
        return throwResponseOK(
            'Data produsen/distributor telah diperbarui',
            ['idProdusenDistributor'    =>  hashidEncode($idProdusenDistributor)]
        );
    }

    private function parametersValidatorProdusenDistributor($isUpdate = false, $idProdusenDistributor = null)
    {
        $rules  =   [
            'arrIdBarangMerk'           =>  ['label' => 'Daftar Merk', 'rules' => 'required'],
            'arrIdBarangMerk.*'         =>  ['label' => 'Daftar Merk', 'rules' => 'alpha_numeric'],
            'tipeProdusenDistributor'   =>  ['label' => 'Tipe Produsen/Distributor', 'rules' => 'required|in_list[0,1]'],
            'nama'                      =>  ['label' => 'Nama Produsen/Distributor', 'rules' => 'required|regex_match[/^[a-zA-Z0-9\s\.\,\!\?\:\;\-\(\)]+$/]|min_length[3]|max_length[50]'],
            'alamat'                    =>  ['label' => 'Alamat Produsen/Distributor', 'rules' => 'required|regex_match[/^[a-zA-Z0-9\s\.\,\!\?\:\;\-\(\)]+$/]|min_length[10]|max_length[150]'],
            'telpon'                    =>  ['label' => 'Telpon Produsen/Distributor', 'rules' => 'required|regex_match[/^\+?[0-9]{10,13}$/]|min_length[8]|max_length[20]'],
            'catatan'                   =>  ['label' => 'Catatan', 'rules' => 'permit_empty|regex_match[/^[a-zA-Z0-9\s\.\,\!\?\:\;\-\(\)]+$/]|max_length[500]'],
        ];

        $messages   =   [
            'arrIdBarangMerk'   => [
                'required'      => 'Harap pilih minimal satu merk terlebih dahulu'
            ],
            'arrIdBarangMerk.*' => [
                'alpha_numeric' => 'Data kiriman tidak valid, silakan periksa kembali'
            ],
            'tipeProdusenDistributor'   => [
                'in_list'       => 'Harap pilih tipe produsen/distributor yang valid'
            ]
        ];

        if($isUpdate) {
            $rules['nama']['rules']                             .=  '|is_unique[m_produsendistributor.NAMA, IDPRODUSENDISTRIBUTOR, '.$idProdusenDistributor.']';
            $messages['idProdusenDistributor']['required']      =   'Data kiriman tidak lengkap, silakan periksa kembali';
            $messages['idProdusenDistributor']['alpha_numeric'] =   'Data kiriman tidak lengkap, silakan periksa kembali';
        } else {
            $rules['nama']['rules']                             .=  '|is_unique[m_produsendistributor.NAMA]';
        }

        if(!$this->validate($rules, $messages)) return $this->validator->getErrors();
        return true;
    }

    private function generateArrayInsertUpdate(): array
    {
        $arrIdBarangMerk        =   $this->request->getVar('arrIdBarangMerk');
        $tipeProdusenDistributor=   $this->request->getVar('tipeProdusenDistributor');
        $nama                   =   $this->request->getVar('nama');
        $alamat                 =   $this->request->getVar('alamat');
        $telpon                 =   $this->request->getVar('telpon');
        $catatan                =   $this->request->getVar('catatan');

        if(is_array($arrIdBarangMerk) && count($arrIdBarangMerk) > 0) {
            $arrIdBarangMerk    =   array_filter(array_map(function($idBarangMerk) {
                return hashidDecode($idBarangMerk);
            }, $arrIdBarangMerk));
        } else {
            $arrIdBarangMerk    =   [];
        }

        $arrInsertUpdate=   [
            'ARRIDBARANGMERK'           =>  json_encode($arrIdBarangMerk),
            'TIPEPRODUSENDISTRIBUTOR'   =>  $tipeProdusenDistributor,
            'NAMA'                      =>  $nama,
            'ALAMAT'                    =>  $alamat,
            'TELPON'                    =>  $telpon,
            'CATATAN'                   =>  $catatan
        ];

        return $arrInsertUpdate;
    }
}