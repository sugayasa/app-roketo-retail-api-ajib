<?php

namespace App\Controllers\ERP\Stok;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\ERP\Stok\KartuStokModel;
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
            'idGudang'      =>  ['label' => 'Gudang', 'rules' => 'required|alpha_numeric'],
            'idBarangSKU'   =>  ['label' => 'SKU Barang', 'rules' => 'required|alpha_numeric'],
            'tanggalAwal'   =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'  =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'dataPerPage'   =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'    =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idGudang'  =>  [
                'required'      =>  'Harap pilih Gudang terlebih dahulu',
                'alpha_numeric' =>  'Data Gudang yang dipilih tidak valid, silakan periksa kembali'
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

        $idGudang       =   $this->request->getVar('idGudang');
        $idGudang       =   isset($idGudang) && $idGudang != "" ? hashidDecode($idGudang) : 0;
        $idBarangSKU    =   $this->request->getVar('idBarangSKU');
        $idBarangSKU    =   isset($idBarangSKU) && $idBarangSKU != "" ? hashidDecode($idBarangSKU) : 0;
        $tanggalAwal    =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir   =   $this->request->getVar('tanggalAkhir');
        $dataPerPage    =   $this->request->getVar('dataPerPage');
        $pageNumber     =   $this->request->getVar('pageNumber');

        $mainOperation  =   new MainOperation();
        $kartuStokModel =   new KartuStokModel();
        
        $baseData       =   $kartuStokModel->getDetailKartuStokGudang($idGudang, $idBarangSKU, $tanggalAwal, $tanggalAkhir, $dataPerPage, $pageNumber);
        $totalNumberData=   $baseData->countAllResults(false);
        $pageProperty   =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $dataDetailKartuStok=   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $saldoStok          =   $saldoAwal  =   $totalMasuk =   $totalKeluar    =   0;

            foreach($dataDetailKartuStok as $index => $row) {
                if($index == 0) {
                    $tanggalWaktuStokAwal   =   $row->INPUTTANGGALWAKTUDB;
                    $saldoStok              =   $kartuStokModel->getSaldoStokGudangByTanggalWaktu($idGudang, $idBarangSKU, $tanggalWaktuStokAwal);
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
            return throwResponseNotFound('Tidak ada data kartu stok yang ditemukan', $dataReturn);
        }
    }

    public function getDataStokBarangGudangToko()
    {
        $rules  =   [
            'idGudang'      =>  ['label' => 'Gudang', 'rules' => 'permit_empty|alpha_numeric'],
            'idToko'        =>  ['label' => 'Toko', 'rules' => 'permit_empty|alpha_numeric'],
            'tipeGudangToko'=>  ['label' => 'Tipe Stok Opname', 'rules' => 'required|in_list[G,T]'],
        ];

        $messages   =   [
            'idGudang'  =>  [
                'alpha_numeric' =>  'Data Gudang yang dipilih tidak valid, silakan periksa kembali'
            ],
            'idToko'  =>  [
                'alpha_numeric' =>  'Data Toko yang dipilih tidak valid, silakan periksa kembali'
            ],
            'tipeGudangToko'    =>  [
                'in_list'   =>  'Tipe stok opname yang dipilih tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $idGudang       =   $this->request->getVar('idGudang');
        $idGudang       =   isset($idGudang) && $idGudang != "" ? hashidDecode($idGudang) : 0;
        $idToko         =   $this->request->getVar('idToko');
        $idToko         =   isset($idToko) && $idToko != "" ? hashidDecode($idToko) : 0;
        $tipeGudangToko =   $this->request->getVar('tipeGudangToko');

        if($tipeGudangToko == 'G' && $idGudang == 0) {
            return throwResponseNotAcceptable("Harap pilih Gudang terlebih dahulu");
        } else if($tipeGudangToko == 'T' && $idToko == 0) {
            return throwResponseNotAcceptable("Harap pilih Toko terlebih dahulu");
        }

        $kartuStokModel =   new KartuStokModel();
        $dataBarangStok =   $kartuStokModel->getDataStokBarangGudangToko($idGudang, $idToko, $tipeGudangToko);

        return $this->setResponseFormat('json')->respond([
            "listData"      =>  encodeDatabaseObjectResultKey($dataBarangStok, ['IDBARANGSKU', 'IDBARANGSATUAN'])
        ]);
    }

    public function saveWorkOrderStokOpnameGudangToko()
    {
        $rules  =   [
            'idGudang'                      =>  ['label' => 'Gudang', 'rules' => 'permit_empty|alpha_numeric'],
            'idToko'                        =>  ['label' => 'Toko', 'rules' => 'permit_empty|alpha_numeric'],
            'tipeGudangToko'                =>  ['label' => 'Tipe Stok Opname', 'rules' => 'required|in_list[G,T]'],
            'keteranganOpname'              =>  ['label' => 'Keterangan Stok Opname', 'rules' => 'required|regex_match[/^[a-zA-Z0-9 .,!?"()-_@#$%&]+$/]|min_length[10]|max_length[500]'],
            'arrDataBarang.*.idBarangSKU'   =>  ['label' => 'Data Barang SKU', 'rules' => 'required|alpha_numeric'],
            'arrDataBarang.*.idBarangSatuan'=>  ['label' => 'Data Satuan Barang', 'rules' => 'required|alpha_numeric'],
        ];

        $messages   =   [
            'idGudang'  =>  [
                'alpha_numeric' =>  'Data Gudang yang dipilih tidak valid, silakan periksa kembali'
            ],
            'idToko'    =>  [
                'alpha_numeric' =>  'Data Toko yang dipilih tidak valid, silakan periksa kembali'
            ],
            'tipeGudangToko'    =>  [
                'in_list'   =>  'Tipe stok opname yang dipilih tidak valid, silakan periksa kembali'
            ],
            'arrDataBarang.*.idBarangSKU'  =>  [
                'alpha_numeric'   =>  'Data Barang SKU yang dipilih tidak valid, silakan periksa kembali',
            ],
            'arrDataBarang.*.idBarangSatuan'    =>  [
                'alpha_numeric'   =>  'Data Satuan Barang yang dipilih tidak valid, silakan periksa kembali',
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $idGudang           =   $this->request->getVar('idGudang');
        $idGudang           =   isset($idGudang) && $idGudang != "" ? hashidDecode($idGudang) : 0;
        $idToko             =   $this->request->getVar('idToko');
        $idToko             =   isset($idToko) && $idToko != "" ? hashidDecode($idToko) : 0;
        $tipeGudangToko     =   $this->request->getVar('tipeGudangToko');
        $keteranganOpname   =   $this->request->getVar('keteranganOpname');
        $arrDataBarang      =   $this->request->getVar('arrDataBarang');
        $jumlahBarangSKU    =   is_array($arrDataBarang) ? count($arrDataBarang) : 0;

        if($jumlahBarangSKU == 0) return throwResponseNotAcceptable("Harap pilih minimal 1 (satu) barang untuk dibuatkan work order stok opname");
        if($jumlahBarangSKU > MAX_BARANG_SKU_STOK_OPNAME) return throwResponseNotAcceptable("Harap pilih maksimal <b>".MAX_BARANG_SKU_STOK_OPNAME."</b> barang untuk dibuatkan work order stok opname");

        $mainOperation      =   new MainOperation();
        $nomorNotaStokOpname=   $this->generateNotaStokOpnameNomor($tipeGudangToko);
        $arrInsertDataRekap =   [
            'IDGUDANG'          =>  $idGudang,
            'IDTOKO'            =>  $idToko,
            'NOTAOPNAMENOMOR'   =>  $nomorNotaStokOpname,
            'TIPEGUDANGTOKO'    =>  $tipeGudangToko,
            'JUMLAHBARANGSKU'   =>  count($arrDataBarang),
            'KETERANGANOPNAME'  =>  $keteranganOpname,
            'INPUTUSER'         =>  $this->userData->name,
            'INPUTTANGGALWAKTU' =>  $this->currentDateTime
        ];
        $procInsertRekap    =   $mainOperation->insertDataTable('t_stokopnamerekap', $arrInsertDataRekap);

        if(!$procInsertRekap['status']) return switchMySQLErrorCode($procInsertRekap['errCode']);
        $idRekapOpname      =   $procInsertRekap['insertID'];

        foreach($arrDataBarang as $dataBarang) {
            $idBarangSKU    =   isset($dataBarang->idBarangSKU) && $dataBarang->idBarangSKU != "" ? hashidDecode($dataBarang->idBarangSKU) : 0;
            $idBarangSatuan =   isset($dataBarang->idBarangSatuan) && $dataBarang->idBarangSatuan != "" ? hashidDecode($dataBarang->idBarangSatuan) : 0;

            if($idBarangSKU > 0 && $idBarangSatuan > 0) {
                $arrInsertDataBarang    =   [
                    'IDSTOKOPNAMEREKAP' =>  $idRekapOpname,
                    'IDBARANGSKU'       =>  $idBarangSKU,
                    'IDBARANGSATUAN'    =>  $idBarangSatuan
                ];
                $mainOperation->insertDataTable('t_stokopnamebarang', $arrInsertDataBarang);
            }
        }
        
        return throwResponseOK('Work order stok opname gudang/toko berhasil dibuat dengan nomor: '.$nomorNotaStokOpname);
    }

    private function generateNotaStokOpnameNomor($tipeGudangToko){
        return 'NSO'.$tipeGudangToko.'-' . strtoupper(bin2hex(random_bytes(2))) . date('ymd');
    }
}