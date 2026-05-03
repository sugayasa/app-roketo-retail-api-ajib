<?php

namespace App\Controllers\WH\Stok;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\WH\Stok\InboundBarangModel;
use App\Models\MainOperation;
use App\Models\ERP\Master\BarangSKUModel;

class InboundBarang extends ResourceController
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

    public function getListNotaAktif()
    {
        $InboundBarangModel =   new InboundBarangModel();
        $listData           =   $InboundBarangModel->getListNotaAktif($this->idGudang);

        if(!$listData) return throwResponseNotFound('Tidak ada nota aktif yang ditemukan', ['listData' =>  []]);

        $listData           =   encodeDatabaseObjectResultKey($listData, ['IDNOTAPEMBELIANREKAP']);
        return $this->setResponseFormat('json')->respond(["listData" =>  $listData]);
    }

    public function getDetailNota()
    {
        $rules      =   ['idNotaPembelianRekap' =>  ['label' => 'Id Nota Pembelian Rekap', 'rules' => 'required|alpha_numeric']];
        $messages   =   [
            'idNotaPembelianRekap'  => [
                'required'      => 'Data kiriman tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak valid, silakan periksa kembali'
            ]
        ];
        
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $inboundBarangModel         =   new InboundBarangModel();
        $idNotaPembelianRekap       =   hashidDecode($this->request->getVar('idNotaPembelianRekap'));
        $detailNotaPembelianRekap   =	$inboundBarangModel->getDetailNotaPembelianRekap($idNotaPembelianRekap);

        if(!$detailNotaPembelianRekap) return throwResponseNotFound('Detail nota pembelian yang ditemukan');

        $dataBarangSKU  =	$inboundBarangModel->getDataBarangSKUNotaPembelian($idNotaPembelianRekap, $this->idGudang);
        if($dataBarangSKU && count($dataBarangSKU) > 0){
            $barangSKUModel =   new BarangSKUModel();
            foreach($dataBarangSKU as $keyBarangSKU){
                $idBarangSKU                =   isset($keyBarangSKU->IDBARANGSKU) && $keyBarangSKU->IDBARANGSKU != "" ? $keyBarangSKU->IDBARANGSKU : 0;
                $keyBarangSKU->ATRIBUTSKUSTR=   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                $keyBarangSKU->PROSESALLOW  =   (bool) $keyBarangSKU->PROSESALLOW;
                unset($keyBarangSKU->IDBARANGSKU);
            }
        }

        $dataBarangSKU  =   encodeDatabaseObjectResultKey($dataBarangSKU, ['IDNOTAPEMBELIANINBOUND']);
        return $this->setResponseFormat('json')->respond(["detailNotaPembelianRekap" => $detailNotaPembelianRekap, "dataBarangSKU" => $dataBarangSKU]);
    }

    public function saveInboundPerBarang()
    {
        $rules      =   [
            'idNotaPembelianInbound'=>  ['label' => 'Id Nota Pembelian Inbound', 'rules' => 'required|alpha_numeric'],
            'jumlahInbound'         =>  ['label' => 'Jumlah Barang Diterima', 'rules' => 'required|numeric|min_length[1]|max_length[4]|greater_than[0]'],
            'forceUpdate'           =>  ['label' => 'Status Pengecualian', 'rules' => 'required|in_list[0,1]']
        ];

        $messages   =   [
            'idNotaPembelianInbound'    => [
                'required'      => 'Data kiriman tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak valid, silakan periksa kembali'
            ]
        ];
        
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $inboundBarangModel         =   new InboundBarangModel();
        $mainOperation              =   new MainOperation();
        $idNotaPembelianInbound     =   hashidDecode($this->request->getVar('idNotaPembelianInbound'));
        $jumlahInbound              =   $this->request->getVar('jumlahInbound');
        $forceUpdate                =   $this->request->getVar('forceUpdate');
        $detailNotaPembelianInbound =	$inboundBarangModel->getDetailNotaPembelianInbound($idNotaPembelianInbound);

        if(!$detailNotaPembelianInbound) return throwResponseNotFound('Detail nota pembelian yang ditemukan');

        $inboundJatah   =   intval($detailNotaPembelianInbound['INBOUNDJATAH']);
        $prosesKe       =   $detailNotaPembelianInbound['PROSESKE'];
        $prosesAllow    =   $detailNotaPembelianInbound['PROSESALLOW'];

        if(!$prosesAllow || (int)$prosesAllow !== 1) return throwResponseNotAcceptable('Proses perubahan data inbound sudah tidak diperbolehkan untuk SKU ini', ['forceUpdate' => 0, 'inputKeteranganPengecualian' => 0]);
        if($prosesKe == 0 && $inboundJatah !== $jumlahInbound) {
            $mainOperation->updateDataTable('t_notapembelianinbound', ['PROSESKE' => 1], ['IDNOTAPEMBELIANINBOUND' => $idNotaPembelianInbound]);
            return throwResponseNotAcceptable('Jumlah barang diterima tidak sesuai dengan jatah inbound yang ditentukan<br/>Harap periksa kembali <i class="text-black"><strong>JUMLAH FISIK BARANG!</strong></i>', ['forceUpdate' => 1, 'inputKeteranganPengecualian' => 1]);
        }

        if($prosesKe != 0 && $inboundJatah !== $jumlahInbound && $forceUpdate !== 1) return throwResponseNotAcceptable('Anda memasukkan jumlah yang tidak sesuai. <b>Pastikan jumlah fisik sudah dihitung ulang.</b><br/><br/>Beri keterangan dibawah jika anda benar-benar ingin memasukkan jumlah yang tidak sesuai.', ['forceUpdate' => 1, 'inputKeteranganPengecualian' => 1]);
        $arrUpdateNotaPembelianInbound = [
            'INBOUNDJUMLAH'     =>  $jumlahInbound,
            'PROSESUSER'        =>  $this->userData->name,
            'PROSESKE'          =>  ($prosesKe + 1),
            'PROSESTANGGALWAKTU'=>  $this->currentDateTime,
            'PROSESALLOW'       =>  0
        ];

        if($prosesKe != 0 && $inboundJatah !== $jumlahInbound && $forceUpdate == 1) {
            $rulesPengecualian  =   ['keteranganPengecualian'=>  ['label' => 'Keterangan Pengecualian', 'rules' => 'required|alpha_numeric_space|min_length[10]|max_length[255]']];
            if(!$this->validate($rulesPengecualian)) {
                return throwResponseNotAcceptable("", array_merge(['forceUpdate' => 1, 'inputKeteranganPengecualian' => 1], ['messages' => $this->validator->getErrors()]));
            }
            
            $keteranganPengecualian     =   $this->request->getVar('keteranganPengecualian');
            $arrUpdateNotaPembelianInbound['PROSESKETERANGAN'] = $keteranganPengecualian;
        }

        $procUpdate =   $mainOperation->updateDataTable('t_notapembelianinbound', $arrUpdateNotaPembelianInbound, ['IDNOTAPEMBELIANINBOUND' => $idNotaPembelianInbound]);

        if(!$procUpdate['status']) return switchMySQLErrorCode($procUpdate['errCode']);
        $this->insertDataStokbarangGudang($detailNotaPembelianInbound, $jumlahInbound);
        
        return throwResponseOK('Data inbound berhasil diperbarui', ['forceUpdate' => 0]);
    }
    
    private function insertDataStokbarangGudang($detailNotaPembelianInbound, $jumlahInbound){
        $idBarang               =   isset($detailNotaPembelianInbound['IDBARANG']) && $detailNotaPembelianInbound['IDBARANG'] != "" ? $detailNotaPembelianInbound['IDBARANG'] : 0;
        $idBarangSKU            =   isset($detailNotaPembelianInbound['IDBARANGSKU']) && $detailNotaPembelianInbound['IDBARANGSKU'] != "" ? $detailNotaPembelianInbound['IDBARANGSKU'] : 0;
        $idBarangSatuan         =   isset($detailNotaPembelianInbound['IDBARANGSATUAN']) && $detailNotaPembelianInbound['IDBARANGSATUAN'] != "" ? $detailNotaPembelianInbound['IDBARANGSATUAN'] : 0;
        $idNotaPembelianBarang  =   isset($detailNotaPembelianInbound['IDNOTAPEMBELIANBARANG']) && $detailNotaPembelianInbound['IDNOTAPEMBELIANBARANG'] != "" ? $detailNotaPembelianInbound['IDNOTAPEMBELIANBARANG'] : 0;
        $idNotaPembelianRekap   =   isset($detailNotaPembelianInbound['IDNOTAPEMBELIANREKAP']) && $detailNotaPembelianInbound['IDNOTAPEMBELIANREKAP'] != "" ? $detailNotaPembelianInbound['IDNOTAPEMBELIANREKAP'] : 0;
        $arrInsertGudangStok    =   [
            'IDGUDANG'              =>  $this->idGudang,
            'IDBARANG'              =>  $idBarang,
            'IDBARANGSKU'           =>  $idBarangSKU,
            'IDBARANGSATUAN'        =>  $idBarangSatuan,
            'IDNOTAPEMBELIANBARANG' =>  $idNotaPembelianBarang,
            'IDMUTASIJENISGUDANG'   =>  1,
            'JUMLAHMASUK'           =>  $jumlahInbound,
            'JUMLAHKELUAR'          =>  0,
            'MUTASIKETERANGAN'      =>  'Inbound barang dari nota pembelian No. '.$detailNotaPembelianInbound['NOTAPEMBELIANNOMOR'].' ('.$detailNotaPembelianInbound['TANGGALNOTA'].')',
            'INPUTUSER'             =>  $this->userData->name.' ('.$this->userData->userLevelName.')',
            'INPUTTANGGALWAKTU'     =>  $this->currentDateTime
        ];
        
        $mainOperation  =   new MainOperation();
        $mainOperation->insertDataTable('t_gudangstok', $arrInsertGudangStok);
        $this->updatePersentaseBarangInbound($idNotaPembelianRekap);
        
        return true;
    }
    
    private function updatePersentaseBarangInbound($idNotaPembelianRekap){
        $inboundBarangModel         =   new InboundBarangModel();
        $mainOperation              =   new MainOperation();
        $dataPenyelesaianInbound    =   $inboundBarangModel->getDataPenyelesaianInbound($idNotaPembelianRekap);
        $jumlahPembelian            =   $dataPenyelesaianInbound['JUMLAHPEMBELIAN'];
        $jumlahInbound              =   $dataPenyelesaianInbound['JUMLAHINBOUND'];
        $persenPenyelesaianInbound  =   $jumlahPembelian > 0 && $jumlahInbound > 0 ? floor(($jumlahInbound / $jumlahPembelian * 100)) : 0;
        $persenPenyelesaianInbound  =   $persenPenyelesaianInbound > 100 ? 100 : $persenPenyelesaianInbound;

        $mainOperation->updateDataTable('t_notapembelianrekap', ['PERSENPENYELESAIANINBOUND' => $persenPenyelesaianInbound], ['IDNOTAPEMBELIANREKAP' => $idNotaPembelianRekap]);

        return true;
    }

    public function getListNotaHistori()
    {
        $rules  =   [
            'searchKeyword' =>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'dataPerPage'   =>  ['label' => 'Data Per Halaman', 'rules' => 'required|numeric|greater_than[0]'],
            'pageNumber'    =>  ['label' => 'Nomor Halaman', 'rules' => 'required|numeric|greater_than[0]'],
        ];

        $messages   =   [
            'dataPerPage'   =>  [
                'required'      =>  'Data kiriman tidak valid, silakan periksa kembali',
                'numeric'       =>  'Data kiriman tidak valid, silakan periksa kembali',
                'greater_than'  =>  'Data kiriman tidak valid, silakan periksa kembali'
            ],
            'pageNumber'    =>  [
                'required'      =>  'Data kiriman tidak valid, silakan periksa kembali',
                'numeric'       =>  'Data kiriman tidak valid, silakan periksa kembali',
                'greater_than'  =>  'Data kiriman tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation      =   new MainOperation();
        $inboundBarangModel =   new InboundBarangModel();
        $searchKeyword      =   $this->request->getVar('searchKeyword');
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $pageNumber         =   $this->request->getVar('pageNumber');
        $baseData           =   $inboundBarangModel->getDataNotaHistori($this->idGudang, $searchKeyword);
        $totalNumberData    =   $baseData->countAllResults(false);
        $pageProperty       =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){            
            $dataNotaHistori    =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $dataNotaHistori    =   encodeDatabaseObjectResultKey($dataNotaHistori, ['IDNOTAPEMBELIANREKAP']);
            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $dataNotaHistori,
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn     =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty
            ];
            return throwResponseNotFound('Data histori nota inbound tidak ditemukan', $dataReturn);
        }
    }
}