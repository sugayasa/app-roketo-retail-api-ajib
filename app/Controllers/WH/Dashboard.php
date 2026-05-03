<?php

namespace App\Controllers\WH;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\ERP\Master\DataDasarBarang\KategoriModel;
use App\Models\WH\DashboardModel;
use App\Models\ERP\Master\BarangSKUModel;

class Dashboard extends ResourceController
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
        $dataKategori   =   $kategoriModel->select('NAMAKATEGORI')->where('STATUS', 1)->asObject()->findAll();
        $dataGraphMutasi=   $dashboardModel->getDataMutasiPerTanggal($this->idGudang, $tglAwalBulanTahunStr, $tglAkhirBulanTahunStr);
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
        $dataRekapMutasiPeriode =   $dashboardModel->getDataRekapMutasiPeriode($this->idGudang, $tglHariIni, $tglAwalBulanTahunStr, $tglAkhirBulanTahunStr, $tglAwalBulanLaluStr, $tglAkhirBulanLaluStr);
        $dataRerataMutasi       =   $dashboardModel->getDataRerataMutasi($this->idGudang, $tglAkhirBulanLaluStr);
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
        $dataRekapMutasiBulanan =   $dashboardModel->getDataRekapMutasiBulanan($this->idGudang, $tglAwalPeriodeHistori, $tglHariIni);

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
        
        // DATA HISTORI MUTASI
        $dataHistoriNota    =   $dashboardModel->getDataHistoriNota($this->idGudang, 10);
        if(!$dataHistoriNota) $dataHistoriNota =   [];

        //DATA HISTORI MUTASI BARANG
        $barangSKUModel         =   new BarangSKUModel();
        $dataHistoriMutasiBarang=   $dashboardModel->getDataHistoriMutasiBarang($this->idGudang);
        if(!$dataHistoriMutasiBarang) $dataHistoriMutasiBarang =   [];

        foreach($dataHistoriMutasiBarang as $keyHistoriMutasiBarang){
            $idBarangSKU                            =   isset($keyHistoriMutasiBarang->IDBARANGSKU) && $keyHistoriMutasiBarang->IDBARANGSKU != "" ? $keyHistoriMutasiBarang->IDBARANGSKU : 0;
            $fotoBarangSKU                          =   isset($keyHistoriMutasiBarang->FOTOBARANGSKU) && $keyHistoriMutasiBarang->FOTOBARANGSKU != "" ? json_decode($keyHistoriMutasiBarang->FOTOBARANGSKU) : [];
            $keyHistoriMutasiBarang->ATRIBUTSKU     =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
            $keyHistoriMutasiBarang->FOTOBARANGSKU  =   count($fotoBarangSKU) > 0 ? $fotoBarangSKU[0] : 'no-image.jpg';
            unset($keyHistoriMutasiBarang->IDBARANGSKU);
        }
        
        return $this->setResponseFormat('json')
                    ->respond([
                        "dataGraph"                 =>  $dataGraph,
                        "dataRekapMutasi"           =>  $dataRekapMutasi,
                        "dataHistoriBulanan"        =>  $dataHistoriBulanan,
                        "dataHistoriNota"           =>  $dataHistoriNota,
                        "dataHistoriMutasiBarang"   =>  $dataHistoriMutasiBarang,
                        "baseURLFotoBarang"         =>  URL_FOTO_BARANG
                    ]);
    }
}