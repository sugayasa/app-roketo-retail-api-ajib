<?php

namespace App\Controllers\POS\Stok;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\POS\Stok\PengaturanStokModel;
use App\Models\MainOperation;
use App\Models\ERP\Master\BarangSKUModel;

class PengaturanStok extends ResourceController
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
            $this->request          =   $request;
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

    public function getListBarangStokPenjualan()
    {
        $rules  =   [
            'idBarangKategori'  =>  ['label' => 'Kategori Barang', 'rules' => 'required|alpha_numeric'],
            'idBarangMerk'      =>  ['label' => 'Merk Barang', 'rules' => 'permit_empty|alpha_numeric'],
            'searchKeyword'     =>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'sortCondition'     =>  ['label' => 'Kondisi Urutan', 'rules' => 'permit_empty|alpha_numeric_punct']
        ];

        $messages   =   [
            'idBarangKategori'  =>  [
                'alpha_numeric' =>  'Data kategori barang tidak valid, silakan periksa kembali'
            ],
            'idBarangMerk'     =>  [
                'alpha_numeric' =>  'Data merk barang tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());
        
        $mainOperation          =   new MainOperation();
        $pengaturanStokModel    =   new PengaturanStokModel();

        $idBarangKategori       =   $this->request->getVar('idBarangKategori');
        $idBarangKategori       =   isset($idBarangKategori) && $idBarangKategori != "" ? hashidDecode($idBarangKategori) : 0;
        $idBarangMerk           =   $this->request->getVar('idBarangMerk');
        $idBarangMerk           =   isset($idBarangMerk) && $idBarangMerk != "" ? hashidDecode($idBarangMerk) : 0;
        $searchKeyword          =   $this->request->getVar('searchKeyword');
        $sortCondition          =   $this->request->getVar('sortCondition');
        $detailToko             =   $mainOperation->getDetailToko($this->idToko);
        $idGudangToko           =   $detailToko['IDGUDANG'] ?? 0;
        $idKelompokHargaGrosir  =   $detailToko['IDKELOMPOKHARGAGROSIR'] ?? 0;
        $dataBarangStokPenjualan=	$pengaturanStokModel->getDataBarangStokPenjualan($idGudangToko, $this->idToko, $idKelompokHargaGrosir, $idBarangKategori, $idBarangMerk, $searchKeyword, $sortCondition);

        if(!$dataBarangStokPenjualan) return throwResponseNotFound('Tidak ada data barang yang ditemukan');
        $barangSKUModel =   new BarangSKUModel();
        foreach($dataBarangStokPenjualan as $keyBarangStokPenjualan){
            $idBarangSKU                            =   isset($keyBarangStokPenjualan->IDBARANGSKU) && $keyBarangStokPenjualan->IDBARANGSKU != "" ? $keyBarangStokPenjualan->IDBARANGSKU : 0;
            $keyBarangStokPenjualan->ATRIBUTSKUSTR  =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
        }

        $dataBarangStokPenjualan    =   encodeDatabaseObjectResultKey($dataBarangStokPenjualan, ['IDBARANGSKU']);
        return $this->setResponseFormat('json')->respond(["dataBarangStokPenjualan" => $dataBarangStokPenjualan]);
    }

    public function saveRequestStok()
    {
        $rules  =   [
            'arrDataBarangRequest.*.idBarangSKU'=>  ['label' => 'Id Barang', 'rules' => 'required|alpha_numeric'],
            'arrDataBarangRequest.*.jumlah'     =>  ['label' => 'Jumlah Barang Diminta', 'rules' => 'required|integer|greater_than[0]'],
            'keterangan'                        =>  ['label' => 'Kondisi Urutan', 'rules' => 'permit_empty|alpha_numeric_punct']
        ];

        $messages   =   [
            'arrDataBarangRequest.*.idBarangSKU'  =>  [
                'required'      =>  'Barang yang dipilih tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data barang yang dipilih tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation          =   new MainOperation();
        $pengaturanStokModel    =   new PengaturanStokModel();
        $arrDataBarangRequest   =   $this->request->getVar('arrDataBarangRequest');
        $arrDataBarangRequest   =   isset($arrDataBarangRequest) && is_array($arrDataBarangRequest) ? $arrDataBarangRequest : [];
        $keterangan             =   $this->request->getVar('keterangan');
        $idGudangParentToko     =   $mainOperation->getIdGudangParentToko($this->idToko);
        $arrDataStokTidakCukup  =   $arrDataBarangSKU   =   [];

        foreach($arrDataBarangRequest as $keyDataBarangRequest){
            $idBarangSKUEncode      =   isset($keyDataBarangRequest->idBarangSKU) && $keyDataBarangRequest->idBarangSKU != "" ? $keyDataBarangRequest->idBarangSKU : 0;
            $idBarangSKU            =   $idBarangSKUEncode != 0 ? hashidDecode($idBarangSKUEncode) : 0;
            $jumlahRequest          =   isset($keyDataBarangRequest->jumlah) && $keyDataBarangRequest->jumlah != "" ? $keyDataBarangRequest->jumlah : 0;
            $dataStokBarangGudang   =   $pengaturanStokModel->getStokBarangGudangParent($idGudangParentToko, $idBarangSKU);
            $idBarang               =   $dataStokBarangGudang->IDBARANG ?? 0;
            $stokBarangGudang       =   $dataStokBarangGudang->STOK ?? 0;

            if($stokBarangGudang < $jumlahRequest) {
                $arrDataStokTidakCukup[] = [
                    'idBarangSKU' => $idBarangSKUEncode,
                    'jumlah'      => intval($jumlahRequest),
                    'stok'        => intval($stokBarangGudang)
                ];
            } else {
                $arrDataBarangSKU[] = [
                    'idBarangSKU' => $idBarangSKU,
                    'idBarang'    => $idBarang,
                    'jumlah'      => intval($jumlahRequest)
                ];
            }
        }

        if(count($arrDataStokTidakCukup) > 0) return throwResponseNotAcceptable('Stok barang tidak mencukupi untuk permintaan ini', ['dataStokTidakCukup' => $arrDataStokTidakCukup]);
        $arrInsertNotaRekap     =   [
            'IDTOKO'                    =>  $this->idToko,
            'IDGUDANG'                  =>  $idGudangParentToko,
            'NOTAMUTASINOMOR'           =>  $this->generateNotaMutasiTokoNomor(),
            'TOTALSKU'                  =>  count($arrDataBarangSKU),
            'PERSENPENYELESAIANINBOUND' =>  0,
            'KETERANGAN'                =>  $keterangan,
            'REQUESTUSER'               =>  $this->userData->name.' (Toko - '.$this->userData->userLevelName.')',
            'REQUESTTANGGALWAKTU'       =>  $this->currentDateTime
        ];

        $procInsertNotaRekap    =   $mainOperation->insertDataTable('t_tokonotamutasirekap', $arrInsertNotaRekap);
        if(!$procInsertNotaRekap['status']) return switchMySQLErrorCode($procInsertNotaRekap['errCode']);
        $idNotaMutasiRekap      =   $procInsertNotaRekap['insertID'];

        foreach($arrDataBarangSKU as $keyDataBarangSKU){
            $idBarang           =   $keyDataBarangSKU['idBarang'];
            $idBarangSKU        =   $keyDataBarangSKU['idBarangSKU'];
            $jumlahRequest      =   $keyDataBarangSKU['jumlah'];
            
            if($jumlahRequest > 0) {
                $arrInsertNotaMutasiBarang  =   [
                    'IDTOKONOTAMUTASIREKAP' =>  $idNotaMutasiRekap,
                    'IDBARANG'              =>  $idBarang,
                    'IDBARANGSKU'           =>  $idBarangSKU,
                    'JUMLAHREQUEST'         =>  $jumlahRequest
                ];
                $mainOperation->insertDataTable('t_tokonotamutasibarang', $arrInsertNotaMutasiBarang);
            }
        }

        return throwResponseOK('Permintaan stok berhasil disimpan', [
            'idNotaMutasiRekap' => hashidEncode($idNotaMutasiRekap)
        ]);
    }

    private function generateNotaMutasiTokoNomor()
    {
        return 'NMT-' . strtoupper(bin2hex(random_bytes(2))) . date('ymd');
    }

    public function getListNotaPenerimaanStokAktif()
    {
        $pengaturanStokModel=   new PengaturanStokModel();
        $listData           =   $pengaturanStokModel->getDataNotaPenerimaanStokAktif($this->idToko);

        if(!$listData) return throwResponseNotFound('Tidak ada nota stok yang ditemukan', ['listData' =>  []]);

        $listData           =   encodeDatabaseObjectResultKey($listData, ['IDTOKONOTAMUTASIREKAP']);
        return $this->setResponseFormat('json')->respond(["listData" =>  $listData]);
    }

    public function getDetailNotaPenerimaanStokAktif()
    {
        $rules      =   ['idTokoNotaMutasiRekap'    =>  ['label' => 'Id Toko Nota Mutasi Rekap', 'rules' => 'required|alpha_numeric']];
        $messages   =   [
            'idTokoNotaMutasiRekap'  => [
                'required'      => 'Data kiriman tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak valid, silakan periksa kembali'
            ]
        ];
        
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $pengaturanStokModel        =   new PengaturanStokModel();
        $idTokoNotaMutasiRekap      =   hashidDecode($this->request->getVar('idTokoNotaMutasiRekap'));
        $detailTokoNotaMutasiRekap  =	$pengaturanStokModel->getDetailTokoNotaMutasiRekap($idTokoNotaMutasiRekap);

        if(!$detailTokoNotaMutasiRekap) return throwResponseNotFound('Detail nota pembelian yang ditemukan');

        $dataBarangSKU  =	$pengaturanStokModel->getDataBarangSKUNotaPengajuanStok($idTokoNotaMutasiRekap);
        if($dataBarangSKU && count($dataBarangSKU) > 0){
            $barangSKUModel =   new BarangSKUModel();
            foreach($dataBarangSKU as $keyBarangSKU){
                $idBarangSKU                =   isset($keyBarangSKU->IDBARANGSKU) && $keyBarangSKU->IDBARANGSKU != "" ? $keyBarangSKU->IDBARANGSKU : 0;
                $keyBarangSKU->ATRIBUTSKUSTR=   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                $keyBarangSKU->PROSESALLOW  =   (bool) $keyBarangSKU->PROSESALLOW;
                unset($keyBarangSKU->IDBARANGSKU);
            }
        }

        $dataBarangSKU=   encodeDatabaseObjectResultKey($dataBarangSKU, ['IDTOKONOTAMUTASIINBOUND']);
        return $this->setResponseFormat('json')->respond(["detailTokoNotaMutasiRekap" => $detailTokoNotaMutasiRekap, "dataBarangSKU" => $dataBarangSKU]);
    }

    public function saveInboundStokPerBarang()
    {
        $rules      =   [
            'idTokoNotaMutasiInbound'   =>  ['label' => 'Id Nota Mutasi Inbound', 'rules' => 'required|alpha_numeric'],
            'jumlahInbound'             =>  ['label' => 'Jumlah Barang Diterima', 'rules' => 'required|numeric|min_length[1]|max_length[4]|greater_than[0]'],
            'forceUpdate'               =>  ['label' => 'Status Pengecualian', 'rules' => 'required|in_list[0,1]']
        ];

        $messages   =   [
            'idTokoNotaMutasiInbound'    => [
                'required'      => 'Data kiriman tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak valid, silakan periksa kembali'
            ]
        ];
        
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $pengaturanStokModel        =   new PengaturanStokModel();
        $mainOperation              =   new MainOperation();
        $idTokoNotaMutasiInbound    =   hashidDecode($this->request->getVar('idTokoNotaMutasiInbound'));
        $jumlahInbound              =   $this->request->getVar('jumlahInbound');
        $forceUpdate                =   $this->request->getVar('forceUpdate');
        $detailNotaTokoMutasiStok   =	$pengaturanStokModel->getDetailNotaTokoMutasiStok($idTokoNotaMutasiInbound);

        if(!$detailNotaTokoMutasiStok) return throwResponseNotFound('Detail nota mutasi stok toko tidak ditemukan');

        $jumlahDisetujui=   intval($detailNotaTokoMutasiStok['JUMLAHDISETUJUI']);
        $prosesKe       =   $detailNotaTokoMutasiStok['PROSESKE'];
        $prosesAllow    =   $detailNotaTokoMutasiStok['PROSESALLOW'];

        if(!$prosesAllow || (int)$prosesAllow !== 1) return throwResponseNotAcceptable('Proses inbound sudah tidak diperbolehkan untuk SKU ini', ['forceUpdate' => 0, 'inputKeteranganPengecualian' => 0]);
        if($prosesKe == 0 && $jumlahDisetujui !== $jumlahInbound) {
            $mainOperation->updateDataTable('t_tokonotamutasiinbound', ['PROSESKE' => 1], ['IDTOKONOTAMUTASIINBOUND' => $idTokoNotaMutasiInbound]);
            return throwResponseNotAcceptable('Jumlah barang diterima tidak sesuai dengan jumlah barang yang dikirim gudang<br/>Harap periksa kembali <i class="text-black"><strong>JUMLAH FISIK BARANG!</strong></i>', ['forceUpdate' => 1, 'inputKeteranganPengecualian' => 1, 'jumlahDisetujui' => $jumlahDisetujui]);
        }

        if($prosesKe != 0 && $jumlahDisetujui !== $jumlahInbound && $forceUpdate !== 1) return throwResponseNotAcceptable('Anda memasukkan jumlah yang tidak sesuai. <b>Pastikan jumlah fisik sudah dihitung ulang.</b><br/><br/>Beri keterangan dibawah jika anda benar-benar ingin memasukkan jumlah yang tidak sesuai.', ['forceUpdate' => 1, 'inputKeteranganPengecualian' => 1, 'jumlahDisetujui' => $jumlahDisetujui]);
        $arrUpdateNotaStokTokoInbound   = [
            'JUMLAHINBOUND'     =>  $jumlahInbound,
            'PROSESUSER'        =>  $this->userData->name,
            'PROSESKE'          =>  ($prosesKe + 1),
            'PROSESTANGGALWAKTU'=>  $this->currentDateTime,
            'PROSESALLOW'       =>  0
        ];

        if($prosesKe != 0 && $jumlahDisetujui !== $jumlahInbound && $forceUpdate == 1) {
            $rulesPengecualian  =   ['keteranganPengecualian'=>  ['label' => 'Keterangan Pengecualian', 'rules' => 'required|alpha_numeric_space|min_length[10]|max_length[255]']];
            if(!$this->validate($rulesPengecualian)) {
                return throwResponseNotAcceptable("", array_merge(['forceUpdate' => 1, 'inputKeteranganPengecualian' => 1], ['messages' => $this->validator->getErrors()]));
            }
            
            $keteranganPengecualian     =   $this->request->getVar('keteranganPengecualian');
            $arrUpdateNotaPembelianInbound['PROSESKETERANGAN'] = $keteranganPengecualian;
        }

        $procUpdate =   $mainOperation->updateDataTable('t_tokonotamutasiinbound', $arrUpdateNotaStokTokoInbound, ['IDTOKONOTAMUTASIINBOUND' => $idTokoNotaMutasiInbound]);

        if(!$procUpdate['status']) return switchMySQLErrorCode($procUpdate['errCode']);
        $this->insertDataStokBarangToko($detailNotaTokoMutasiStok, $jumlahInbound);

        return throwResponseOK('Data inbound berhasil diperbarui', ['forceUpdate' => 0]);
    }

    private function insertDataStokBarangToko($detailNotaTokoMutasiStok, $jumlahInbound)
    {
        $idGudang               =   isset($detailNotaTokoMutasiStok['IDGUDANG']) && $detailNotaTokoMutasiStok['IDGUDANG'] != "" ? $detailNotaTokoMutasiStok['IDGUDANG'] : 0;
        $idBarang               =   isset($detailNotaTokoMutasiStok['IDBARANG']) && $detailNotaTokoMutasiStok['IDBARANG'] != "" ? $detailNotaTokoMutasiStok['IDBARANG'] : 0;
        $idBarangSKU            =   isset($detailNotaTokoMutasiStok['IDBARANGSKU']) && $detailNotaTokoMutasiStok['IDBARANGSKU'] != "" ? $detailNotaTokoMutasiStok['IDBARANGSKU'] : 0;
        $idTokoNotaMutasiBarang =   isset($detailNotaTokoMutasiStok['IDTOKONOTAMUTASIBARANG']) && $detailNotaTokoMutasiStok['IDTOKONOTAMUTASIBARANG'] != "" ? $detailNotaTokoMutasiStok['IDTOKONOTAMUTASIBARANG'] : 0;
        $idTokoNotaMutasiRekap  =   isset($detailNotaTokoMutasiStok['IDTOKONOTAMUTASIREKAP']) && $detailNotaTokoMutasiStok['IDTOKONOTAMUTASIREKAP'] != "" ? $detailNotaTokoMutasiStok['IDTOKONOTAMUTASIREKAP'] : 0;
        $idBarangSatuan         =   isset($detailNotaTokoMutasiStok['IDBARANGSATUAN']) && $detailNotaTokoMutasiStok['IDBARANGSATUAN'] != "" ? $detailNotaTokoMutasiStok['IDBARANGSATUAN'] : 1;
        $arrInsertTokoStok      =   [
            'IDTOKO'                =>  $this->idToko,
            'IDBARANG'              =>  $idBarang,
            'IDBARANGSKU'           =>  $idBarangSKU,
            'IDTOKONOTAMUTASIBARANG'=>  $idTokoNotaMutasiBarang,
            'IDMUTASIJENISTOKO'     =>  1,
            'IDBARANGSATUAN'        =>  $idBarangSatuan,
            'JUMLAHMASUK'           =>  $jumlahInbound,
            'JUMLAHKELUAR'          =>  0,
            'MUTASIKETERANGAN'      =>  'Inbound barang dari nota mutasi stok gudang no. '.$detailNotaTokoMutasiStok['NOTAMUTASINOMOR'].' ('.$detailNotaTokoMutasiStok['TANGGALNOTA'].')',
            'INPUTUSER'             =>  $this->userData->name.' ('.$this->userData->userLevelName.')',
            'INPUTTANGGALWAKTU'     =>  $this->currentDateTime
        ];
        
        $mainOperation      =   new MainOperation();
        $procInsertStokToko =   $mainOperation->insertDataTable('t_tokostok', $arrInsertTokoStok);

        if($procInsertStokToko['status']){
            $arrInsertGudangStok    =   [
                'IDGUDANG'              =>  $idGudang,
                'IDBARANG'              =>  $idBarang,
                'IDBARANGSKU'           =>  $idBarangSKU,
                'IDMUTASIJENISGUDANG'   =>  2,
                'IDBARANGSATUAN'        =>  $idBarangSatuan,
                'JUMLAHMASUK'           =>  0,
                'JUMLAHKELUAR'          =>  $jumlahInbound,
                'MUTASIKETERANGAN'      =>  'Mutasi barang stok toko. Nota mutasi no. '.$detailNotaTokoMutasiStok['NOTAMUTASINOMOR'].' ('.$detailNotaTokoMutasiStok['TANGGALNOTA'].')',
                'INPUTUSER'             =>  $this->userData->name.' ('.$this->userData->userLevelName.')',
                'INPUTTANGGALWAKTU'     =>  $this->currentDateTime
            ];

            $mainOperation->insertDataTable('t_gudangstok', $arrInsertGudangStok);
            $this->updatePersentaseBarangInbound($idTokoNotaMutasiRekap);
        }
        
        return true;
    }
    
    private function updatePersentaseBarangInbound($idTokoNotaMutasiRekap)
    {
        $pengaturanStokModel        =   new PengaturanStokModel();
        $mainOperation              =   new MainOperation();
        $dataPenyelesaianInbound    =   $pengaturanStokModel->getDataPenyelesaianInbound($idTokoNotaMutasiRekap);
        $jumlahMutasi               =   $dataPenyelesaianInbound['JUMLAHMUTASI'];
        $jumlahInbound              =   $dataPenyelesaianInbound['JUMLAHINBOUND'];
        $persenPenyelesaianInbound  =   $jumlahMutasi > 0 && $jumlahInbound > 0 ? floor(($jumlahInbound / $jumlahMutasi * 100)) : 0;
        $persenPenyelesaianInbound  =   $persenPenyelesaianInbound > 100 ? 100 : $persenPenyelesaianInbound;

        $mainOperation->updateDataTable('t_tokonotamutasirekap', ['PERSENPENYELESAIANINBOUND' => $persenPenyelesaianInbound], ['IDTOKONOTAMUTASIREKAP' => $idTokoNotaMutasiRekap]);

        return true;
    }

    public function getDataHistoryNotaStok()
    {
        $rules  =   [
            'searchKeyword' =>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct']
        ];

        $messages   =   [];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation      =   new MainOperation();
        $pengaturanStokModel=   new PengaturanStokModel();

        $idToko             =   $this->idToko;
        $searchKeyword      =   $this->request->getVar('searchKeyword');
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $pageNumber         =   $this->request->getVar('pageNumber');
        $baseData           =	$pengaturanStokModel->getDataNotaStokHistori($idToko, $searchKeyword);
        $totalNumberData    =   $baseData->countAllResults(false);
        $pageProperty       =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $dataNotaStokHistory=   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $dataNotaStokHistory=   encodeDatabaseObjectResultKey($dataNotaStokHistory, ['IDTOKONOTAMUTASIREKAP']);

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $dataNotaStokHistory,
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty
            ];
            return throwResponseNotFound('Tidak ada data nota yang ditemukan', $dataReturn);
        }
    }

    public function getDetailHistoryNotaStok()
    {
        $rules      =   ['idTokoNotaMutasiRekap'    =>  ['label' => 'Id Toko Nota Mutasi Rekap', 'rules' => 'required|alpha_numeric']];
        $messages   =   [
            'idTokoNotaMutasiRekap'  => [
                'required'      => 'Data kiriman tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak valid, silakan periksa kembali'
            ]
        ];
        
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $pengaturanStokModel        =   new PengaturanStokModel();
        $idTokoNotaMutasiRekap      =   hashidDecode($this->request->getVar('idTokoNotaMutasiRekap'));
        $detailTokoNotaMutasiRekap  =	$pengaturanStokModel->getDetailTokoNotaMutasiRekapHistori($idTokoNotaMutasiRekap);

        if(!$detailTokoNotaMutasiRekap) return throwResponseNotFound('Detail nota pembelian yang ditemukan');

        $dataPembayaran =	$pengaturanStokModel->getDataPembayaranNotaMutasiToko($idTokoNotaMutasiRekap);
        $dataBarangSKU  =	$pengaturanStokModel->getDataBarangSKUNotaPengajuanStokHistori($idTokoNotaMutasiRekap);
        if($dataBarangSKU && count($dataBarangSKU) > 0){
            $barangSKUModel =   new BarangSKUModel();
            foreach($dataBarangSKU as $keyBarangSKU){
                $idBarangSKU                =   isset($keyBarangSKU->IDBARANGSKU) && $keyBarangSKU->IDBARANGSKU != "" ? $keyBarangSKU->IDBARANGSKU : 0;
                $keyBarangSKU->ATRIBUTSKUSTR=   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                unset($keyBarangSKU->IDBARANGSKU);
            }
        }

        return $this->setResponseFormat('json')
                    ->respond([
                        "detailTokoNotaMutasiRekap" =>  $detailTokoNotaMutasiRekap,
                        "dataBarangSKU"             =>  $dataBarangSKU,
                        "dataPembayaran"            =>  $dataPembayaran,
                        "baseURLBuktiPembayaran"    =>  URL_BUKTI_PEMBAYARAN
                    ]);
    }
}