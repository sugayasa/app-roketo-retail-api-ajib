<?php

namespace App\Controllers\ERP\MonitoringToko;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\ERP\MonitoringToko\DashboardModel;
use App\Models\ERP\Master\DataDasarBarang\KategoriModel;

class Dashboard extends ResourceController
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

    public function getDataDashboard()
    {
        $rules  =   [
            'idToko'        =>  ['label' => 'Toko', 'rules' => 'required|alpha_numeric'],
            'bulan'         =>  ['label' => 'Bulan', 'rules' => 'required|in_list[01,02,03,04,05,06,07,08,09,10,11,12]'],
            'tahun'         =>  ['label' => 'Tahun', 'rules' => 'required|greater_than_equal_to['.APP_MIN_YEAR.']'],
            'orderStokTipe' =>  ['label' => 'Tipe Pengurutan Stok', 'rules' => 'required|in_list[SB,SS,S0]'],
        ];

        $messages   =   [
            'idToko'  =>  [
                'required'      =>  'Harap pilih toko terlebih dahulu',
                'alpha_numeric' =>  'Data toko tidak valid, silakan periksa kembali'
            ],
            'orderStokTipe'  =>  [
                'in_list'       =>  'Jenis pengurutan stok tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $dashboardModel         =   new DashboardModel();
        $kategoriModel          =   new KategoriModel();

        $idToko                     =   $this->request->getVar('idToko');
        $idToko                     =   isset($idToko) && $idToko != "" ? hashidDecode($idToko) : 0;
        $bulan                      =   $this->request->getVar('bulan');
        $tahun                      =   $this->request->getVar('tahun');
        $orderStokTipe              =   $this->request->getVar('orderStokTipe');
        $jumlahHari                 =   cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
        $tglHariIni                 =   date('Y-m-d');
        $tglAwalBulanTahun          =   new \DateTime("{$tahun}-{$bulan}-01");
        $tglAwalBulanTahunHistori   =   &$tglAwalBulanTahun;
        $tglAwalBulanTahun          =   clone $tglAwalBulanTahun;
        $tglAwalBulanTahunStr       =   &$tglAwalBulanTahun->format('Y-m-d');
        $tglAkhirBulanTahunStr      =   &$tglAwalBulanTahun->format('Y-m-t');
        $tglAwalBulanLalu           =   &$tglAwalBulanTahun->modify('-1 month');
        $tglAwalBulanLaluStr        =   &$tglAwalBulanLalu->format('Y-m-01');
        $tglAkhirBulanLaluStr       =   &$tglAwalBulanLalu->format('Y-m-t');
        $dataKategoriBarang         =   $kategoriModel->select('NAMAKATEGORI')->where('STATUS', 1)->asObject()->findAll();
        
        //DATA GRAPH PENJUALAN
        $dataPenjualanPerTanggal=   $dashboardModel->getDataPenjualanPerTanggal($idToko, $tglAwalBulanTahunStr, $tglAkhirBulanTahunStr);
        $arrKategoriPenjualan   =   $dataGraphPenjualan  =   [];
        
        if(!empty($dataKategoriBarang)){
            foreach($dataKategoriBarang as $kategori){
                $arrKategoriPenjualan[$kategori->NAMAKATEGORI]   =   0;
            }
        }
        
        for($hari = 1; $hari <= $jumlahHari; $hari++) {
            $timestamp  =   mktime(0, 0, 0, $bulan, $hari, $tahun);
            $tanggal    =   date('d', $timestamp);
            $dataGraphPenjualan[]=   array_merge([
                'tanggal' => $tanggal
            ], $arrKategoriPenjualan);
        }
        
        foreach($dataPenjualanPerTanggal as $keyPenjualanPerTanggal){
            $indexDataGraph =   intval($keyPenjualanPerTanggal->TANGGAL) - 1;
            $namaKategoriDB =   $keyPenjualanPerTanggal->NAMAKATEGORI;
            if(isset($dataGraphPenjualan[$indexDataGraph])){
                if(isset($dataGraphPenjualan[$indexDataGraph][$namaKategoriDB])){
                    $dataGraphPenjualan[$indexDataGraph][$namaKategoriDB]    =   intval($keyPenjualanPerTanggal->TOTALPENJUALAN);
                }
            }
        }

        //DATA REKAP PENJUALAN
        $dataRekapPenjualanPeriode  =   $dashboardModel->getDataRekapPenjualanPeriode($idToko, $tglHariIni, $tglAwalBulanTahunStr, $tglAkhirBulanTahunStr, $tglAwalBulanLaluStr, $tglAkhirBulanLaluStr);
        $dataRerataPenjualan        =   $dashboardModel->getDataRerataPenjualan($idToko, $tglAkhirBulanLaluStr);
        $totalHariTransaksi         =   isset($dataRerataPenjualan['TOTALHARITRANSAKSI']) ? intval($dataRerataPenjualan['TOTALHARITRANSAKSI']) : 0;
        $totalBulanTransaksi        =   isset($dataRerataPenjualan['TOTALBULANTRANSAKSI']) ? intval($dataRerataPenjualan['TOTALBULANTRANSAKSI']) : 0;
        $totalNominalHargabarang    =   isset($dataRerataPenjualan['TOTALNOMINALHARGABARANG']) ? intval($dataRerataPenjualan['TOTALNOMINALHARGABARANG']) : 0;
        $rerataTransaksiPerHari     =   $totalHariTransaksi > 0 ? round($totalNominalHargabarang / $totalHariTransaksi) : 0;
        $rerataTransaksiPerBulan    =   $totalBulanTransaksi > 0 ? round($totalNominalHargabarang / $totalBulanTransaksi) : 0;
        $dataRekapPenjualan         =   [
            "penjualanHariIni"  =>  [
                "nominal"       =>  intval($dataRekapPenjualanPeriode['TOTALNOMINALHARIINI'] ?? 0),
                "persentase"    =>  $rerataTransaksiPerHari > 0 ? round(intval($dataRekapPenjualanPeriode['TOTALNOMINALHARIINI'] ?? 0) / $rerataTransaksiPerHari * 100) : 100,
                "keterangan"    =>  number_format($dataRekapPenjualanPeriode['TOTALTRANSAKSIHARIINI'], 0, ',', '.').' transaksi | '.number_format($dataRekapPenjualanPeriode['TOTALITEMHARIINI'], 0, ',', '.').' item'
            ],
            "penjualanBulanIni" =>  [
                "nominal"       =>  intval($dataRekapPenjualanPeriode['TOTALNOMINALBULANINI'] ?? 0),
                "persentase"    =>  $rerataTransaksiPerBulan > 0 ? round(intval($dataRekapPenjualanPeriode['TOTALNOMINALBULANINI'] ?? 0) / $rerataTransaksiPerBulan * 100) : 100,
                "keterangan"    =>  number_format($dataRekapPenjualanPeriode['TOTALTRANSAKSIBULANINI'], 0, ',', '.').' transaksi | '.number_format($dataRekapPenjualanPeriode['TOTALITEMBULANINI'], 0, ',', '.').' item'
            ],
            "penjualanBulanLalu"=>  [
                "nominal"       =>  intval($dataRekapPenjualanPeriode['TOTALNOMINALBULANLALU'] ?? 0),
                "persentase"    =>  $rerataTransaksiPerBulan > 0 ? round(intval($dataRekapPenjualanPeriode['TOTALNOMINALBULANLALU'] ?? 0) / $rerataTransaksiPerBulan * 100) : 100,
                "keterangan"    =>  number_format($dataRekapPenjualanPeriode['TOTALTRANSAKSIBULANLALU'], 0, ',', '.').' transaksi | '.number_format($dataRekapPenjualanPeriode['TOTALITEMBULANLALU'], 0, ',', '.').' item'
            ]
        ];

        //DATA HISTORI PENJUALAN BULANAN
        $tglAwalPeriodeHistori  =   $tglAwalBulanTahun;
        for ($i = 0; $i < 12; $i++) {
            $dataHistoriBulanan[]   =   [
                'bulanTahun'        =>  $tglAwalBulanTahunHistori->format('Y-m'),
                'bulanTahunStr'     =>  OPTION_MONTH_BAHASA[(int)$tglAwalBulanTahunHistori->format('m')] .' '.$tglAwalBulanTahunHistori->format('Y'),
                'jumlahTransaksi'   =>  0,
                'jumlahItem'        =>  0,
                'jumlahNominal'     =>  0,
            ];
            $tglAwalBulanTahunHistori->modify('last day of previous month');
        }

        $tglAwalPeriodeHistori      =   $tglAwalPeriodeHistori->format('Y-m-d');
        $dataRekapPenjualanBulanan  =   $dashboardModel->getDataRekapPenjualanBulanan($idToko, $tglAwalPeriodeHistori, $tglHariIni);

        if($dataRekapPenjualanBulanan){
            foreach($dataRekapPenjualanBulanan as $keyRekapPenjualanBulanan){
                $bulanTahunDB   =   $keyRekapPenjualanBulanan->BULANTAHUN;
                $indexData      =   -1;
                
                foreach ($dataHistoriBulanan as $index => $data) {
                    if ($data['bulanTahun'] === $bulanTahunDB) {
                        $indexData = $index;
                        break;
                    }
                }

                if($indexData !== -1) {
                    $dataHistoriBulanan[$indexData]['jumlahTransaksi']  =   intval($keyRekapPenjualanBulanan->TOTALTRANSAKSI ?? 0);
                    $dataHistoriBulanan[$indexData]['jumlahItem']       =   intval($keyRekapPenjualanBulanan->TOTALITEM ?? 0);
                    $dataHistoriBulanan[$indexData]['jumlahNominal']    =   intval($keyRekapPenjualanBulanan->TOTALNOMINAL ?? 0);
                }
            }
        }
        
        //DATA STOK PER BARANG
        $dataStokBarang    =   $dashboardModel->getDataStokBarangToko($idToko, $tglAkhirBulanTahunStr, $orderStokTipe);
        if(!$dataStokBarang) $dataStokBarang =   [];

        foreach($dataStokBarang as $keyStokBarang){
            $fotoBarang                     =   isset($keyStokBarang->FOTOBARANG) && $keyStokBarang->FOTOBARANG != "" ? json_decode($keyStokBarang->FOTOBARANG) : [];
            $keyStokBarang->FOTOBARANG      =   count($fotoBarang) > 0 ? $fotoBarang[0] : 'no-image.jpg';
            $keyStokBarang->JUMLAHBARANGSKU =   intval($keyStokBarang->JUMLAHBARANGSKU);
            $keyStokBarang->TOTALSTOKBARANG =   intval($keyStokBarang->TOTALSTOKBARANG);
        }

        //DATA MUTASI TERBANYAK PER BARANG
        $dataMutasiTerbanyak    =   $dashboardModel->getDataMutasiTerbanyak($idToko, $tglAwalBulanTahunStr, $tglAkhirBulanTahunStr);
        if(!$dataMutasiTerbanyak) $dataMutasiTerbanyak =   [];

        foreach($dataMutasiTerbanyak as $keyMutasiTerbanyak){
            $fotoBarang                             =   isset($keyMutasiTerbanyak->FOTOBARANG) && $keyMutasiTerbanyak->FOTOBARANG != "" ? json_decode($keyMutasiTerbanyak->FOTOBARANG) : [];
            $keyMutasiTerbanyak->FOTOBARANG         =   count($fotoBarang) > 0 ? $fotoBarang[0] : 'no-image.jpg';
            $keyMutasiTerbanyak->JUMLAHBARANGSKU    =   intval($keyMutasiTerbanyak->JUMLAHBARANGSKU);
            $keyMutasiTerbanyak->TOTALMUTASIMASUK   =   intval($keyMutasiTerbanyak->TOTALMUTASIMASUK);
            $keyMutasiTerbanyak->TOTALMUTASIKELUAR  =   intval($keyMutasiTerbanyak->TOTALMUTASIKELUAR);
        }
        
        //DATA GRAPH STOK BARANG PER KATEGORI
        $dataStokBarangKategori =   $dashboardModel->getDataStokBarangKategori($idToko, $tglAkhirBulanTahunStr);
        $dataChartStokBarang    =   [];
        
        if(!empty($dataKategoriBarang)){
            foreach($dataKategoriBarang as $kategori){
                $dataChartStokBarang[$kategori->NAMAKATEGORI]   =   0;
            }
        }

        foreach($dataStokBarangKategori as $keyStokBarangKategori){
            $namaKategoriDB =   $keyStokBarangKategori->NAMAKATEGORI;
            if(isset($dataChartStokBarang[$namaKategoriDB])){
                $dataChartStokBarang[$namaKategoriDB]   =   intval($keyStokBarangKategori->TOTALSTOK);
            }
        }

        foreach($dataChartStokBarang as $kategori => $totalStok){
            $dataChartStokBarang[]   =   [
                'category'  =>  $kategori,
                'value'     =>  $totalStok
            ];
            unset($dataChartStokBarang[$kategori]);
        }

        return $this->setResponseFormat('json')
                    ->respond([
                        "penjualan" =>  [
                            "dataRekapPenjualan"    =>  $dataRekapPenjualan,
                            "dataHistoriBulanan"    =>  $dataHistoriBulanan,
                            "dataGraphPenjualan"    =>  $dataGraphPenjualan
                        ],
                        "stokBarang"=>  [
                            "dataStokBarang"        =>  $dataStokBarang,
                            "dataMutasiTerbanyak"   =>  $dataMutasiTerbanyak,
                            "dataChartStokBarang"   =>  $dataChartStokBarang
                        ]
                    ]);
    }
}