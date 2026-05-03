<?php

namespace App\Controllers\POS\Stok;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\POS\Stok\StokBarangModel;
use App\Models\MainOperation;
use App\Models\ERP\Master\BarangSKUModel;

class StokBarang extends ResourceController
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
        $this->request = $request;

        try {
            $this->userData         =   $request->userData;
            $this->idToko           =   $this->userData->idToko;
            $this->currentDateTime  =   $request->currentDateTime;
        } catch (\Throwable $th) {
        }
    }

    public function index()
    {
        return $this->failForbidden('[E-AUTH-000] Forbidden Access');
    }

    public function getDaftarStokBarang()
    {
        $rules  =   [
            'idBarangKategori'  =>  ['label' => 'Kategori Barang', 'rules' => 'permit_empty|alpha_numeric'],
            'idBarangMerk'      =>  ['label' => 'Merk Barang', 'rules' => 'permit_empty|alpha_numeric'],
            'jenisStok'         =>  ['label' => 'Jenis Stok', 'rules' => 'required|in_list[ALL,LDN,SDN]'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'urutan'            =>  ['label' => 'Urutan', 'rules' => 'required|in_list[AZ,ZA,ASC,DESC]'],
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idBarangKategori'  =>  [
                'alpha_numeric' =>  'Data kategori barang tidak valid, silakan periksa kembali'
            ],
            'idBarangMerk'     =>  [
                'alpha_numeric' =>  'Data merk barang tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $idBarangKategori   =   $this->request->getVar('idBarangKategori');
        $idBarangKategori   =   isset($idBarangKategori) && $idBarangKategori != "" ? hashidDecode($idBarangKategori) : 0;
        $idBarangMerk       =   $this->request->getVar('idBarangMerk');
        $idBarangMerk       =   isset($idBarangMerk) && $idBarangMerk != "" ? hashidDecode($idBarangMerk) : 0;
        $jenisStok          =   $this->request->getVar('jenisStok');
        $kataKunciPencarian =   $this->request->getVar('kataKunciPencarian');
        $urutan             =   $this->request->getVar('urutan');
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $pageNumber         =   $this->request->getVar('pageNumber');

        $mainOperation      =   new MainOperation();
        $stokBarangModel    =   new StokBarangModel();

        $arrIdKategoriBarang=   $mainOperation->getArrIdKategoriBarangToko($this->idToko);
        $baseData           =   $stokBarangModel->getDaftarStokBarang($this->idToko, $arrIdKategoriBarang, $idBarangKategori, $idBarangMerk, $jenisStok, $kataKunciPencarian, $urutan);
        $totalNumberData    =   $baseData->countAllResults(false);
        $pageProperty       =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $dataStokBarang =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $barangSKUModel =   new BarangSKUModel();

            foreach($dataStokBarang as $keyBarangStok){
                $idBarangSKU                    =   isset($keyBarangStok->IDBARANGSKU) && $keyBarangStok->IDBARANGSKU != "" ? $keyBarangStok->IDBARANGSKU : 0;
                $keyBarangStok->ATRIBUTSKUSTR   =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                unset($keyBarangStok->IDBARANGSKU);
            }

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $dataStokBarang,
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty
            ];
            return throwResponseNotFound('Tidak ada data stok barang yang ditemukan', $dataReturn);
        }
    }
}