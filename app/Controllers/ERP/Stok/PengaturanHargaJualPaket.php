<?php

namespace App\Controllers\ERP\Stok;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\ERP\Stok\PengaturanHargaJualPaketModel;
use App\Models\MainOperation;

class PengaturanHargaJualPaket extends ResourceController
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

    public function getListPaket()
    {
        $rules  =   [
            'searchKeyword'     =>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
        ];

        $messages   =   [];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation                  =   new MainOperation();
        $PengaturanHargaJualPaketModel  =   new PengaturanHargaJualPaketModel();
        $searchKeyword                  =   $this->request->getVar('searchKeyword');
        $dataPerPage                    =   $this->request->getVar('dataPerPage');
        $pageNumber                     =   $this->request->getVar('pageNumber');
        $baseData                       =   $PengaturanHargaJualPaketModel->getDataHargaJualPaket($searchKeyword);
        $totalNumberData                =   $baseData->countAllResults(false);
        $pageProperty                   =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $dataHargaJualPaket =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);

            if($dataHargaJualPaket && count($dataHargaJualPaket) > 0) {
                foreach($dataHargaJualPaket as $keyHargaJualPaket){
                    $status                         =   $keyHargaJualPaket->STATUS;
                    $keyHargaJualPaket->STATUSSTR   =   $status == 1 ? 'Aktif' : 'Tidak Aktif';
                }
            }

            $dataHargaJualPaket =   encodeDatabaseObjectResultKey($dataHargaJualPaket, ['IDHARGARETAILPAKET']);

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $dataHargaJualPaket,
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn =   [
                "listData"          =>  [],
                "pageProperty"      =>  $pageProperty
            ];
            return throwResponseNotFound('Data harga jual paket tidak ditemukan', $dataReturn);
        }
    }
}