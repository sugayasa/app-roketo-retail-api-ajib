<?php

namespace App\Controllers\WH\Laporan;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\WH\Laporan\MutasiTokoGrosirModel;
use App\Models\WH\DashboardModel;
use App\Models\ERP\Master\BarangSKUModel;
use App\Models\ERP\Master\DataDasarBarang\KategoriModel;
use App\Models\MainOperation;

class MutasiTokoGrosir extends ResourceController
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
            $this->idGudang         =   $this->userData->idGudang;
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

        $mutasiTokoGrosirModel  =   new MutasiTokoGrosirModel();
        $tanggalAwal            =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir           =   $this->request->getVar('tanggalAkhir');
        $dataPerPage            =   $this->request->getVar('dataPerPage');
        $pageNumber             =   $this->request->getVar('pageNumber');
        $tanggalAwalDT          =   new \DateTime($tanggalAwal);
        $tanggalAkhirDT         =   new \DateTime($tanggalAkhir);
        $tanggalInterval        =   $tanggalAwalDT->diff($tanggalAkhirDT);
        $jumlahHari             =   intval($tanggalInterval->days) + 1;
        
        if($jumlahHari > 31) return throwResponseNotAcceptable('Rentang tanggal maksimal adalah 31 hari');

        //DATA GRAPH
        $dashboardModel =   new DashboardModel();
        $kategoriModel  =   new KategoriModel();
        $mainOperation  =   new MainOperation();
        $dataKategori   =   $kategoriModel->select('NAMAKATEGORI')->where('STATUS', 1)->asObject()->findAll();
        $dataGraphMutasi=   $dashboardModel->getDataMutasiPerTanggal($this->idGudang, $tanggalAwal, $tanggalAkhir);
        $arrKategori    =   $dataGraph  =   $dataRekap  =   [];        

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
                    'TOTALHARGAAKHIR'      =>    0
                ];
            }

            $tanggalAwalDT->modify('+1 day');
            $urutanTanggal++;
        }
        
        $totalTransaksi     =   $totalNominal   =   $totalItem =   0;
        foreach($dataGraphMutasi as $keyGraphMutasi){
            $tanggalGraph   =   $keyGraphMutasi->TANGGALDM;
            $namaKategoriDB =   $keyGraphMutasi->NAMAKATEGORI;
            $indexDataGraph =   array_search($tanggalGraph, array_column($dataGraph, 'tanggal'));

            if($indexDataGraph && isset($dataGraph[$indexDataGraph])){
                if(isset($dataGraph[$indexDataGraph][$namaKategoriDB])){
                    $dataGraph[$indexDataGraph][$namaKategoriDB]    =   intval($keyGraphMutasi->TOTALPENJUALANGROSIR);
                    $totalTransaksi                                 +=  intval($keyGraphMutasi->JUMLAHTRANSAKSI);
                    $totalItem                                      +=  intval($keyGraphMutasi->JUMLAHITEM);
                    $totalNominal                                   +=  intval($keyGraphMutasi->TOTALPENJUALANGROSIR);
                }
            }
        }

        //DATA TABLE REKAP PER TANGGAL
        $baseData                   =   $mutasiTokoGrosirModel->getDataLaporanRekapPerTanggal($this->idGudang, $tanggalAwal, $tanggalAkhir);
        $totalNumberData            =   $baseData->countAllResults(false);
        $dataLaporanRekapPerTanggal =   [];
        
        if($totalNumberData > 0) $dataLaporanRekapPerTanggal    =   $baseData->asObject()->findAll(99999, 0);
        $pageProperty       =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $jumlahHari);
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
                        'TOTALHARGAAKHIR'      =>    intval($keyLaporanRekapPerTanggal->TOTALHARGAAKHIR)
                    ];
                    break;
                }
            }
        }

        $arrParameters  =   [
            'tanggalAwal'   =>  $tanggalAwal,
            'tanggalAkhir'  =>  $tanggalAkhir
        ];
        $arrParametersEncode    =   encodeJWTToken($arrParameters);
        $urlExcelRekapPerTanggal=   base_url(URL_EXCEL_REKAP_PENJUALAN_RETAIL_PER_TANGGAL).'/'.$arrParametersEncode;

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
            'idToko'            =>  ['label' => 'Toko', 'rules' => 'permit_empty|alpha_numeric'],
            'idCaraPelunasan'   =>  ['label' => 'Cara Pelunasan', 'rules' => 'permit_empty|alpha_numeric'],
            'tanggalAwal'       =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'      =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idToko'        =>  [
                'alpha_numeric' => 'Data Toko yang dipilih tidak valid, silakan periksa kembali'
            ],
            'idCaraPelunasan' =>  [
                'alpha_numeric' => 'Data Metode Pembayaran yang dipilih tidak valid, silakan periksa kembali'
            ],
            'tanggalAwal'   =>    [
                'regex_match'   => 'Tanggal awal periode tidak valid, silakan periksa kembali',
            ],
            'tanggalAkhir'  =>   [
                'regex_match'   => 'Tanggal akhir periode tidak valid, silakan periksa kembali',
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());
        
        $mutasiTokoGrosirModel  =   new MutasiTokoGrosirModel();
        $mainOperation          =   new MainOperation();
        $idToko                 =   $this->request->getVar('idToko');
        $idCaraPelunasan        =   $this->request->getVar('idCaraPelunasan');
        $tanggalAwal            =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir           =   $this->request->getVar('tanggalAkhir');
        $kataKunciPencarian     =   $this->request->getVar('kataKunciPencarian');
        $dataPerPage            =   $this->request->getVar('dataPerPage');
        $pageNumber             =   $this->request->getVar('pageNumber');
        $idToko                 =   isset($idToko) && $idToko != "" ? hashidDecode($idToko) : 0;
        $idCaraPelunasan        =   isset($idCaraPelunasan) && $idCaraPelunasan != "" ? hashidDecode($idCaraPelunasan) : 0;
        $baseData               =   $mutasiTokoGrosirModel->getDataLaporanRekapPerNota($this->idGudang, $idToko, $idCaraPelunasan, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
        $totalNumberData        =   $baseData->countAllResults(false);
        $pageProperty           =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0) {
            $listData   =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $listData,
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty
            ];
            return throwResponseNotFound('Tidak ada data nota mutasi / penjualan grosir yang ditemukan', $dataReturn);
        }
    }

    public function getLaporanDetailPerNota()
    {
        $rules  =   [
            'idToko'            =>  ['label' => 'Toko', 'rules' => 'permit_empty|alpha_numeric'],
            'tanggalAwal'       =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'      =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idToko'        =>  [
                'alpha_numeric' => 'Data Toko yang dipilih tidak valid, silakan periksa kembali'
            ],
            'tanggalAwal'   => [
                'regex_match'   => 'Tanggal awal periode tidak valid, silakan periksa kembali',
            ],
            'tanggalAkhir'  => [
                'regex_match'   => 'Tanggal akhir periode tidak valid, silakan periksa kembali',
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());
        
        $mutasiTokoGrosirModel  =   new MutasiTokoGrosirModel();
        $mainOperation          =   new MainOperation();
        $idToko                 =   $this->request->getVar('idToko');
        $idToko                 =   isset($idToko) && $idToko != "" ? hashidDecode($idToko) : 0;
        $tanggalAwal            =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir           =   $this->request->getVar('tanggalAkhir');
        $kataKunciPencarian     =   $this->request->getVar('kataKunciPencarian');
        $dataPerPage            =   $this->request->getVar('dataPerPage');
        $pageNumber             =   $this->request->getVar('pageNumber');
        $baseData               =   $mutasiTokoGrosirModel->getDataLaporanDetailPerNota($this->idGudang, $idToko, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
        $totalNumberData        =   $baseData->countAllResults(false);
        $pageProperty           =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){ 
            $listData   =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);

            foreach($listData as $keyData){
                $idTokoNotaMutasiRekap  =   isset($keyData->IDTOKONOTAMUTASIREKAP) && $keyData->IDTOKONOTAMUTASIREKAP != "" ? $keyData->IDTOKONOTAMUTASIREKAP : 0;
                $dataBarangSKU          =   $mutasiTokoGrosirModel->getDataBarangSKUMutasiGrosir($idTokoNotaMutasiRekap);

                $keyData->DAFTARBARANGSKU    =   $dataBarangSKU;
                unset($keyData->IDTOKONOTAMUTASIREKAP);
            }

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $listData,
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn     =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty
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
        
        $mutasiTokoGrosirModel  =   new MutasiTokoGrosirModel();
        $mainOperation          =   new MainOperation();
        $idBarangKategori       =   $this->request->getVar('idBarangKategori');
        $idBarangKategori       =   isset($idBarangKategori) && $idBarangKategori != "" ? hashidDecode($idBarangKategori) : 0;
        $idBarangMerk           =   $this->request->getVar('idBarangMerk');
        $idBarangMerk           =   isset($idBarangMerk) && $idBarangMerk != "" ? hashidDecode($idBarangMerk) : 0;
        $tanggalAwal            =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir           =   $this->request->getVar('tanggalAkhir');
        $kataKunciPencarian     =   $this->request->getVar('kataKunciPencarian');
        $dataPerPage            =   $this->request->getVar('dataPerPage');
        $pageNumber             =   $this->request->getVar('pageNumber');
        $baseData               =   $mutasiTokoGrosirModel->getDataLaporanRekapPerBarang($this->idGudang, $idBarangKategori, $idBarangMerk, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
        $totalNumberData        =   $baseData->countAllResults(false);
        $pageProperty           =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){ 
            $listData       =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $listData,
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn     =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty
            ];
            return throwResponseNotFound('Tidak ada data yang ditemukan', $dataReturn);
        }
    }

    public function getLaporanDetailPerBarang()
    {
        $rules  =   [
            'idToko'            =>  ['label' => 'Toko', 'rules' => 'permit_empty|alpha_numeric'],
            'tanggalAwal'       =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'      =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idToko'        =>  [
                'alpha_numeric' => 'Data Toko yang dipilih tidak valid, silakan periksa kembali'
            ],
            'tanggalAwal'   => [
                'regex_match'   => 'Tanggal awal periode tidak valid, silakan periksa kembali',
            ],
            'tanggalAkhir'  => [
                'regex_match'   => 'Tanggal akhir periode tidak valid, silakan periksa kembali',
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());
        
        $mutasiTokoGrosirModel  =   new MutasiTokoGrosirModel();
        $mainOperation          =   new MainOperation();
        $idToko                 =   $this->request->getVar('idToko');
        $tanggalAwal            =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir           =   $this->request->getVar('tanggalAkhir');
        $kataKunciPencarian     =   $this->request->getVar('kataKunciPencarian');
        $dataPerPage            =   $this->request->getVar('dataPerPage');
        $pageNumber             =   $this->request->getVar('pageNumber');
        $idToko                 =   isset($idToko) && $idToko != "" ? hashidDecode($idToko) : 0;
        $baseData               =   $mutasiTokoGrosirModel->getDataLaporanDetailPerBarang($this->idGudang, $idToko, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
        $totalNumberData        =   $baseData->countAllResults(false);
        $pageProperty           =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){ 
            $listData       =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $barangSKUModel =   new BarangSKUModel();

            foreach($listData as $keyData){
                $idBarangSKU            =   isset($keyData->IDBARANGSKU) && $keyData->IDBARANGSKU != "" ? $keyData->IDBARANGSKU : 0;
                $keyData->ATRIBUTSKUSTR =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                unset($keyData->IDBARANGSKU);
            }

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $listData,
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn     =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty
            ];
            return throwResponseNotFound('Tidak ada data yang ditemukan', $dataReturn);
        }
    }
}