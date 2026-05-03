<?php

namespace App\Controllers\POS;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\PrintOut;
use App\Models\MainOperation;
use App\Models\POS\PenjualanModel;
use App\Models\POS\Laporan\PenjualanModel as LaporanPenjualanModel;
use App\Models\ERP\Master\BarangSKUModel;
use App\Models\ERP\Stok\PengaturanDiskonModel;

class Penjualan extends ResourceController
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
            $this->idToko           =   $this->userData->idToko;
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
            'arrIdBarangKategori'   =>  ['label' => 'Id Kategori', 'rules' => 'is_array'],
            'arrIdBarangMerk'       =>  ['label' => 'Id Merk', 'rules' => 'is_array'],
            'arrIdBarangKategori.*' =>  ['label' => 'Id Kategori', 'rules' => 'permit_empty|alpha_numeric'],
            'arrIdBarangMerk.*'     =>  ['label' => 'Id Merk', 'rules' => 'permit_empty|alpha_numeric'],
            'searchKeyword'         =>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'sortCondition'         =>  ['label' => 'Urutan Data', 'rules' => 'required|in_list[AZ,ZA,HT,HR]'],
        ];

        $messages   =   [
            'arrIdBarangKategori'   =>  ['is_array' =>  'Data kategori yang anda pilih tidak valid, silakan periksa kembali'],
            'arrIdBarangMerk'       =>  ['is_array' =>  'Data merk yang anda pilih tidak valid, silakan periksa kembali'],
            'arrIdBarangKategori.*' =>  ['is_array' =>  'Data kategori yang anda pilih tidak valid, silakan periksa kembali'],
            'arrIdBarangMerk.*'     =>  ['is_array' =>  'Data merk yang anda pilih tidak valid, silakan periksa kembali'],
            'sortCondition'         =>  ['in_list' =>  'Pilihan urutan data yang anda pilih tidak valid, silakan periksa kembali']
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $penjualanModel     =   new PenjualanModel();
        $mainOperation      =   new MainOperation();
        $arrIdBarangKategori=   $this->request->getVar('arrIdBarangKategori');
        $arrIdBarangMerk    =   $this->request->getVar('arrIdBarangMerk');
        $searchKeyword      =   $this->request->getVar('searchKeyword');
        $sortCondition      =   $this->request->getVar('sortCondition');
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $dataPerPage        =   isset($dataPerPage) && is_numeric($dataPerPage) ? (int)$dataPerPage : 10;
        $pageNumber         =   $this->request->getVar('pageNumber');
        $pageNumber         =   isset($pageNumber) && is_numeric($pageNumber) ? (int)$pageNumber : 1;

        if(isset($arrIdBarangKategori) && is_array($arrIdBarangKategori) && count($arrIdBarangKategori) > 0) {
            foreach($arrIdBarangKategori as &$idBarangKategori) {
                $idBarangKategori = hashidDecode($idBarangKategori);
            }
        }

        if(isset($arrIdBarangMerk) && is_array($arrIdBarangMerk) && count($arrIdBarangMerk) > 0) {
            foreach($arrIdBarangMerk as &$idBarangMerk) {
                $idBarangMerk = hashidDecode($idBarangMerk);
            }
        }

        $baseData           =   $penjualanModel->getListBarang($arrIdBarangKategori, $arrIdBarangMerk, $searchKeyword);
        $totalNumberData    =   $baseData->countAllResults(false);

        switch($sortCondition) {
            case 'AZ': $penjualanModel->orderBy('A.NAMABARANG', 'ASC'); break;
            case 'ZA': $penjualanModel->orderBy('A.NAMABARANG', 'DESC'); break;
            case 'HR': $penjualanModel->orderBy('HARGATERENDAH', 'ASC'); break;
            case 'HT': $penjualanModel->orderBy('HARGATERTINGGI', 'DESC'); break;
        }
        
        $pageProperty   =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);
        if($totalNumberData > 0){
            $listData   =   $baseData->groupBy('A.IDBARANG')->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            foreach($listData as $keyData) {
                $keyData->FOTOBARANG    =  isset($keyData->FOTOBARANG) && $keyData->FOTOBARANG != "" ? json_decode($keyData->FOTOBARANG) : [];
            }

            $listData   =   encodeDatabaseObjectResultKey($listData, ['IDBARANG']);
            return $this->setResponseFormat('json')->respond([
                "listData"          =>  $listData,
                "pageProperty"      =>  $pageProperty,
                "baseURLFotoBarang" =>  URL_FOTO_BARANG
            ]);
        } else {
            $dataReturn =   [
                "listData"          =>  [],
                "pageProperty"      =>  $pageProperty,
                "baseURLFotoBarang" =>  URL_FOTO_BARANG
            ];
            return throwResponseNotFound('Tidak ada data yang ditemukan', $dataReturn);
        }
    }

    public function getListPaket()
    {
        $rules  =   [
            'arrIdBarangKategori'   =>  ['label' => 'Id Kategori', 'rules' => 'is_array'],
            'arrIdBarangMerk'       =>  ['label' => 'Id Merk', 'rules' => 'is_array'],
            'arrIdBarangKategori.*' =>  ['label' => 'Id Kategori', 'rules' => 'permit_empty|alpha_numeric'],
            'arrIdBarangMerk.*'     =>  ['label' => 'Id Merk', 'rules' => 'permit_empty|alpha_numeric'],
            'searchKeyword'         =>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'sortCondition'         =>  ['label' => 'Urutan Data', 'rules' => 'required|in_list[AZ,ZA,HT,HR]'],
        ];

        $messages   =   [
            'arrIdBarangKategori'   =>  ['is_array' =>  'Data kategori yang anda pilih tidak valid, silakan periksa kembali'],
            'arrIdBarangMerk'       =>  ['is_array' =>  'Data merk yang anda pilih tidak valid, silakan periksa kembali'],
            'arrIdBarangKategori.*' =>  ['is_array' =>  'Data kategori yang anda pilih tidak valid, silakan periksa kembali'],
            'arrIdBarangMerk.*'     =>  ['is_array' =>  'Data merk yang anda pilih tidak valid, silakan periksa kembali'],
            'sortCondition'         =>  ['in_list' =>  'Pilihan urutan data yang anda pilih tidak valid, silakan periksa kembali']
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $penjualanModel     =   new PenjualanModel();
        $mainOperation      =   new MainOperation();
        $arrIdBarangKategori=   $this->request->getVar('arrIdBarangKategori');
        $arrIdBarangMerk    =   $this->request->getVar('arrIdBarangMerk');
        $searchKeyword      =   $this->request->getVar('searchKeyword');
        $sortCondition      =   $this->request->getVar('sortCondition');
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $dataPerPage        =   isset($dataPerPage) && is_numeric($dataPerPage) ? (int)$dataPerPage : 10;
        $pageNumber         =   $this->request->getVar('pageNumber');
        $pageNumber         =   isset($pageNumber) && is_numeric($pageNumber) ? (int)$pageNumber : 1;

        if(isset($arrIdBarangKategori) && is_array($arrIdBarangKategori) && count($arrIdBarangKategori) > 0) {
            foreach($arrIdBarangKategori as &$idBarangKategori) {
                $idBarangKategori = hashidDecode($idBarangKategori);
            }
        }

        if(isset($arrIdBarangMerk) && is_array($arrIdBarangMerk) && count($arrIdBarangMerk) > 0) {
            foreach($arrIdBarangMerk as &$idBarangMerk) {
                $idBarangMerk = hashidDecode($idBarangMerk);
            }
        }

        $baseData           =   $penjualanModel->getListPaket($this->idToko, $arrIdBarangKategori, $arrIdBarangMerk, $searchKeyword);
        $totalNumberData    =   $baseData->countAllResults(false);

        switch($sortCondition) {
            case 'AZ': $penjualanModel->orderBy('A.NAMAHARGARETAILPAKET', 'ASC'); break;
            case 'ZA': $penjualanModel->orderBy('A.NAMAHARGARETAILPAKET', 'DESC'); break;
            case 'HR': $penjualanModel->orderBy('HARGA', 'ASC'); break;
            case 'HT': $penjualanModel->orderBy('HARGA', 'DESC'); break;
        }
        
        $pageProperty   =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);
        if($totalNumberData > 0){
            $listData   =   $baseData->groupBy('A.IDHARGARETAILPAKET')->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            foreach($listData as $keyData) {
                $dataFotoBarang =  explode(',', $keyData->FOTOBARANG);
                $arrFotoBarang  = [];
                foreach($dataFotoBarang as $valueFoto) {
                    $dataFotoPerBarang  =   json_decode($valueFoto);
                    $arrFotoBarang[]    =   isset($dataFotoPerBarang[0]) ? $dataFotoPerBarang[0] : 'noimage.jpg';
                }

                $keyData->FOTOBARANG    =  $arrFotoBarang;
            }

            $listData   =   encodeDatabaseObjectResultKey($listData, ['IDHARGARETAILPAKET']);
            return $this->setResponseFormat('json')->respond([
                "listData"          =>  $listData,
                "pageProperty"      =>  $pageProperty,
                "baseURLFotoBarang" =>  URL_FOTO_BARANG
            ]);
        } else {
            $dataReturn =   [
                "listData"          =>  [],
                "pageProperty"      =>  $pageProperty,
                "baseURLFotoBarang" =>  URL_FOTO_BARANG
            ];
            return throwResponseNotFound('Tidak ada data yang ditemukan', $dataReturn);
        }
    }

    public function getDataStokHargaJualBarang()
    {
        $rules      =   ['idBarang' =>  ['label' => 'Id Barang', 'rules' => 'required|alpha_numeric']];
        $messages   =   [
            'idBarang'  => [
                'required'      => 'Data kiriman tidak lengkap, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak lengkap, silakan periksa kembali'
            ]
        ];
        
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $idBarang       =   $this->request->getVar('idBarang');
        $idBarang       =   isset($idBarang) && $idBarang != "" ? hashidDecode($idBarang) : 0;
        $idToko         =   $this->idToko;

        if($idBarang == 0) return throwResponseNotFound('Daftar SKU barang yang dipilih tidak ditemukan');
        if(is_null($idToko) || $idToko == 0 || $idToko == "") return throwResponseNotFound('Data toko tidak ditemukan');

        $penjualanModel =   new PenjualanModel();
        $listBarangSKU  =   $penjualanModel->getListBarangSKUStokHarga($idBarang);
        
        $barangSKUModel =   new BarangSKUModel();
        foreach($listBarangSKU as $keyBarangSKU) {
            $idBarangSKU        =   isset($keyBarangSKU->IDBARANGSKU) && $keyBarangSKU->IDBARANGSKU != "" ? $keyBarangSKU->IDBARANGSKU : 0;
            $dataStokHargaJual  =   $penjualanModel->getDataStokHargaJualBarangPerSKU($idToko, $idBarangSKU);
            
            $keyBarangSKU->ATRIBUTSKUSTR  =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
            $keyBarangSKU->FOTOBARANGSKU  =   isset($keyBarangSKU->FOTOBARANGSKU) && $keyBarangSKU->FOTOBARANGSKU != "" ? json_decode($keyBarangSKU->FOTOBARANGSKU) : [];

            if(is_array($dataStokHargaJual) && count($dataStokHargaJual) > 0){
                $pengaturanDiskonModel  =   new PengaturanDiskonModel();
                foreach($dataStokHargaJual as $keyDataStokHargaJual){
                    $idBarangSatuan         =   isset($keyDataStokHargaJual->IDBARANGSATUAN) && $keyDataStokHargaJual->IDBARANGSATUAN != "" ? $keyDataStokHargaJual->IDBARANGSATUAN : "";
                    $dataDiskonBarangRetail =   $pengaturanDiskonModel->getDataDiskonBarangSKURetail($idBarangSKU, $idBarangSatuan);

                    if($dataDiskonBarangRetail){
                        $hargaAwal          =   isset($keyDataStokHargaJual->HARGA) && $keyDataStokHargaJual->HARGA != "" ? $keyDataStokHargaJual->HARGA : 0;
                        $tipeDiskon         =   isset($dataDiskonBarangRetail['TIPEDISKON']) && $dataDiskonBarangRetail['TIPEDISKON'] != "" ? $dataDiskonBarangRetail['TIPEDISKON'] : 1;
                        $jumlahDiskon       =   isset($dataDiskonBarangRetail['JUMLAHDISKON']) && $dataDiskonBarangRetail['JUMLAHDISKON'] != "" ? $dataDiskonBarangRetail['JUMLAHDISKON'] : 1;
                        $jumlahDiskonStr    =   $tipeDiskon == 1 ? $jumlahDiskon.'% OFF' : '- Rp '.number_format($jumlahDiskon,0,',','.');
                        $hargaSetelahDiskon =   $tipeDiskon == 1 ? $hargaAwal - ($hargaAwal * ($jumlahDiskon / 100)) : $hargaAwal - $jumlahDiskon;

                        $keyDataStokHargaJual->IDDISKONRETAIL       =   hashidEncode($dataDiskonBarangRetail['IDDISKONRETAIL']);
                        $keyDataStokHargaJual->DISKONDESKRIPSI      =   $dataDiskonBarangRetail['DESKRIPSI'];
                        $keyDataStokHargaJual->DISKONJUMLAH         =   $jumlahDiskonStr;
                        $keyDataStokHargaJual->HARGASETELAHDISKON   =   (string)$hargaSetelahDiskon;
                    }
                }
            }

            $keyBarangSKU->STOKHARGAJUAL  =   is_array($dataStokHargaJual) && count($dataStokHargaJual) > 0 ? encodeDatabaseObjectResultKey($dataStokHargaJual, ['IDBARANGSATUAN']) : [];
        }

        $listBarangSKU  =   encodeDatabaseObjectResultKey($listBarangSKU, ['IDBARANGSKU']);
        return $this->setResponseFormat('json')->respond(["listDataSKU" =>  $listBarangSKU, "baseURLFotoBarang" => URL_FOTO_BARANG]);
    }

    public function getDetailStokHargaJualPaket()
    {
        $rules      =   ['idHargaRetailPaket' =>  ['label' => 'Id Harga Retail Paket', 'rules' => 'required|alpha_numeric']];
        $messages   =   [
            'idHargaRetailPaket'=> [
                'required'      => 'Data kiriman tidak lengkap, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak lengkap, silakan periksa kembali'
            ]
        ];
        
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $idHargaRetailPaket =   $this->request->getVar('idHargaRetailPaket');
        $idHargaRetailPaket =   isset($idHargaRetailPaket) && $idHargaRetailPaket != "" ? hashidDecode($idHargaRetailPaket) : 0;
        $idToko             =   $this->idToko;

        if($idHargaRetailPaket == 0) return throwResponseNotFound('Paket penjualan yang dipilih tidak ditemukan');
        if(is_null($idToko) || $idToko == 0 || $idToko == "") return throwResponseNotFound('Data toko tidak ditemukan');

        $penjualanModel =   new PenjualanModel();
        $listBarangSKU  =   $penjualanModel->getListBarangSKUPaket($idHargaRetailPaket);
        $barangSKUModel =   new BarangSKUModel();
        $arrStokPaket   = [];
        
        foreach($listBarangSKU as $keyBarangSKU) {
            $idBarangSKU            =   isset($keyBarangSKU->IDBARANGSKU) && $keyBarangSKU->IDBARANGSKU != "" ? $keyBarangSKU->IDBARANGSKU : 0;
            $idBarangSatuan         =   isset($keyBarangSKU->IDBARANGSATUAN) && $keyBarangSKU->IDBARANGSATUAN != "" ? $keyBarangSKU->IDBARANGSATUAN : 0;
            $jumlahBarangPerPaket   =   isset($keyBarangSKU->JUMLAH) && $keyBarangSKU->JUMLAH != "" ? $keyBarangSKU->JUMLAH : 0;
            $stokBarangSKU          =   $penjualanModel->getDataStokBarangPerSKU($idToko, $idBarangSKU, $idBarangSatuan);
            
            $keyBarangSKU->ATRIBUTSKUSTR=   $barangSKUModel->getArrAtributSKU($idBarangSKU);
            $keyBarangSKU->FOTOBARANGSKU=   isset($keyBarangSKU->FOTOBARANGSKU) && $keyBarangSKU->FOTOBARANGSKU != "" ? json_decode($keyBarangSKU->FOTOBARANGSKU) : ['noimage.jpg'];
            $keyBarangSKU->STOK         =   $stokBarangSKU;
            $arrStokPaket[]             =   floor($stokBarangSKU / $jumlahBarangPerPaket);

            unset($keyBarangSKU->IDHARGARETAILPAKETSKU);
            unset($keyBarangSKU->IDBARANGSKU);
            unset($keyBarangSKU->IDBARANGSATUAN);
            unset($keyBarangSKU->NAMAHARGARETAILPAKET);
        }

        return $this
                ->setResponseFormat('json')
                ->respond(
                    [
                        "stokPaket"         =>  count($arrStokPaket) > 0 ? min($arrStokPaket) : 0,
                        "listDataSKU"       =>  $listBarangSKU,
                        "baseURLFotoBarang" =>  URL_FOTO_BARANG
                    ]
                );
    }

    public function getRingkasanPenjualanDataCustomer()
    {
        $rules  =   [
            'arrDataBarangSKU.*.idBarangSKU'            =>  ['label' => 'Id Barang SKU', 'rules' => 'permit_empty|alpha_numeric'],
            'arrDataBarangSKU.*.idBarangSatuan'         =>  ['label' => 'Id Barang Satuan', 'rules' => 'permit_empty|alpha_numeric'],
            'arrDataBarangSKU.*.jumlah'                 =>  ['label' => 'Jumlah', 'rules' => 'permit_empty|numeric|greater_than[0]|min_length[1]|max_length[4]'],
            'arrDataBarangSKU.*.harga'                  =>  ['label' => 'Harga', 'rules' => 'permit_empty|numeric|greater_than[0]|min_length[1]|max_length[8]'],
        ];

        $messages   =   [
            'arrDataBarangSKU.*.idBarangSKU' =>  [
                'alpha_numeric' =>  'Data barang yang dipilih tidak valid, silakan periksa kembali'
            ],
            'arrDataBarangSKU.*.idBarangSatuan' =>  [
                'alpha_numeric' =>  'Data satuan barang yang dipilih tidak valid, silakan periksa kembali'
            ],
            'arrDataBarangSKU.*.jumlah' =>  [
                'numeric'       =>  'Jumlah pembelian untuk barang yang dipilih tidak valid, silakan periksa kembali',
                'greater_than'  =>  'Jumlah pembelian untuk barang yang dipilih harus lebih besar dari 0',
                'min_length'    =>  'Jumlah pembelian untuk barang yang dipilih tidak valid, silakan periksa kembali',
                'max_length'    =>  'Jumlah pembelian untuk barang yang dipilih tidak valid, silakan periksa kembali'
            ],
            'arrDataBarangSKU.*.harga' =>  [
                'numeric'       =>  'Harga untuk barang yang dipilih tidak valid, silakan periksa kembali',
                'greater_than'  =>  'Harga untuk barang yang dipilih harus lebih besar dari 0',
                'min_length'    =>  'Harga untuk barang yang dipilih tidak valid, silakan periksa kembali',
                'max_length'    =>  'Harga untuk barang yang dipilih tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $penjualanModel     =   new PenjualanModel();
        $mainOperation      =   new MainOperation();
        $idToko             =   $this->idToko;
        $arrDataBarangSKU   =   $this->request->getVar('arrDataBarangSKU');
        $arrDataBarangSKUCek=   [];
        
        if(isset($arrDataBarangSKU) && is_array($arrDataBarangSKU) && count($arrDataBarangSKU) > 0) {
            foreach($arrDataBarangSKU as $keyDataBarangSKU) {
                $idBarangSKU    =   isset($keyDataBarangSKU->idBarangSKU) ? hashidDecode($keyDataBarangSKU->idBarangSKU) : 0;
                $idBarangSatuan =   isset($keyDataBarangSKU->idBarangSatuan) ? hashidDecode($keyDataBarangSKU->idBarangSatuan) : 0;
                $jumlah         =   isset($keyDataBarangSKU->jumlah) ? $keyDataBarangSKU->jumlah : 0;
                $harga          =   isset($keyDataBarangSKU->harga) ? $keyDataBarangSKU->harga : 0;

                if($idBarangSKU <= 0 || $idBarangSatuan <= 0 || $jumlah <= 0) {
                    return throwResponseNotFound('Data barang yang dipilih tidak valid, silakan periksa kembali');
                }
                
                $arrDataBarangSKUCek[] =   [
                    "idBarangSKU"   =>  $idBarangSKU,
                    "idBarangSatuan"=>  $idBarangSatuan,
                    "jumlah"        =>  $jumlah,
                    "harga"         =>  $harga
                ];
            }
        }

        $arrDataBarangSKUSisa   =   $arrDataBarangSKUCek;
        $arrDataBarangSKUDiskon =   [];
        $dataDiskonPaketBySKU   =   $penjualanModel->getDataDiskonPaketRetailByBarangSKU($arrDataBarangSKUCek);

        if($dataDiskonPaketBySKU){
            while(count($dataDiskonPaketBySKU) > 0 && count($arrDataBarangSKUSisa) > 0){
                foreach($dataDiskonPaketBySKU as $keyDataDiskonPaketBySKU){
                    $idDiskonRetailPaket    =   isset($keyDataDiskonPaketBySKU->IDDISKONRETAILPAKET) ? $keyDataDiskonPaketBySKU->IDDISKONRETAILPAKET : 0;
                    $namaDiskonRetailPaket  =   isset($keyDataDiskonPaketBySKU->NAMAPAKETDISKON) ? $keyDataDiskonPaketBySKU->NAMAPAKETDISKON : '-';
                    $dataDiskonPaketKondisi =   $penjualanModel->getDataDiskonPaketKondisi($idDiskonRetailPaket);
                    $arrDataBarangSKUTampung=   [];

                    if($dataDiskonPaketKondisi){
                        //Iterate data kondisi & bandingkan dengan data barang SKU yang tersisa, jika memenuhi semua kondisi maka data barang SKU tersebut akan dimasukkan ke dalam data barang SKU yang memenuhi kondisi paket diskon
                        foreach($dataDiskonPaketKondisi as $keyDataDiskonPaketKondisi){
                            $idBarangSKUKondisi     =   isset($keyDataDiskonPaketKondisi->IDBARANGSKU) ? $keyDataDiskonPaketKondisi->IDBARANGSKU : 0;
                            $idBarangSatuanKondisi  =   isset($keyDataDiskonPaketKondisi->IDBARANGSATUAN) ? $keyDataDiskonPaketKondisi->IDBARANGSATUAN : 0;
                            $minimalJumlahKondisi   =   isset($keyDataDiskonPaketKondisi->MINIMALJUMLAH) ? $keyDataDiskonPaketKondisi->MINIMALJUMLAH : 0;

                            $filteredBarangSKUSisa  =   array_filter($arrDataBarangSKUSisa, function($dataBarangSKUSisa) use ($idBarangSKUKondisi, $idBarangSatuanKondisi, $minimalJumlahKondisi) {
                                return $dataBarangSKUSisa['idBarangSKU'] == $idBarangSKUKondisi && $dataBarangSKUSisa['idBarangSatuan'] == $idBarangSatuanKondisi && $dataBarangSKUSisa['jumlah'] >= $minimalJumlahKondisi;
                            });
                            
                            if(count($filteredBarangSKUSisa) > 0) {
                                $filteredValues = array_values($filteredBarangSKUSisa);
                                $arrDataBarangSKUTampung[] = $filteredValues[0];
                            }
                        }

                        //Cek jika jumlah data barang SKU tampungan yang memenuhi kondisi paket diskon sama dengan jumlah kondisi paket diskon
                        //Jika sama maka data paket diskon tersebut akan dimasukkan ke dalam data paket diskon yang memenuhi kondisi dan data barang SKU yang memenuhi kondisi akan dihapus dari data barang SKU yang tersisa
                        if(count($dataDiskonPaketKondisi) == count($arrDataBarangSKUTampung)){
                            foreach($arrDataBarangSKUTampung as $dataBarangSKUTampung){
                                $hargaAwal              =   isset($dataBarangSKUTampung['harga']) ? $dataBarangSKUTampung['harga'] : 0;
                                $hargaAwal              =   isset($dataBarangSKUTampung['harga']) ? $dataBarangSKUTampung['harga'] : 0;
                                $dataDiskonRetailPaket  =   $penjualanModel->getDataDiskonPaketNominal($idDiskonRetailPaket, $dataBarangSKUTampung['idBarangSKU'], $dataBarangSKUTampung['idBarangSatuan']);
                                
                                if($dataDiskonRetailPaket) {
                                    $tipeDiskon         =   isset($dataDiskonRetailPaket->TIPEDISKON) ? $dataDiskonRetailPaket->TIPEDISKON : 2;
                                    $jumlahDiskon       =   isset($dataDiskonRetailPaket->JUMLAHDISKON) ? $dataDiskonRetailPaket->JUMLAHDISKON : 0;
                                    $nominalDiskonStr   =   $tipeDiskon == 1 ? $jumlahDiskon.'% OFF' : 'Rp '.number_format($jumlahDiskon,0,',','.');
                                    $nominalDiskon      =   $tipeDiskon == 1 ? $hargaAwal * $jumlahDiskon / 100 : $jumlahDiskon;
                                    $barangSatuanStr    =   $mainOperation->getKodeSatuanById($dataBarangSKUTampung['idBarangSatuan']);

                                    unset($dataBarangSKUTampung['jumlah']);
                                    unset($dataBarangSKUTampung['harga']);
                                    $arrDataBarangSKUDiskon[]   =   array_merge(
                                        $dataBarangSKUTampung,
                                        [
                                            'idDiskonRetailPaket'   =>  $idDiskonRetailPaket,
                                            'keteranganDiskonPaket' =>  'Diskon Paket '.$namaDiskonRetailPaket.' ('.$nominalDiskonStr.' / '.$barangSatuanStr.')',
                                            'nominalDiskon'         =>  intval($nominalDiskon)
                                        ]
                                    );
                                }
                            }
                            
                            $arrDataBarangSKUSisa = array_filter($arrDataBarangSKUSisa, function($dataBarangSKUSisa) use ($arrDataBarangSKUTampung) {
                                foreach($arrDataBarangSKUTampung as $dataBarangSKUTampung){
                                    if(
                                        $dataBarangSKUSisa['idBarangSKU'] == $dataBarangSKUTampung['idBarangSKU'] &&
                                        $dataBarangSKUSisa['idBarangSatuan'] == $dataBarangSKUTampung['idBarangSatuan']
                                    ){
                                        return false;
                                    }
                                }
                                return true; 
                            });
                            
                            $arrDataBarangSKUSisa = array_values($arrDataBarangSKUSisa);
                        }
                    }

                    unset($dataDiskonPaketBySKU[array_search($keyDataDiskonPaketBySKU, $dataDiskonPaketBySKU)]);
                }
            }
        }

        $dataCustomer   =   $penjualanModel->getListCustomer($idToko, null);
        $dataCustomer   =   !$dataCustomer ? [] : encodeDatabaseObjectResultKey($dataCustomer, ['IDCUSTOMER']);
        $dataDiskonEvent=   $penjualanModel->getListDiskonEvent($idToko);
        $dataDiskonEvent=   !$dataDiskonEvent ? [] : encodeDatabaseObjectResultKey($dataDiskonEvent, ['IDDISKONEVENT']);

        return $this->setResponseFormat('json')->respond([
            "arrDataBarangSKUDiskon"=>  $arrDataBarangSKUDiskon == [] ? [] : encodeDataArrayKey($arrDataBarangSKUDiskon, ['idBarangSKU', 'idBarangSatuan', 'idDiskonRetailPaket']),
            "dataCustomer"          =>  $dataCustomer,
            "dataDiskonEvent"       =>  $dataDiskonEvent
        ]);
    }

    public function getListCustomer()
    {
        $rules  =   [
            'searchKeyword' =>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct']
        ];

        $messages   =   [];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $penjualanModel =   new PenjualanModel();
        $idToko         =   $this->idToko;
        $searchKeyword  =   $this->request->getVar('searchKeyword');
        $dataCustomer   =   $penjualanModel->getListCustomer($idToko, $searchKeyword);

        if(!$dataCustomer) return throwResponseNotFound('Tidak ada data customer yang ditemukan', ['listData' =>  []]);

        $dataCustomer   =   encodeDatabaseObjectResultKey($dataCustomer, ['IDCUSTOMER']);
        return $this->setResponseFormat('json')->respond(["listData" =>  $dataCustomer]);
    }

    public function addNewCustomer()
    {
        $rules  =   [
            'nama'      =>  ['label' => 'Nama Customer', 'rules' => 'required|alpha_numeric_space|min_length[3]|max_length[50]'],
            'alamat'    =>  ['label' => 'Alamat Customer', 'rules' => 'required|alpha_numeric_punct|min_length[10]|max_length[150]'],
            'telpon'    =>  ['label' => 'Telpon Customer', 'rules' => 'required|regex_match[/^\+?[0-9]{10,13}$/]|min_length[8]|max_length[20]|is_unique[m_customer.TELPON]'],
            'catatan'   =>  ['label' => 'Catatan', 'rules' => 'permit_empty|alpha_numeric_punct|max_length[500]']
        ];

        if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
 
        $mainOperation  =   new MainOperation();
        $nama           =   $this->request->getVar('nama');
        $alamat         =   $this->request->getVar('alamat');
        $telpon         =   $this->request->getVar('telpon');
        $catatan        =   $this->request->getVar('catatan');
        $catatan        =   isset($catatan) && $catatan != "" ? $catatan : "-";
        $arrInsertUpdate=   [
            'IDTOKO'            =>  $this->idToko,
            'NAMA'              =>  $nama,
            'ALAMAT'            =>  $alamat,
            'TELPON'            =>  $telpon,
            'CATATAN'           =>  $catatan,
            'INPUTTANGGALWAKTU' =>  $this->currentDateTime
        ];

        $procInsertData =   $mainOperation->insertDataTable('m_customer', $arrInsertUpdate);
        if(!$procInsertData['status']) return switchMySQLErrorCode($procInsertData['errCode']);
        $idCustomer     =   $procInsertData['insertID'];

        return throwResponseOK(
            'Data customer telah disimpan',
            [
                'idCustomer'=>  hashidEncode($idCustomer),
                'nama'      =>  $nama,
                'telpon'    =>  $telpon,
            ]
        );
    }

    public function savePenjualanBarang()
    {
        $rules  =   [
            'idCustomer'                                =>  ['label' => 'Customer', 'rules' => 'permit_empty|alpha_numeric'],
            'idMetodeBayar'                             =>  ['label' => 'Metode Bayar', 'rules' => 'required|alpha_numeric'],
            'arrDataBarang.*.idBarangSKU'               =>  ['label' => 'SKU Barang', 'rules' => 'permit_empty|alpha_numeric'],
            'arrDataBarang.*.idSatuan'                  =>  ['label' => 'Satuan', 'rules' => 'permit_empty|alpha_numeric'],
            'arrDataBarang.*.idDiskonRetail'            =>  ['label' => 'Diskon Retail', 'rules' => 'permit_empty|alpha_numeric'],
            'arrDataBarang.*.idDiskonRetailPaket'       =>  ['label' => 'Diskon Retail Paket', 'rules' => 'permit_empty|alpha_numeric'],
            'arrDataBarang.*.jumlah'                    =>  ['label' => 'Jumlah', 'rules' => 'permit_empty|numeric|greater_than[0]|min_length[1]|max_length[10]'],
            'arrDataBarang.*.harga'                     =>  ['label' => 'Harga', 'rules' => 'permit_empty|numeric|greater_than[0]|min_length[1]|max_length[10]'],
            'arrDataBarangPaket.*.idHargaRetailPaket'   =>  ['label' => 'Id Harga Retail Paket', 'rules' => 'permit_empty|alpha_numeric'],
            'arrDataBarangPaket.*.jumlahPaket'          =>  ['label' => 'Jumlah Paket', 'rules' => 'permit_empty|numeric|greater_than[0]|min_length[1]|max_length[4]'],
            'arrDataBiayaLainnya.*.jenisBiaya'          =>  ['label' => '[Biaya Lainnya] Jenis', 'rules' => 'permit_empty|alpha_numeric_punct|min_length[3]|max_length[25]'],
            'arrDataBiayaLainnya.*.deskripsi'           =>  ['label' => '[Biaya Lainnya] Deskripsi', 'rules' => 'permit_empty|alpha_numeric_punct|min_length[5]|max_length[200]'],
            'arrDataDiskonEvent.*.idDiskonEvent'        =>  ['label' => '[Diskon Event] ID', 'rules' => 'permit_empty|alpha_numeric'],
            'arrDataDiskonEvent.*.nominal'              =>  ['label' => '[Diskon Event] Nominal', 'rules' => 'permit_empty|numeric|greater_than[0]|min_length[1]|max_length[10]'],
            'arrDataDiskonEvent.*.keterangan'           =>  ['label' => '[Diskon Event] Keterangan', 'rules' => 'permit_empty|alpha_numeric_punct|min_length[2]|max_length[200]'],
            'grandTotalHarga'                           =>  ['label' => 'Grand Total Harga', 'rules' => 'required|numeric|greater_than[0]|min_length[3]|max_length[10]'],
            'totalBayar'                                =>  ['label' => 'Total Bayar', 'rules' => 'required|numeric|greater_than[0]|min_length[3]|max_length[10]'],
            'catatan'                                   =>  ['label' => 'Catatan', 'rules' => 'permit_empty|alpha_numeric_punct|max_length[255]']
        ];

        $messages   =   [
            'arrDataBarang.*.idBarangSKU'   =>  [
                'alpha_numeric' =>  'SKU barang yang dipilih tidak valid, silakan periksa kembali'
            ],
            'arrDataBarang.*.idSatuan'  =>  [
                'alpha_numeric' =>  'Satuan barang yang dipilih tidak valid, silakan periksa kembali'
            ],
            'arrDataBarang.*.idDiskonRetail'=>  [
                'alpha_numeric' =>  'Diskon retail yang dipilih tidak valid, silakan periksa kembali'
            ],
            'arrDataBarang.*.idDiskonRetailPaket'=>  [
                'alpha_numeric' =>  'Diskon retail paket yang dipilih tidak valid, silakan periksa kembali'
            ],
            'arrDataBarangPaket.*.idHargaRetailPaket' =>  [
                'alpha_numeric' =>  'Data paket yang dipilih tidak valid, silakan periksa kembali'
            ],
            'arrDataBarangPaket.*.jumlahPaket' =>  [
                'numeric'       =>  'Jumlah pembelian untuk paket yang dipilih tidak valid, silakan periksa kembali',
                'greater_than'  =>  'Jumlah pembelian untuk paket yang dipilih harus lebih besar dari 0',
                'min_length'    =>  'Jumlah pembelian untuk paket yang dipilih tidak valid, silakan periksa kembali',
                'max_length'    =>  'Jumlah pembelian untuk paket yang dipilih tidak valid, silakan periksa kembali'
            ],
            'arrDataDiskonEvent.*.idDiskonEvent' =>  [
                'alpha_numeric' =>  'Diskon event yang dipilih tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());
 
        $idCustomer         =   $this->request->getVar('idCustomer');
        $idCustomer         =   isset($idCustomer) && $idCustomer != "" ? hashidDecode($idCustomer) : 1;
        $idMetodeBayar      =   $this->request->getVar('idMetodeBayar');
        $idMetodeBayar      =   isset($idMetodeBayar) && $idMetodeBayar != "" ? hashidDecode($idMetodeBayar) : 0;
        $arrDataBarang      =   $this->request->getVar('arrDataBarang');
        $arrDataBarangPaket =   $this->request->getVar('arrDataBarangPaket');
        $arrDataBiayaLainnya=   $this->request->getVar('arrDataBiayaLainnya');
        $arrDataDiskonEvent =   $this->request->getVar('arrDataDiskonEvent');
        $grandTotalHarga    =   $this->request->getVar('grandTotalHarga');
        $totalBayar         =   $this->request->getVar('totalBayar');
        $catatan            =   $this->request->getVar('catatan');

        if($idCustomer <= 0) return throwResponseNotFound('Data customer tidak ditemukan');
        if($idMetodeBayar <= 0) return throwResponseNotFound('Jenis metode bayar tidak valid');
        if(empty($arrDataBarang) && empty($arrDataBarangPaket)) return throwResponseNotFound('Harap pilih minimal 1 barang atau paket yang akan dibeli');

        $mainOperation          =   new MainOperation();
        $penjualanModel         =   new PenjualanModel();
        $pengaturanDiskonModel  =   new PengaturanDiskonModel();

        $totalHargaBarangAwal   =   $totalHargaBarang   =   $totalHargaDiskon   =   $totalHargaDiskonEvent   =   $totalHargaLain =   $totalBarangSKU =   0;
        $arrInsertDataDetail    =   $arrInsertDataStok  =   $arrInsertDataBiayaLain =   $arrInsertDataDiskonEvent =   [];
        $notaPenjualanNomor     =   $this->generateNotaPenjualanNomor();

        foreach($arrDataBarang as $keyDataBarang){
            $idBarangSKU                =   isset($keyDataBarang->idBarangSKU) ? hashidDecode($keyDataBarang->idBarangSKU) : 0;
            $idDiskonRetailPaket        =   isset($keyDataBarang->idDiskonRetailPaket) ? hashidDecode($keyDataBarang->idDiskonRetailPaket) : 0;
            $idDiskonRetail             =   isset($keyDataBarang->idDiskonRetail) ? hashidDecode($keyDataBarang->idDiskonRetail) : 0;
            $idDiskonRetail             =   $idDiskonRetailPaket > 0 ? 0 : $idDiskonRetail;
            $idSatuan                   =   isset($keyDataBarang->idSatuan) ? hashidDecode($keyDataBarang->idSatuan) : 0;
            $jumlah                     =   isset($keyDataBarang->jumlah) ? $keyDataBarang->jumlah : 0;
            $hargaJualBarangRetail      =   isset($keyDataBarang->harga) ? $keyDataBarang->harga : 0;
            $idDiskonRetailPaketNominal =   0;

            if($idBarangSKU <= 0) return throwResponseNotFound('Data SKU barang tidak ditemukan');
            if($idSatuan <= 0) return throwResponseNotFound('Data satuan tidak valid');
            if($jumlah <= 0) return throwResponseNotFound('Jumlah pembelian tidak valid');
            if($hargaJualBarangRetail <= 0) return throwResponseNotFound('Harga barang tidak valid');

            $detailBarangSKU    =   $mainOperation->getDetailBarangSKU($idBarangSKU);
            $namaBarangSKU      =   isset($detailBarangSKU['NAMABARANGFULL']) ? $detailBarangSKU['NAMABARANGFULL'] : '-';

            $dataStokBarangSKU  =   $penjualanModel->getStokBarangToko($this->idToko, $idBarangSKU);
            $idBarang           =   isset($dataStokBarangSKU['IDBARANG']) ? $dataStokBarangSKU['IDBARANG'] : 0;
            $stokBarangSKU      =   isset($dataStokBarangSKU['STOK']) ? $dataStokBarangSKU['STOK'] : 0;
            if($stokBarangSKU < $jumlah) return throwResponseNotFound('Stok barang <b>'.$namaBarangSKU.'</b> tidak mencukupi');

            $dataHargaBarangSKU =   $penjualanModel->getHargaBarangSKUToko($this->idToko, $idBarangSKU, $idSatuan);
            $hargaDiskonSebelum =   isset($dataHargaBarangSKU['HARGA']) ? $dataHargaBarangSKU['HARGA'] : 0;
            $hargaDiskonNominal =   0;

            if($idDiskonRetail > 0){
                $detailDiskonRetail     =   $pengaturanDiskonModel->getDetailDiskonBarangRetail($idDiskonRetail);
                if(!$detailDiskonRetail) return throwResponseNotFound('Detail diskon untuk barang <b>'.$namaBarangSKU.'</b> tidak valid, harap ulangi proses penjualan');

                $tipeDiskon             =   isset($detailDiskonRetail['TIPEDISKON']) && $detailDiskonRetail['TIPEDISKON'] != "" ? $detailDiskonRetail['TIPEDISKON'] : 1;
                $jumlahDiskon           =   isset($detailDiskonRetail['JUMLAHDISKON']) && $detailDiskonRetail['JUMLAHDISKON'] != "" ? $detailDiskonRetail['JUMLAHDISKON'] : 0;
                $hargaDiskonNominal     =   $tipeDiskon == 1 ? $hargaDiskonSebelum * ($jumlahDiskon / 100) : $jumlahDiskon;
                $hargaJualBarangRetail  =   $hargaDiskonSebelum - $hargaDiskonNominal;
            }

            if($idDiskonRetailPaket > 0){
                $detailDiskonRetailPaket=   $penjualanModel->getDataDiskonPaketNominal($idDiskonRetailPaket, $idBarangSKU, $idSatuan);
                if(!$detailDiskonRetailPaket) return throwResponseNotFound('Detail diskon paket untuk barang <b>'.$namaBarangSKU.'</b> tidak valid, harap ulangi proses penjualan');

                $idDiskonRetailPaketNominal =   isset($detailDiskonRetailPaket->IDDISKONRETAILPAKETNOMINAL) && $detailDiskonRetailPaket->IDDISKONRETAILPAKETNOMINAL != "" ? $detailDiskonRetailPaket->IDDISKONRETAILPAKETNOMINAL : 1;
                $tipeDiskon                 =   isset($detailDiskonRetailPaket->TIPEDISKON) && $detailDiskonRetailPaket->TIPEDISKON != "" ? $detailDiskonRetailPaket->TIPEDISKON : 1;
                $jumlahDiskon               =   isset($detailDiskonRetailPaket->JUMLAHDISKON) && $detailDiskonRetailPaket->JUMLAHDISKON != "" ? $detailDiskonRetailPaket->JUMLAHDISKON : 0;
                $hargaDiskonNominal         =   $tipeDiskon == 1 ? $hargaDiskonSebelum * ($jumlahDiskon / 100) : $jumlahDiskon;
                $hargaJualBarangRetail      =   $hargaDiskonSebelum - $hargaDiskonNominal;
            }

            $arrInsertDataDetail[]  =   [
                'IDPENJUALANREKAP'          =>  null,
                'IDBARANGSKU'               =>  $idBarangSKU,
                'IDBARANGSATUAN'            =>  $idSatuan,
                'IDDISKONRETAIL'            =>  $idDiskonRetail,
                'IDDISKONRETAILPAKETNOMINAL'=>  $idDiskonRetailPaketNominal,
                'JUMLAH'                    =>  $jumlah,
                'HARGAAWAL'                 =>  $hargaDiskonSebelum,
                'HARGADISKON'               =>  $hargaDiskonNominal * -1,
                'HARGASATUAN'               =>  $hargaJualBarangRetail
            ];

            $arrInsertDataStok[]    =   [
                'IDTOKO'                =>  $this->idToko,
                'IDBARANG'              =>  $idBarang,
                'IDBARANGSKU'           =>  $idBarangSKU,
                'IDTOKONOTAMUTASIBARANG'=>  0,
                'IDMUTASIJENISTOKO'     =>  2,
                'IDPENJUALANBARANG'     =>  null,
                'IDBARANGSATUAN'        =>  $idSatuan,
                'JUMLAHMASUK'           =>  0,
                'JUMLAHKELUAR'          =>  $jumlah,
                'MUTASIKETERANGAN'      =>  'Penjualan barang retail dari nota no. '.$notaPenjualanNomor.' ('.$this->currentDateTime.')',
                'INPUTUSER'             =>  $this->userData->name.' ('.$this->userData->userLevelName.')',
                'INPUTTANGGALWAKTU'     =>  $this->currentDateTime
            ];

            $totalHargaBarangAwal   +=  ($jumlah * $hargaDiskonSebelum);
            $totalHargaBarang       +=  ($jumlah * $hargaJualBarangRetail);
            $totalHargaDiskon       +=  ($jumlah * $hargaDiskonNominal) * -1;
            $totalBarangSKU++;
        }

        foreach($arrDataBarangPaket as $keyBarangPaket){
            $idBarangPaket      =   isset($keyBarangPaket->idHargaRetailPaket) ? hashidDecode($keyBarangPaket->idHargaRetailPaket) : 0;
            $jumlahPaket        =   isset($keyBarangPaket->jumlahPaket) ? $keyBarangPaket->jumlahPaket : 0;
            $dataBarangSKUPaket =   $penjualanModel->getListBarangSKUPaket($idBarangPaket);

            if(!$dataBarangSKUPaket) return throwResponseNotFound('Data paket yang dipilih tidak valid, harap ulangi proses penjualan');
            foreach($dataBarangSKUPaket as $keyDataBarangSKUPaket){
                $idBarangSKU            =   isset($keyDataBarangSKUPaket->IDBARANGSKU) ? $keyDataBarangSKUPaket->IDBARANGSKU : 0;
                $idBarangSatuan         =   isset($keyDataBarangSKUPaket->IDBARANGSATUAN) ? $keyDataBarangSKUPaket->IDBARANGSATUAN : 0;
                $idHargaRetailPaketSKU  =   isset($keyDataBarangSKUPaket->IDHARGARETAILPAKETSKU) ? $keyDataBarangSKUPaket->IDHARGARETAILPAKETSKU : 0;
                $namaHargaPaket         =   isset($keyDataBarangSKUPaket->NAMAHARGARETAILPAKET) ? $keyDataBarangSKUPaket->NAMAHARGARETAILPAKET : 0;
                $jumlahBarangSKU        =   isset($keyDataBarangSKUPaket->JUMLAH) ? $keyDataBarangSKUPaket->JUMLAH : 0;
                $hargaBarangSKU         =   isset($keyDataBarangSKUPaket->HARGA) ? $keyDataBarangSKUPaket->HARGA : 0;
                $totalBarangSKU         =   $jumlahPaket * $jumlahBarangSKU;
                $detailBarangSKU        =   $mainOperation->getDetailBarangSKU($idBarangSKU);
                $namaBarangSKU          =   isset($detailBarangSKU['NAMABARANGFULL']) ? $detailBarangSKU['NAMABARANGFULL'] : '-';

                $dataStokBarangSKU  =   $penjualanModel->getStokBarangToko($this->idToko, $idBarangSKU);
                $idBarang           =   isset($dataStokBarangSKU['IDBARANG']) ? $dataStokBarangSKU['IDBARANG'] : 0;
                $stokBarangSKU      =   isset($dataStokBarangSKU['STOK']) ? $dataStokBarangSKU['STOK'] : 0;
                if($stokBarangSKU < $totalBarangSKU) return throwResponseNotFound('Stok barang <b>'.$namaBarangSKU.'</b> tidak mencukupi');               

                $arrInsertDataDetail[]  =   [
                    'IDPENJUALANREKAP'          =>  null,
                    'IDBARANGSKU'               =>  $idBarangSKU,
                    'IDBARANGSATUAN'            =>  $idBarangSatuan,
                    'IDHARGARETAILPAKETSKU'     =>  $idHargaRetailPaketSKU,
                    'IDDISKONRETAIL'            =>  0,
                    'IDDISKONRETAILPAKETNOMINAL'=>  0,
                    'JUMLAH'                    =>  $totalBarangSKU,
                    'HARGAAWAL'                 =>  $hargaBarangSKU,
                    'HARGADISKON'               =>  0,
                    'HARGASATUAN'               =>  $hargaBarangSKU
                ];

                $arrInsertDataStok[]    =   [
                    'IDTOKO'                =>  $this->idToko,
                    'IDBARANG'              =>  $idBarang,
                    'IDBARANGSKU'           =>  $idBarangSKU,
                    'IDTOKONOTAMUTASIBARANG'=>  0,
                    'IDMUTASIJENISTOKO'     =>  2,
                    'IDPENJUALANBARANG'     =>  null,
                    'IDBARANGSATUAN'        =>  $idBarangSatuan,
                    'JUMLAHMASUK'           =>  0,
                    'JUMLAHKELUAR'          =>  $totalBarangSKU,
                    'MUTASIKETERANGAN'      =>  'Penjualan barang retail dari nota no. '.$notaPenjualanNomor.' - '.$namaHargaPaket.' ('.$this->currentDateTime.')',
                    'INPUTUSER'             =>  $this->userData->name.' ('.$this->userData->userLevelName.')',
                    'INPUTTANGGALWAKTU'     =>  $this->currentDateTime
                ];

                $totalHargaBarangAwal   +=  ($totalBarangSKU * $hargaBarangSKU);
                $totalHargaBarang       +=  ($totalBarangSKU * $hargaBarangSKU);
                $totalBarangSKU++;
            }
        }

        foreach($arrDataBiayaLainnya as $keyDataBiayaLainnya){
            $jenisBiaya =   isset($keyDataBiayaLainnya->jenisBiaya) ? $keyDataBiayaLainnya->jenisBiaya : '';
            $deskripsi  =   isset($keyDataBiayaLainnya->deskripsi) ? $keyDataBiayaLainnya->deskripsi : '';
            $nominal    =   isset($keyDataBiayaLainnya->nominal) ? $keyDataBiayaLainnya->nominal : 0;

            $arrInsertDataBiayaLain[]  = [
                'IDPENJUALANREKAP'  =>  null,
                'JENISBIAYA'        =>  $jenisBiaya,
                'DESKRIPSI'         =>  $deskripsi,
                'NOMINAL'           =>  $nominal
            ];

            $totalHargaLain +=  $nominal;
        }

        foreach($arrDataDiskonEvent as $keyDataDiskonEvent){
            $idDiskonEvent          =   isset($keyDataDiskonEvent->idDiskonEvent) ? hashidDecode($keyDataDiskonEvent->idDiskonEvent) : 0;
            $nominalDiskonRequest   =   isset($keyDataDiskonEvent->nominal) ? $keyDataDiskonEvent->nominal : 0;
            $keteranganDiskonEvent  =   isset($keyDataDiskonEvent->keterangan) ? $keyDataDiskonEvent->keterangan : '';
            $detailDiskonEvent      =   $penjualanModel->getDetailDiskonEvent($idDiskonEvent);
            
            if(!$detailDiskonEvent) return throwResponseNotFound('Detail diskon event yang dipilih tidak valid, harap ulangi proses penjualan');
            
            $tipeDiskonEventDB      =   isset($detailDiskonEvent['TIPEDISKON']) && $detailDiskonEvent['TIPEDISKON'] != "" ? $detailDiskonEvent['TIPEDISKON'] : 1;
            $namaDiskonEventDB      =   isset($detailDiskonEvent['NAMAEVENT']) && $detailDiskonEvent['NAMAEVENT'] != "" ? $detailDiskonEvent['NAMAEVENT'] : '-';
            $jumlahDiskonEventDB    =   isset($detailDiskonEvent['JUMLAHDISKON']) && $detailDiskonEvent['JUMLAHDISKON'] != "" ? $detailDiskonEvent['JUMLAHDISKON'] : 0;
            $nominalDiskonEventDB   =   $tipeDiskonEventDB == 1 ? $totalHargaBarang * $jumlahDiskonEventDB / 100 : $jumlahDiskonEventDB;

            if($nominalDiskonRequest != $nominalDiskonEventDB) return throwResponseNotFound('Perhitungan nominal diskon event ['.$namaDiskonEventDB.'] tidak sesuai, harap ulangi proses penjualan');

            $arrInsertDataDiskonEvent[]   =   [
                'IDPENJUALANREKAP'  =>  null,
                'IDDISKONEVENT'     =>  $idDiskonEvent,
                'NOMINAL'           =>  $nominalDiskonEventDB,
                'KETERANGAN'        =>  $keteranganDiskonEvent
            ];

            $totalHargaDiskonEvent +=  $nominalDiskonEventDB;
        }

        if(($totalHargaBarang + $totalHargaLain - $totalHargaDiskonEvent) != $grandTotalHarga) {
            return throwResponseNotFound(
                        'Total harga tidak sesuai, harap ulangi proses penjualan',
                        [
                            "totalHargaBarangAwal"  =>  $totalHargaBarangAwal,
                            "totalHargaDiskon"      =>  $totalHargaDiskon,
                            "totalHargaDiskonEvent" =>  $totalHargaDiskonEvent,
                            "totalHargaBarang"      =>  $totalHargaBarang,
                            "totalHargaLain"        =>  $totalHargaLain,
                            "grandTotalHarga"       =>  $grandTotalHarga
                        ]
                    );
        }

        $inputUser  =   $this->userData->name.' ('.$this->userData->userLevelName.')';
        $arrInsertPenjualanRekap    =   [
            'IDTOKO'                =>  $this->idToko,
            'IDCUSTOMER'            =>  $idCustomer,
            'IDMETODEBAYAR'         =>  $idMetodeBayar,
            'NOTAPENJUALANNOMOR'    =>  $notaPenjualanNomor,
            'TOTALJENISBARANGSKU'   =>  $totalBarangSKU,
            'TOTALHARGABARANG'      =>  $totalHargaBarangAwal,
            'TOTALHARGADISKON'      =>  $totalHargaDiskon,
            'TOTALHARGADISKONEVENT' =>  $totalHargaDiskonEvent,
            'TOTALHARGALAIN'        =>  $totalHargaLain,
            'TOTALHARGAAKHIR'       =>  $grandTotalHarga,
            'TOTALBAYAR'            =>  $totalBayar,
            'CATATAN'               =>  $catatan,
            'INPUTUSER'             =>  $inputUser,
            'INPUTTANGGALWAKTU'     =>  $this->currentDateTime
        ];

        $procInsertDataRekap=   $mainOperation->insertDataTable('t_penjualanrekap', $arrInsertPenjualanRekap);
        if(!$procInsertDataRekap['status']) return switchMySQLErrorCode($procInsertDataRekap['errCode']);
        $idPenjualanRekap   =   $procInsertDataRekap['insertID'];

        foreach($arrInsertDataDetail as $index => &$keyInsertDataDetail) {
            $keyInsertDataDetail['IDPENJUALANREKAP']    =   $idPenjualanRekap;
            $procInsertDataBarang   =   $mainOperation->insertDataTable('t_penjualanbarang', $keyInsertDataDetail);

            if($procInsertDataBarang['status']) {
                $idPenjualanBarang  =   $procInsertDataBarang['insertID'];
                $arrInsertDataStok[$index]['IDPENJUALANBARANG']   =   $idPenjualanBarang;
                $mainOperation->insertDataTable('t_tokostok', $arrInsertDataStok[$index]);
            }
        }

        if(!empty($arrInsertDataBiayaLain) && count($arrInsertDataBiayaLain) > 0){
            foreach($arrInsertDataBiayaLain as &$keyInsertDataBiayaLain) {
                $keyInsertDataBiayaLain['IDPENJUALANREKAP'] =   $idPenjualanRekap;
            }
            $mainOperation->insertDataBatchTable('t_penjualanbiayalain', $arrInsertDataBiayaLain);
        }

        if(!empty($arrInsertDataDiskonEvent) && count($arrInsertDataDiskonEvent) > 0){
            foreach($arrInsertDataDiskonEvent as &$keyInsertDataDiskonEvent) {
                $keyInsertDataDiskonEvent['IDPENJUALANREKAP'] =   $idPenjualanRekap;
            }
            $mainOperation->insertDataBatchTable('t_penjualandiskonevent', $arrInsertDataDiskonEvent);
        }

        $dataPrintNota      =   $this->generateDataPrintNotaRetail($idPenjualanRekap);
        $dataPrintNotaArsip =   $this->generateDataPrintNotaRetail($idPenjualanRekap, true);
        return throwResponseOK(
            'Data penjualan telah disimpan',
            [
                'idPenjualanRekap'  =>  hashidEncode($idPenjualanRekap),
                'dataPrintNota'     =>  explode("\n", $dataPrintNota),
                'dataPrintNotaArsip'=>  explode("\n", $dataPrintNotaArsip)
            ]
        );
    }

    private function generateNotaPenjualanNomor()
    {
        return 'NPB-' . strtoupper(bin2hex(random_bytes(2))) . date('ymd');
    }

    private function generateDataPrintNotaRetail($idPenjualanRekap, $isArsip = false)
    {
        $LaporanPenjualanModel  =   new LaporanPenjualanModel();
        $detailNotaPenjualan    =   $LaporanPenjualanModel->getDetailNotaPenjualan($idPenjualanRekap);

        if(!$detailNotaPenjualan) return '';

        $printOut           =   new PrintOut();
        $daftarBarangNota   =   $LaporanPenjualanModel->getDaftarBarangNota($idPenjualanRekap);
        $daftarBiayaLain    =   $LaporanPenjualanModel->getDaftarBiayaLain($idPenjualanRekap);
        $daftarHargaPaket   =   $LaporanPenjualanModel->getDaftarHargaPaket($idPenjualanRekap);
        $daftarDiskonEvent  =   $LaporanPenjualanModel->getDaftarDiskonEventPenjualan($idPenjualanRekap);
        $dataPrintNota      =   $printOut->generatePrintOutNotaRetail($detailNotaPenjualan, $daftarBarangNota, $daftarBiayaLain, $daftarHargaPaket, $daftarDiskonEvent, $isArsip);

        return $dataPrintNota;
    }
}