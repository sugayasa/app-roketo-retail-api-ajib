<?php

namespace App\Controllers\ERP\Stok;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\MainOperation;
use App\Models\ERP\Stok\PembelianBarangModel;
use App\Models\ERP\Master\BarangModel;
use App\Models\ERP\Master\BarangSKUModel;

class PembelianBarang extends ResourceController
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

    public function getList()
    {
        $pembelianBarangModel   =   new PembelianBarangModel();
        $tahun                  =   $this->request->getVar('tahun');
        $searchKeyword          =   $this->request->getVar('searchKeyword');
        $isStatusNotaAktif      =   $this->request->getVar('isStatusNotaAktif');
        $listData               =   $pembelianBarangModel->getListNotaPembelian($tahun, $isStatusNotaAktif, $searchKeyword);

        if(!$listData) return throwResponseNotFound('Tidak ada data yang ditemukan', ['listData' =>  []]);
        $listData   =   encodeDatabaseObjectResultKey($listData, ['IDNOTAPEMBELIANREKAP']);
        return $this->setResponseFormat('json')->respond(["listData" =>  $listData]);
    }

    public function getDetail()
    {
        $rules  =   [
            'idNotaPembelianRekap'  =>  ['label' => 'Id Nota Pembelian Rekap', 'rules' => 'required|alpha_numeric']
        ];

        $messages   =   [
            'idNotaPembelianRekap'  => [
                'required'      =>  'Invalid data sent',
                'alpha_numeric' =>  'Invalid data sent'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $pembelianBarangModel   =   new PembelianBarangModel();
        $idNotaPembelianRekap   =   $this->request->getVar('idNotaPembelianRekap');
        $idNotaPembelianRekap   =   hashidDecode($idNotaPembelianRekap);
        $dataBarangSKU          =	$pembelianBarangModel->getDataBarangSKUNotaPembelian($idNotaPembelianRekap);

        if($dataBarangSKU){
            $barangSKUModel     =   new BarangSKUModel();
            $dataInboundGudang  =   [];

            foreach($dataBarangSKU as $keyBarangSKU){
                $idBarangSKU                =   isset($keyBarangSKU->IDBARANGSKU) && $keyBarangSKU->IDBARANGSKU != "" ? $keyBarangSKU->IDBARANGSKU : 0;
                $keyBarangSKU->ATRIBUTSKUSTR=   $barangSKUModel->getArrAtributSKU($idBarangSKU);
            }

            foreach($dataBarangSKU as $keyBarangSKU){
                $keyBarangInboundGudang =   clone $keyBarangSKU;
                $idNotaPembelianBarang  =   isset($keyBarangInboundGudang->IDNOTAPEMBELIANBARANG) && $keyBarangInboundGudang->IDNOTAPEMBELIANBARANG != "" ? $keyBarangInboundGudang->IDNOTAPEMBELIANBARANG : 0;
                $dataBarangInboundGudang=   $pembelianBarangModel->getDataInboundGudangNotaPembelianBarang($idNotaPembelianBarang);
                $arrDataInboundGudang   =   [];

                if($dataBarangInboundGudang&& count($dataBarangInboundGudang) > 0) {
                    $arrDataInboundGudang   =   encodeDatabaseObjectResultKey($dataBarangInboundGudang, ['IDGUDANG']);
                }

                $keyBarangInboundGudang->DATAINBOUNDGUDANG    =   $arrDataInboundGudang;
                unset($keyBarangInboundGudang->IDBARANG);
                unset($keyBarangInboundGudang->IDBARANGSKU);
                unset($keyBarangInboundGudang->HARGABELI);
                $dataInboundGudang[]  =   $keyBarangInboundGudang;
            }

            $dataBarangSKU      =   encodeDatabaseObjectResultKey($dataBarangSKU, ['IDNOTAPEMBELIANBARANG', 'IDBARANG', 'IDBARANGSKU']);
            $dataInboundGudang  =   encodeDatabaseObjectResultKey($dataInboundGudang, ['IDNOTAPEMBELIANBARANG']);
            return $this->setResponseFormat('json')
                        ->respond([
                            "dataBarangSKU"     =>  $dataBarangSKU,
                            "dataInboundGudang" =>  $dataInboundGudang
                        ]);
        } else {
            return throwResponseNotFound('Tidak ada detail yang ditemukan', ['dataBarangSKU' =>  []]);
        }
    }

    public function getDataOptionMerkNamaBarang()
    {
        $barangModel                =   new BarangModel();
        $listOptionMerkNamaBarang   =   $barangModel
                                        ->select("A.IDBARANG AS IDBARANG, CONCAT('[', C.NAMAKATEGORI, '] [', B.NAMAMERK, '] [', A.KODEBARANG, '] ', A.NAMABARANG) AS NAMABARANG")
                                        ->from('m_barang A', true)
                                        ->join('m_barangmerk AS B', 'B.IDBARANGMERK = A.IDBARANGMERK', 'LEFT')
                                        ->join('m_barangkategori AS C', 'C.IDBARANGKATEGORI = A.IDBARANGKATEGORI', 'LEFT')
                                        ->asObject()
                                        ->findAll();
        $listOptionMerkNamaBarang   =   encodeDatabaseObjectResultKey($listOptionMerkNamaBarang, ['IDBARANG']);
        return $this->setResponseFormat('json')->respond(["listOptionMerkNamaBarang" =>  $listOptionMerkNamaBarang]);
    }

    public function getDataOptionSKUBarang()
    {
        $rules  =   [
            'idBarang'  =>  ['label' => 'Id Barang', 'rules' => 'required|alpha_numeric']
        ];

        $messages   =   [
            'idBarang'  => [
                'required'      =>  'Invalid data sent',
                'alpha_numeric' =>  'Invalid data sent'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $pembelianBarangModel   =   new PembelianBarangModel();
        $barangSKUModel         =   new BarangSKUModel();
        $idBarang               =   $this->request->getVar('idBarang');
        $idBarang               =   hashidDecode($idBarang);
        $idNotaPembelianRekap   =   $this->request->getVar('idNotaPembelianRekap');
        $idNotaPembelianRekap   =   isset($idNotaPembelianRekap) && $idNotaPembelianRekap != "" ? hashidDecode($idNotaPembelianRekap) : false;
        $listOptionSKUBarang    =   $pembelianBarangModel->getDataOptionSKUBarang($idBarang, $idNotaPembelianRekap);

        if(!empty($listOptionSKUBarang) && count($listOptionSKUBarang) > 0) {
            foreach($listOptionSKUBarang as $keyOptionSKUBarang) {
                $idBarangSKU                            =   isset($keyOptionSKUBarang->IDBARANGSKU) && $keyOptionSKUBarang->IDBARANGSKU != "" ? $keyOptionSKUBarang->IDBARANGSKU : 0;
                $keyOptionSKUBarang->ARRIDBARANGSATUAN  =   encodeDatabaseObjectResultKey($barangSKUModel->getDataBarangSatuan($idBarang), ['IDBARANGSATUAN']);
                $keyOptionSKUBarang->ATRIBUTSKUSTR      =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
            }
        }

        $listOptionSKUBarang    =   encodeDatabaseObjectResultKey($listOptionSKUBarang, ['IDBARANGSKU', 'IDBARANG']);
        return $this->setResponseFormat('json')->respond(["listOptionSKUBarang" =>  $listOptionSKUBarang]);
    }

    public function addDataNotaPembelian()
    {
        $rules  =   [
            'idProdusenDistributor'         =>  ['label' => 'Produsen/Distributor', 'rules' => 'required|alpha_numeric'],
            'idGudangUtama'                 =>  ['label' => 'Gudang Utama', 'rules' => 'required|alpha_numeric'],
            'keterangan'                    =>  ['label' => 'Keterangan', 'rules' => 'required|regex_match[/^[a-zA-Z0-9 .,!?"()-_@#$%&]+$/]|min_length[3]|max_length[500]'],
            'dataBarangSKU.*.idBarang'      =>  ['label' => 'ID Barang', 'rules' => 'required|alpha_numeric'],
            'dataBarangSKU.*.idBarangSKU'   =>  ['label' => 'ID Barang SKU', 'rules' => 'required|alpha_numeric'],
            'dataBarangSKU.*.idBarangSatuan'=>  ['label' => 'ID Satuan Barang', 'rules' => 'required|alpha_numeric'],
            'dataBarangSKU.*.jumlah'        =>  ['label' => 'Jumlah Barang SKU', 'rules' => 'required|numeric|greater_than[0]'],
            'dataBarangSKU.*.hargaBeli'     =>  ['label' => 'Harga Beli Barang SKU', 'rules' => 'required|numeric|greater_than[0]']
        ];

        $messages   =   [
            'idProdusenDistributor'          => [
                'required'      => 'Data Produsen/Distributor yang dipilih tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data Produsen/Distributor yang dipilih tidak valid, silakan periksa kembali'
            ],
            'idGudangUtama'         => [
                'required'      => 'Harap pilih Gudang Utama yang dijadikan tujuan pembelian',
                'alpha_numeric' => 'Data Gudang Utama yang dipilih tidak valid, silakan periksa kembali'
            ],
            'dataBarangSKU.*.idBarang'      =>  [
                'required'      =>  'Data barang tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data barang tidak valid, silakan periksa kembali'
            ],
            'dataBarangSKU.*.idBarangSKU'   =>  [
                'required'      =>  'Data barang SKU tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data barang SKU tidak valid, silakan periksa kembali'
            ],
            'dataBarangSKU.*.idBarangSatuan'=>  [
                'required'      =>  'Data Satuan Barang tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data Satuan Barang tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation          =   new MainOperation();
        $idProdusenDistributor  =   $this->request->getVar('idProdusenDistributor');
        $idGudangUtama          =   $this->request->getVar('idGudangUtama');
        $keterangan             =   $this->request->getVar('keterangan');
        $dataBarangSKU          =   $this->request->getVar('dataBarangSKU');
        $idProdusenDistributor  =   isset($idProdusenDistributor) && $idProdusenDistributor != "" ? hashidDecode($idProdusenDistributor) : 0;
        $idGudangUtama          =   isset($idGudangUtama) && $idGudangUtama != "" ? hashidDecode($idGudangUtama) : 0;

        if(!is_array($dataBarangSKU) || count($dataBarangSKU) <= 0) return throwResponseNotAcceptable("Harap masukkan setidaknya 1 data barang pembelian");
        $arrInsertNotaPembelianRekap    = [
            'IDPRODUSENDISTRIBUTOR'     => $idProdusenDistributor,
            'NOTAPEMBELIANNOMOR'        => $this->generateNotaPembelianNomor(),
            'TOTALJENISBARANG'          => $this->getTotalJenisBarang($dataBarangSKU ?? []),
            'TOTALSKU'                  => count($dataBarangSKU),
            'PERSENPENYELESAIANINBOUND' => 0,
            'KETERANGAN'                => $keterangan,
            'INPUTUSER'                 => $this->userData->name,
            'INPUTTANGGALWAKTU'         => $this->currentDateTime
        ];
        $procInsertNotaPembelian    =   $mainOperation->insertDataTable('t_notapembelianrekap', $arrInsertNotaPembelianRekap);

        if(!$procInsertNotaPembelian['status']) return switchMySQLErrorCode($procInsertNotaPembelian['errCode']);
        $idNotaPembelianRekap       =   $procInsertNotaPembelian['insertID'];

        foreach($dataBarangSKU as $keyBarangSKU){
            $idBarangSKU    =   isset($keyBarangSKU->idBarangSKU) && $keyBarangSKU->idBarangSKU != "" ? hashidDecode($keyBarangSKU->idBarangSKU) : 0;
            $idBarang       =   isset($keyBarangSKU->idBarang) && $keyBarangSKU->idBarang != "" ? hashidDecode($keyBarangSKU->idBarang) : 0;
            $idBarangSatuan =   isset($keyBarangSKU->idBarangSatuan) && $keyBarangSKU->idBarangSatuan != "" ? hashidDecode($keyBarangSKU->idBarangSatuan) : 0;
            if($idBarangSKU > 0){
                $arrInsertNotaPembelianBarang   = [
                    'IDNOTAPEMBELIANREKAP'  => $idNotaPembelianRekap,
                    'IDBARANG'              => $idBarang,
                    'IDBARANGSKU'           => $idBarangSKU,
                    'IDBARANGSATUAN'        => $idBarangSatuan,
                    'JUMLAH'                => isset($keyBarangSKU->jumlah) ? (int)$keyBarangSKU->jumlah : 0,
                    'HARGABELI'             => isset($keyBarangSKU->hargaBeli) ? (int)$keyBarangSKU->hargaBeli : 0,
                ];
                $procInsertNotaPembelianBarang  =   $mainOperation->insertDataTable('t_notapembelianbarang', $arrInsertNotaPembelianBarang);

                if($procInsertNotaPembelianBarang['status']) {
                    $idNotaPembelianBarang          =   $procInsertNotaPembelianBarang['insertID'];
                    $arrInsertNotaPembelianInbound  =   [
                        'IDNOTAPEMBELIANBARANG' =>  $idNotaPembelianBarang,
                        'IDGUDANG'              =>  $idGudangUtama,
                        'INBOUNDJATAH'          =>  (int)$keyBarangSKU->jumlah
                    ];
                    $mainOperation->insertDataTable('t_notapembelianinbound', $arrInsertNotaPembelianInbound);
                }
            }
        }

        return throwResponseOK(
            'Data nota pembelian telah disimpan',
            ['idNotaPembelianRekap'  =>  hashidEncode($idNotaPembelianRekap)]
        );
    }

    private function generateNotaPembelianNomor(){
        return 'NPB-' . strtoupper(bin2hex(random_bytes(2))) . date('ymd');
    }

    private function getTotalJenisBarang($dataBarangSKU)
    {
        $idBarangList   =   array_column($dataBarangSKU, 'idBarang');
        $jumlahUnik     =   count(array_unique($idBarangList));
        return $jumlahUnik;
    }

    public function saveDataNotaBarangSKU()
    {
        $idNotaPembelianBarang  =   $this->request->getVar('idNotaPembelianBarang');
        $idNotaPembelianBarang  =   $idNotaPembelianBarang != "" ? hashidDecode($idNotaPembelianBarang) : 0;

        return $idNotaPembelianBarang == 0 ? $this->addDataNotaBarangSKU() : $this->updateDataNotaBarangSKU($idNotaPembelianBarang);
    }

    private function addDataNotaBarangSKU()
    {
        $rules  =   [
            'idNotaPembelianRekap'  =>  ['label' => 'Id Nota Pembelian', 'rules' => 'required|alpha_numeric'],
            'idBarang'              =>  ['label' => 'Barang', 'rules' => 'required|alpha_numeric'],
            'idBarangSKU'           =>  ['label' => 'Barang SKU', 'rules' => 'required|alpha_numeric'],
            'jumlah'                =>  ['label' => 'Jumlah', 'rules' => 'required|integer|greater_than[0]'],
            'hargaBeli'             =>  ['label' => 'Harga Beli', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idNotaPembelianRekap'  => [
                'required'      => 'Data Nota Pembelian yang dipilih tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data Nota Pembelian yang dipilih tidak valid, silakan periksa kembali'
            ],
            'idBarang' => [
                'required'      => 'Data Barang yang dipilih tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data Barang yang dipilih tidak valid, silakan periksa kembali'
            ],
            'idBarangSKU' => [
                'required'      => 'Data Barang SKU yang dipilih tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data Barang SKU yang dipilih tidak valid, silakan periksa kembali'
            ],
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation          =   new MainOperation();
        $idNotaPembelianRekap   =   $this->request->getVar('idNotaPembelianRekap');
        $idBarang               =   $this->request->getVar('idBarang');
        $idBarangSKU            =   $this->request->getVar('idBarangSKU');
        $jumlah                 =   $this->request->getVar('jumlah');
        $hargaBeli              =   $this->request->getVar('hargaBeli');
        $idNotaPembelianRekap   =   isset($idNotaPembelianRekap) && $idNotaPembelianRekap != "" ? hashidDecode($idNotaPembelianRekap) : 0;
        $idBarang               =   isset($idBarang) && $idBarang != "" ? hashidDecode($idBarang) : 0;
        $idBarangSKU            =   isset($idBarangSKU) && $idBarangSKU != "" ? hashidDecode($idBarangSKU) : 0;

        if($idNotaPembelianRekap > 0 && $idBarang > 0 && $idBarangSKU > 0 && $jumlah > 0 && $hargaBeli > 0){
            $isBarangSKUNotaExist   =   $mainOperation->isDataExist('t_notapembelianbarang', [
                'IDNOTAPEMBELIANREKAP'  => $idNotaPembelianRekap,
                'IDBARANGSKU'           => $idBarangSKU
            ]);
            if($isBarangSKUNotaExist) return throwResponseNotAcceptable('Barang SKU ini sudah ada di dalam nota pembelian ini, silakan periksa kembali');

            $arrInsertNotaPembelianBarang   = [
                'IDNOTAPEMBELIANREKAP'  => $idNotaPembelianRekap,
                'IDBARANG'              => $idBarang,
                'IDBARANGSKU'           => $idBarangSKU,
                'JUMLAH'                => (int)$jumlah,
                'HARGABELI'             => (int)$hargaBeli,
            ];
            $mainOperation->insertDataTable('t_notapembelianbarang', $arrInsertNotaPembelianBarang);
            $this->calculateDataNotaPembelianRekap($idNotaPembelianRekap);

            return throwResponseOK(
                'Data nota pembelian telah disimpan',
                ['idNotaPembelianRekap'  =>  hashidEncode($idNotaPembelianRekap)]
            );
        } else {
            return throwResponseNotAcceptable('Tidak dapat menyimpan data barang, silakan periksa kembali data yang dimasukkan');
        }
    }

    private function updateDataNotaBarangSKU($idNotaPembelianBarang)
    {
        $rules  =   [
            'idNotaPembelianRekap'  =>  ['label' => 'Id Nota Pembelian', 'rules' => 'required|alpha_numeric'],
            'idNotaPembelianBarang' =>  ['label' => 'Id Nota Pembelian Barang', 'rules' => 'required|alpha_numeric'],
            'jumlah'                =>  ['label' => 'Jumlah', 'rules' => 'required|integer|greater_than[0]'],
            'hargaBeli'             =>  ['label' => 'Harga Beli', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idNotaPembelianRekap'  => [
                'required'      => 'Data Nota Pembelian yang dipilih tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data Nota Pembelian yang dipilih tidak valid, silakan periksa kembali'
            ],
            'idNotaPembelianBarang' => [
                'required'      => 'Data barang Nota Pembelian yang dipilih tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data barang Nota Pembelian yang dipilih tidak valid, silakan periksa kembali'
            ],
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation          =   new MainOperation();
        $idNotaPembelianRekap   =   $this->request->getVar('idNotaPembelianRekap');
        $idNotaPembelianRekap   =   isset($idNotaPembelianRekap) && $idNotaPembelianRekap != "" ? hashidDecode($idNotaPembelianRekap) : 0;
        $jumlah                 =   $this->request->getVar('jumlah');
        $hargaBeli              =   $this->request->getVar('hargaBeli');

        if($idNotaPembelianBarang > 0 && $jumlah > 0 && $hargaBeli > 0){
            $arrUpdateNotaPembelianBarang   = [
                'JUMLAH'                => (int)$jumlah,
                'HARGABELI'             => (int)$hargaBeli,
            ];
            $mainOperation->updateDataTable('t_notapembelianbarang', $arrUpdateNotaPembelianBarang, ['IDNOTAPEMBELIANBARANG' => $idNotaPembelianBarang]);
            $this->calculateDataNotaPembelianRekap($idNotaPembelianRekap);

            return throwResponseOK('Data barang nota pembelian telah diperbarui');
        } else {
            return throwResponseNotAcceptable('Tidak dapat menyimpan data, silakan periksa kembali data yang dimasukkan');
        }
    }

    public function deleteDataNotaBarangSKU()
    {
        $rules  =   ['idNotaPembelianBarang' =>  ['label' => 'Barang Nota Pembelian', 'rules' => 'required|alpha_numeric']];

        $messages   =   [
            'idNotaPembelianBarang' => [
                'required'      => 'Data barang yang dipilih tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data barang yang dipilih tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation          =   new MainOperation();
        $idNotaPembelianRekap   =   $this->request->getVar('idNotaPembelianRekap');
        $idNotaPembelianRekap   =   isset($idNotaPembelianRekap) && $idNotaPembelianRekap != "" ? hashidDecode($idNotaPembelianRekap) : 0;
        $idNotaPembelianBarang  =   $this->request->getVar('idNotaPembelianBarang');
        $idNotaPembelianBarang  =   isset($idNotaPembelianBarang) && $idNotaPembelianBarang != "" ? hashidDecode($idNotaPembelianBarang) : 0;

        if($idNotaPembelianBarang && $idNotaPembelianBarang > 0){
            ////JANGAN LUPA TAMBAHKAN CEK APAKAH BARANG SKU INI SUDAH DIPROSES INBOUND ATAU BELUM
            $isBarangSKUNotaExist   =   $mainOperation->isDataExist('t_notapembelianbarang', [
                'IDNOTAPEMBELIANREKAP'  =>  $idNotaPembelianRekap,
                'IDNOTAPEMBELIANBARANG' =>  $idNotaPembelianBarang
            ]);
            if(!$isBarangSKUNotaExist) return throwResponseNotAcceptable('Barang SKU ini tidak ada di dalam nota pembelian ini, silakan periksa kembali');
            $mainOperation->deleteDataTable('t_notapembelianbarang', ['IDNOTAPEMBELIANBARANG' => $idNotaPembelianBarang]);
            $this->calculateDataNotaPembelianRekap($idNotaPembelianRekap);

            return throwResponseOK('Data barang nota pembelian telah dihapus');
        } else {
            return throwResponseNotAcceptable('Tidak dapat menghapus data barang, silakan periksa kembali data yang dimasukkan');
        }
    }

    private function calculateDataNotaPembelianRekap($idNotaPembelianRekap)
    {
        $mainOperation          =   new MainOperation();
        $pembelianBarangModel   =   new PembelianBarangModel();
        $dataBarangSKU          =   $pembelianBarangModel->getDataBarangSKUNotaPembelian($idNotaPembelianRekap);

        if($dataBarangSKU && count($dataBarangSKU) > 0){
            $totalJenisBarang   =   count(array_unique(array_column($dataBarangSKU, 'IDBARANG')));
            $totalSKU           =   count($dataBarangSKU);

            $arrUpdateNotaPembelianRekap = [
                'TOTALJENISBARANG'  =>  $totalJenisBarang,
                'TOTALSKU'          =>  $totalSKU
            ];
            $mainOperation->insertDataTable('t_notapembelianrekap', $arrUpdateNotaPembelianRekap);
        }
        return true;
    }

    public function saveDataBarangInboundGudang()
    {
        $rules  =   [
            'idNotaPembelianBarang'             =>  ['label' => 'Id Nota Pembelian Barang', 'rules' => 'required|alpha_numeric'],
            'arrDataInboundGudang'              =>  ['label' => 'Data Inbound Gudang', 'rules' => 'required|is_array'],
            'arrDataInboundGudang.*.idGudang'   =>  ['label' => 'Gudang', 'rules' => 'required|alpha_numeric'],
            'arrDataInboundGudang.*.jumlahJatah'=>  ['label' => 'Jumlah Jatah', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idNotaPembelianBarang' =>  [
                'required'      =>  'Data Nota Pembelian Barang yang dipilih tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data Nota Pembelian Barang yang dipilih tidak valid, silakan periksa kembali'
            ],
            'arrDataInboundGudang'  =>  [
                'required'      =>  'Harap masukkan data gudang dan jatah barang',
                'alpha_numeric' =>  'Data gudang dan jatah barang yang dimasukkan tidak valid, silakan periksa kembali'
            ],
            'arrDataInboundGudang.*.idGudang'  =>  [
                'required'      =>  'Gudang yang dipilih tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data gudang dan jatah barang yang dimasukkan tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation          =   new MainOperation();
        $idNotaPembelianBarang  =   $this->request->getVar('idNotaPembelianBarang');
        $idNotaPembelianBarang  =   $idNotaPembelianBarang != "" ? hashidDecode($idNotaPembelianBarang) : 0;
        $arrDataInboundGudang   =   $this->request->getVar('arrDataInboundGudang');
        $arrDataInboundGudang   =   isset($arrDataInboundGudang) && is_array($arrDataInboundGudang) ? $arrDataInboundGudang : [];
        $arrIdGudangRequest     =   [];

        if($idNotaPembelianBarang <= 0 || count($arrDataInboundGudang) <= 0) return throwResponseNotAcceptable('Tidak dapat menyimpan data inbound gudang, silakan periksa kembali data yang dimasukkan');

        foreach($arrDataInboundGudang as $dataInboundGudang){
            $idGudang                   =   isset($dataInboundGudang->idGudang) && $dataInboundGudang->idGudang != "" ? hashidDecode($dataInboundGudang->idGudang) : 0;
            $jumlahJatah                =   isset($dataInboundGudang->jumlahJatah) && $dataInboundGudang->jumlahJatah != "" ? (int)$dataInboundGudang->jumlahJatah : 0;
            $isDataInboundGudangExist   =   (new MainOperation())->isDataExist('t_notapembelianinbound', [
                'IDNOTAPEMBELIANBARANG' =>  $idNotaPembelianBarang,
                'IDGUDANG'              =>  $idGudang
            ]);

            if($isDataInboundGudangExist){
                $mainOperation->updateDataTable('t_notapembelianinbound', ['INBOUNDJATAH' => $jumlahJatah], [
                    'IDNOTAPEMBELIANBARANG' =>  $idNotaPembelianBarang,
                    'IDGUDANG'              =>  $idGudang
                ]);
            } else {
                if($idGudang > 0 && $jumlahJatah > 0){
                    $arrInsertInboundGudang = [
                        'IDNOTAPEMBELIANBARANG' => $idNotaPembelianBarang,
                        'IDGUDANG'              => $idGudang,
                        'INBOUNDJATAH'          => $jumlahJatah
                    ];
                    $mainOperation->insertDataTable('t_notapembelianinbound', $arrInsertInboundGudang);
                }
            }

            $arrIdGudangRequest[]   =   $idGudang;
        }

        $dataGudangInboundBarang    =   $mainOperation->getColumnData('t_notapembelianinbound', 'IDGUDANG', ['IDNOTAPEMBELIANBARANG' => $idNotaPembelianBarang]);
        if(isset($dataGudangInboundBarang) && $dataGudangInboundBarang != null && is_array($dataGudangInboundBarang) && count($dataGudangInboundBarang) > 0){
            foreach($dataGudangInboundBarang as $keyGudangInboundBarang){
                $idGudangInboundBarang  =   isset($keyGudangInboundBarang->IDGUDANG) && $keyGudangInboundBarang->IDGUDANG != "" ? $keyGudangInboundBarang->IDGUDANG : 0;

                if($idGudangInboundBarang > 0){
                    if(!in_array($idGudangInboundBarang, $arrIdGudangRequest)){
                        $mainOperation->deleteDataTable('t_notapembelianinbound', [
                            'IDNOTAPEMBELIANBARANG' =>  $idNotaPembelianBarang,
                            'IDGUDANG'              =>  $idGudangInboundBarang
                        ]);
                    }
                }
            }
        }
        
        return throwResponseOK('Pembaruan data inbound gudang telah disimpan');
    }
}