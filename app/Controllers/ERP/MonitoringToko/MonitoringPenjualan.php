<?php

namespace App\Controllers\ERP\MonitoringToko;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\ERP\MonitoringToko\MonitoringPenjualanModel;
use App\Models\ERP\Master\DataDasarBarang\KategoriModel;
use App\Models\ERP\Master\BarangSKUModel;

class MonitoringPenjualan extends ResourceController
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

    public function getDataMonitoringPenjualan()
    {
        $rules  =   [
            'idToko'=>  ['label' => 'Toko', 'rules' => 'required|alpha_numeric'],
            'bulan' =>  ['label' => 'Bulan', 'rules' => 'required|in_list[01,02,03,04,05,06,07,08,09,10,11,12]'],
            'tahun' =>  ['label' => 'Tahun', 'rules' => 'required|greater_than_equal_to['.APP_MIN_YEAR.']']
        ];

        $messages   =   [
            'idToko'  =>  [
                'required'      =>  'Harap pilih toko terlebih dahulu',
                'alpha_numeric' =>  'Data toko tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $monitoringPenjualanModel   =   new MonitoringPenjualanModel();
        $kategoriModel              =   new KategoriModel();

        $idToko                     =   $this->request->getVar('idToko');
        $idToko                     =   isset($idToko) && $idToko != "" ? hashidDecode($idToko) : 0;
        $bulan                      =   $this->request->getVar('bulan');
        $tahun                      =   $this->request->getVar('tahun');
        $tahunBulan                 =   "{$tahun}-{$bulan}";
        $jumlahHari                 =   cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
        $tglHariIni                 =   date('Y-m-d');
        $tglAwalBulanTahun          =   new \DateTime("{$tahun}-{$bulan}-01");
        $tglAwalBulanTahun          =   clone $tglAwalBulanTahun;
        $tglAkhirBulanTahunStr      =   &$tglAwalBulanTahun->format('Y-m-t');
        $dataKategoriBarang         =   $kategoriModel->select('NAMAKATEGORI')->where('STATUS', 1)->asObject()->findAll();

        //DATA GRAFIK PENJUALAN PER TANGGAL
        $dataPenjualanPerTanggal    =   $monitoringPenjualanModel->getDataPenjualanPerTanggal($idToko, $tahunBulan);
        $dataGrafikPenjualan        =   [];
        
        for($hari = 1; $hari <= $jumlahHari; $hari++) {
            $timestamp              =   mktime(0, 0, 0, $bulan, $hari, $tahun);
            $tanggal                =   date('d', $timestamp);
            $dataGrafikPenjualan[]  =   [
                'tanggal'       =>  $tanggal,
                'totalPenjualan'=>  0
            ];
        }
        
        foreach($dataPenjualanPerTanggal as $keyPenjualanPerTanggal){
            $tanggalTransaksi   =   $keyPenjualanPerTanggal->TANGGAL;
            $indexDataGraph     =   array_search($tanggalTransaksi, array_column($dataGrafikPenjualan, 'tanggal'));
            if(isset($dataGrafikPenjualan[$indexDataGraph])){
                $dataGrafikPenjualan[$indexDataGraph]['totalPenjualan']   =   intval($keyPenjualanPerTanggal->TOTALPENJUALAN);
            }
        }

        //DATA CHART PENJUALAN PER KATEGORI
        $dataPenjualanPerKategori   =   $monitoringPenjualanModel->getDataPenjualanPerKategori($idToko, $tahunBulan);
        $dataChartPerKategori       =   [];
        
        foreach($dataKategoriBarang as $keyKategoriBarang){
            $dataChartPerKategori[]  =   [
                'namaKategori'  =>  $keyKategoriBarang->NAMAKATEGORI,
                'totalPenjualan'=>  0
            ];
        }
        
        foreach($dataPenjualanPerKategori as $keyPenjualanPerKategori){
            $namaKategori      =   $keyPenjualanPerKategori->NAMAKATEGORI;
            $indexDataChart    =   array_search($namaKategori, array_column($dataChartPerKategori, 'namaKategori'));
            if(isset($dataChartPerKategori[$indexDataChart])){
                $dataChartPerKategori[$indexDataChart]['totalPenjualan']   =   intval($keyPenjualanPerKategori->TOTALPENJUALAN);
            }
        }

        //REKAP PENJUALAN PER TANGGAL
        $rekapPenjualanPerTanggal       =   [];
        $totalHariRekapPenjualan        =   $tglAkhirBulanTahunStr <= $tglHariIni ? $jumlahHari : intval(date('d'));
        $dataRekapPenjualanPerTanggal   =   $monitoringPenjualanModel->getRekapPenjualanPerTanggal($idToko, $tahunBulan);

        for($i=1; $i<=$totalHariRekapPenjualan; $i++){
            $tanggalRekapPenjualan      =   str_pad($i, 2, '0', STR_PAD_LEFT);
            $rekapPenjualanPerTanggal[] =   [
                'TANGGAL'           =>  $tanggalRekapPenjualan,
                'TOTALNOTA'         =>  0,
                'TOTALITEM'         =>  0,
                'TOTALHARGABARANG'  =>  0,
                'TOTALHARGADISKON'  =>  0,
                'TOTALHARGALAIN'    =>  0,
                'TOTALHARGAAKHIR'   =>  0
            ];
        }

        foreach($dataRekapPenjualanPerTanggal as $keyRekapPenjualan){
            $tanggalTransaksi   =   $keyRekapPenjualan->TANGGALTRANSAKSI;
            $indexTanggal       =   array_search($tanggalTransaksi, array_column($rekapPenjualanPerTanggal, 'TANGGAL'));
            
            $rekapPenjualanPerTanggal[$indexTanggal]=   [
                'TANGGAL'           =>  $keyRekapPenjualan->TANGGALTRANSAKSI,
                'TOTALNOTA'         =>  intval($keyRekapPenjualan->TOTALNOTA),
                'TOTALITEM'         =>  intval($keyRekapPenjualan->TOTALITEM),
                'TOTALHARGABARANG'  =>  intval($keyRekapPenjualan->TOTALHARGABARANG),
                'TOTALHARGADISKON'  =>  intval($keyRekapPenjualan->TOTALHARGADISKON),
                'TOTALHARGALAIN'    =>  intval($keyRekapPenjualan->TOTALHARGALAIN),
                'TOTALHARGAAKHIR'   =>  intval($keyRekapPenjualan->TOTALHARGAAKHIR)
            ];
        }
        
        //DATA HISTORI PENJUALAN
        $dataHistoriPenjualan   =   $monitoringPenjualanModel->getDataHistoriPenjualan($idToko, $tahunBulan);
        if(!$dataHistoriPenjualan) $dataHistoriPenjualan =   [];
        $barangSKUModel         = new BarangSKUModel();

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
        $dataBarangTerlaris =   $monitoringPenjualanModel->getDataBarangTerlaris($idToko, $tahunBulan);
        if(!$dataBarangTerlaris) $dataBarangTerlaris =   [];

        foreach($dataBarangTerlaris as $keyBarangTerlaris){
            $fotoBarang                         =   isset($keyBarangTerlaris->FOTOBARANG) && $keyBarangTerlaris->FOTOBARANG != "" ? json_decode($keyBarangTerlaris->FOTOBARANG) : [];
            $keyBarangTerlaris->FOTOBARANG      =   count($fotoBarang) > 0 ? $fotoBarang[0] : 'no-image.jpg';
            $keyBarangTerlaris->JUMLAHBARANGSKU =   intval($keyBarangTerlaris->JUMLAHBARANGSKU);
            $keyBarangTerlaris->JUMLAHTERJUAL   =   intval($keyBarangTerlaris->JUMLAHTERJUAL);
        }

        return $this->setResponseFormat('json')
                    ->respond([
                        "dataGrafikPenjualan"       =>  $dataGrafikPenjualan,
                        "dataChartPerKategori"      =>  $dataChartPerKategori,
                        "rekapPenjualanPerTanggal"  =>  $rekapPenjualanPerTanggal,
                        "dataHistoriPenjualan"      =>  $dataHistoriPenjualan,
                        "dataBarangTerlaris"        =>  $dataBarangTerlaris
                    ]);
    }
}