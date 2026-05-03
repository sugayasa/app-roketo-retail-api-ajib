<?php

namespace App\Controllers\POS\Laporan;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\PrintOut;
use App\Libraries\SpreadsheetGenerator;
use App\Models\POS\Laporan\PenjualanModel;
use App\Models\POS\DashboardModel;
use App\Models\ERP\Master\BarangSKUModel;
use App\Models\ERP\Master\DataDasarBarang\KategoriModel;
use App\Models\MainOperation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use CodeIgniter\I18n\Time;

class Penjualan extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    use ResponseTrait;
    protected $userData, $idToko, $currentDateTime, $request;
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        try {
            $this->userData         =   $request->userData;
            $this->currentDateTime  =   $request->currentDateTime;
            $this->idToko           =   $this->userData->idToko;
        } catch (\Throwable $th) {
        }
    }

    public function index()
    {
        return $this->failForbidden('[E-AUTH-000] Forbidden Access');
    }

    public function getLaporanRekapPerTanggal()
    {
        $rules  =   [
            'tanggalAwal'   =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'  =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'dataPerPage'   =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'    =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'tanggalAwal' => [
                'regex_match'   => 'Tanggal awal periode tidak valid, silakan periksa kembali',
            ],
            'tanggalAkhir'=> [
                'regex_match'   => 'Tanggal akhir periode tidak valid, silakan periksa kembali',
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $penjualanModel     =   new PenjualanModel();
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $pageNumber         =   $this->request->getVar('pageNumber');
        $tanggalAwal        =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir       =   $this->request->getVar('tanggalAkhir');
        $tanggalAwalDT      =   new \DateTime($tanggalAwal);
        $tanggalAkhirDT     =   new \DateTime($tanggalAkhir);
        $tanggalInterval    =   $tanggalAwalDT->diff($tanggalAkhirDT);
        $jumlahHari         =   intval($tanggalInterval->days) + 1;
        
        if($jumlahHari > 31) return throwResponseNotAcceptable('Rentang tanggal maksimal adalah 31 hari');

        //DATA GRAPH
        $dashboardModel     =   new DashboardModel();
        $kategoriModel      =   new KategoriModel();
        $mainOperation      =   new MainOperation();
        $dataKategori       =   $kategoriModel->select('NAMAKATEGORI')->where('STATUS', 1)->asObject()->findAll();
        $dataGraphPenjualan =   $dashboardModel->getDataPenjualanPerTanggal($this->idToko, $tanggalAwal, $tanggalAkhir);
        $arrKategori        =   $dataGraph  =   $dataRekap  =   [];        

        if(!empty($dataKategori)){
            foreach($dataKategori as $kategori){
                $arrKategori[$kategori->NAMAKATEGORI]   =   0;
            }
        }

        $urutanTanggal      =   1;
        $urutanTanggalMin   =   $pageNumber * $dataPerPage - $dataPerPage + 1;
        $urutanTanggalMax   =   $pageNumber * $dataPerPage;
        while ($tanggalAwalDT <= $tanggalAkhirDT) {
            $dataGraph[]    =   array_merge([
                'tanggal'   =>    $tanggalAwalDT->format('d M')
            ], $arrKategori);

            if($urutanTanggal >= $urutanTanggalMin && $urutanTanggal <= $urutanTanggalMax){
                $dataRekap[]    =   [
                    'TANGGALTRANSAKSI'     =>    $tanggalAwalDT->format('d M Y'),
                    'TOTALNOTA'            =>    0,
                    'TOTALJENISBARANGSKU'  =>    0,
                    'TOTALITEM'            =>    0,
                    'TOTALHARGABARANG'     =>    0,
                    'TOTALHARGADISKON'     =>    0,
                    'TOTALHARGALAIN'       =>    0,
                    'TOTALHARGAAKHIR'      =>    0
                ];
            }

            $tanggalAwalDT->modify('+1 day');
            $urutanTanggal++;
        }
        
        $totalTransaksi =   $totalNominal   =   $totalItem =   0;
        foreach($dataGraphPenjualan as $keyGraphPenjualan){
            $tanggalGraph   =   $keyGraphPenjualan->TANGGALDM;
            $namaKategoriDB =   $keyGraphPenjualan->NAMAKATEGORI;
            $indexDataGraph =   array_search($tanggalGraph, array_column($dataGraph, 'tanggal'));

            if($indexDataGraph && isset($dataGraph[$indexDataGraph])){
                if(isset($dataGraph[$indexDataGraph][$namaKategoriDB])){
                    $dataGraph[$indexDataGraph][$namaKategoriDB]    =   intval($keyGraphPenjualan->TOTALPENJUALAN);
                    $totalTransaksi                                 +=  intval($keyGraphPenjualan->JUMLAHTRANSAKSI);
                    $totalItem                                      +=  intval($keyGraphPenjualan->JUMLAHITEM);
                    $totalNominal                                   +=  intval($keyGraphPenjualan->TOTALPENJUALAN);
                }
            }
        }

        //DATA TABLE REKAP PER TANGGAL
        $baseData           =   $penjualanModel->getDataLaporanRekapPerTanggal($this->idToko, $tanggalAwal, $tanggalAkhir);
        $totalNumberData    =   $baseData->countAllResults(false);
        $pageProperty       =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $jumlahHari);
        
        if($totalNumberData > 0) {
            $dataLaporanRekapPerTanggal    =   $baseData->asObject()->findAll(99999, 0);
            foreach($dataLaporanRekapPerTanggal as $keyLaporanRekapPerTanggal){
                $tanggalLaporan  =   date('d M Y', strtotime($keyLaporanRekapPerTanggal->TANGGALTRANSAKSI));
                foreach($dataRekap as $index => $data){
                    log_message('debug', $data['TANGGALTRANSAKSI'].' == '.$tanggalLaporan);
                    if($data['TANGGALTRANSAKSI'] == $tanggalLaporan){
                        $dataRekap[$index]  =   [
                            'TANGGALTRANSAKSI'     =>    $tanggalLaporan,
                            'TOTALNOTA'            =>    intval($keyLaporanRekapPerTanggal->TOTALNOTA),
                            'TOTALJENISBARANGSKU'  =>    intval($keyLaporanRekapPerTanggal->TOTALJENISBARANGSKU),
                            'TOTALITEM'            =>    intval($keyLaporanRekapPerTanggal->TOTALITEM),
                            'TOTALHARGABARANG'     =>    intval($keyLaporanRekapPerTanggal->TOTALHARGABARANG),
                            'TOTALHARGADISKON'     =>    intval($keyLaporanRekapPerTanggal->TOTALHARGADISKON),
                            'TOTALHARGALAIN'       =>    intval($keyLaporanRekapPerTanggal->TOTALHARGALAIN),
                            'TOTALHARGAAKHIR'      =>    intval($keyLaporanRekapPerTanggal->TOTALHARGAAKHIR)
                        ];
                        break;
                    }
                }
            }
        }

        $arrParameters  =   [
            'idToko'        =>  $this->idToko,
            'tanggalAwal'   =>  $tanggalAwal,
            'tanggalAkhir'  =>  $tanggalAkhir
        ];
        $arrParametersEncode    =   encodeJWTToken($arrParameters);
        $urlExcelRekapPerTanggal=   base_url(URL_EXCEL_REKAP_PENJUALAN_RETAIL_PER_TANGGAL).$arrParametersEncode;

        return $this->setResponseFormat('json')->respond([
            "totalTransaksi"=>  $totalTransaksi,
            "totalItem"     =>  $totalItem,
            "totalNominal"  =>  $totalNominal,
            "dataGraph"     =>  $dataGraph,
            "dataRekap"     =>  $dataRekap,
            "pageProperty"  =>  $pageProperty,
            "urlExcel"      =>  $urlExcelRekapPerTanggal
        ]);
    }

    public function getLaporanRekapPerNota()
    {
        $rules  =   [
            'tanggalAwal'       =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'      =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'idMetodeBayar'     =>  ['label' => 'Metode Pembayaran', 'rules' => 'permit_empty|alpha_numeric'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'tanggalAwal' => [
                'regex_match'   => 'Tanggal awal periode tidak valid, silakan periksa kembali',
            ],
            'tanggalAkhir' => [
                'regex_match'   => 'Tanggal akhir periode tidak valid, silakan periksa kembali',
            ],
            'idMetodeBayar' => [
                'alpha_numeric' => 'Data Metode Pembayaran yang dipilih tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());
        
        $penjualanModel     =   new PenjualanModel();
        $mainOperation      =   new MainOperation();
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $pageNumber         =   $this->request->getVar('pageNumber');
        $tanggalAwal        =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir       =   $this->request->getVar('tanggalAkhir');
        $idMetodeBayar      =   $this->request->getVar('idMetodeBayar');
        $kataKunciPencarian =   $this->request->getVar('kataKunciPencarian');
        $idMetodeBayar      =   isset($idMetodeBayar) && $idMetodeBayar != "" ? hashidDecode($idMetodeBayar) : 0;
        $baseData           =   $penjualanModel->getDataLaporanRekapPerNota($this->idToko, $tanggalAwal, $tanggalAkhir, $idMetodeBayar, $kataKunciPencarian);
        $totalNumberData    =   $baseData->countAllResults(false);
        $pageProperty       =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0) {
            $listData       =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            foreach($listData as $keyData){
                $arrParameters  =   [
                    'idPenjualanRekap'  =>  $keyData->IDPENJUALANREKAP
                ];
                $arrParametersEncode            =   encodeJWTToken($arrParameters);
                $urlprintNotaPenjualanRetail    =   base_url(URL_PRINT_NOTA_PENJUALAN_RETAIL).$arrParametersEncode;
                $keyData->URLPRINTNOTAPENJUALAN =   $urlprintNotaPenjualanRetail;
                unset($keyData->IDPENJUALANREKAP);
            }

            $arrParameters  =   [
                'idToko'            =>  $this->idToko,
                'idMetodeBayar'     =>  $idMetodeBayar,
                'tanggalAwal'       =>  $tanggalAwal,
                'tanggalAkhir'      =>  $tanggalAkhir,
                'kataKunciPencarian'=>  $kataKunciPencarian
            ];
            $arrParametersEncode    =   encodeJWTToken($arrParameters);
            $urlExcelRekapPerNota   =   base_url(URL_EXCEL_REKAP_PENJUALAN_RETAIL_PER_NOTA).$arrParametersEncode;

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $listData,
                "pageProperty"  =>  $pageProperty,
                "urlExcel"      =>  $urlExcelRekapPerNota
            ]);
        } else {
            $dataReturn     =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty,
                "urlExcel"      =>  ""
            ];
            return throwResponseNotFound('Tidak ada data rekap penjualan yang ditemukan', $dataReturn);
        }
    }

    public function getLaporanDetailPerNota()
    {
        $rules  =   [
            'tanggalAwal'       =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'      =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'tanggalAwal' => [
                'regex_match'   => 'Tanggal awal periode tidak valid, silakan periksa kembali',
            ],
            'tanggalAkhir' => [
                'regex_match'   => 'Tanggal akhir periode tidak valid, silakan periksa kembali',
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());
        
        $penjualanModel     =   new PenjualanModel();
        $mainOperation      =   new MainOperation();
        $tanggalAwal        =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir       =   $this->request->getVar('tanggalAkhir');
        $kataKunciPencarian =   $this->request->getVar('kataKunciPencarian');
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $pageNumber         =   $this->request->getVar('pageNumber');
        $baseData           =   $penjualanModel->getDataLaporanDetailPerNota($this->idToko, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
        $totalNumberData    =   $baseData->countAllResults(false);
        $pageProperty       =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){ 
            $listData       =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);

            foreach($listData as $keyData){
                $idPenjualanRekap   =   isset($keyData->IDPENJUALANREKAP) && $keyData->IDPENJUALANREKAP != "" ? $keyData->IDPENJUALANREKAP : 0;
                $dataBarangSKU      =   $penjualanModel->getDataBarangSKUPenjualan($idPenjualanRekap);

                $keyData->DAFTARBARANGSKU    =   $dataBarangSKU;
                unset($keyData->IDPENJUALANREKAP);
            }

            $arrParameters  =   [
                'idToko'            =>  $this->idToko,
                'tanggalAwal'       =>  $tanggalAwal,
                'tanggalAkhir'      =>  $tanggalAkhir,
                'kataKunciPencarian'=>  $kataKunciPencarian
            ];
            $arrParametersEncode    =   encodeJWTToken($arrParameters);
            $urlExcelDetailPerNota  =   base_url(URL_EXCEL_DETAIL_PENJUALAN_RETAIL_PER_NOTA).$arrParametersEncode;

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $listData,
                "pageProperty"  =>  $pageProperty,
                "urlExcel"      =>  $urlExcelDetailPerNota
            ]);
        } else {
            $dataReturn     =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty,
                "urlExcel"      =>  ""
            ];
            return throwResponseNotFound('Tidak ada data yang ditemukan', $dataReturn);
        }
    }

    public function getLaporanRekapPerBarang()
    {
        $rules  =   [
            'idBarangKategori'  =>  ['label' => 'Kategori Barang', 'rules' => 'permit_empty|alpha_numeric'],
            'idBarangMerk'      =>  ['label' => 'Merk Barang', 'rules' => 'permit_empty|alpha_numeric'],
            'tanggalAwal'       =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'      =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idBarangKategori'  =>  [
                'alpha_numeric' =>  'Data kategori barang tidak valid, silakan periksa kembali'
            ],
            'idBarangMerk'     =>  [
                'alpha_numeric' =>  'Data merk barang tidak valid, silakan periksa kembali'
            ],
            'tanggalAwal' => [
                'regex_match'   => 'Tanggal awal periode tidak valid, silakan periksa kembali',
            ],
            'tanggalAkhir' => [
                'regex_match'   => 'Tanggal akhir periode tidak valid, silakan periksa kembali',
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());
        
        $penjualanModel     =   new PenjualanModel();
        $mainOperation      =   new MainOperation();
        $idBarangKategori   =   $this->request->getVar('idBarangKategori');
        $idBarangKategori   =   isset($idBarangKategori) && $idBarangKategori != "" ? hashidDecode($idBarangKategori) : 0;
        $idBarangMerk       =   $this->request->getVar('idBarangMerk');
        $idBarangMerk       =   isset($idBarangMerk) && $idBarangMerk != "" ? hashidDecode($idBarangMerk) : 0;
        $tanggalAwal        =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir       =   $this->request->getVar('tanggalAkhir');
        $kataKunciPencarian =   $this->request->getVar('kataKunciPencarian');
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $pageNumber         =   $this->request->getVar('pageNumber');
        $baseData           =   $penjualanModel->getDataLaporanRekapPerBarang($this->idToko, $idBarangKategori, $idBarangMerk, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
        $totalNumberData    =   $baseData->countAllResults(false);
        $pageProperty       =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){ 
            $listData       =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);

            $arrParameters  =   [
                'idToko'            =>  $this->idToko,
                'idBarangKategori'  =>  $idBarangKategori,
                'idBarangMerk'      =>  $idBarangMerk,
                'tanggalAwal'       =>  $tanggalAwal,
                'tanggalAkhir'      =>  $tanggalAkhir,
                'kataKunciPencarian'=>  $kataKunciPencarian
            ];
            $arrParametersEncode    =   encodeJWTToken($arrParameters);
            $urlExcelRekapPerBarang =   base_url(URL_EXCEL_REKAP_PENJUALAN_RETAIL_PER_BARANG).$arrParametersEncode;

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $listData,
                "pageProperty"  =>  $pageProperty,
                "urlExcel"      =>  $urlExcelRekapPerBarang
            ]);
        } else {
            $dataReturn     =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty,
                "urlExcel"      =>  ""
            ];
            return throwResponseNotFound('Tidak ada data yang ditemukan', $dataReturn);
        }
    }

    public function getLaporanDetailPerBarang()
    {
        $rules  =   [
            'tanggalAwal'       =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'      =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'tanggalAwal' => [
                'regex_match'   => 'Tanggal awal periode tidak valid, silakan periksa kembali',
            ],
            'tanggalAkhir' => [
                'regex_match'   => 'Tanggal akhir periode tidak valid, silakan periksa kembali',
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());
        
        $penjualanModel     =   new PenjualanModel();
        $mainOperation      =   new MainOperation();
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $pageNumber         =   $this->request->getVar('pageNumber');
        $tanggalAwal        =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir       =   $this->request->getVar('tanggalAkhir');
        $kataKunciPencarian =   $this->request->getVar('kataKunciPencarian');
        $baseData           =   $penjualanModel->getDataLaporanDetailPerBarang($this->idToko, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
        $totalNumberData    =   $baseData->countAllResults(false);
        $pageProperty       =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){ 
            $listData       =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $barangSKUModel =   new BarangSKUModel();

            foreach($listData as $keyData){
                $idBarangSKU            =   isset($keyData->IDBARANGSKU) && $keyData->IDBARANGSKU != "" ? $keyData->IDBARANGSKU : 0;
                $keyData->ATRIBUTSKUSTR =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                unset($keyData->IDBARANGSKU);
            }

            $arrParameters  =   [
                'idToko'            =>  $this->idToko,
                'tanggalAwal'       =>  $tanggalAwal,
                'tanggalAkhir'      =>  $tanggalAkhir,
                'kataKunciPencarian'=>  $kataKunciPencarian
            ];
            $arrParametersEncode    =   encodeJWTToken($arrParameters);
            $urlExcelDetailPerBarang=   base_url(URL_EXCEL_DETAIL_PENJUALAN_RETAIL_PER_BARANG).$arrParametersEncode;

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $listData,
                "pageProperty"  =>  $pageProperty,
                "urlExcel"      =>  $urlExcelDetailPerBarang
            ]);
        } else {
            $dataReturn     =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty,
                "urlExcel"      =>  ""
            ];
            return throwResponseNotFound('Tidak ada data yang ditemukan', $dataReturn);
        }
    }

    public function printNotaPenjualanRetail($arrParametersEncode = null)
    {
        if(!isset($arrParametersEncode) || $arrParametersEncode == "") return $this->failForbidden('[E-AUTH-000] Forbidden Access');
        try {
            helper(['firebaseJWT']);
            $arrParameters      =   (array) decodeJWTToken($arrParametersEncode);
            if(!isset($arrParameters['idPenjualanRekap'])) return $this->failForbidden('[E-AUTH-000] Forbidden Access');
            
            $penjualanModel     =   new PenjualanModel();
            $idPenjualanRekap   =   $arrParameters['idPenjualanRekap'];
            $detailNotaPenjualan=   $penjualanModel->getDetailNotaPenjualan($idPenjualanRekap);
            
            if(!$detailNotaPenjualan) return $this->failForbidden('[E-AUTH-001] Detail nota penjualan tidak ditemukan');

            $printOut           =   new PrintOut();
            $daftarBarangNota   =   $penjualanModel->getDaftarBarangNota($idPenjualanRekap);
            $daftarBiayaLain    =   $penjualanModel->getDaftarBiayaLain($idPenjualanRekap);
            $daftarHargaPaket   =   $penjualanModel->getDaftarHargaPaket($idPenjualanRekap);
            $daftarDiskonEvent  =   $penjualanModel->getDaftarDiskonEventPenjualan($idPenjualanRekap);
            $dataPrintNota      =   $printOut->generatePrintOutNotaRetail($detailNotaPenjualan, $daftarBarangNota, $daftarBiayaLain, $daftarHargaPaket, $daftarDiskonEvent, false);

            return throwResponseOK(
                'Print nota penjualan retail',
                ['dataPrintNota'     =>  explode("\n", $dataPrintNota)]
            );
        } catch (\Throwable $th) {
            return $this->failForbidden('[E-AUTH-001] Internal server error');
        }
    }

    public function excelRekapPerTanggal($arrParametersEncode = null)
    {
        if(!isset($arrParametersEncode) || $arrParametersEncode == "") return $this->failForbidden('[E-AUTH-000] Forbidden Access');

        try {
            helper(['firebaseJWT']);
            $arrParameters  =   (array) decodeJWTToken($arrParametersEncode);
            if(!isset($arrParameters['tanggalAwal']) || !isset($arrParameters['tanggalAkhir'])) return $this->failForbidden('[E-AUTH-000] Forbidden Access');

            $mainOperation  =   new MainOperation();
            $penjualanModel =   new PenjualanModel();

            $idToko         =   $arrParameters['idToko'];
            $tanggalAwal    =   $arrParameters['tanggalAwal'];
            $tanggalAwalDT  =   Time::parse($tanggalAwal);
            $tanggalAwalStr =   $tanggalAwalDT->format('d M Y');
            $tanggalAkhir   =   $arrParameters['tanggalAkhir'];
            $tanggalAkhirDT =   Time::parse($tanggalAkhir);
            $tanggalAkhirStr=   $tanggalAkhirDT->format('d M Y');
            $baseData       =   $penjualanModel->getDataLaporanRekapPerTanggal($idToko, $tanggalAwal, $tanggalAkhir);
            $dataLaporan    =   $baseData->asObject()->findAll(99999, 0);

            $spreadsheet            =   new Spreadsheet();
            $spreadsheetGenerator   =   new SpreadsheetGenerator();
            $activeWorksheet        =   $spreadsheet->getActiveSheet();

            $arrTitleData   =   ['Laporan Rekap Penjualan Retail Per Tanggal'];
            $arrFilterData  =   [
                ['Toko', $mainOperation->getDetailToko($idToko)['NAMA']],
                ['Periode', $tanggalAwalStr.' s/d '.$tanggalAkhirStr],
                ['Waktu Proses', date('d M Y H:i')]
            ];
            $arrHeaderData  =   [
                [['A'], 2, 'Tanggal', 18, 'center'],
                [['B', 'D'], 1, 'Detail Jumlah', false, 'center'],
                [['B'], 1, 'Nota', 12, 'right', 1],
                [['C'], 1, 'SKU', 12, 'right', 1],
                [['D'], 1, 'Item', 12, 'right', 1],
                [['E', 'H'], 1, 'Detail Harga', false, 'center'],
                [['E'], 1, 'Barang', 12, 'right', 1],
                [['F'], 1, 'Diskon', 12, 'right', 1],
                [['G'], 1, 'Biaya Lain', 12, 'right', 1],
                [['H'], 1, 'Total', 12, 'right', 1]
            ];

            $rowStartDocument       =   1;
            $documentProperties     =   $spreadsheetGenerator->getDocumentProperties($rowStartDocument, $arrTitleData, $arrFilterData, $arrHeaderData);
            $rowStartFilter         =   $documentProperties['rowStartFilter'];
            $rowStartTableHeader    =   $documentProperties['rowStartTableHeader'];
            $rowNumberTableContent  =   $documentProperties['rowNumberTableContent'];
            $rowFirstTable          =   $documentProperties['rowFirstTable'];
            $columnFirstTable       =   $arrHeaderData[0][0][0];
            $columnLastTable        =   end(end($arrHeaderData)[0]);

            $spreadsheetGenerator->setDocumentTitle($activeWorksheet, $arrTitleData, $columnFirstTable, $columnLastTable, $rowStartDocument);
            $spreadsheetGenerator->setDocumentFilter($activeWorksheet, $arrFilterData, $columnLastTable, $rowStartFilter);
            $spreadsheetGenerator->setDocumentTableHeader($activeWorksheet, $arrHeaderData, $rowStartTableHeader);

            if(isset($dataLaporan) && !empty($dataLaporan)){
                foreach($dataLaporan as $keyDataLaporan){
                    $activeWorksheet
                    ->setCellValue('A'.$rowNumberTableContent, $keyDataLaporan->TANGGALTRANSAKSISTR)
                    ->setCellValue('B'.$rowNumberTableContent, intval($keyDataLaporan->TOTALNOTA))
                    ->setCellValue('C'.$rowNumberTableContent, intval($keyDataLaporan->TOTALJENISBARANGSKU))
                    ->setCellValue('D'.$rowNumberTableContent, intval($keyDataLaporan->TOTALITEM))
                    ->setCellValue('E'.$rowNumberTableContent, intval($keyDataLaporan->TOTALHARGABARANG))
                    ->setCellValue('F'.$rowNumberTableContent, intval($keyDataLaporan->TOTALHARGADISKON))
                    ->setCellValue('G'.$rowNumberTableContent, intval($keyDataLaporan->TOTALHARGALAIN))
                    ->setCellValue('H'.$rowNumberTableContent, intval($keyDataLaporan->TOTALHARGAAKHIR));

                    $activeWorksheet->getStyle($columnFirstTable.$rowNumberTableContent)->getAlignment()->setHorizontal('center');
                    $rowNumberTableContent++;
                }
            } else {
                $activeWorksheet->setCellValue($columnFirstTable.$rowNumberTableContent, 'Tidak ada data rekap penjualan retail per tanggal yang ditemukan pada periode tersebut');
                $activeWorksheet->mergeCells($columnFirstTable.$rowNumberTableContent.':'.$columnLastTable.$rowNumberTableContent);
            }
		
            $spreadsheetGenerator->setDocumentTableStyle($activeWorksheet, $columnFirstTable, $columnLastTable, $rowFirstTable, $rowNumberTableContent);
            $spreadsheetGenerator->setDocumentPassword($spreadsheet, $activeWorksheet);
            $spreadsheetGenerator->writeDocumentOutput($spreadsheet, 'Laporan_Penjualan_Rekap_Per_Tanggal');
        } catch (\Throwable $th) {
            return throwResponseInternalServerError('[E-AUTH-001] Internal server error', [$th]);
        }
    }

    public function excelRekapPerNota($arrParametersEncode = null)
    {
        if(!isset($arrParametersEncode) || $arrParametersEncode == "") return $this->failForbidden('[E-AUTH-000] Forbidden Access');

        try {
            helper(['firebaseJWT']);
            $arrParameters  =   (array) decodeJWTToken($arrParametersEncode);
            if(!isset($arrParameters['tanggalAwal']) || !isset($arrParameters['tanggalAkhir'])) return $this->failForbidden('[E-AUTH-000] Forbidden Access');

            $mainOperation  =   new MainOperation();
            $penjualanModel =   new PenjualanModel();

            $idToko             =   $arrParameters['idToko'];
            $idMetodeBayar      =   $arrParameters['idMetodeBayar'];
            $tanggalAwal        =   $arrParameters['tanggalAwal'];
            $tanggalAwalDT      =   Time::parse($tanggalAwal);
            $tanggalAwalStr     =   $tanggalAwalDT->format('d M Y');
            $tanggalAkhir       =   $arrParameters['tanggalAkhir'];
            $tanggalAkhirDT     =   Time::parse($tanggalAkhir);
            $tanggalAkhirStr    =   $tanggalAkhirDT->format('d M Y');
            $kataKunciPencarian =   $arrParameters['kataKunciPencarian'];
            $metodeBayarStr     =   $idMetodeBayar == "" || $idMetodeBayar == 0 ? 'Semua Metode Bayar' : $mainOperation->getDetailMetodeBayar($idMetodeBayar)['METODEBAYAR'];
            $baseData           =   $penjualanModel->getDataLaporanRekapPerNota($idToko, $tanggalAwal, $tanggalAkhir, $idMetodeBayar, $kataKunciPencarian);
            $dataLaporan        =   $baseData->asObject()->findAll(99999, 0);

            $spreadsheet            =   new Spreadsheet();
            $spreadsheetGenerator   =   new SpreadsheetGenerator();
            $activeWorksheet        =   $spreadsheet->getActiveSheet();

            $arrTitleData   =   ['Laporan Rekap Penjualan Retail Per Nota'];
            $arrFilterData  =   [
                ['Toko', $mainOperation->getDetailToko($idToko)['NAMA']],
                ['Metode Bayar', $metodeBayarStr],
                ['Periode', $tanggalAwalStr.' s/d '.$tanggalAkhirStr],
                ['Kata Pencarian', $kataKunciPencarian == "" ? '-' : $kataKunciPencarian],
                ['Waktu Proses', date('d M Y H:i')]
            ];
            $arrHeaderData  =   [
                [['A'], 2, 'Nomor Nota', 18, 'center'],
                [['B', 'D'], 1, 'Detail Customer', false, 'center'],
                [['B'], 1, 'Nama', 18, 'left', 1],
                [['C'], 1, 'Alamat', 22, 'left', 1],
                [['D'], 1, 'Telpon', 14, 'left', 1],
                [['E'], 2, 'Metode Bayar', 14, 'center'],
                [['F'], 2, 'Catatan', 25, 'center'],
                [['G'], 2, 'Jumlah SKU', 12, 'right'],
                [['H'], 2, 'Harga Barang', 12, 'right'],
                [['I'], 2, 'Diskon', 12, 'right'],
                [['J'], 2, 'Biaya Lain', 12, 'right'],
                [['K'], 2, 'Harga Akhir', 12, 'right']
            ];

            $rowStartDocument       =   1;
            $documentProperties     =   $spreadsheetGenerator->getDocumentProperties($rowStartDocument, $arrTitleData, $arrFilterData, $arrHeaderData);
            $rowStartFilter         =   $documentProperties['rowStartFilter'];
            $rowStartTableHeader    =   $documentProperties['rowStartTableHeader'];
            $rowNumberTableContent  =   $documentProperties['rowNumberTableContent'];
            $rowFirstTable          =   $documentProperties['rowFirstTable'];
            $columnFirstTable       =   $arrHeaderData[0][0][0];
            $columnLastTable        =   end(end($arrHeaderData)[0]);

            $spreadsheetGenerator->setDocumentTitle($activeWorksheet, $arrTitleData, $columnFirstTable, $columnLastTable, $rowStartDocument);
            $spreadsheetGenerator->setDocumentFilter($activeWorksheet, $arrFilterData, $columnLastTable, $rowStartFilter);
            $spreadsheetGenerator->setDocumentTableHeader($activeWorksheet, $arrHeaderData, $rowStartTableHeader);

            if(isset($dataLaporan) && !empty($dataLaporan)){
                foreach($dataLaporan as $keyDataLaporan){
                    $activeWorksheet
                    ->setCellValue('A'.$rowNumberTableContent, $keyDataLaporan->NOTAPENJUALANNOMOR)
                    ->setCellValue('B'.$rowNumberTableContent, $keyDataLaporan->CUSTOMERNAMA)
                    ->setCellValue('C'.$rowNumberTableContent, $keyDataLaporan->CUSTOMERALAMAT)
                    ->setCellValue('D'.$rowNumberTableContent, $keyDataLaporan->CUSTOMERTELPON)
                    ->setCellValue('E'.$rowNumberTableContent, $keyDataLaporan->METODEBAYAR)
                    ->setCellValue('F'.$rowNumberTableContent, $keyDataLaporan->CATATAN)
                    ->setCellValue('G'.$rowNumberTableContent, intval($keyDataLaporan->TOTALJENISBARANGSKU))
                    ->setCellValue('H'.$rowNumberTableContent, intval($keyDataLaporan->TOTALHARGABARANG))
                    ->setCellValue('I'.$rowNumberTableContent, intval($keyDataLaporan->TOTALHARGADISKON))
                    ->setCellValue('J'.$rowNumberTableContent, intval($keyDataLaporan->TOTALHARGALAIN))
                    ->setCellValue('K'.$rowNumberTableContent, intval($keyDataLaporan->TOTALHARGAAKHIR));
                    $rowNumberTableContent++;
                }
            } else {
                $activeWorksheet->setCellValue($columnFirstTable.$rowNumberTableContent, 'Tidak ada data rekap penjualan retail per nota yang ditemukan pada periode tersebut');
                $activeWorksheet->mergeCells($columnFirstTable.$rowNumberTableContent.':'.$columnLastTable.$rowNumberTableContent);
            }
		
            $spreadsheetGenerator->setDocumentTableStyle($activeWorksheet, $columnFirstTable, $columnLastTable, $rowFirstTable, $rowNumberTableContent);
            $spreadsheetGenerator->writeDocumentOutput($spreadsheet, 'Laporan_Penjualan_Rekap_Per_Nota');
        } catch (\Throwable $th) {
            return throwResponseInternalServerError('[E-AUTH-001] Internal server error', [$th]);
        }
    }

    public function excelDetailPerNota($arrParametersEncode = null)
    {
        if(!isset($arrParametersEncode) || $arrParametersEncode == "") return $this->failForbidden('[E-AUTH-000] Forbidden Access');

        try {
            helper(['firebaseJWT']);
            $arrParameters  =   (array) decodeJWTToken($arrParametersEncode);
            if(!isset($arrParameters['tanggalAwal']) || !isset($arrParameters['tanggalAkhir'])) return $this->failForbidden('[E-AUTH-000] Forbidden Access');

            $mainOperation  =   new MainOperation();
            $penjualanModel =   new PenjualanModel();

            $idToko             =   $arrParameters['idToko'];
            $tanggalAwal        =   $arrParameters['tanggalAwal'];
            $tanggalAwalDT      =   Time::parse($tanggalAwal);
            $tanggalAwalStr     =   $tanggalAwalDT->format('d M Y');
            $tanggalAkhir       =   $arrParameters['tanggalAkhir'];
            $tanggalAkhirDT     =   Time::parse($tanggalAkhir);
            $tanggalAkhirStr    =   $tanggalAkhirDT->format('d M Y');
            $kataKunciPencarian =   $arrParameters['kataKunciPencarian'];
            $baseData           =   $penjualanModel->getDataLaporanDetailPerNota($idToko, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
            $dataLaporan        =   $baseData->asObject()->findAll(99999, 0);

            $spreadsheet            =   new Spreadsheet();
            $spreadsheetGenerator   =   new SpreadsheetGenerator();
            $activeWorksheet        =   $spreadsheet->getActiveSheet();

            $arrTitleData   =   ['Laporan Detail Penjualan Retail Per Nota'];
            $arrFilterData  =   [
                ['Toko', $mainOperation->getDetailToko($idToko)['NAMA']],
                ['Periode', $tanggalAwalStr.' s/d '.$tanggalAkhirStr],
                ['Kata Pencarian', $kataKunciPencarian == "" ? '-' : $kataKunciPencarian],
                ['Waktu Proses', date('d M Y H:i')]
            ];
            $arrHeaderData  =   [
                [['A'], 2, 'Nomor Nota', 18, 'center'],
                [['B'], 2, 'Nama Customer', 20, 'center'],
                [['C'], 2, 'Metode Bayar', 14, 'center'],
                [['D'], 2, 'Catatan', 25, 'center'],
                [['E'], 2, 'Detail Input', 28, 'center'],
                [['F', 'L'], 1, 'Daftar Barang', false, 'center'],
                [['F'], 1, 'Kode SKU', 20, 'left', 1],
                [['G'], 1, 'Detail SKU', 25, 'left', 1],
                [['H'], 1, 'Jumlah', 12, 'right', 1],
                [['I'], 1, 'Satuan', 10, 'left', 1],
                [['J'], 1, 'Harga Awal', 12, 'right', 1],
                [['K'], 1, 'Diskon', 12, 'right', 1],
                [['L'], 1, 'Harga Total', 12, 'right', 1],
                [['M'], 2, 'Grand Total', 12, 'right']
            ];

            $rowStartDocument           =   1;
            $documentProperties         =   $spreadsheetGenerator->getDocumentProperties($rowStartDocument, $arrTitleData, $arrFilterData, $arrHeaderData);
            $rowStartFilter             =   $documentProperties['rowStartFilter'];
            $rowStartTableHeader        =   $documentProperties['rowStartTableHeader'];
            $rowNumberTableContent      =   $documentProperties['rowNumberTableContent'];
            $rowNumberTableContentStart =   $rowNumberTableContent;
            $rowFirstTable              =   $documentProperties['rowFirstTable'];
            $columnFirstTable           =   $arrHeaderData[0][0][0];
            $columnLastTable            =   end(end($arrHeaderData)[0]);

            $spreadsheetGenerator->setDocumentTitle($activeWorksheet, $arrTitleData, $columnFirstTable, $columnLastTable, $rowStartDocument);
            $spreadsheetGenerator->setDocumentFilter($activeWorksheet, $arrFilterData, $columnLastTable, $rowStartFilter);
            $spreadsheetGenerator->setDocumentTableHeader($activeWorksheet, $arrHeaderData, $rowStartTableHeader);

            if(isset($dataLaporan) && !empty($dataLaporan)){
                foreach($dataLaporan as $keyDataLaporan){
                    $activeWorksheet
                    ->setCellValue('A'.$rowNumberTableContent, $keyDataLaporan->NOTAPENJUALANNOMOR)
                    ->setCellValue('B'.$rowNumberTableContent, $keyDataLaporan->NAMACUSTOMER)
                    ->setCellValue('C'.$rowNumberTableContent, $keyDataLaporan->METODEBAYAR)
                    ->setCellValue('D'.$rowNumberTableContent, $keyDataLaporan->CATATAN)
                    ->setCellValue('E'.$rowNumberTableContent, $keyDataLaporan->INPUTUSER.PHP_EOL.$keyDataLaporan->INPUTTANGGALWAKTU)
                    ->setCellValue('M'.$rowNumberTableContent, intval($keyDataLaporan->TOTALHARGAAKHIR));

                    $idPenjualanRekap   =   isset($keyDataLaporan->IDPENJUALANREKAP) && $keyDataLaporan->IDPENJUALANREKAP != "" ? $keyDataLaporan->IDPENJUALANREKAP : 0;
                    $dataBarangSKU      =   $penjualanModel->getDataBarangSKUPenjualan($idPenjualanRekap);

                    if(!empty($dataBarangSKU) && !is_null($dataBarangSKU)){
                        $totalBarangSKU =   count($dataBarangSKU);
                        foreach($dataBarangSKU as $keyDataBarangSKU){
                            $activeWorksheet
                            ->setCellValue('F'.$rowNumberTableContent, $keyDataBarangSKU->KODESKU)
                            ->setCellValue('G'.$rowNumberTableContent, $keyDataBarangSKU->DESKRIPSISKU)
                            ->setCellValue('H'.$rowNumberTableContent, intval($keyDataBarangSKU->JUMLAH))
                            ->setCellValue('I'.$rowNumberTableContent, $keyDataBarangSKU->NAMASATUAN)
                            ->setCellValue('J'.$rowNumberTableContent, intval($keyDataBarangSKU->HARGAAWAL))
                            ->setCellValue('K'.$rowNumberTableContent, intval($keyDataBarangSKU->HARGADISKON))
                            ->setCellValue('L'.$rowNumberTableContent, intval($keyDataBarangSKU->TOTALHARGAJUAL));
                            $rowNumberTableContent++;
                        }

                        if($totalBarangSKU > 1){
                            $rowNumberMergeStart=   $rowNumberTableContent - $totalBarangSKU;
                            $rowNumberMergeEnd  =   $rowNumberTableContent - 1;

                            $activeWorksheet->mergeCells('A'.$rowNumberMergeStart.':A'.$rowNumberMergeEnd);
                            $activeWorksheet->mergeCells('B'.$rowNumberMergeStart.':B'.$rowNumberMergeEnd);
                            $activeWorksheet->mergeCells('C'.$rowNumberMergeStart.':C'.$rowNumberMergeEnd);
                            $activeWorksheet->mergeCells('D'.$rowNumberMergeStart.':D'.$rowNumberMergeEnd);
                            $activeWorksheet->mergeCells('E'.$rowNumberMergeStart.':E'.$rowNumberMergeEnd);
                            $activeWorksheet->mergeCells('M'.$rowNumberMergeStart.':M'.$rowNumberMergeEnd);
                        }
                    } else {
                        $rowNumberTableContent++;
                    }
                }

                $activeWorksheet->getStyle('M'.$rowNumberTableContentStart.':M'.$rowNumberTableContent)->getFont()->setBold(true);
            } else {
                $activeWorksheet->setCellValue($columnFirstTable.$rowNumberTableContent, 'Tidak ada data detail penjualan retail per nota yang ditemukan pada periode tersebut');
                $activeWorksheet->mergeCells($columnFirstTable.$rowNumberTableContent.':'.$columnLastTable.$rowNumberTableContent);
            }
		
            $spreadsheetGenerator->setDocumentTableStyle($activeWorksheet, $columnFirstTable, $columnLastTable, $rowFirstTable, $rowNumberTableContent);
            $spreadsheetGenerator->writeDocumentOutput($spreadsheet, 'Laporan_Penjualan_Detail_Per_Nota');
        } catch (\Throwable $th) {
            return throwResponseInternalServerError('[E-AUTH-001] Internal server error', [$th]);
        }
    }

    public function excelRekapPerBarang($arrParametersEncode = null)
    {
        if(!isset($arrParametersEncode) || $arrParametersEncode == "") return $this->failForbidden('[E-AUTH-000] Forbidden Access');

        try {
            helper(['firebaseJWT']);
            $arrParameters  =   (array) decodeJWTToken($arrParametersEncode);
            if(!isset($arrParameters['tanggalAwal']) || !isset($arrParameters['tanggalAkhir'])) return $this->failForbidden('[E-AUTH-000] Forbidden Access');

            $mainOperation  =   new MainOperation();
            $penjualanModel =   new PenjualanModel();

            $idToko             =   $arrParameters['idToko'];
            $idBarangKategori   =   $arrParameters['idBarangKategori'];
            $idBarangMerk       =   $arrParameters['idBarangMerk'];
            $tanggalAwal        =   $arrParameters['tanggalAwal'];
            $tanggalAwalDT      =   Time::parse($tanggalAwal);
            $tanggalAwalStr     =   $tanggalAwalDT->format('d M Y');
            $tanggalAkhir       =   $arrParameters['tanggalAkhir'];
            $tanggalAkhirDT     =   Time::parse($tanggalAkhir);
            $tanggalAkhirStr    =   $tanggalAkhirDT->format('d M Y');
            $kataKunciPencarian =   $arrParameters['kataKunciPencarian'];
            $kategoriBarangStr  =   $idBarangKategori == "" || $idBarangKategori == 0 ? 'Semua Kategori Barang' : $mainOperation->getDetailBarangKategori($idBarangKategori)['NAMAKATEGORI'];
            $merkBarangStr      =   $idBarangMerk == "" || $idBarangMerk == 0 ? 'Semua Merk Barang' : $mainOperation->getDetailBarangMerk($idBarangMerk)['NAMAMERK'];
            $baseData           =   $penjualanModel->getDataLaporanRekapPerBarang($idToko, $idBarangKategori, $idBarangMerk, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
            $dataLaporan        =   $baseData->asObject()->findAll(99999, 0);

            $spreadsheet            =   new Spreadsheet();
            $spreadsheetGenerator   =   new SpreadsheetGenerator();
            $activeWorksheet        =   $spreadsheet->getActiveSheet();

            $arrTitleData   =   ['Laporan Rekap Penjualan Retail Per Barang'];
            $arrFilterData  =   [
                ['Toko', $mainOperation->getDetailToko($idToko)['NAMA']],
                ['Kategori Barang', $kategoriBarangStr],
                ['Merk Barang', $merkBarangStr],
                ['Periode', $tanggalAwalStr.' s/d '.$tanggalAkhirStr],
                ['Kata Pencarian', $kataKunciPencarian == "" ? '-' : $kataKunciPencarian],
                ['Waktu Proses', date('d M Y H:i')]
            ];
            $arrHeaderData  =   [
                [['A'], 2, 'Kategori Barang', 16, 'center'],
                [['B'], 2, 'Merk Barang', 16, 'center'],
                [['C'], 2, 'Nama Barang', 18, 'center'],
                [['D'], 2, 'Kode SKU', 20, 'center'],
                [['E'], 2, 'Deskripsi SKU', 22, 'center'],
                [['F'], 2, 'Satuan', 12, 'center'],
                [['G', 'K'], 1, 'Detail Penjualan', false, 'center'],
                [['G'], 1, 'Jumlah Item', 14, 'right', 1],
                [['H'], 1, 'Harga Awal', 14, 'right', 1],
                [['I'], 1, 'Diskon', 14, 'right', 1],
                [['J'], 1, 'Harga Akhir', 14, 'right', 1],
                [['K'], 1, 'Total Harga', 14, 'right', 1],
            ];

            $rowStartDocument       =   1;
            $documentProperties     =   $spreadsheetGenerator->getDocumentProperties($rowStartDocument, $arrTitleData, $arrFilterData, $arrHeaderData);
            $rowStartFilter         =   $documentProperties['rowStartFilter'];
            $rowStartTableHeader    =   $documentProperties['rowStartTableHeader'];
            $rowNumberTableContent  =   $documentProperties['rowNumberTableContent'];
            $rowFirstTable          =   $documentProperties['rowFirstTable'];
            $columnFirstTable       =   $arrHeaderData[0][0][0];
            $columnLastTable        =   end(end($arrHeaderData)[0]);

            $spreadsheetGenerator->setDocumentTitle($activeWorksheet, $arrTitleData, $columnFirstTable, $columnLastTable, $rowStartDocument);
            $spreadsheetGenerator->setDocumentFilter($activeWorksheet, $arrFilterData, $columnLastTable, $rowStartFilter);
            $spreadsheetGenerator->setDocumentTableHeader($activeWorksheet, $arrHeaderData, $rowStartTableHeader);

            if(isset($dataLaporan) && !empty($dataLaporan)){
                foreach($dataLaporan as $keyDataLaporan){
                    $activeWorksheet
                    ->setCellValue('A'.$rowNumberTableContent, $keyDataLaporan->NAMAKATEGORI)
                    ->setCellValue('B'.$rowNumberTableContent, $keyDataLaporan->NAMAMERK)
                    ->setCellValue('C'.$rowNumberTableContent, $keyDataLaporan->NAMABARANG)
                    ->setCellValue('D'.$rowNumberTableContent, $keyDataLaporan->KODESKU)
                    ->setCellValue('E'.$rowNumberTableContent, $keyDataLaporan->DESKRIPSISKU)
                    ->setCellValue('F'.$rowNumberTableContent, $keyDataLaporan->NAMASATUAN)
                    ->setCellValue('G'.$rowNumberTableContent, intval($keyDataLaporan->JUMLAHTERJUAL))
                    ->setCellValue('H'.$rowNumberTableContent, intval($keyDataLaporan->HARGAAWAL))
                    ->setCellValue('I'.$rowNumberTableContent, intval($keyDataLaporan->HARGADISKON))
                    ->setCellValue('J'.$rowNumberTableContent, intval($keyDataLaporan->HARGASATUAN))
                    ->setCellValue('K'.$rowNumberTableContent, intval($keyDataLaporan->TOTALHARGAJUAL));
                    $rowNumberTableContent++;
                }
            } else {
                $activeWorksheet->setCellValue($columnFirstTable.$rowNumberTableContent, 'Tidak ada data rekap penjualan retail per barang yang ditemukan pada periode tersebut');
                $activeWorksheet->mergeCells($columnFirstTable.$rowNumberTableContent.':'.$columnLastTable.$rowNumberTableContent);
            }
		
            $spreadsheetGenerator->setDocumentTableStyle($activeWorksheet, $columnFirstTable, $columnLastTable, $rowFirstTable, $rowNumberTableContent);
            $spreadsheetGenerator->writeDocumentOutput($spreadsheet, 'Laporan_Penjualan_Rekap_Per_Barang');
        } catch (\Throwable $th) {
            return throwResponseInternalServerError('[E-AUTH-001] Internal server error', [$th]);
        }
    }

    public function excelDetailPerBarang($arrParametersEncode = null)
    {
        if(!isset($arrParametersEncode) || $arrParametersEncode == "") return $this->failForbidden('[E-AUTH-000] Forbidden Access');

        try {
            helper(['firebaseJWT']);
            $arrParameters  =   (array) decodeJWTToken($arrParametersEncode);
            if(!isset($arrParameters['tanggalAwal']) || !isset($arrParameters['tanggalAkhir'])) return $this->failForbidden('[E-AUTH-000] Forbidden Access');

            $mainOperation  =   new MainOperation();
            $penjualanModel =   new PenjualanModel();

            $idToko             =   $arrParameters['idToko'];
            $tanggalAwal        =   $arrParameters['tanggalAwal'];
            $tanggalAwalDT      =   Time::parse($tanggalAwal);
            $tanggalAwalStr     =   $tanggalAwalDT->format('d M Y');
            $tanggalAkhir       =   $arrParameters['tanggalAkhir'];
            $tanggalAkhirDT     =   Time::parse($tanggalAkhir);
            $tanggalAkhirStr    =   $tanggalAkhirDT->format('d M Y');
            $kataKunciPencarian =   $arrParameters['kataKunciPencarian'];
            $baseData           =   $penjualanModel->getDataLaporanDetailPerBarang($idToko, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
            $dataLaporan        =   $baseData->asObject()->findAll(99999, 0);

            $spreadsheet            =   new Spreadsheet();
            $spreadsheetGenerator   =   new SpreadsheetGenerator();
            $activeWorksheet        =   $spreadsheet->getActiveSheet();

            $arrTitleData   =   ['Laporan Detail Penjualan Retail Per Barang'];
            $arrFilterData  =   [
                ['Toko', $mainOperation->getDetailToko($idToko)['NAMA']],
                ['Periode', $tanggalAwalStr.' s/d '.$tanggalAkhirStr],
                ['Kata Pencarian', $kataKunciPencarian == "" ? '-' : $kataKunciPencarian],
                ['Waktu Proses', date('d M Y H:i')]
            ];
            $arrHeaderData  =   [
                [['A'], 1, 'Nama Barang', 30, 'center'],
                [['B'], 1, 'Kode SKU', 16, 'center'],
                [['C'], 1, 'Detail SKU', 30, 'center'],
                [['D'], 1, 'Atribut SKU', 40, 'center'],
                [['E'], 1, 'Nomor Nota', 18, 'center'],
                [['F'], 1, 'Tanggal Transaksi', 20, 'center'],
                [['G'], 1, 'Jumlah', 12, 'right'],
                [['H'], 1, 'Satuan', 12, 'left'],
                [['I'], 1, 'Harga Satuan', 12, 'right'],
                [['J'], 1, 'Diskon', 12, 'right'],
                [['K'], 1, 'Harga Total', 12, 'right']
            ];

            $rowStartDocument           =   1;
            $documentProperties         =   $spreadsheetGenerator->getDocumentProperties($rowStartDocument, $arrTitleData, $arrFilterData, $arrHeaderData);
            $rowStartFilter             =   $documentProperties['rowStartFilter'];
            $rowStartTableHeader        =   $documentProperties['rowStartTableHeader'];
            $rowNumberTableContent      =   $documentProperties['rowNumberTableContent'];
            $rowFirstTable              =   $documentProperties['rowFirstTable'];
            $columnFirstTable           =   $arrHeaderData[0][0][0];
            $columnLastTable            =   end(end($arrHeaderData)[0]);

            $spreadsheetGenerator->setDocumentTitle($activeWorksheet, $arrTitleData, $columnFirstTable, $columnLastTable, $rowStartDocument);
            $spreadsheetGenerator->setDocumentFilter($activeWorksheet, $arrFilterData, $columnLastTable, $rowStartFilter);
            $spreadsheetGenerator->setDocumentTableHeader($activeWorksheet, $arrHeaderData, $rowStartTableHeader);

            if(isset($dataLaporan) && !empty($dataLaporan)){
                $barangSKUModel =   new BarangSKUModel();
                foreach($dataLaporan as $keyDataLaporan){
                    $idBarangSKU=   isset($keyDataLaporan->IDBARANGSKU) && $keyDataLaporan->IDBARANGSKU != "" ? $keyDataLaporan->IDBARANGSKU : 0;
                    $atributSKU =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                    $atributSKU =   isset($atributSKU) && !empty($atributSKU) ? implode(',', $atributSKU) : '-';

                    $activeWorksheet
                    ->setCellValue('A'.$rowNumberTableContent, $keyDataLaporan->NAMABARANG)
                    ->setCellValue('B'.$rowNumberTableContent, $keyDataLaporan->KODESKU)
                    ->setCellValue('C'.$rowNumberTableContent, $keyDataLaporan->DESKRIPSISKU)
                    ->setCellValue('D'.$rowNumberTableContent, $atributSKU)
                    ->setCellValue('E'.$rowNumberTableContent, $keyDataLaporan->NOTAPENJUALANNOMOR)
                    ->setCellValue('F'.$rowNumberTableContent, $keyDataLaporan->INPUTTANGGALWAKTU)
                    ->setCellValue('G'.$rowNumberTableContent, intval($keyDataLaporan->JUMLAH))
                    ->setCellValue('H'.$rowNumberTableContent, $keyDataLaporan->NAMASATUAN)
                    ->setCellValue('I'.$rowNumberTableContent, intval($keyDataLaporan->HARGASATUAN))
                    ->setCellValue('J'.$rowNumberTableContent, intval($keyDataLaporan->HARGADISKON))
                    ->setCellValue('K'.$rowNumberTableContent, intval($keyDataLaporan->TOTALHARGA));
                    $rowNumberTableContent++;
                }
            } else {
                $activeWorksheet->setCellValue($columnFirstTable.$rowNumberTableContent, 'Tidak ada data detail penjualan retail per barang yang ditemukan pada periode tersebut');
                $activeWorksheet->mergeCells($columnFirstTable.$rowNumberTableContent.':'.$columnLastTable.$rowNumberTableContent);
            }
		
            $spreadsheetGenerator->setDocumentTableStyle($activeWorksheet, $columnFirstTable, $columnLastTable, $rowFirstTable, $rowNumberTableContent);
            $spreadsheetGenerator->writeDocumentOutput($spreadsheet, 'Laporan_Penjualan_Detail_Per_Barang');
        } catch (\Throwable $th) {
            return throwResponseInternalServerError('[E-AUTH-001] Internal server error', [$th]);
        }
    }
}