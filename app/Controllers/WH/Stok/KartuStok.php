<?php

namespace App\Controllers\WH\Stok;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\WH\Stok\KartuStokModel;
use App\Models\MainOperation;

class KartuStok extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    use ResponseTrait;
    protected $userData, $idGudang, $currentDateTime, $request;
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger) {
        parent::initController($request, $response, $logger);

        try {
            $this->userData         =   $request->userData;
            $this->idGudang         =   $this->userData->idGudang;
            $this->currentDateTime  =   $request->currentDateTime;
        } catch (\Throwable $th) {
        }
    }

    public function index()
    {
        return $this->failForbidden('[E-AUTH-000] Forbidden Access');
    }

    public function getDetailKartuStokGudang()
    {
        $rules  =   [
            'idBarangSKU'   =>  ['label' => 'SKU Barang', 'rules' => 'required|alpha_numeric'],
            'tanggalAwal'   =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'  =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'dataPerPage'   =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'    =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idBarangSKU'  =>  [
                'required'      =>  'Harap pilih SKU barang terlebih dahulu',
                'alpha_numeric' =>  'Data SKU barang yang dipilih tidak valid, silakan periksa kembali'
            ],
            'tanggalAwal' => [
                'regex_match'   => 'Tanggal awal periode tidak valid, silakan periksa kembali',
            ],
            'tanggalAkhir'=> [
                'regex_match'   => 'Tanggal akhir periode tidak valid, silakan periksa kembali',
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $idBarangSKU    =   $this->request->getVar('idBarangSKU');
        $idBarangSKU    =   isset($idBarangSKU) && $idBarangSKU != "" ? hashidDecode($idBarangSKU) : 0;
        $tanggalAwal    =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir   =   $this->request->getVar('tanggalAkhir');
        $dataPerPage    =   $this->request->getVar('dataPerPage');
        $pageNumber     =   $this->request->getVar('pageNumber');

        $mainOperation  =   new MainOperation();
        $kartuStokModel =   new KartuStokModel();
        
        $baseData       =   $kartuStokModel->getDetailKartuStokGudang($this->idGudang, $idBarangSKU, $tanggalAwal, $tanggalAkhir, $dataPerPage, $pageNumber);
        $totalNumberData=   $baseData->countAllResults(false);
        $pageProperty   =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $dataDetailKartuStok=   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $saldoStok          =   $saldoAwal  =   $totalMasuk =   $totalKeluar    =   0;

            foreach($dataDetailKartuStok as $index => $row) {
                if($index == 0) {
                    $tanggalWaktuStokAwal   =   $row->INPUTTANGGALWAKTUDB;
                    $saldoStok              =   $kartuStokModel->getSaldoStokGudangByTanggalWaktu($this->idGudang, $idBarangSKU, $tanggalWaktuStokAwal);
                    $dataSaldoAwal          =   [
                        'NOMORNOTA'             =>  '',
                        'INPUTTANGGALWAKTU'     =>  date('d M Y H:i', strtotime($tanggalWaktuStokAwal)),
                        'INPUTUSER'             =>  '',
                        'MUTASIJENIS'           =>  '',
                        'MUTASIKETERANGAN'      =>  'Saldo Awal',
                        'JUMLAHMASUK'           =>  0,
                        'JUMLAHKELUAR'          =>  0,
                        'JUMLAHSALDO'           =>  $saldoStok,
                        'NAMAGUDANGCUSTOMER'    =>  ''
                    ];

                    $saldoAwal  =   $saldoStok;
                    array_unshift($dataDetailKartuStok, $dataSaldoAwal);
                }

                $totalMasuk         +=  intval($row->JUMLAHMASUK);
                $totalKeluar        +=  intval($row->JUMLAHKELUAR);
                $saldoStok          +=  intval($row->JUMLAHMASUK) - intval($row->JUMLAHKELUAR);
                $row->JUMLAHSALDO   =   $saldoStok;
                unset($row->INPUTTANGGALWAKTUDB);
            }

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $dataDetailKartuStok,
                "saldoAwal"     =>  $saldoAwal,
                "totalMasuk"    =>  $totalMasuk,
                "totalKeluar"   =>  $totalKeluar,
                "saldoAkhir"    =>  $saldoStok,
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty
            ];
            return throwResponseNotFound('Tidak ada data kartu stok yang ditemukan', $dataReturn);
        }
    }

    public function getDetailKartuStokToko()
    {
        $rules  =   [
            'idToko'        =>  ['label' => 'Toko', 'rules' => 'required|alpha_numeric'],
            'idBarangSKU'   =>  ['label' => 'SKU Barang', 'rules' => 'required|alpha_numeric'],
            'tanggalAwal'   =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'  =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'dataPerPage'   =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'    =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idToko'  =>  [
                'required'      =>  'Harap pilih Toko terlebih dahulu',
                'alpha_numeric' =>  'Data Toko yang dipilih tidak valid, silakan periksa kembali'
            ],
            'idBarangSKU'  =>  [
                'required'      =>  'Harap pilih SKU barang terlebih dahulu',
                'alpha_numeric' =>  'Data SKU barang yang dipilih tidak valid, silakan periksa kembali'
            ],
            'tanggalAwal' => [
                'regex_match'   => 'Tanggal awal periode tidak valid, silakan periksa kembali',
            ],
            'tanggalAkhir'=> [
                'regex_match'   => 'Tanggal akhir periode tidak valid, silakan periksa kembali',
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $idToko         =   $this->request->getVar('idToko');
        $idToko         =   isset($idToko) && $idToko != "" ? hashidDecode($idToko) : 0;
        $idBarangSKU    =   $this->request->getVar('idBarangSKU');
        $idBarangSKU    =   isset($idBarangSKU) && $idBarangSKU != "" ? hashidDecode($idBarangSKU) : 0;
        $tanggalAwal    =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir   =   $this->request->getVar('tanggalAkhir');
        $dataPerPage    =   $this->request->getVar('dataPerPage');
        $pageNumber     =   $this->request->getVar('pageNumber');

        $mainOperation  =   new MainOperation();
        $kartuStokModel =   new KartuStokModel();
        
        $baseData       =   $kartuStokModel->getDetailKartuStokToko($idToko, $idBarangSKU, $tanggalAwal, $tanggalAkhir, $dataPerPage, $pageNumber);
        $totalNumberData=   $baseData->countAllResults(false);
        $pageProperty   =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $dataDetailKartuStok=   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $saldoStok          =   $saldoAwal  =   $totalMasuk =   $totalKeluar    =   0;

            foreach($dataDetailKartuStok as $index => $row) {
                if($index == 0) {
                    $tanggalWaktuStokAwal   =   $row->INPUTTANGGALWAKTUDB;
                    $saldoStok              =   $kartuStokModel->getSaldoStokTokoByTanggalWaktu($idToko, $idBarangSKU, $tanggalWaktuStokAwal);
                    $dataSaldoAwal          =   [
                        'NOMORNOTA'             =>  '',
                        'INPUTTANGGALWAKTU'     =>  date('d M Y H:i', strtotime($tanggalWaktuStokAwal)),
                        'INPUTUSER'             =>  '',
                        'MUTASIJENIS'           =>  '',
                        'MUTASIKETERANGAN'      =>  'Saldo Awal',
                        'JUMLAHMASUK'           =>  0,
                        'JUMLAHKELUAR'          =>  0,
                        'JUMLAHSALDO'           =>  $saldoStok,
                        'NAMAGUDANGCUSTOMER'    =>  ''
                    ];

                    $saldoAwal  =   $saldoStok;
                    array_unshift($dataDetailKartuStok, $dataSaldoAwal);
                }

                $totalMasuk         +=  intval($row->JUMLAHMASUK);
                $totalKeluar        +=  intval($row->JUMLAHKELUAR);
                $saldoStok          +=  intval($row->JUMLAHMASUK) - intval($row->JUMLAHKELUAR);
                $row->JUMLAHSALDO   =   $saldoStok;
                unset($row->INPUTTANGGALWAKTUDB);
            }

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $dataDetailKartuStok,
                "saldoAwal"     =>  $saldoAwal,
                "totalMasuk"    =>  $totalMasuk,
                "totalKeluar"   =>  $totalKeluar,
                "saldoAkhir"    =>  $saldoStok,
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty
            ];
            return throwResponseNotFound('Tidak ada data mutasi stok yang ditemukan', $dataReturn);
        }
    }
}