<?php

namespace App\Controllers\ERP\Stok;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\ERP\Stok\PengaturanHargaJualModel;
use App\Models\ERP\Master\BarangSKUModel;
use App\Models\ERP\Master\TokoModel;
use App\Models\MainOperation;

class PengaturanHargaJual extends ResourceController
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

    public function getListBarang()
    {
        $rules  =   [
            'idBarangKategori'  =>  ['label' => 'Id Kategori', 'rules' => 'permit_empty|alpha_numeric'],
            'idBarangMerk'      =>  ['label' => 'Id Merk', 'rules' => 'permit_empty|alpha_numeric'],
            'searchKeyword'     =>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
        ];

        $messages   =   [
            'idBarangKategori'  =>  [
                'alpha_numeric' =>  'Data kategori tidak valid, silakan periksa kembali'
            ],
            'idBarangMerk'      =>  [
                'alpha_numeric' =>  'Data merk tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation              =   new MainOperation();
        $pengaturanHargaJualModel   =   new PengaturanHargaJualModel();
        $barangSKUModel             =   new BarangSKUModel();
        $idBarangKategori           =   $this->request->getVar('idBarangKategori');
        $idBarangKategori           =   isset($idBarangKategori) && $idBarangKategori != "" ? hashidDecode($idBarangKategori) : 0;
        $idBarangMerk               =   $this->request->getVar('idBarangMerk');
        $idBarangMerk               =   isset($idBarangMerk) && $idBarangMerk != "" ? hashidDecode($idBarangMerk) : 0;
        $searchKeyword              =   $this->request->getVar('searchKeyword');
        $dataPerPage                =   $this->request->getVar('dataPerPage');
        $pageNumber                 =   $this->request->getVar('pageNumber');
        $baseData                   =   $pengaturanHargaJualModel->getDataHargaJualBarang($idBarangKategori, $idBarangMerk, $searchKeyword);
        $totalNumberData            =   $baseData->countAllResults(false);
        $pageProperty               =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $dataHargaJualBarang    =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);

            if($dataHargaJualBarang && count($dataHargaJualBarang) > 0) {
                foreach($dataHargaJualBarang as $keyHargaJualBarang){
                    $idBarang           =   $keyHargaJualBarang->IDBARANG;
                    $dataBarangSatuan   =   $barangSKUModel->getDataBarangSatuan($idBarang);
                    $keyHargaJualBarang->ARRIDBARANGSATUAN  =   encodeDatabaseObjectResultKey($dataBarangSatuan, ['IDBARANGSATUAN']);
                }
            }

            $dataHargaJualBarang=   encodeDatabaseObjectResultKey($dataHargaJualBarang, ['IDBARANG']);
            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $dataHargaJualBarang,
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn =   [
                "listData"          =>  [],
                "pageProperty"      =>  $pageProperty
            ];
            return throwResponseNotFound('Data harga jual barang tidak ditemukan', $dataReturn);
        }
    }

    public function getDetailHargaJual()
    {
        $rules  =   [
            'idBarang'          =>  ['label' => 'Id Barang', 'rules' => 'required|alpha_numeric'],
            'idBarangSatuan'    =>  ['label' => 'Id Barang Satuan', 'rules' => 'required|alpha_numeric']
        ];

        $messages   =   [
            'idBarang'  =>  [
                'required'      =>  'Data barang tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data barang tidak valid, silakan periksa kembali'
            ],
            'idBarangSatuan'    =>  [
                'required'      =>  'Satuan barang tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Satuan barang tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $idBarang                   =   $this->request->getVar('idBarang');
        $idBarang                   =   isset($idBarang) && $idBarang != "" ? hashidDecode($idBarang) : 0;
        $idBarangSatuan             =   $this->request->getVar('idBarangSatuan');
        $idBarangSatuan             =   isset($idBarangSatuan) && $idBarangSatuan != "" ? hashidDecode($idBarangSatuan) : 0;
        $pengaturanHargaJualModel   =   new PengaturanHargaJualModel();
        $dataDetailBarang           =   $pengaturanHargaJualModel->getDataDetailBarang($idBarang);
        $dataDetailBarangSKU        =   $pengaturanHargaJualModel->getDataDetailBarangSKU($idBarang);

        if(!$dataDetailBarangSKU) return throwResponseNotFound('Daftar SKU barang yang dipilih tidak ditemukan', ['dataDetailBarang' =>  $dataDetailBarang, 'dataDetailBarangSKU' =>  [], 'dataDetailHargaJualPerToko' =>  []]);

        $tokoModel                  =   new TokoModel();
        $dataDetailHargaJualPerToko =   $tokoModel->getListAllTokoHargaJual();
        if($dataDetailHargaJualPerToko && count($dataDetailHargaJualPerToko) > 0){
            foreach($dataDetailHargaJualPerToko as $keyDetailHargaJualPerToko){
                $idToko                 =   isset($keyDetailHargaJualPerToko->IDTOKO) && $keyDetailHargaJualPerToko->IDTOKO != "" ? $keyDetailHargaJualPerToko->IDTOKO : 0;
                $dataHargaJualBarangSKU =   $pengaturanHargaJualModel->getDataHargaJualBarangPerSKU($idToko, $idBarang, $idBarangSatuan);
                $arrHargaJualPerSKU     =   [];

                foreach($dataDetailBarangSKU as $keyDetailBarangSKU){
                    $idBarangSKU            =   isset($keyDetailBarangSKU->IDBARANGSKU) && $keyDetailBarangSKU->IDBARANGSKU != "" ? $keyDetailBarangSKU->IDBARANGSKU : 0;
                    $arrHargaJualPerSKU[]   =   [
                        'IDBARANGSKU'   =>  isset($keyDetailBarangSKU->IDBARANGSKU) && $keyDetailBarangSKU->IDBARANGSKU != "" ? hashidEncode($keyDetailBarangSKU->IDBARANGSKU) : "",
                        'HARGA'         =>  $this->getHargaBarangSKUToko($idBarangSKU, $dataHargaJualBarangSKU)
                    ];
                }

                $keyDetailHargaJualPerToko->ARRHARGAJUALPERSKU  =   $arrHargaJualPerSKU;
            }
        }

        if($dataDetailBarangSKU && count($dataDetailBarangSKU) > 0){
            $barangSKUModel         =   new BarangSKUModel();
            foreach($dataDetailBarangSKU as $keyDetailBarangSKU){
                $idBarangSKU        =   isset($keyDetailBarangSKU->IDBARANGSKU) && $keyDetailBarangSKU->IDBARANGSKU != "" ? $keyDetailBarangSKU->IDBARANGSKU : 0;
                $historyHargaBeli   =   $pengaturanHargaJualModel->getHistoryHargaBeliPerSKU($idBarangSKU);
                
                $keyDetailBarangSKU->IDBARANGSKU        =   isset($keyDetailBarangSKU->IDBARANGSKU) && $keyDetailBarangSKU->IDBARANGSKU != "" ? hashidEncode($keyDetailBarangSKU->IDBARANGSKU) : "";
                $keyDetailBarangSKU->ATRIBUTSKUSTR      =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                $keyDetailBarangSKU->HISTORYHARGABELI   =   $historyHargaBeli;
            }
        }

        $dataDetailHargaJualPerToko =   encodeDatabaseObjectResultKey($dataDetailHargaJualPerToko, ['IDTOKO']);
        return $this->setResponseFormat('json')
                    ->respond([
                        'dataDetailBarang'          =>  $dataDetailBarang,
                        'dataDetailBarangSKU'       =>  $dataDetailBarangSKU,
                        'dataDetailHargaJualPerToko'=>  $dataDetailHargaJualPerToko
                    ]);
    }

    private function getHargaBarangSKUToko($idBarangSKU, $dataHargaJualBarangSKU){
        $harga          =   0;
        $filteredArray  =   array_filter($dataHargaJualBarangSKU, function($item) use ($idBarangSKU) {
            return $item->IDBARANGSKU == $idBarangSKU;
        });

        if (!empty($filteredArray)) {
            $foundItem  =   reset($filteredArray);
            $harga      =   $foundItem->HARGA;
        }
        
        return $harga;
    }

    public function getUrlExcelHargaJualByFilter()
    {
        $rules  =   [
            'tipeHargaJual'     =>  ['label' => 'Tipe Harga Jual', 'rules' => 'required|in_list[R,G]'],
            'idToko'            =>  ['label' => 'Id Toko', 'rules' => 'required|alpha_numeric'],
            'idBarangKategori'  =>  ['label' => 'Id Kategori', 'rules' => 'permit_empty|alpha_numeric'],
            'idBarangMerk'      =>  ['label' => 'Id Merk', 'rules' => 'permit_empty|alpha_numeric'],
        ];

        $messages   =   [
            'tipeHargaJual'     =>  [
                'required'      =>  'Harap pilih tipe harga jual terlebih dahulu',
                'in_list'       =>  'Tipe harga jual tidak valid, silakan periksa kembali'
            ],
            'idToko'  =>  [
                'required'      =>  'Harap pilih toko terlebih dahulu',
                'alpha_numeric' =>  'Toko yang dipilih tidak valid, silakan periksa kembali'
            ],
            'idBarangKategori'  =>  [
                'alpha_numeric' =>  'Data kategori tidak valid, silakan periksa kembali'
            ],
            'idBarangMerk'      =>  [
                'alpha_numeric' =>  'Data merk tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $tipeHargaJual      =   $this->request->getVar('tipeHargaJual');
        $idToko             =   $this->request->getVar('idToko');
        $idToko             =   isset($idToko) && $idToko != "" ? hashidDecode($idToko) : 0;
        $idBarangKategori   =   $this->request->getVar('idBarangKategori');
        $idBarangKategori   =   isset($idBarangKategori) && $idBarangKategori != "" ? hashidDecode($idBarangKategori) : 0;
        $idBarangMerk       =   $this->request->getVar('idBarangMerk');
        $idBarangMerk       =   isset($idBarangMerk) && $idBarangMerk != "" ? hashidDecode($idBarangMerk) : 0;
        $arrParameters      =   [
            'idToko'            =>  $idToko,
            'idBarangKategori'  =>  $idBarangKategori,
            'idBarangMerk'      =>  $idBarangMerk
        ];
        $arrParametersEncode        =   encodeJWTToken($arrParameters);
        $baseURLFunctionExcelData   =   $tipeHargaJual == 'R' ? URL_EXCEL_ERP_DATA_HARGA_BARANG_RETAIL : URL_EXCEL_ERP_DATA_HARGA_BARANG_GROSIR;
        $urlExcelHargaJualRetail    =   base_url($baseURLFunctionExcelData).$arrParametersEncode;

        return $this->setResponseFormat('json')->respond([
            "urlExcel"      =>  $urlExcelHargaJualRetail
        ]);
    }

    public function getDetailHargaJualGrosir()
    {
        $rules  =   [
            'idBarang'      =>  ['label' => 'Id Barang', 'rules' => 'required|alpha_numeric'],
            'idBarangSatuan'=>  ['label' => 'Id Barang Satuan', 'rules' => 'required|alpha_numeric']
        ];

        $messages   =   [
            'idBarang'  =>  [
                'required'      =>  'Data barang tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data barang tidak valid, silakan periksa kembali'
            ],
            'idBarangSatuan'    =>  [
                'required'      =>  'Satuan barang tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Satuan barang tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $idBarang                   =   $this->request->getVar('idBarang');
        $idBarang                   =   isset($idBarang) && $idBarang != "" ? hashidDecode($idBarang) : 0;
        $idBarangSatuan             =   $this->request->getVar('idBarangSatuan');
        $idBarangSatuan             =   isset($idBarangSatuan) && $idBarangSatuan != "" ? hashidDecode($idBarangSatuan) : 0;
        $pengaturanHargaJualModel   =   new PengaturanHargaJualModel();
        $dataKelompokHargaGrosir    =   $pengaturanHargaJualModel->getDataKelompokHargaGrosir();
        $dataKelompokHargaGrosir    =   encodeDatabaseObjectResultKey($dataKelompokHargaGrosir, ['IDKELOMPOKHARGAGROSIR']);
        $dataDetailBarang           =   $pengaturanHargaJualModel->getDataDetailBarang($idBarang);
        $dataDetailBarangSKU        =   $pengaturanHargaJualModel->getDataDetailBarangSKU($idBarang);

        if(!$dataDetailBarangSKU) return throwResponseNotFound('Daftar SKU barang yang dipilih tidak ditemukan', ['dataKelompokHargaGrosir' => $dataKelompokHargaGrosir, 'dataDetailBarang' =>  $dataDetailBarang, 'dataDetailBarangSKU' =>  []]);
        
        if($dataDetailBarangSKU && count($dataDetailBarangSKU) > 0){
            $barangSKUModel         =   new BarangSKUModel();
            foreach($dataDetailBarangSKU as $keyDetailBarangSKU){
                $idBarangSKU        =   isset($keyDetailBarangSKU->IDBARANGSKU) && $keyDetailBarangSKU->IDBARANGSKU != "" ? $keyDetailBarangSKU->IDBARANGSKU : 0;
                $historyHargaBeli   =   $pengaturanHargaJualModel->getHistoryHargaBeliPerSKU($idBarangSKU);
                $dataHargaJualGrosir=   $pengaturanHargaJualModel->getHargaBarangSKUGrosir($idBarangSKU, $idBarangSatuan);
                
                $keyDetailBarangSKU->IDBARANGSKU        =   isset($keyDetailBarangSKU->IDBARANGSKU) && $keyDetailBarangSKU->IDBARANGSKU != "" ? hashidEncode($keyDetailBarangSKU->IDBARANGSKU) : "";
                $keyDetailBarangSKU->ATRIBUTSKUSTR      =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                $keyDetailBarangSKU->HISTORYHARGABELI   =   $historyHargaBeli;
                $keyDetailBarangSKU->HARGA              =   encodeDatabaseObjectResultKey($dataHargaJualGrosir, ['IDKELOMPOKHARGAGROSIR']);
            }
        }

        return $this->setResponseFormat('json')
                    ->respond([
                        'dataKelompokHargaGrosir'   =>  $dataKelompokHargaGrosir,
                        'dataDetailBarang'          =>  $dataDetailBarang,
                        'dataDetailBarangSKU'       =>  $dataDetailBarangSKU
                    ]);
    }

    public function saveDetailHargaJual()
    {
        $rules  =   [
            'idBarang'                  =>  ['label' => 'Id Barang', 'rules' => 'required|alpha_numeric'],
            'idBarangSatuan'            =>  ['label' => 'Id Barang Satuan', 'rules' => 'required|alpha_numeric'],
            'dataDetailHarga.*.idToko'  =>  ['label' => 'Id Toko', 'rules' => 'required|alpha_numeric'],
            'dataDetailHarga.*.arrHargaJualPerSKU.*.idBarangSKU'=>  ['label' => 'Id Barang SKU', 'rules' => 'required|alpha_numeric'],
            'dataDetailHarga.*.arrHargaJualPerSKU.*.harga'      =>  ['label' => 'Harga Barang SKU', 'rules' => 'required|numeric|greater_than_equal_to[0]']
        ];

        $messages   =   [
            'idBarang'  =>  [
                'required'      =>  'Data barang tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data barang tidak valid, silakan periksa kembali'
            ],
            'idBarangSatuan'    =>  [
                'required'      =>  'Satuan barang tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Satuan barang tidak valid, silakan periksa kembali'
            ],
            'dataDetailHarga.*.idToko'  =>  [
                'required'      =>  'Data toko tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data toko tidak valid, silakan periksa kembali'
            ],
            'dataDetailHarga.*.arrHargaJualPerSKU.*.idBarangSKU'  =>  [
                'required'      =>  'Data barang SKU tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data barang SKU tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation  =   new MainOperation();
        $idBarang       =   $this->request->getVar('idBarang');
        $idBarang       =   isset($idBarang) && $idBarang != "" ? hashidDecode($idBarang) : 0;
        $idBarangSatuan =   $this->request->getVar('idBarangSatuan');
        $idBarangSatuan =   isset($idBarangSatuan) && $idBarangSatuan != "" ? hashidDecode($idBarangSatuan) : 0;
        $dataDetailHarga=   $this->request->getVar('dataDetailHarga');
        
        if($dataDetailHarga && count($dataDetailHarga) > 0){
            $pengaturanHargaJualModel   =   new PengaturanHargaJualModel();
            foreach($dataDetailHarga as $keyDetailHarga){
                $idToko                     =   isset($keyDetailHarga->idToko) && $keyDetailHarga->idToko != "" ? hashidDecode($keyDetailHarga->idToko) : 0;
                $arrHargaJualPerSKU         =   isset($keyDetailHarga->arrHargaJualPerSKU) && is_array($keyDetailHarga->arrHargaJualPerSKU) ? $keyDetailHarga->arrHargaJualPerSKU : [];

                if($idToko > 0 && count($arrHargaJualPerSKU) > 0){
                    foreach($arrHargaJualPerSKU as $keyHargaJual){
                        $idBarangSKU        =   isset($keyHargaJual->idBarangSKU) && $keyHargaJual->idBarangSKU != "" ? hashidDecode($keyHargaJual->idBarangSKU) : 0;
                        $idBarangSKU        =   intval($idBarangSKU);
                        $harga              =   isset($keyHargaJual->harga) && $keyHargaJual->harga != "" ? intval($keyHargaJual->harga) : 0;
                        $isHargaBarangExist =   $pengaturanHargaJualModel->where([
                                                'IDBARANG'      => $idBarang,
                                                'IDBARANGSKU'   => $idBarangSKU,
                                                'IDBARANGSATUAN'=> $idBarangSatuan,
                                                'IDGUDANG'      => 0,
                                                'IDTOKO'        => $idToko,
                                                'JUMLAHSATUAN'  => 1
                                                ])->first();
                                                        
                        $arrDataHargaJualSave   =   [
                            'IDBARANG'         =>  $idBarang,
                            'IDBARANGSKU'      =>  $idBarangSKU,
                            'IDGUDANG'         =>  0,
                            'IDTOKO'           =>  $idToko,
                            'IDBARANGSATUAN'   =>  $idBarangSatuan,
                            'JUMLAHSATUAN'     =>  1,
                            'HARGA'            =>  $harga
                        ];
                        
                        if($idBarangSKU > 0) {
                            if ($isHargaBarangExist && !is_null($isHargaBarangExist)) {
                                $mainOperation->updateDataTable('t_baranghargajual', $arrDataHargaJualSave, ['IDBARANGHARGAJUAL' => $isHargaBarangExist['IDBARANGHARGAJUAL']]);
                            } else {
                                $mainOperation->insertDataTable('t_baranghargajual', $arrDataHargaJualSave);
                            }
                        }
                    }
                }
            }
        }

        return throwResponseOK('Data detail harga jual barang berhasil disimpan');
    }

    public function saveDetailHargaJualGrosir()
    {
        $rules  =   [
            'idBarang'                                          =>  ['label' => 'Id Barang', 'rules' => 'required|alpha_numeric'],
            'idBarangSatuan'                                    =>  ['label' => 'Id Barang Satuan', 'rules' => 'required|alpha_numeric'],
            'dataDetailHarga.*.idBarangSKU'                     =>  ['label' => 'Id Barang SKU', 'rules' => 'required|alpha_numeric'],
            'dataDetailHarga.*.arrHarga.*.idKelompokHargaGrosir'=>  ['label' => 'Id Kelompok Harga Grosir', 'rules' => 'required|alpha_numeric'],
            'dataDetailHarga.*.arrHarga.*.harga'                =>  ['label' => 'Harga Barang SKU', 'rules' => 'required|numeric|greater_than_equal_to[0]']
        ];

        $messages   =   [
            'idBarang'  =>  [
                'required'      =>  'Data barang tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data barang tidak valid, silakan periksa kembali'
            ],
            'idBarangSatuan'    =>  [
                'required'      =>  'Satuan barang tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Satuan barang tidak valid, silakan periksa kembali'
            ],
            'dataDetailHarga.*.idBarangSKU' =>  [
                'required'      =>  'Data barang SKU tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data barang SKU tidak valid, silakan periksa kembali'
            ],
            'dataDetailHarga.*.arrHarga.*.idKelompokHargaGrosir'   =>  [
                'required'      =>  'Data kelompok harga grosir tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data kelompok harga grosir tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation  =   new MainOperation();
        $idBarang       =   $this->request->getVar('idBarang');
        $idBarang       =   isset($idBarang) && $idBarang != "" ? hashidDecode($idBarang) : 0;
        $idBarangSatuan =   $this->request->getVar('idBarangSatuan');
        $idBarangSatuan =   isset($idBarangSatuan) && $idBarangSatuan != "" ? hashidDecode($idBarangSatuan) : 0;
        $dataDetailHarga=   $this->request->getVar('dataDetailHarga');
        
        if($dataDetailHarga && count($dataDetailHarga) > 0){
            $pengaturanHargaJualModel  =   new PengaturanHargaJualModel();
            foreach($dataDetailHarga as $keyDetailHarga){
                $idBarangSKU    =   isset($keyDetailHarga->idBarangSKU) && $keyDetailHarga->idBarangSKU != "" ? hashidDecode($keyDetailHarga->idBarangSKU) : 0;
                $idBarangSKU    =   intval($idBarangSKU);
                $arrHarga       =   isset($keyDetailHarga->arrHarga) && is_array($keyDetailHarga->arrHarga) ? $keyDetailHarga->arrHarga : [];

                foreach($arrHarga as $keyHarga){
                    $idKelompokHargaGrosir  =   isset($keyHarga->idKelompokHargaGrosir) && $keyHarga->idKelompokHargaGrosir != "" ? hashidDecode($keyHarga->idKelompokHargaGrosir) : 0;
                    $idKelompokHargaGrosir  =   intval($idKelompokHargaGrosir);
                    $harga                  =   isset($keyHarga->harga) && $keyHarga->harga != "" ? intval($keyHarga->harga) : 0;
                    $isHargaBarangExist     =   $pengaturanHargaJualModel->isHargaBarangGrosirExist($idKelompokHargaGrosir, $idBarangSKU, $idBarangSatuan);
                                                    
                    $arrDataHargaJualSave   =   [
                        'IDBARANG'              =>  $idBarang,
                        'IDKELOMPOKHARGAGROSIR' =>  $idKelompokHargaGrosir,
                        'IDBARANGSKU'           =>  $idBarangSKU,
                        'IDBARANGSATUAN'        =>  $idBarangSatuan,
                        'JUMLAHSATUAN'          =>  1,
                        'HARGA'                 =>  $harga
                    ];
                    
                    if($idBarangSKU > 0) {
                        if ($isHargaBarangExist && !is_null($isHargaBarangExist)) {
                            $mainOperation->updateDataTable('t_baranghargajualgrosir', $arrDataHargaJualSave, ['IDBARANGHARGAJUALGROSIR' => $isHargaBarangExist['IDBARANGHARGAJUALGROSIR']]);
                        } else {
                            $mainOperation->insertDataTable('t_baranghargajualgrosir', $arrDataHargaJualSave);
                        }
                    }
                }
            }
        }

        return throwResponseOK('Data detail harga jual barang berhasil disimpan');
    }
}