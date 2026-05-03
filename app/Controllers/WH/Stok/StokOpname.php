<?php
namespace App\Controllers\WH\Stok;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\WH\Stok\StokOpnameModel;
use App\Models\MainOperation;

class StokOpname extends ResourceController
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

    public function getListDataStokOpname()
    {
        $rules  =   [
            'tanggalAwal'       =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'      =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'tampilBelumProses' =>  ['label' => 'Tampilkan Work Order Belum Proses Saja', 'rules' => 'required|in_list[0,1]'],
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'tanggalAwal' => [
                'regex_match'   => 'Tanggal awal periode tidak valid, silakan periksa kembali',
            ],
            'tanggalAkhir' => [
                'regex_match'   => 'Tanggal akhir periode tidak valid, silakan periksa kembali',
            ],
            'tampilBelumProses' => [
                'in_list'   => 'Tampilkan work order belum proses tidak valid, silakan periksa kembali',
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $tanggalAwal        =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir       =   $this->request->getVar('tanggalAkhir');
        $kataKunciPencarian =   $this->request->getVar('kataKunciPencarian');
        $tampilBelumProses  =   $this->request->getVar('tampilBelumProses');
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $pageNumber         =   $this->request->getVar('pageNumber');

        $mainOperation      =   new MainOperation();
        $stokOpnameModel    =   new StokOpnameModel();
        $baseData           =   $stokOpnameModel->getDaftarStokOpnameRekap($this->idGudang, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian, $tampilBelumProses);
        $totalNumberData    =   $baseData->countAllResults(false);
        $pageProperty       =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $dataStokOpname =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            foreach($dataStokOpname as $keyStokOpname){
                $idStokOpnameRekap              =   isset($keyStokOpname->IDSTOKOPNAMEREKAP) && $keyStokOpname->IDSTOKOPNAMEREKAP != "" ? $keyStokOpname->IDSTOKOPNAMEREKAP : 0;
                $dataBarangSKU                  =   $stokOpnameModel->getDaftarStokOpnameBarang($idStokOpnameRekap);
                $keyStokOpname->DATABARANGSKU   =   encodeDatabaseObjectResultKey($dataBarangSKU, ['IDSTOKOPNAMEBARANG']);

                switch (intval($keyStokOpname->STATUS)) {
                    case -1: $keyStokOpname->STATUSSTR  =   'Dibatalkan'; break;
                    case 0: $keyStokOpname->STATUSSTR   =   'Belum Diproses'; break;
                    case 1: $keyStokOpname->STATUSSTR   =   'Dalam Proses'; break;
                    case 2: $keyStokOpname->STATUSSTR   =   'Sudah Diproses'; break;
                    default: $keyStokOpname->STATUSSTR  =   '-'; break;
                }
            }

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  encodeDatabaseObjectResultKey($dataStokOpname, ['IDSTOKOPNAMEREKAP']),
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty
            ];
            return throwResponseNotFound('Tidak ada data stok opname yang ditemukan', $dataReturn);
        }
    }

    public function checkStokSaveOpnameBarang()
    {
        $rules  =   [
            'idStokOpnameRekap' =>  ['label' => 'Id Stok Opname Rekap', 'rules' => 'required|alpha_numeric'],
            'idStokOpnameBarang'=>  ['label' => 'Id Stok Opname Barang', 'rules' => 'required|alpha_numeric'],
            'jumlahStokFisik'   =>  ['label' => 'Jumlah Barang Fisik', 'rules' => 'required|integer|greater_than_equal_to[0]'],
        ];

        $messages   =   [
            'idStokOpnameRekap' =>  [
                'alpha_numeric' =>  'Data stok opname tidak valid, silakan periksa kembali'
            ],
            'idStokOpnameBarang'=>  [
                'alpha_numeric' =>  'Data stok opname barang tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $idStokOpnameRekap  =   hashidDecode($this->request->getVar('idStokOpnameRekap'));
        $idStokOpnameBarang =   hashidDecode($this->request->getVar('idStokOpnameBarang'));
        
        $stokOpnameModel    =   new StokOpnameModel();
        $detailStokBarang   =   $stokOpnameModel->getDetailStokBarangOpname($this->idGudang, $idStokOpnameRekap, $idStokOpnameBarang);
        if(!$detailStokBarang) return throwResponseNotFound('Data stok opname barang tidak ditemukan atau tidak valid');
        
        $idBarang           =   intval($detailStokBarang['IDBARANG'] ?? 0);
        $idBarangSKU        =   intval($detailStokBarang['IDBARANGSKU'] ?? 0);
        $idBarangSatuan     =   intval($detailStokBarang['IDBARANGSATUAN'] ?? 0);
        $kodeBarangSKU      =   $detailStokBarang['KODESKU'] ?? '-';
        $deskripsiSKU       =   $detailStokBarang['DESKRIPSISKU'] ?? '-';
        $namaSatuan         =   $detailStokBarang['NAMASATUAN'] ?? '-';
        $statusOpnameBarang =   intval($detailStokBarang['OPNAMESTATUS'] ?? 0);
        if($statusOpnameBarang != 0) return throwResponseNotFound('Stok opname untuk barang <b>' . $kodeBarangSKU . ' - ' . $deskripsiSKU . '</b> sudah diproses & tidak dapat dilakukan perubahan jumlah fisik lagi');
        
        $mainOperation      =   new MainOperation();
        $jumlahStokFisik    =   $this->request->getVar('jumlahStokFisik');
        $jumlahStokData     =   $stokOpnameModel->getJumlahStokDataBarang($this->idGudang, $idBarangSKU, $idBarangSatuan);
        $jumlahSelisih      =   $jumlahStokFisik - $jumlahStokData;

        if($jumlahSelisih != 0){
            $idMutasiJenisGudang=   $jumlahSelisih > 0 ? 3 : 4; // 3=Stok Opname - Penambahan, 4=Stok Opname - Pengurangan
            $jumlahMasuk        =   $jumlahSelisih > 0 ? $jumlahSelisih : 0;
            $jumlahKeluar       =   $jumlahSelisih < 0 ? abs($jumlahSelisih) : 0;
            $keteranganSelisih  =   $jumlahSelisih < 0 ? 'kurang' : 'lebih';
            $arrInsertStok      =   [
                'IDGUDANG'              =>  $this->idGudang,
                'IDBARANG'              =>  $idBarang,
                'IDBARANGSKU'           =>  $idBarangSKU,
                'IDMUTASIJENISGUDANG'   =>  $idMutasiJenisGudang,
                'IDBARANGSATUAN'        =>  $idBarangSatuan,
                'JUMLAHMASUK'           =>  $jumlahMasuk,
                'JUMLAHKELUAR'          =>  $jumlahKeluar,
                'MUTASIKETERANGAN'      =>  'Penyesuaian stok hasil opname barang [jumlah fisik '.$keteranganSelisih.']['.$jumlahSelisih.' '.$namaSatuan.']',
                'INPUTUSER'             =>  $this->userData->name,
                'INPUTTANGGALWAKTU'     =>  $this->currentDateTime
            ];

            $procInsertStokToko =   $mainOperation->insertDataTable('t_gudangstok', $arrInsertStok);
            if(!$procInsertStokToko['status']) return switchMySQLErrorCode($procInsertStokToko['errCode']);
        }

        $keteranganOpname   =   $jumlahSelisih == 0 ? 'Stok fisik sesuai dengan data sistem' : 'Stok fisik tidak sesuai dengan data sistem';
        $arrUpdateOpname    =   [
            'STOKDATA'          =>  $jumlahStokData,
            'STOKFISIK'         =>  $jumlahStokFisik,
            'STOKSELISIH'       =>  $jumlahSelisih,
            'KETERANGAN'        =>  $keteranganOpname,
            'OPNAMEUSER'        =>  $this->userData->name,
            'OPNAMETANGGALWAKTU'=>  $this->currentDateTime,
            'OPNAMESTATUS'      =>  1
        ];
        $mainOperation->updateDataTable('t_stokopnamebarang', $arrUpdateOpname, ['IDSTOKOPNAMEBARANG'=>  $idStokOpnameBarang]);
        $this->updateStatusStokOpnameRekap($idStokOpnameRekap);

        if($jumlahSelisih != 0){
            return throwResponseNotAcceptable(
                        'Data stok fisik <b>['.$jumlahStokFisik.' '.$namaSatuan.']</b> tidak sesuai dengan data sistem <b>['.$jumlahStokData.' '.$namaSatuan.']</b>.<br/><b>Harap tambahkan keterangan/penjelasan lebih lanjut!</b>',
                        [
                            'idStokOpnameBarang'    =>  hashidEncode($idStokOpnameBarang),
                            'inputPenjelasan'       =>  true
                        ]
                    );
        } else {
            return throwResponseOK('Data stok fisik sesuai, stok opname disimpan');
        }
    }

    private function updateStatusStokOpnameRekap($idStokOpnameRekap)
    {
        $stokOpnameModel    =   new StokOpnameModel();
        $isStokOpnameFinish =   $stokOpnameModel->isStokOpnameFinish($idStokOpnameRekap);

        if($isStokOpnameFinish) {
            $mainOperation  =   new MainOperation();
            $mainOperation->updateDataTable('t_stokopnamerekap', ['STATUS' => 2], ['IDSTOKOPNAMEREKAP' => $idStokOpnameRekap]);
        }
        return true;
    }

    public function updatePenjelasanStokOpname()
    {
        $rules  =   [
            'idStokOpnameBarang'=>  ['label' => 'Id Stok Opname Barang', 'rules' => 'required|alpha_numeric'],
            'penjelasanOpname'  =>  ['label' => 'Penjelasan Opname', 'rules' => 'required|alpha_numeric_space|min_length[10]|max_length[255]'],
        ];

        $messages   =   [
            'idStokOpnameBarang'=>  [
                'alpha_numeric' =>  'Data stok opname barang tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation      =   new MainOperation();
        $idStokOpnameBarang =   hashidDecode($this->request->getVar('idStokOpnameBarang'));
        $penjelasanOpname   =   $this->request->getVar('penjelasanOpname');
        $procUpdateOpname   =   $mainOperation->updateDataTable('t_stokopnamebarang', ['KETERANGAN' => $penjelasanOpname], ['IDSTOKOPNAMEBARANG'=>  $idStokOpnameBarang]);

        if(!$procUpdateOpname['status']) return switchMySQLErrorCode($procUpdateOpname['errCode']);
        return throwResponseOK('Penjelasan stok opname barang telah diterima');
    }
}