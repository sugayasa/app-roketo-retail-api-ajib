<?php

namespace App\Controllers\WH\Laporan;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\WH\Laporan\TagihanModel;
use App\Models\MainOperation;

class Tagihan extends ResourceController
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

    public function getLaporanDetailTagihan()
    {
        $rules  =   [
            'idToko'                    =>  ['label' => 'Toko', 'rules' => 'permit_empty|alpha_numeric'],
            'statusPelunasan'           =>  ['label' => 'Status Pelunasan', 'rules' => 'permit_empty|in_list[0,1]'],
            'tanggalAwal'               =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'              =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'kataKunciPencarian'        =>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'tampilTagihanBelumLunas'   =>  ['label' => 'Tampilkan Tagihan Belum Lunas', 'rules' => 'required|in_list[0,1]'],
            'dataPerPage'               =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'                =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]'],
        ];

        $messages   =   [
            'idToko'        =>  [
                'alpha_numeric' => 'Data Toko yang dipilih tidak valid, silakan periksa kembali'
            ],
            'statusPelunasan'   => [
                'in_list'   => 'Status pelunasan tidak valid, silakan periksa kembali',
            ],
            'tanggalAwal'   => [
                'regex_match'   => 'Tanggal awal periode tidak valid, silakan periksa kembali',
            ],
            'tanggalAkhir' => [
                'regex_match'   => 'Tanggal akhir periode tidak valid, silakan periksa kembali',
            ],
            'tampilTagihanBelumLunas' => [
                'in_list'   => 'Tampilkan tagihan belum lunas tidak valid, silakan periksa kembali',
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $tagihanModel           =   new TagihanModel();
        $mainOperation          =   new MainOperation();
        $idToko                 =   $this->request->getVar('idToko');
        $statusPelunasan        =   $this->request->getVar('statusPelunasan');
        $tanggalAwal            =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir           =   $this->request->getVar('tanggalAkhir');
        $kataKunciPencarian     =   $this->request->getVar('kataKunciPencarian');
        $tampilTagihanBelumLunas=   $this->request->getVar('tampilTagihanBelumLunas');
        $dataPerPage            =   $this->request->getVar('dataPerPage');
        $pageNumber             =   $this->request->getVar('pageNumber');
        $idToko                 =   isset($idToko) && $idToko != "" ? hashidDecode($idToko) : 0;
        $baseData               =   $tagihanModel->getDetailTagihanMutasiToko($this->idGudang, $idToko, $statusPelunasan, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian, $tampilTagihanBelumLunas);
        $totalNumberData        =   $baseData->countAllResults(false);
        $pageProperty           =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0) {
            $baseDataQueryString=   $tagihanModel->getDetailTagihanMutasiToko($this->idGudang, $idToko, $statusPelunasan, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian, $tampilTagihanBelumLunas, true);
            $rekapNotaTagihan   =   $tagihanModel->getDataRekapitulasiTagihan($baseDataQueryString);
            $dataNotaTagihan    =   $baseData->limit($dataPerPage, ($pageNumber - 1) * $dataPerPage)->get()->getResultObject();
            
            return $this->setResponseFormat('json')->respond([
                "nominalTagihan"        =>  $rekapNotaTagihan['NOMINALTAGIHAN'],
                "nominalLunas"          =>  $rekapNotaTagihan['NOMINALLUNAS'],
                "nominalBelumLunas"     =>  $rekapNotaTagihan['NOMINALBELUMLUNAS'],
                "jumlahNotaTagihan"     =>  $rekapNotaTagihan['JUMLAHNOTATAGIHAN'],
                "jumlahNotaLunas"       =>  $rekapNotaTagihan['JUMLAHNOTALUNAS'],
                "jumlahNotaBelumLunas"  =>  $rekapNotaTagihan['JUMLAHNOTABELUMLUNAS'],
                "dataNotaTagihan"       =>  $dataNotaTagihan,
                "pageProperty"          =>  $pageProperty
            ]);
        } else {
            $dataReturn     =   [
                "nominalTagihan"        =>  0,
                "nominalLunas"          =>  0,
                "nominalBelumLunas"     =>  0,
                "jumlahNotaTagihan"     =>  0,
                "jumlahNotaLunas"       =>  0,
                "jumlahNotaBelumLunas"  =>  0,
                "dataNotaTagihan"       =>  [],
                "pageProperty"          =>  $pageProperty
            ];
            return throwResponseNotFound('Tidak ada data nota tagihan yang ditemukan', $dataReturn);
        }
    }
}