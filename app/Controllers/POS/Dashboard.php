<?php

namespace App\Controllers\POS;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\ERP\Master\DataDasarBarang\KategoriModel;
use App\Models\POS\DashboardModel;
use App\Models\ERP\Master\BarangSKUModel;

class Dashboard extends ResourceController
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
            $this->idToko           =   $this->userData->idToko;
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

        $dashboardModel             =   new DashboardModel();
        $kategoriModel              =   new KategoriModel();
        $bulan                      =   $this->request->getVar('bulan');
        $tahun                      =   $this->request->getVar('tahun');
        $jumlahHari                 =   cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
        $tglHariIni                 =   date('Y-m-d');
        $tglAwalBulanTahun          =   new \DateTime("{$tahun}-{$bulan}-01");
        $tglAwalBulanTahunHistori   =   clone $tglAwalBulanTahun;
        $tglAwalBulanTahunStr       =   $tglAwalBulanTahun->format('Y-m-d');
        $tglAkhirBulanTahunStr      =   $tglAwalBulanTahun->format('Y-m-t');
        $tglAwalBulanLalu           =   $tglAwalBulanTahun->modify('-1 month');
        $tglAwalBulanLaluStr        =   $tglAwalBulanLalu->format('Y-m-01');
        $tglAkhirBulanLaluStr       =   $tglAwalBulanLalu->format('Y-m-t');

        //DATA GRAPH
        $dataKategori               =   $kategoriModel->select('NAMAKATEGORI')->where('STATUS', 1)->asObject()->findAll();
        $dataGraphPenjualan         =   $dashboardModel->getDataPenjualanPerTanggal($this->idToko, $tglAwalBulanTahunStr, $tglAkhirBulanTahunStr);
        $arrKategori                =   $dataGraph  =   $dataRekapPenjualan =   $dataHistoriBulanan =   [];
        
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
        $dataRekapPenjualanPeriode  =   $dashboardModel->getDataRekapPenjualanPeriode($this->idToko, $tglHariIni, $tglAwalBulanTahunStr, $tglAkhirBulanTahunStr, $tglAwalBulanLaluStr, $tglAkhirBulanLaluStr);
        $dataRerataPenjualan        =   $dashboardModel->getDataRerataPenjualan($this->idToko, $tglAkhirBulanLaluStr);
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
        $dataRekapPenjualanBulanan  =   $dashboardModel->getDataRekapPenjualanBulanan($this->idToko, $tglAwalPeriodeHistori, $tglHariIni);

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
        
        //DATA HISTORI PENJUALAN
        $dataHistoriPenjualan   =   $dashboardModel->getDataHistoriPenjualan($this->idToko, $tglAwalBulanTahunStr, $tglAkhirBulanTahunStr, 10);
        if(!$dataHistoriPenjualan) $dataHistoriPenjualan =   [];
        $barangSKUModel = new BarangSKUModel();

        foreach($dataHistoriPenjualan as $keyHistoriPenjualan){
            $idBarangSKU    =   isset($keyHistoriPenjualan->IDBARANGSKU) && $keyHistoriPenjualan->IDBARANGSKU != "" ? $keyHistoriPenjualan->IDBARANGSKU : 0;
            $fotoBarangSKU  =   isset($keyHistoriPenjualan->FOTOBARANGSKU) && $keyHistoriPenjualan->FOTOBARANGSKU != "" ? json_decode($keyHistoriPenjualan->FOTOBARANGSKU) : [];
            
            $keyHistoriPenjualan->FOTOBARANGSKU =   count($fotoBarangSKU) > 0 ? $fotoBarangSKU[0] : 'no-image.jpg';
            $keyHistoriPenjualan->ATRIBUTSKU    =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
            $keyHistoriPenjualan->JUMLAH        =   intval($keyHistoriPenjualan->JUMLAH);
            $keyHistoriPenjualan->HARGASATUAN   =   intval($keyHistoriPenjualan->HARGASATUAN);
            $keyHistoriPenjualan->TOTALHARGA    =   intval($keyHistoriPenjualan->TOTALHARGA);
            unset($keyHistoriPenjualan->IDBARANGSKU);
        }

        //DATA BARANG TERLARIS
        $tglHariIniStr      =   date('Y-m-d');
        $tglHariIniDT       =   new \DateTime($tglHariIniStr);
        $tgl30HariSebelum   =   $tglHariIniDT->modify('-30 days');
        $tgl30HariSebelum   =   $tgl30HariSebelum->format('Y-m-d');
        $dataBarangTerlaris =   $dashboardModel->getDataBarangTerlaris($this->idToko, $tgl30HariSebelum, $tglHariIniStr);
        if(!$dataBarangTerlaris) $dataBarangTerlaris =   [];

        foreach($dataBarangTerlaris as $keyBarangTerlaris){
            $fotoBarang                         =   isset($keyBarangTerlaris->FOTOBARANG) && $keyBarangTerlaris->FOTOBARANG != "" ? json_decode($keyBarangTerlaris->FOTOBARANG) : [];
            $keyBarangTerlaris->FOTOBARANG      =   count($fotoBarang) > 0 ? $fotoBarang[0] : 'no-image.jpg';
            $keyBarangTerlaris->JUMLAHBARANGSKU =   intval($keyBarangTerlaris->JUMLAHBARANGSKU);
            $keyBarangTerlaris->JUMLAHTERJUAL   =   intval($keyBarangTerlaris->JUMLAHTERJUAL);
        }
        
        //DATA STOK KRITIS
        $dataStokKritisToko =   $dashboardModel->getDataStokKritis($this->idToko);
        if(!$dataStokKritisToko) $dataStokKritisToko=   [];
        foreach($dataStokKritisToko as $keyStokKritisToko){
            $fotoBarang                         =   isset($keyStokKritisToko->FOTOBARANG) && $keyStokKritisToko->FOTOBARANG != "" ? json_decode($keyStokKritisToko->FOTOBARANG) : [];
            $keyStokKritisToko->FOTOBARANG      =   count($fotoBarang) > 0 ? $fotoBarang[0] : 'no-image.jpg';
            $keyStokKritisToko->STOKMINIMALTOKO =   intval($keyStokKritisToko->STOKMINIMALTOKO);
            $keyStokKritisToko->STOK            =   intval($keyStokKritisToko->STOK);
        }
        
        return $this->setResponseFormat('json')
                    ->respond([
                        "dataGraph"             =>  $dataGraph,
                        "dataRekapPenjualan"    =>  $dataRekapPenjualan,
                        "dataHistoriBulanan"    =>  $dataHistoriBulanan,
                        "dataHistoriPenjualan"  =>  $dataHistoriPenjualan,
                        "dataBarangTerlaris"    =>  $dataBarangTerlaris,
                        "dataStokKritisToko"    =>  $dataStokKritisToko,
                        "baseURLFotoBarang"     =>  URL_FOTO_BARANG
                    ]);
    }
}