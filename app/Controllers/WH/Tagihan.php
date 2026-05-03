<?php

namespace App\Controllers\WH;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\WH\TagihanModel;
use App\Models\MainOperation;

class Tagihan extends ResourceController
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
            $this->idGudang         =   $this->userData->idGudang;
            $this->currentDateTime  =   $request->currentDateTime;
        } catch (\Throwable $th) {
        }
    }

    public function index()
    {
        return $this->failForbidden('[E-AUTH-000] Forbidden Access');
    }

    public function getDaftarTagihanToko()
    {
        $rules  =   [
            'idToko'                =>  ['label' => 'Id Toko', 'rules' => 'permit_empty|alpha_numeric'],
            'statusTagihan'         =>  ['label' => 'Status Tagihan', 'rules' => 'permit_empty|in_list[-1,0,1]'],
            'tanggalJTAwal'         =>  ['label' => 'Tanggal JTAwal', 'rules' => 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalJTAkhir'        =>  ['label' => 'Tanggal JTAkhir', 'rules' => 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'kataKunciPencarian'    =>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'tampilHanyaBelumLunas' =>  ['label' => 'Tampilkan Hanya Belum Lunas', 'rules' => 'required|in_list[0,1]'],
            'dataPerPage'           =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'            =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idToko'  =>  [
                'alpha_numeric' =>  'Data toko yang dipilih tidak valid, silakan periksa kembali'
            ],
            'statusTagihan' =>  [
                'in_list'       =>  'Status tagihan yang dipilih tidak valid, silakan periksa kembali'
            ],
            'tanggalJTAwal' => [
                'regex_match'   =>  'Tanggal awal jatuh tempo tidak valid, silakan periksa kembali'
            ],
            'tanggalJTAkhir'=> [
                'regex_match'   =>  'Tanggal akhir jatuh tempo tidak valid, silakan periksa kembali'
            ],
            'tampilHanyaBelumLunas'=> [
                'in_list'   =>  'Pilihan untuk menampilkan hanya yang belum lunas tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation          =   new MainOperation();
        $tagihanModel           =   new TagihanModel();
        $idToko                 =   $this->request->getVar('idToko');
        $idToko                 =   isset($idToko) && $idToko != "" ? hashidDecode($idToko) : 0;
        $statusTagihan          =   $this->request->getVar('statusTagihan');
        $tanggalJTAwal          =   $this->request->getVar('tanggalJTAwal');
        $tanggalJTAkhir         =   $this->request->getVar('tanggalJTAkhir');
        $kataKunciPencarian     =   $this->request->getVar('kataKunciPencarian');
        $tampilHanyaBelumLunas  =   $this->request->getVar('tampilHanyaBelumLunas');
        $dataPerPage            =   $this->request->getVar('dataPerPage');
        $pageNumber             =   $this->request->getVar('pageNumber');
        $baseData               =   $tagihanModel->getDaftarTagihanToko($this->idGudang, $idToko, $statusTagihan, $tanggalJTAwal, $tanggalJTAkhir, $kataKunciPencarian, $tampilHanyaBelumLunas);
        $totalNumberData        =   $baseData->countAllResults(false);
        $pageProperty           =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0) {
            $dataTagihan    =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $dataTagihan    =   $dataTagihan == [] ? [] : encodeDatabaseObjectResultKey($dataTagihan, ['IDTOKONOTAMUTASIPEMBAYARAN']);
            $arrParameters  =   [
                'idToko'                =>  $idToko,
                'statusTagihan'         =>  $statusTagihan,
                'tanggalJTAwal'         =>  $tanggalJTAwal,
                'tanggalJTAkhir'        =>  $tanggalJTAkhir,
                'kataKunciPencarian'    =>  $kataKunciPencarian,
                'tampilHanyaBelumLunas' =>  $tampilHanyaBelumLunas
            ];
            $arrParametersEncode    =   encodeJWTToken($arrParameters);
            $urlExcelDaftarTagihan  =   base_url('wh/tagihan/excelDaftarTagihanToko').'/'.$arrParametersEncode;

            return $this->setResponseFormat('json')->respond([
                "dataTagihan"           =>  $dataTagihan,
                "pageProperty"          =>  $pageProperty,
                "urlExcel"              =>  $urlExcelDaftarTagihan,
                "baseURLBuktiPembayaran"=>  URL_BUKTI_PEMBAYARAN
            ]);
        } else {
            $dataReturn     =   [
                "dataTagihan"           =>  [],
                "pageProperty"          =>  $pageProperty,
                "urlExcel"              =>  '',
                "baseURLBuktiPembayaran"=>  URL_BUKTI_PEMBAYARAN
            ];
            return throwResponseNotFound('Tidak ada data nota tagihan yang ditemukan', $dataReturn);
        }
    }

    public function savePelunasanTagihan()
    {
        $rules  =   [
            'idTokoNotaMutasiPembayaran'=>  ['label' => 'Id Toko', 'rules' => 'permit_empty|alpha_numeric'],
            'buktiBayar'                =>  ['label' => 'Bukti Bayar', 'rules' => 'permit_empty|alpha_numeric_punct']
        ];

        if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
 
        $mainOperation              =   new MainOperation();
        $idTokoNotaMutasiPembayaran =   $this->request->getVar('idTokoNotaMutasiPembayaran');
        $idTokoNotaMutasiPembayaran =   isset($idTokoNotaMutasiPembayaran) && $idTokoNotaMutasiPembayaran != "" ? hashidDecode($idTokoNotaMutasiPembayaran) : 0;
        $buktiBayar                 =   $this->request->getVar('buktiBayar');
        $arrUpdatePembayaran        =   [
            'BUKTIBAYAR'            =>  $buktiBayar,
            'STATUS'                =>  1,
            'VALIDASIUSER'          =>  $this->userData->name.' (Gudang - '.$this->userData->userLevelName.')',
            'VALIDASITANGGALWAKTU'  =>  $this->currentDateTime
        ];

        $procUpdateData =   $mainOperation->updateDataTable('t_tokonotamutasipembayaran', $arrUpdatePembayaran, ['IDTOKONOTAMUTASIPEMBAYARAN' => $idTokoNotaMutasiPembayaran]);
        if(!$procUpdateData['status']) return switchMySQLErrorCode($procUpdateData['errCode']);

        return throwResponseOK('Data pelunasan tagihan telah disimpan');
    }
}