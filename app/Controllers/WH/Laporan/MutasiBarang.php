<?php

namespace App\Controllers\WH\Laporan;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\WH\Laporan\MutasiBarangModel;
use App\Models\ERP\Master\BarangSKUModel;
use App\Models\MainOperation;

class MutasiBarang extends ResourceController
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

    public function getLaporanDetailMutasiBarang()
    {
        $rules  =   [
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]'],
            'tanggalAwal'       =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'      =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
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

        $mutasiBarangModel  =   new MutasiBarangModel();
        $mainOperation      =   new MainOperation();
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $pageNumber         =   $this->request->getVar('pageNumber');
        $tanggalAwal        =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir       =   $this->request->getVar('tanggalAkhir');
        $kataKunciPencarian =   $this->request->getVar('kataKunciPencarian');
        $tanggalAwalDT      =   new \DateTime($tanggalAwal);
        $tanggalAkhirDT     =   new \DateTime($tanggalAkhir);
        $tanggalInterval    =   $tanggalAwalDT->diff($tanggalAkhirDT);
        $jumlahHari         =   intval($tanggalInterval->days) + 1;
        
        if($jumlahHari > 31) return throwResponseNotAcceptable('Rentang tanggal maksimal adalah 31 hari');

        //DATA GRAPH
        $mainOperation  =   new MainOperation();
        $dataGraphMutasi=   $mutasiBarangModel->getRekapMutasiBarangPerTanggal($this->idGudang, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
        $dataGraph      =   [];

        while ($tanggalAwalDT <= $tanggalAkhirDT) {
            $dataGraph[]    =   [
                'Tanggal'       =>    $tanggalAwalDT->format('d M'),
                'Jumlah Masuk'  =>    0,
                'Jumlah Keluar' =>    0
            ];

            $tanggalAwalDT->modify('+1 day');
        }
        
        $jumlahMutasi   =   $totalMasuk =   $totalKeluar    =   0;
        foreach($dataGraphMutasi as $keyGraphMutasi){
            $tanggalGraph   =   $keyGraphMutasi->TANGGAL;
            $indexDataGraph =   array_search($tanggalGraph, array_column($dataGraph, 'Tanggal'));

            if($indexDataGraph && isset($dataGraph[$indexDataGraph])){
                $dataGraph[$indexDataGraph]['Jumlah Masuk'] =   intval($keyGraphMutasi->JUMLAHMASUK);
                $dataGraph[$indexDataGraph]['Jumlah Keluar']=   intval($keyGraphMutasi->JUMLAHKELUAR);
                $jumlahMutasi                               +=  intval($keyGraphMutasi->JUMLAHMUTASI);
                $totalMasuk                                 +=  intval($keyGraphMutasi->JUMLAHMASUK);
                $totalKeluar                                +=  intval($keyGraphMutasi->JUMLAHKELUAR);
            }
        }

        //DATA TABEL DETAIL
        $baseData           =   $mutasiBarangModel->getDetailMutasiBarang($this->idGudang, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
        $totalNumberData    =   $baseData->countAllResults(false);
        $pageProperty       =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $dataMutasiBarang   =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $barangSKUModel     =   new BarangSKUModel();

            foreach($dataMutasiBarang as $keyMutasiBarang){
                $idBarangSKU                    =   isset($keyMutasiBarang->IDBARANGSKU) && $keyMutasiBarang->IDBARANGSKU != "" ? $keyMutasiBarang->IDBARANGSKU : 0;
                $keyMutasiBarang->ATRIBUTSKUSTR =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                unset($keyMutasiBarang->IDBARANGSKU);
            }

            return $this->setResponseFormat('json')->respond([
                "jumlahMutasi"      =>  $jumlahMutasi,
                "totalMasuk"        =>  $totalMasuk,
                "totalKeluar"       =>  $totalKeluar,
                "dataGraph"         =>  $dataGraph,
                "dataMutasiBarang"  =>  $dataMutasiBarang,
                "pageProperty"      =>  $pageProperty
            ]);
        } else {
            $dataReturn     =   [
                "jumlahMutasi"      =>  $jumlahMutasi,
                "totalMasuk"        =>  $totalMasuk,
                "totalKeluar"       =>  $totalKeluar,
                "dataGraph"         =>  $dataGraph,
                "dataMutasiBarang"  =>  [],
                "pageProperty"      =>  $pageProperty
            ];
            return throwResponseNotFound('Tidak ada data mutasi barang yang ditemukan', $dataReturn);
        }
    }
}