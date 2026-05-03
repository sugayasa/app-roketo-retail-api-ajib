<?php

namespace App\Controllers\ERP;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\ERP\Master\DataDasarBarang\KategoriModel;
use App\Models\ERP\DashboardGrosirModel;
use App\Models\ERP\DashboardRetailModel;

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
            'bulan' =>  ['label' => 'Bulan', 'rules' => 'required|in_list[01,02,03,04,05,06,07,08,09,10,11,12]'],
            'tahun' =>  ['label' => 'Tahun', 'rules' => 'required|greater_than_equal_to['.APP_MIN_YEAR.']'],
        ];

        if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $bulan                          =   $this->request->getVar('bulan');
        $tahun                          =   $this->request->getVar('tahun');
        $jumlahHari                     =   cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
        $tglHariIni                     =   date('Y-m-d');
        $tglAwalBulanTahun              =   new \DateTime("{$tahun}-{$bulan}-01");
        $tglAwalBulanTahunHistoriGrosir =   clone $tglAwalBulanTahun;
        $tglAwalBulanTahunHistoriRetail =   clone $tglAwalBulanTahun;
        $tglAwalBulanTahunStr           =   $tglAwalBulanTahun->format('Y-m-d');
        $tglAkhirBulanTahunStr          =   $tglAwalBulanTahun->format('Y-m-t');
        $tglAwalBulanLalu               =   $tglAwalBulanTahun->modify('-1 month');
        $tglAwalBulanLaluStr            =   $tglAwalBulanLalu->format('Y-m-01');
        $tglAkhirBulanLaluStr           =   $tglAwalBulanLalu->format('Y-m-t');
        $dataStatistikGrosir            =   $this->getDataStatistikGrosir($bulan, $tahun, $jumlahHari, $tglHariIni, $tglAwalBulanTahun, $tglAwalBulanTahunHistoriGrosir, $tglAwalBulanTahunStr, $tglAkhirBulanTahunStr, $tglAwalBulanLaluStr, $tglAkhirBulanLaluStr);
        $dataStatistikRetail            =   $this->getDataStatistikRetail($bulan, $tahun, $jumlahHari, $tglHariIni, $tglAwalBulanTahun, $tglAwalBulanTahunHistoriRetail, $tglAwalBulanTahunStr, $tglAkhirBulanTahunStr, $tglAwalBulanLaluStr, $tglAkhirBulanLaluStr);

        return $this->setResponseFormat('json')
                    ->respond([
                        "dataGrosir"    =>  $dataStatistikGrosir,
                        "dataRetail"    =>  $dataStatistikRetail
                    ]);
    }

    private function getDataStatistikGrosir($bulan, $tahun, $jumlahHari, $tglHariIni, $tglAwalBulanTahun, $tglAwalBulanTahunHistori, $tglAwalBulanTahunStr, $tglAkhirBulanTahunStr, $tglAwalBulanLaluStr, $tglAkhirBulanLaluStr)
    {
        $dashboardGrosirModel   =   new DashboardGrosirModel();
        $kategoriModel          =   new KategoriModel();
        
        //DATA GRAPH
        $dataKategori   =   $kategoriModel->select('NAMAKATEGORI')->where('STATUS', 1)->asObject()->findAll();
        $dataGraphMutasi=   $dashboardGrosirModel->getDataMutasiPerTanggal($tglAwalBulanTahunStr, $tglAkhirBulanTahunStr);
        $arrKategori    =   $dataGraph  =   $dataRekapMutasi =   $dataHistoriBulanan =   [];
        
        if(!empty($dataKategori)){
            foreach($dataKategori as $kategori){
                $arrKategori[$kategori->NAMAKATEGORI]   =   0;
            }
        }
        
        for($hari = 1; $hari <= $jumlahHari; $hari++) {
            $timestamp  =   mktime(0, 0, 0, $bulan, $hari, $tahun);
            $tanggal    =   date('d', $timestamp);
            $dataGraph[]=   array_merge([
                'tanggal' => $tanggal
            ], $arrKategori);
        }
        
        foreach($dataGraphMutasi as $keyGraphMutasi){
            $indexDataGraph =   intval($keyGraphMutasi->TANGGAL) - 1;
            $namaKategoriDB =   $keyGraphMutasi->NAMAKATEGORI;
            if(isset($dataGraph[$indexDataGraph])){
                if(isset($dataGraph[$indexDataGraph][$namaKategoriDB])){
                    $dataGraph[$indexDataGraph][$namaKategoriDB]    =   intval($keyGraphMutasi->TOTALPENJUALANGROSIR);
                }
            }
        }
        
        //DATA REKAP MUTASI
        $dataRekapMutasiPeriode =   $dashboardGrosirModel->getDataRekapMutasiPeriode($tglHariIni, $tglAwalBulanTahunStr, $tglAkhirBulanTahunStr, $tglAwalBulanLaluStr, $tglAkhirBulanLaluStr);
        $dataRerataMutasi       =   $dashboardGrosirModel->getDataRerataMutasi($tglAkhirBulanLaluStr);
        $totalHariTransaksi     =   isset($dataRerataMutasi['TOTALHARITRANSAKSI']) ? intval($dataRerataMutasi['TOTALHARITRANSAKSI']) : 0;
        $totalBulanTransaksi    =   isset($dataRerataMutasi['TOTALBULANTRANSAKSI']) ? intval($dataRerataMutasi['TOTALBULANTRANSAKSI']) : 0;
        $totalNominalHargabarang=   isset($dataRerataMutasi['TOTALNOMINALHARGABARANG']) ? intval($dataRerataMutasi['TOTALNOMINALHARGABARANG']) : 0;
        $rerataTransaksiPerHari =   $totalHariTransaksi > 0 ? round($totalNominalHargabarang / $totalHariTransaksi) : 0;
        $rerataTransaksiPerBulan=   $totalBulanTransaksi > 0 ? round($totalNominalHargabarang / $totalBulanTransaksi) : 0;
        $dataRekapMutasi        =   [
            "mutasiHariIni"     =>  [
                "nominal"       =>  intval($dataRekapMutasiPeriode['TOTALNOMINALHARIINI'] ?? 0),
                "persentase"    =>  $rerataTransaksiPerHari > 0 ? round(intval($dataRekapMutasiPeriode['TOTALNOMINALHARIINI'] ?? 0) / $rerataTransaksiPerHari * 100) : 100,
                "keterangan"    =>  number_format($dataRekapMutasiPeriode['TOTALTRANSAKSIHARIINI'], 0, ',', '.').' transaksi | '.number_format($dataRekapMutasiPeriode['TOTALITEMHARIINI'], 0, ',', '.').' item'
            ],
            "mutasiBulanIni"    =>  [
                "nominal"       =>  intval($dataRekapMutasiPeriode['TOTALNOMINALBULANINI'] ?? 0),
                "persentase"    =>  $rerataTransaksiPerBulan > 0 ? round(intval($dataRekapMutasiPeriode['TOTALNOMINALBULANINI'] ?? 0) / $rerataTransaksiPerBulan * 100) : 100,
                "keterangan"    =>  number_format($dataRekapMutasiPeriode['TOTALTRANSAKSIBULANINI'], 0, ',', '.').' transaksi | '.number_format($dataRekapMutasiPeriode['TOTALITEMBULANINI'], 0, ',', '.').' item'
            ],
            "mutasiBulanLalu"   =>  [
                "nominal"       =>  intval($dataRekapMutasiPeriode['TOTALNOMINALBULANLALU'] ?? 0),
                "persentase"    =>  $rerataTransaksiPerBulan > 0 ? round(intval($dataRekapMutasiPeriode['TOTALNOMINALBULANLALU'] ?? 0) / $rerataTransaksiPerBulan * 100) : 100,
                "keterangan"    =>  number_format($dataRekapMutasiPeriode['TOTALTRANSAKSIBULANLALU'], 0, ',', '.').' transaksi | '.number_format($dataRekapMutasiPeriode['TOTALITEMBULANLALU'], 0, ',', '.').' item'
            ]
        ];

        //DATA HISTORI BULANAN
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

        $tglAwalPeriodeHistori  =   $tglAwalPeriodeHistori->format('Y-m-d');
        $dataRekapMutasiBulanan =   $dashboardGrosirModel->getDataRekapMutasiBulanan($tglAwalPeriodeHistori, $tglHariIni);

        if($dataRekapMutasiBulanan){
            foreach($dataRekapMutasiBulanan as $keyRekapMutasiBulanan){
                $bulanTahunDB   =   $keyRekapMutasiBulanan->BULANTAHUN;
                $indexData      =   -1;
                
                foreach ($dataHistoriBulanan as $index => $data) {
                    if ($data['bulanTahun'] === $bulanTahunDB) {
                        $indexData = $index;
                        break;
                    }
                }

                if($indexData !== -1) {
                    $dataHistoriBulanan[$indexData]['jumlahTransaksi']  =   intval($keyRekapMutasiBulanan->TOTALTRANSAKSI ?? 0);
                    $dataHistoriBulanan[$indexData]['jumlahItem']       =   intval($keyRekapMutasiBulanan->TOTALITEM ?? 0);
                    $dataHistoriBulanan[$indexData]['jumlahNominal']    =   intval($keyRekapMutasiBulanan->TOTALNOMINAL ?? 0);
                }
            }
        }

        return [
            "dataGraph"         =>  $dataGraph,
            "dataRekapMutasi"   =>  $dataRekapMutasi,
            "dataHistoriBulanan"=>  $dataHistoriBulanan
        ];
    }

    private function getDataStatistikRetail($bulan, $tahun, $jumlahHari, $tglHariIni, $tglAwalBulanTahun, $tglAwalBulanTahunHistori, $tglAwalBulanTahunStr, $tglAkhirBulanTahunStr, $tglAwalBulanLaluStr, $tglAkhirBulanLaluStr)
    {
        $dashboardRetailModel   =   new DashboardRetailModel();
        $kategoriModel          =   new KategoriModel();

        //DATA GRAPH
        $dataKategori           =   $kategoriModel->select('NAMAKATEGORI')->where('STATUS', 1)->asObject()->findAll();
        $dataGraphPenjualan     =   $dashboardRetailModel->getDataPenjualanPerTanggal($tglAwalBulanTahunStr, $tglAkhirBulanTahunStr);
        $arrKategori            =   $dataGraph  =   $dataRekapPenjualan =   $dataHistoriBulanan =   [];
        
        if(!empty($dataKategori)){
            foreach($dataKategori as $kategori){
                $arrKategori[$kategori->NAMAKATEGORI]   =   0;
            }
        }
        
        for($hari = 1; $hari <= $jumlahHari; $hari++) {
            $timestamp  =   mktime(0, 0, 0, $bulan, $hari, $tahun);
            $tanggal    =   date('d', $timestamp);
            $dataGraph[]=   array_merge([
                'tanggal' => $tanggal
            ], $arrKategori);
        }
        
        foreach($dataGraphPenjualan as $keyGraphPenjualan){
            $indexDataGraph =   intval($keyGraphPenjualan->TANGGAL) - 1;
            $namaKategoriDB =   $keyGraphPenjualan->NAMAKATEGORI;
            if(isset($dataGraph[$indexDataGraph])){
                if(isset($dataGraph[$indexDataGraph][$namaKategoriDB])){
                    $dataGraph[$indexDataGraph][$namaKategoriDB]    =   intval($keyGraphPenjualan->TOTALPENJUALAN);
                }
            }
        }
        
        //DATA REKAP PENJUALAN
        $dataRekapPenjualanPeriode  =   $dashboardRetailModel->getDataRekapPenjualanPeriode($tglHariIni, $tglAwalBulanTahunStr, $tglAkhirBulanTahunStr, $tglAwalBulanLaluStr, $tglAkhirBulanLaluStr);
        $dataRerataPenjualan        =   $dashboardRetailModel->getDataRerataPenjualan($tglAkhirBulanLaluStr);
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

        //DATA HISTORI BULANAN
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
        $dataRekapPenjualanBulanan  =   $dashboardRetailModel->getDataRekapPenjualanBulanan($tglAwalPeriodeHistori, $tglHariIni);

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

        return [
            "dataGraph"             =>  $dataGraph,
            "dataRekapPenjualan"    =>  $dataRekapPenjualan,
            "dataHistoriBulanan"    =>  $dataHistoriBulanan
        ];
    }
}