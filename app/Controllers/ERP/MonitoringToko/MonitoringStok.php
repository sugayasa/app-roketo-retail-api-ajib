<?php

namespace App\Controllers\ERP\MonitoringToko;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\ERP\MonitoringToko\MonitoringStokModel;
use App\Models\ERP\Master\DataDasarBarang\KategoriModel;
use App\Models\ERP\Master\BarangSKUModel;

class MonitoringStok extends ResourceController
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

    public function getDataMonitoringStok()
    {
        $rules  =   [
            'idToko'    =>  ['label' => 'Toko', 'rules' => 'required|alpha_numeric'],
            'bulan'     =>  ['label' => 'Bulan', 'rules' => 'required|in_list[01,02,03,04,05,06,07,08,09,10,11,12]'],
            'tahun'     =>  ['label' => 'Tahun', 'rules' => 'required|greater_than_equal_to['.APP_MIN_YEAR.']'],
            'orderTipe' =>  ['label' => 'Tipe Pengurutan', 'rules' => 'required|in_list[SAWB,SAWS,SAKB,SAKS,MMB,MMS,MKB,MKS]'],
        ];

        $messages   =   [
            'idToko'  =>  [
                'required'      =>  'Harap pilih toko terlebih dahulu',
                'alpha_numeric' =>  'Data toko tidak valid, silakan periksa kembali'
            ],
            'orderTipe'  =>  [
                'in_list'       =>  'Jenis pengurutan tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $monitoringStokModel    =   new MonitoringStokModel();
        $barangSKUModel         =   new BarangSKUModel();
        $kategoriModel          =   new KategoriModel();

        $idToko                 =   $this->request->getVar('idToko');
        $idToko                 =   isset($idToko) && $idToko != "" ? hashidDecode($idToko) : 0;
        $bulan                  =   $this->request->getVar('bulan');
        $tahun                  =   $this->request->getVar('tahun');
        $orderTipe              =   $this->request->getVar('orderTipe');
        $tahunBulan             =   "{$tahun}-{$bulan}";
        $jumlahHari             =   cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
        $tglAwalBulanTahun      =   new \DateTime("{$tahun}-{$bulan}-01");
        $tglAwalBulanTahun      =   clone $tglAwalBulanTahun;
        $tglAwalBulanTahunStr   =   &$tglAwalBulanTahun->format('Y-m-d');
        $tglAkhirBulanTahunStr  =   &$tglAwalBulanTahun->format('Y-m-t');
        $dataKategoriBarang     =   $kategoriModel->select('NAMAKATEGORI')->where('STATUS', 1)->asObject()->findAll();

        //DATA GRAFIK MUTASI PER TANGGAL
        $dataMutasiPerTanggal    =   $monitoringStokModel->getRekapMutasiBarangPerTanggal($idToko, $tahunBulan);
        $dataGrafikMutasi        =   [];
        
        for($hari = 1; $hari <= $jumlahHari; $hari++) {
            $timestamp          =   mktime(0, 0, 0, $bulan, $hari, $tahun);
            $tanggal            =   date('d', $timestamp);
            $dataGrafikMutasi[] =   [
                'Tanggal'       =>  $tanggal,
                'Jumlah Masuk'  =>  0,
                'Jumlah Keluar' =>  0
            ];
        }
        
        foreach($dataMutasiPerTanggal as $keyMutasiPerTanggal){
            $tanggalMutasi  =   $keyMutasiPerTanggal->TANGGAL;
            $indexDataGraph =   array_search($tanggalMutasi, array_column($dataGrafikMutasi, 'Tanggal'));
            if(isset($dataGrafikMutasi[$indexDataGraph])){
                $dataGrafikMutasi[$indexDataGraph]['Jumlah Masuk']  =   intval($keyMutasiPerTanggal->JUMLAHMASUK);
                $dataGrafikMutasi[$indexDataGraph]['Jumlah Keluar'] =   intval($keyMutasiPerTanggal->JUMLAHKELUAR);
            }
        }

        //DATA HISTORI MUTASI BARANG
        $dataHistoriMutasi  =   $monitoringStokModel->getHistoriMutasiBarang($idToko, $tahunBulan);

        foreach($dataHistoriMutasi as $keyHistoriMutasi){
            $idBarangSKU                    =   isset($keyHistoriMutasi->IDBARANGSKU) && $keyHistoriMutasi->IDBARANGSKU != "" ? $keyHistoriMutasi->IDBARANGSKU : 0;
            $keyHistoriMutasi->ATRIBUTSKUSTR=   $barangSKUModel->getArrAtributSKU($idBarangSKU);
            unset($keyHistoriMutasi->IDBARANGSKU);
        }
        
        //DATA PIE CHART STOK BARANG PER KATEGORI
        $dataStokBarangKategori =   $monitoringStokModel->getDataStokBarangKategori($idToko, $tglAkhirBulanTahunStr);
        $dataPieChartStokBarang =   [];
        
        if(!empty($dataKategoriBarang)){
            foreach($dataKategoriBarang as $kategori){
                $dataPieChartStokBarang[$kategori->NAMAKATEGORI]   =   0;
            }
        }

        foreach($dataStokBarangKategori as $keyStokBarangKategori){
            $namaKategoriDB =   $keyStokBarangKategori->NAMAKATEGORI;
            if(isset($dataPieChartStokBarang[$namaKategoriDB])){
                $dataPieChartStokBarang[$namaKategoriDB]   =   intval($keyStokBarangKategori->TOTALSTOK);
            }
        }

        foreach($dataPieChartStokBarang as $namaKategoriDB => $totalStokKategori){
            $dataPieChartStokBarang[]  =   [
                'kategori'  =>  $namaKategoriDB,
                'totalStok' =>  $totalStokKategori
            ];
            unset($dataPieChartStokBarang[$namaKategoriDB]);
        }
        
        //DATA STOK MUTASI PER BARANG
        $dataStokMutasiBarang    =   $monitoringStokModel->getDataStokMutasiBarangToko($idToko, $tglAwalBulanTahunStr, $tglAkhirBulanTahunStr, $orderTipe);
        if(!$dataStokMutasiBarang) $dataStokMutasiBarang =   [];

        foreach($dataStokMutasiBarang as $keyStokBarang){
            $keyStokBarang->STOKAWAL    =   intval($keyStokBarang->STOKAWAL);
            $keyStokBarang->TOTALMASUK  =   intval($keyStokBarang->TOTALMASUK);
            $keyStokBarang->TOTALKELUAR =   intval($keyStokBarang->TOTALKELUAR);
            $keyStokBarang->STOKAKHIR   =   intval($keyStokBarang->STOKAKHIR);
        }

        return $this->setResponseFormat('json')
                    ->respond([
                        "dataGrafikMutasi"      =>  $dataGrafikMutasi,
                        "dataHistoriMutasi"     =>  $dataHistoriMutasi,
                        "dataPieChartStokBarang"=>  $dataPieChartStokBarang,
                        "dataStokMutasiBarang"  =>  $dataStokMutasiBarang
                    ]);
    }
}