<?php

namespace App\Controllers\WH\Stok;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\MPDFGenerator;
use App\Models\WH\Stok\StokTokoModel;
use App\Models\ERP\Master\BarangSKUModel;
use App\Models\ERP\Master\TokoModel;
use App\Models\ERP\Stok\PengaturanDiskonModel;
use App\Models\MainOperation;

class StokToko extends ResourceController
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

    public function getDataStokToko()
    {
        $rules  =   [
            'idToko'            =>  ['label' => 'Id Toko', 'rules' => 'required|alpha_numeric'],
            'idBarangKategori'  =>  ['label' => 'Kategori Barang', 'rules' => 'permit_empty|alpha_numeric'],
            'idBarangMerk'      =>  ['label' => 'Merk Barang', 'rules' => 'permit_empty|alpha_numeric'],
            'searchKeyword'     =>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'sortCondition'     =>  ['label' => 'Kondisi Urutan', 'rules' => 'permit_empty|in_list[stok_asc,stok_desc,penjualan_asc,penjualan_desc]']
        ];

        $messages   =   [
            'idToko'  =>  [
                'required'      =>  'Data toko tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data toko tidak valid, silakan periksa kembali'
            ],
            'idBarangKategori'  =>  [
                'alpha_numeric' =>  'Data kategori barang tidak valid, silakan periksa kembali'
            ],
            'idBarangMerk'     =>  [
                'alpha_numeric' =>  'Data merk barang tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $idToko             =   $this->request->getVar('idToko');
        $idToko             =   isset($idToko) && $idToko != "" ? hashidDecode($idToko) : 0;
        $idBarangKategori   =   $this->request->getVar('idBarangKategori');
        $idBarangKategori   =   isset($idBarangKategori) && $idBarangKategori != "" ? hashidDecode($idBarangKategori) : 0;
        $idBarangMerk       =   $this->request->getVar('idBarangMerk');
        $idBarangMerk       =   isset($idBarangMerk) && $idBarangMerk != "" ? hashidDecode($idBarangMerk) : 0;
        $searchKeyword      =   $this->request->getVar('searchKeyword');
        $sortCondition      =   $this->request->getVar('sortCondition');
        $stokTokoModel      =   new StokTokoModel();
        
        if($idToko == '' || $idToko == 0) return throwResponseOK('Data stok & penjualan toko tidak ditemukan', ['listData' =>  []]);
        $dataKelompokHargaGrosir=   $stokTokoModel->getDataKelompokHargaGrosir($idToko);
        $idKelompokHargaGrosir  =   $dataKelompokHargaGrosir['IDKELOMPOKHARGAGROSIR'] ?? 0;
        if($idKelompokHargaGrosir == 0) return throwResponseNotFound('Data kelompok harga grosir untuk toko tidak valid', ['listData' =>  ["idToko"=>$idToko]]);
        $dataKelompokHargaGrosir['IDKELOMPOKHARGAGROSIR'] =   hashidEncode($idKelompokHargaGrosir);

        $dataStokPenjualanToko  =	$stokTokoModel->getDataBarangStokPenjualan($this->idGudang, $idToko, $idKelompokHargaGrosir, $idBarangKategori, $idBarangMerk, $searchKeyword, $sortCondition);
        if(!$dataStokPenjualanToko) return throwResponseNotFound('Data stok & penjualan toko tidak ditemukan', ['listData' =>  []]);
        
        $barangSKUModel         =   new BarangSKUModel();
        $pengaturanDiskonModel  =   new PengaturanDiskonModel();

        foreach($dataStokPenjualanToko as $keyStokPenjualanToko){
            $idBarangSKU                        =   isset($keyStokPenjualanToko->IDBARANGSKU) && $keyStokPenjualanToko->IDBARANGSKU != "" ? $keyStokPenjualanToko->IDBARANGSKU : 0;
            $idBarangSatuan                     =   isset($keyStokPenjualanToko->IDBARANGSATUAN) && $keyStokPenjualanToko->IDBARANGSATUAN != "" ? $keyStokPenjualanToko->IDBARANGSATUAN : 0;
            $dataDiskonBarangGrosir             =   $pengaturanDiskonModel->getDataDiskonBarangSKUGrosir($idBarangSKU, $idBarangSatuan, $idToko);
            $keyStokPenjualanToko->ATRIBUTSKUSTR=   $barangSKUModel->getArrAtributSKU($idBarangSKU);
            
            if($dataDiskonBarangGrosir){
                $hargaAwal          =   isset($keyStokPenjualanToko->HARGAGROSIR) && $keyStokPenjualanToko->HARGAGROSIR != "" ? $keyStokPenjualanToko->HARGAGROSIR : 0;
                $tipeDiskon         =   isset($dataDiskonBarangGrosir['TIPEDISKON']) && $dataDiskonBarangGrosir['TIPEDISKON'] != "" ? $dataDiskonBarangGrosir['TIPEDISKON'] : 1;
                $jumlahDiskon       =   isset($dataDiskonBarangGrosir['JUMLAHDISKON']) && $dataDiskonBarangGrosir['JUMLAHDISKON'] != "" ? $dataDiskonBarangGrosir['JUMLAHDISKON'] : 1;
                $jumlahDiskonStr    =   $tipeDiskon == 1 ? $jumlahDiskon.'% OFF' : '- Rp '.number_format($jumlahDiskon,0,',','.');
                $hargaSetelahDiskon =   $tipeDiskon == 1 ? $hargaAwal - ($hargaAwal * ($jumlahDiskon / 100)) : $hargaAwal - $jumlahDiskon;

                $keyStokPenjualanToko->IDDISKONGROSIR       =   hashidEncode($dataDiskonBarangGrosir['IDDISKONGROSIR']);
                $keyStokPenjualanToko->DISKONDESKRIPSI      =   $dataDiskonBarangGrosir['DESKRIPSI'];
                $keyStokPenjualanToko->DISKONJUMLAH         =   $jumlahDiskonStr;
                $keyStokPenjualanToko->DISKONMINIMALITEM    =   $dataDiskonBarangGrosir['MINIMALITEM'];
                $keyStokPenjualanToko->HARGASETELAHDISKON   =   (string)$hargaSetelahDiskon;
            }
        }

        $dataStokPenjualanToko  =   encodeDatabaseObjectResultKey($dataStokPenjualanToko, ['IDBARANGSKU']);
        return $this->setResponseFormat('json')
                    ->respond([
                        "listData"                  =>  $dataStokPenjualanToko,
                        "dataKelompokHargaGrosir"   =>  $dataKelompokHargaGrosir
                    ]);
    }

    public function uploadImagePembayaran()
    {
        $imagePembayaran            =   $this->request->getFile('imagePembayaran');
        $jenisImage                 =   $this->request->getVar('jenisImage');
        $idTokoNotaMutasiPembayaran =   $this->request->getVar('idTokoNotaMutasiPembayaran');
        $idTokoNotaMutasiPembayaran =   isset($idTokoNotaMutasiPembayaran) && $idTokoNotaMutasiPembayaran != "" ? hashidDecode($idTokoNotaMutasiPembayaran) : 0;
        $allowedExtensions          =   ['png', 'jpg', 'jpeg'];
        if ($imagePembayaran && !$imagePembayaran->hasMoved()) {
            $extension = strtolower($imagePembayaran->getExtension());
            if (!in_array($extension, $allowedExtensions)) {
                return $this->failValidationErrors('Ekstensi file tidak diizinkan. Hanya png, jpg dan jpeg yang diperbolehkan.');
            }
        }
        
        if ($imagePembayaran && $imagePembayaran->isValid() && !$imagePembayaran->hasMoved()) {
            $imagePembayaranName    =   $jenisImage."_".$imagePembayaran->getRandomName();
            $imagePembayaran->move(PATH_STORAGE . 'pembayaran/', $imagePembayaranName);

            if($idTokoNotaMutasiPembayaran != 0){
                $mainOperation          =   new MainOperation();
                $arrUpdatePembayaran    =   [
                    'BUKTIBAYAR'    =>  $imagePembayaranName,
                ];

                $mainOperation->updateDataTable('t_tokonotamutasipembayaran', $arrUpdatePembayaran, ['IDTOKONOTAMUTASIPEMBAYARAN' => $idTokoNotaMutasiPembayaran]);
            }

            return $this->setResponseFormat('json')->respond([
                'message'   =>  'Bukti pembayaran berhasil diunggah',
                'imageUrl'  =>  URL_BUKTI_PEMBAYARAN.$imagePembayaranName,
                'imageName' =>  $imagePembayaranName
            ]);
        }
    }   

    public function checkStokBarangMutasi()
    {
        $rules  =   [
            'idToko'                                        =>  ['label' => 'Id Toko', 'rules' => 'required|alpha_numeric'],
            'arrDataBarangMutasi.*.idTokoNotaMutasiBarang'  =>  ['label' => 'Id Toko Nota Mutasi Barang', 'rules' => 'permit_empty|alpha_numeric'],
            'arrDataBarangMutasi.*.idBarangSKU'             =>  ['label' => 'Id Barang', 'rules' => 'required|alpha_numeric'],
            'arrDataBarangMutasi.*.jumlah'                  =>  ['label' => 'Jumlah Barang', 'rules' => 'required|integer|greater_than_equal_to[0]'],
            'arrDataBarangMutasi.*.harga'                   =>  ['label' => 'Harga Barang', 'rules' => 'required|integer|greater_than[0]']
        ];
        $messages   =   [
            'idToko'  =>  [
                'required'      =>  'Data kiriman tidak valid, silakan periksa kembali [Id Toko]',
                'alpha_numeric' =>  'Data kiriman tidak valid, silakan periksa kembali [Id Toko]'
            ],
            'arrDataBarangMutasi.*.idTokoNotaMutasiBarang'  =>  [
                'alpha_numeric' =>  'Data nota mutasi yang dipilih tidak valid, silakan periksa kembali'
            ],
            'arrDataBarangMutasi.*.idBarangSKU'  =>  [
                'required'      =>  'Barang yang dipilih tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data barang yang dipilih tidak valid, silakan periksa kembali'
            ]  
        ];
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $stokTokoModel          =   new StokTokoModel();
        $idToko                 =   $this->request->getVar('idToko');
        $idToko                 =   $idToko != "" ? hashidDecode($idToko) : 0;
        $dataKelompokHargaGrosir=   $stokTokoModel->getDataKelompokHargaGrosir($idToko);
        $idKelompokHargaGrosir  =   $dataKelompokHargaGrosir['IDKELOMPOKHARGAGROSIR'] ?? 0;
        $arrDataBarangMutasi    =   $this->request->getVar('arrDataBarangMutasi');
        $arrDataBarangMutasi    =   isset($arrDataBarangMutasi) && is_array($arrDataBarangMutasi) ? $arrDataBarangMutasi : [];
        $arrDataStokTidakCukup  =   $arrDataHargaTidakSesuai    =   [];
        
        foreach($arrDataBarangMutasi as $keyDataBarangMutasi){
            $idTokoNotaMutasiBarang =   isset($keyDataBarangMutasi->idTokoNotaMutasiBarang) && $keyDataBarangMutasi->idTokoNotaMutasiBarang != "" ? $keyDataBarangMutasi->idTokoNotaMutasiBarang : 0;
            $idBarangSKU            =   isset($keyDataBarangMutasi->idBarangSKU) && $keyDataBarangMutasi->idBarangSKU != "" ? hashidDecode($keyDataBarangMutasi->idBarangSKU) : 0;
            $idBarangSKUEncode      =   $idBarangSKU != 0 ? hashidEncode($idBarangSKU) : 0;
            $jumlahMutasi           =   isset($keyDataBarangMutasi->jumlah) && $keyDataBarangMutasi->jumlah != "" ? $keyDataBarangMutasi->jumlah : 0;
            $hargaPayload           =   isset($keyDataBarangMutasi->harga) && $keyDataBarangMutasi->harga != "" ? $keyDataBarangMutasi->harga : 0;
            $dataStokBarangGudang   =   $stokTokoModel->getStokBarangGudang($this->idGudang, $idBarangSKU);
            $stokBarangGudang       =   $dataStokBarangGudang->STOK ?? 0;
            
            if($stokBarangGudang < $jumlahMutasi) {
                $arrDataStokTidakCukup[] = [
                    'idTokoNotaMutasiBarang'=>  $idTokoNotaMutasiBarang,
                    'idBarangSKU'           =>  $idBarangSKUEncode,
                    'jumlah'                =>  $jumlahMutasi,
                    'stok'                  =>  $stokBarangGudang
                ];
            }

            $idBarangSatuan         =   $dataStokBarangGudang->IDBARANGSATUAN ?? 0;
            $hargaJualBarangGrosir  =   $stokTokoModel->getHargaJualBarangGrosir($idBarangSKU, $idBarangSatuan, $idKelompokHargaGrosir);

            if($hargaJualBarangGrosir != $hargaPayload){
                $arrDataHargaTidakSesuai[] = [
                    'idTokoNotaMutasiBarang'=>  $idTokoNotaMutasiBarang,
                    'idBarangSKU'           =>  $idBarangSKUEncode
                ];
            }
        }

        if(count($arrDataStokTidakCukup) > 0) return throwResponseNotAcceptable('Stok barang tidak mencukupi untuk permintaan ini', ["dataStokTidakCukup" => $arrDataStokTidakCukup]);
        if(count($arrDataHargaTidakSesuai) > 0) return throwResponseNotAcceptable('Harga barang tidak sesuai untuk permintaan ini', ["dataHargaTidakSesuai" => $arrDataHargaTidakSesuai]);

        return throwResponseOK('Stok barang mencukupi untuk permintaan ini', ["hargaJualBarangGrosir" => $hargaJualBarangGrosir, "hargaPayload" => $hargaPayload]);
    }

    private function getArrRulesPembayaran()
    {
        return [
            'idCaraPelunasan'                   =>  ['label' => 'Id Cara Pelunasan', 'rules' => 'required|alpha_numeric'],
            'arrDataPembayaran.*.pembayaranKe'  =>  ['label' => 'Pembayaran Ke', 'rules' => 'required|integer|greater_than[0]'],
            'arrDataPembayaran.*.keterangan'    =>  ['label' => 'Keterangan Pembayaran', 'rules' => 'required|alpha_numeric_punct'],
            'arrDataPembayaran.*.persentase'    =>  ['label' => 'Persentase Pembayaran', 'rules' => 'required|decimal|greater_than[0]|less_than_equal_to[100]'],
            'arrDataPembayaran.*.nominal'       =>  ['label' => 'Nominal Pembayaran', 'rules' => 'required|integer|greater_than[0]'],
            'arrDataPembayaran.*.jatuhTempo'    =>  ['label' => 'Tanggal Jatuh Tempo', 'rules' => 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'arrDataPembayaran.*.buktiBayar'    =>  ['label' => 'Bukti Bayar', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'arrDataPembayaran.*.status'        =>  ['label' => 'Status Pembayaran', 'rules' => 'required|in_list[0,1]']
        ];
    }

    private function getArrRulesMessagePembayaran()
    {
        return [
            'idCaraPelunasan'   => [
                'required'      => 'Harap pilih cara pelunasan yang valid',
                'alpha_numeric' => 'Data cara pelunasan tidak valid, silakan periksa kembali [Cara Pelunasan]'
            ],
            'arrDataPembayaran.*.jatuhTempo'  =>  [
                'regex_match'   =>  'Format tanggal jatuh tempo tidak valid, silakan periksa kembali'
            ],
            'arrDataPembayaran.*.buktiBayar'  =>  [
                'alpha_numeric_punct'   =>  'Nama file bukti bayar tidak valid, silakan periksa kembali'
            ],
            'arrDataPembayaran.*.status'     =>  [
                'in_list'   =>  'Status pembayaran tidak valid, silakan periksa kembali'
            ]
        ];
    }

    public function saveNotaMutasiStok()
    {
        $rulesPembayaran=   $this->getArrRulesPembayaran();
        $rules          =   array_merge($rulesPembayaran, [
            'idToko'                                =>  ['label' => 'Id Toko', 'rules' => 'required|alpha_numeric'],
            'basisHitungTermin'                     =>  ['label' => 'Basis Hitung Termin', 'rules' => 'required|in_list[N,P]'],
            'arrDataBarangMutasi.*.idBarangSKU'     =>  ['label' => 'Id Barang', 'rules' => 'required|alpha_numeric'],
            'arrDataBarangMutasi.*.idDiskonGrosir'  =>  ['label' => 'Id Diskon Grosir', 'rules' => 'permit_empty|alpha_numeric'],
            'arrDataBarangMutasi.*.jumlah'          =>  ['label' => 'Jumlah Barang Diminta', 'rules' => 'required|integer|greater_than[0]'],
            'keterangan'                            =>  ['label' => 'Keterangan Mutasi', 'rules' => 'permit_empty|alpha_numeric_punct']
        ]);

        $messagesPembayaran =   $this->getArrRulesMessagePembayaran();
        $messages           =   array_merge($messagesPembayaran, [
            'idToko'    => [
                'required'      => 'Harap pilih toko tujuan stok yang valid',
                'alpha_numeric' => 'Data kiriman tidak valid, silakan periksa kembali [Toko]'
            ],
            'basisHitungTermin' => [
                'in_list'       => 'Data basis hitung termin tidak valid, silakan periksa kembali [Basis Hitung Termin]'
            ],
            'arrDataBarangMutasi.*.idBarangSKU'  =>  [
                'required'      =>  'Barang yang dipilih tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data barang yang dipilih tidak valid, silakan periksa kembali'
            ] ,
            'arrDataBarangMutasi.*.idDiskonGrosir'  =>  [
                'alpha_numeric' =>  'Data diskon yang diterapkan pada barang tidak valid, silakan periksa kembali'
            ] 
        ]);

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation          =   new MainOperation();
        $stokTokoModel          =   new StokTokoModel();
        $pengaturanDiskonModel  =   new PengaturanDiskonModel();

        $idToko                 =   $this->request->getVar('idToko');
        $idToko                 =   isset($idToko) && $idToko != "" ? hashidDecode($idToko) : 0;
        $idCaraPelunasan        =   $this->request->getVar('idCaraPelunasan');
        $idCaraPelunasan        =   isset($idCaraPelunasan) && $idCaraPelunasan != "" ? hashidDecode($idCaraPelunasan) : 0;
        $arrDataBarangMutasi    =   $this->request->getVar('arrDataBarangMutasi');
        $arrDataBarangMutasi    =   isset($arrDataBarangMutasi) && is_array($arrDataBarangMutasi) ? $arrDataBarangMutasi : [];
        $arrDataPembayaran      =   $this->request->getVar('arrDataPembayaran');
        $arrDataPembayaran      =   isset($arrDataPembayaran) && is_array($arrDataPembayaran) ? $arrDataPembayaran : [];
        $basisHitungTermin      =   $this->request->getVar('basisHitungTermin');
        $keterangan             =   $this->request->getVar('keterangan');
        $dataKelompokHargaGrosir=   $stokTokoModel->getDataKelompokHargaGrosir($idToko);
        $idKelompokHargaGrosir  =   $dataKelompokHargaGrosir['IDKELOMPOKHARGAGROSIR'] ?? 0;
        $arrDataDiskonTidakValid=   $arrDataStokTidakCukup  =   $arrDataBarangSKU   =   [];
        $totalNominalBarang     =   0;

        foreach($arrDataBarangMutasi as $keyDataBarangMutasi){
            $idBarangSKU            =   isset($keyDataBarangMutasi->idBarangSKU) && $keyDataBarangMutasi->idBarangSKU != "" ? hashidDecode($keyDataBarangMutasi->idBarangSKU) : 0;
            $idBarangSKUEncode      =   $idBarangSKU != 0 ? hashidEncode($idBarangSKU) : 0;
            $idDiskonGrosir         =   isset($keyDataBarangMutasi->idDiskonGrosir) && $keyDataBarangMutasi->idDiskonGrosir != "" ? hashidDecode($keyDataBarangMutasi->idDiskonGrosir) : 0;
            $jumlahMutasi           =   isset($keyDataBarangMutasi->jumlah) && $keyDataBarangMutasi->jumlah != "" ? $keyDataBarangMutasi->jumlah : 0;
            $dataStokBarangGudang   =   $stokTokoModel->getStokBarangGudang($this->idGudang, $idBarangSKU);
            $idBarang               =   $dataStokBarangGudang->IDBARANG ?? 0;
            $idBarangSatuan         =   $dataStokBarangGudang->IDBARANGSATUAN ?? 0;
            $stokBarangGudang       =   $dataStokBarangGudang->STOK ?? 0;
            $hargaJualBarangGrosir  =   $stokTokoModel->getHargaJualBarangGrosir($idBarangSKU, $idBarangSatuan, $idKelompokHargaGrosir);
            $hargaDiskonSebelum     =   $hargaJualBarangGrosir;
            $hargaDiskonNominal     =   0;
            
            if($idDiskonGrosir > 0){
                $detailDiskonGrosir =   $pengaturanDiskonModel->getDetailDiskonBarangGrosir($idDiskonGrosir, $idToko);
                if(!$detailDiskonGrosir) {
                    $arrDataDiskonTidakValid[]  = [
                        'idTokoNotaMutasiBarang'=> 0,
                        'idBarangSKU'           => $idBarangSKUEncode,
                        'message'               => 'Detail diskon grosir tidak valid (sudah kadaluarsa atau tidak aktif)'
                    ];
                }

                $tipeDiskon         =   isset($detailDiskonGrosir['TIPEDISKON']) && $detailDiskonGrosir['TIPEDISKON'] != "" ? $detailDiskonGrosir['TIPEDISKON'] : 1;
                $jumlahDiskon       =   isset($detailDiskonGrosir['JUMLAHDISKON']) && $detailDiskonGrosir['JUMLAHDISKON'] != "" ? $detailDiskonGrosir['JUMLAHDISKON'] : 0;
                $minimalItemDiskon  =   isset($detailDiskonGrosir['MINIMALITEM']) && $detailDiskonGrosir['MINIMALITEM'] != "" ? $detailDiskonGrosir['MINIMALITEM'] : 1;
                
                if($jumlahMutasi < $minimalItemDiskon) {
                    $arrDataDiskonTidakValid[]  = [
                        'idTokoNotaMutasiBarang'=> 0,
                        'idBarangSKU'           => $idBarangSKUEncode,
                        'message'               => 'Diskon grosir yang diterapkan tidak valid (tidak memenuhi syarat minimal <b>'.$minimalItemDiskon.'</b> item)'
                    ];
                }

                $hargaDiskonNominal     =   $tipeDiskon == 1 ? $hargaJualBarangGrosir * ($jumlahDiskon / 100) : $jumlahDiskon;
                $hargaJualBarangGrosir  =   $hargaDiskonSebelum - $hargaDiskonNominal;
            }
            
            if($stokBarangGudang < $jumlahMutasi) {
                $arrDataStokTidakCukup[] = [
                    'idTokoNotaMutasiBarang'=> 0,
                    'idBarangSKU'           => $idBarangSKUEncode,
                    'message'               => "Stok barang gudang [".$stokBarangGudang."] tidak mencukupi untuk permintaan [".$jumlahMutasi."]"
                ];
            } else {
                $arrDataBarangSKU[] = [
                    'idBarangSKU'   => $idBarangSKU,
                    'idBarang'      => $idBarang,
                    'idDiskonGrosir'=> $idDiskonGrosir,
                    'jumlah'        => $jumlahMutasi,
                    'hargaAwal'     => $hargaDiskonSebelum,
                    'hargaDiskon'   => $hargaDiskonNominal,
                    'hargaGrosir'   => $hargaJualBarangGrosir
                ];
            }

            $totalNominalBarang    +=   $hargaJualBarangGrosir * $jumlahMutasi;
        }

        if(count($arrDataDiskonTidakValid) > 0) return throwResponseNotAcceptable('Diskon barang tidak valid untuk permintaan ini', ["dataBarangError" => $arrDataDiskonTidakValid]);
        if(count($arrDataStokTidakCukup) > 0) return throwResponseNotAcceptable('Stok barang tidak mencukupi untuk permintaan ini', ["dataBarangError" => $arrDataStokTidakCukup]);
        
        $arrInsertNotaRekap     =   [
            'IDTOKO'                    =>  $idToko,
            'IDGUDANG'                  =>  $this->idGudang,
            'IDKELOMPOKHARGAGROSIR'     =>  $idKelompokHargaGrosir,
            'NOTAMUTASINOMOR'           =>  $this->generateNotaMutasiTokoNomor(),
            'BASISHITUNGTERMIN'         =>  $basisHitungTermin,
            'TOTALSKU'                  =>  count($arrDataBarangSKU),
            'TOTALNOMINALBARANG'        =>  $totalNominalBarang,
            'PERSENPENYELESAIANINBOUND' =>  0,
            'KETERANGAN'                =>  $keterangan,
            'REQUESTUSER'               =>  $this->userData->name.' (Gudang - '.$this->userData->userLevelName.')',
            'REQUESTTANGGALWAKTU'       =>  $this->currentDateTime,
            'PROSESUSER'                =>  $this->userData->name.' (Gudang - '.$this->userData->userLevelName.')',
            'PROSESTANGGALWAKTU'        =>  $this->currentDateTime,
            'STATUS'                    =>  1
        ];

        $this->checkDataPembayaranMutasiToko($idCaraPelunasan, $totalNominalBarang, $arrDataPembayaran);

        $procInsertNotaRekap    =   $mainOperation->insertDataTable('t_tokonotamutasirekap', $arrInsertNotaRekap);
        if(!$procInsertNotaRekap['status']) return switchMySQLErrorCode($procInsertNotaRekap['errCode']);
        
        $tokoModel              =   new TokoModel();
        $idNotaMutasiRekap      =   $procInsertNotaRekap['insertID'];
        $statusTokoEksternal    =   $tokoModel->select('STATUSEKSTERNAL')->where('IDTOKO', $idToko)->asArray()->first();
        $statusTokoEksternal    =   isset($statusTokoEksternal['STATUSEKSTERNAL']) && $statusTokoEksternal['STATUSEKSTERNAL'] != "" ? $statusTokoEksternal['STATUSEKSTERNAL'] : 1;

        foreach($arrDataBarangSKU as $keyDataBarangSKU){
            $idBarang           =   $keyDataBarangSKU['idBarang'];
            $idBarangSKU        =   $keyDataBarangSKU['idBarangSKU'];
            $idDiskonGrosir     =   $keyDataBarangSKU['idDiskonGrosir'];
            $jumlahRequest      =   $keyDataBarangSKU['jumlah'];
            $hargaAwal          =   $keyDataBarangSKU['hargaAwal'];
            $hargaDiskon        =   $keyDataBarangSKU['hargaDiskon'];
            $hargaGrosir        =   $keyDataBarangSKU['hargaGrosir'];

            if($jumlahRequest > 0) {
                $arrInsertNotaMutasiBarang  =   [
                    'IDTOKONOTAMUTASIREKAP' =>  $idNotaMutasiRekap,
                    'IDBARANG'              =>  $idBarang,
                    'IDBARANGSKU'           =>  $idBarangSKU,
                    'IDDISKONGROSIR'        =>  $idDiskonGrosir,
                    'JUMLAH'                =>  $jumlahRequest,
                    'HARGAAWAL'             =>  $hargaAwal,
                    'HARGADISKON'           =>  $hargaDiskon,
                    'HARGAGROSIR'           =>  $hargaGrosir
                ];
                $procInsertNotaMutasiBarang =   $mainOperation->insertDataTable('t_tokonotamutasibarang', $arrInsertNotaMutasiBarang);

                if($procInsertNotaMutasiBarang['status'] && $statusTokoEksternal == 0){
                    $idTokoNotaMutasiBarang     =   $procInsertNotaMutasiBarang['insertID'];
                    $arrInsertNotaMutasiInbound =   [
                        'IDTOKONOTAMUTASIBARANG'=>  $idTokoNotaMutasiBarang,
                        'JUMLAHINBOUND'         =>  0,
                        'PROSESKE'              =>  0
                    ];
                    $mainOperation->insertDataTable('t_tokonotamutasiinbound', $arrInsertNotaMutasiInbound);
                }
            }
        }
        
        $this->saveDetailPembayaranMutasiToko($idNotaMutasiRekap, $idCaraPelunasan, $arrDataPembayaran, $mainOperation);

        return throwResponseOK('Permintaan stok berhasil disimpan', [
            'idNotaMutasiRekap' => hashidEncode($idNotaMutasiRekap)
        ]);
    }

    private function generateNotaMutasiTokoNomor()
    {
        return 'NMT-' . strtoupper(bin2hex(random_bytes(2))) . date('ymd');
    }

    public function getDataNotaPengajuanStok()
    {
        $stokTokoModel  =   new StokTokoModel();
        $listData       =   $stokTokoModel->getDataNotaPengajuanStokAktif($this->idGudang);

        if(!$listData) return throwResponseNotFound('Tidak ada nota pengajuan stok yang ditemukan', ['listData' =>  []]);

        $listData       =   encodeDatabaseObjectResultKey($listData, ['IDTOKONOTAMUTASIREKAP']);
        return $this->setResponseFormat('json')->respond(["listData" =>  $listData]);
    }

    public function getDetailNotaPengajuanStok()
    {
        $rules      =   ['idTokoNotaMutasiRekap' =>  ['label' => 'Id Nota Mutasi Rekap', 'rules' => 'required|alpha_numeric']];
        $messages   =   [
            'idTokoNotaMutasiRekap'  => [
                'required'      => 'Data kiriman tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak valid, silakan periksa kembali'
            ]
        ];
        
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $stokTokoModel              =   new StokTokoModel();
        $mainOperation              =   new MainOperation();
        $idTokoNotaMutasiRekap      =   hashidDecode($this->request->getVar('idTokoNotaMutasiRekap'));
        $detailTokoNotaMutasiRekap  =	$stokTokoModel->getDetailTokoNotaMutasiRekap($idTokoNotaMutasiRekap);

        if(!$detailTokoNotaMutasiRekap) return throwResponseNotFound('Detail nota mutasi toko tidak ditemukan');

        $idToko                     =   isset($detailTokoNotaMutasiRekap['IDTOKO']) && $detailTokoNotaMutasiRekap['IDTOKO'] != "" ? $detailTokoNotaMutasiRekap['IDTOKO'] : 0;
        $detailToko                 =   $mainOperation->getDetailToko($idToko);
        $idGudangToko               =   $detailToko['IDGUDANG'] ?? 0;
        $idKelompokHargaGrosir      =   $detailToko['IDKELOMPOKHARGAGROSIR'] ?? 0;
        $dataBarangSKU              =	$stokTokoModel->getDataBarangSKUNotaPengajuanStok($idTokoNotaMutasiRekap, $idKelompokHargaGrosir, $idGudangToko);
        $detailTokoNotaMutasiRekap['IDTOKO']    =   hashidEncode($detailTokoNotaMutasiRekap['IDTOKO']);
        $detailTokoNotaMutasiRekap['IDGUDANG']  =   hashidEncode($detailTokoNotaMutasiRekap['IDGUDANG']);
        
        if($dataBarangSKU && count($dataBarangSKU) > 0){
            $barangSKUModel         =   new BarangSKUModel();
            $pengaturanDiskonModel  =   new PengaturanDiskonModel();

            foreach($dataBarangSKU as $keyBarangSKU){
                $idBarangSKU                =   isset($keyBarangSKU->IDBARANGSKU) && $keyBarangSKU->IDBARANGSKU != "" ? $keyBarangSKU->IDBARANGSKU : 0;
                $idBarangSatuan             =   isset($keyBarangSKU->IDBARANGSATUAN) && $keyBarangSKU->IDBARANGSATUAN != "" ? $keyBarangSKU->IDBARANGSATUAN : 0;
                $dataDiskonBarangGrosir     =   $pengaturanDiskonModel->getDataDiskonBarangSKUGrosir($idBarangSKU, $idBarangSatuan, $idToko);
                $keyBarangSKU->ATRIBUTSKUSTR=   $barangSKUModel->getArrAtributSKU($idBarangSKU);
            
                if($dataDiskonBarangGrosir){
                    $hargaAwal          =   isset($keyBarangSKU->HARGAGROSIR) && $keyBarangSKU->HARGAGROSIR != "" ? $keyBarangSKU->HARGAGROSIR : 0;
                    $tipeDiskon         =   isset($dataDiskonBarangGrosir['TIPEDISKON']) && $dataDiskonBarangGrosir['TIPEDISKON'] != "" ? $dataDiskonBarangGrosir['TIPEDISKON'] : 1;
                    $jumlahDiskon       =   isset($dataDiskonBarangGrosir['JUMLAHDISKON']) && $dataDiskonBarangGrosir['JUMLAHDISKON'] != "" ? $dataDiskonBarangGrosir['JUMLAHDISKON'] : 1;
                    $jumlahDiskonStr    =   $tipeDiskon == 1 ? $jumlahDiskon.'% OFF' : '- Rp '.number_format($jumlahDiskon,0,',','.');
                    $hargaSetelahDiskon =   $tipeDiskon == 1 ? $hargaAwal - ($hargaAwal * ($jumlahDiskon / 100)) : $hargaAwal - $jumlahDiskon;

                    $keyBarangSKU->IDDISKONGROSIR       =   hashidEncode($dataDiskonBarangGrosir['IDDISKONGROSIR']);
                    $keyBarangSKU->DISKONDESKRIPSI      =   $dataDiskonBarangGrosir['DESKRIPSI'];
                    $keyBarangSKU->DISKONJUMLAH         =   $jumlahDiskonStr;
                    $keyBarangSKU->DISKONMINIMALITEM    =   $dataDiskonBarangGrosir['MINIMALITEM'];
                    $keyBarangSKU->HARGASETELAHDISKON   =   (string)$hargaSetelahDiskon;
                }

                unset($keyBarangSKU->IDBARANGSATUAN);
            }
        }

        $dataBarangSKU  =   encodeDatabaseObjectResultKey($dataBarangSKU, ['IDTOKONOTAMUTASIBARANG', 'IDBARANGSKU']);
        return $this->setResponseFormat('json')
                    ->respond([
                        "detailTokoNotaMutasiRekap" =>  $detailTokoNotaMutasiRekap,
                        "dataBarangSKU"             =>  $dataBarangSKU
                    ]);
    }

    public function downloadPDFCheckListStok()
    {
        $rules      =   [
            'idTokoNotaMutasiRekap'                  =>  ['label' => 'Id Nota Mutasi Rekap', 'rules' => 'required|alpha_numeric'],
            'arrPersetujuan.*.idTokoNotaMutasiBarang'=>  ['label' => 'Id Toko Nota Mutasi Barang', 'rules' => 'required|alpha_numeric'],
            'arrPersetujuan.*.jumlah'                =>  ['label' => 'Jumlah Disetujui', 'rules' => 'required|integer|greater_than_equal_to[0]'],
        ];
        $messages   =   [
            'idTokoNotaMutasiRekap'  => [
                'required'      => 'Data kiriman tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak valid, silakan periksa kembali'
            ]
        ];
        
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mPDFGenerator      =   new MPDFGenerator();
        $stokTokoModel      =   new StokTokoModel();
        $mainOperation      =   new MainOperation();
        
        $idTokoNotaMutasiRekap  =   hashidDecode($this->request->getVar('idTokoNotaMutasiRekap'));
        $arrPersetujuan         =   $this->request->getVar('arrPersetujuan');
        $detailNotaMutasi       =	$stokTokoModel->getDetailTokoNotaMutasiRekap($idTokoNotaMutasiRekap);
        $idToko                 =   isset($detailNotaMutasi['IDTOKO']) && $detailNotaMutasi['IDTOKO'] != "" ? $detailNotaMutasi['IDTOKO'] : 0;
        $detailToko             =   $mainOperation->getDetailToko($idToko);
        $idGudangToko           =   $detailToko['IDGUDANG'] ?? 0;
        $idKelompokHargaGrosir  =   $detailToko['IDKELOMPOKHARGAGROSIR'] ?? 0;
        $dataBarangSKU          =	$stokTokoModel->getDataBarangSKUNotaPengajuanStok($idTokoNotaMutasiRekap, $idKelompokHargaGrosir, $idGudangToko);

        foreach($arrPersetujuan as $keyPersetujuan){
            $keyPersetujuan->idTokoNotaMutasiBarang =   hashidDecode($keyPersetujuan->idTokoNotaMutasiBarang);
        }

        foreach($dataBarangSKU as $keyBarangSKU){
            $idTokoNotaMutasiBarang                 =   isset($keyBarangSKU->IDTOKONOTAMUTASIBARANG) && $keyBarangSKU->IDTOKONOTAMUTASIBARANG != "" ? $keyBarangSKU->IDTOKONOTAMUTASIBARANG : 0;
            $indexPersetujuan                       =   array_search($idTokoNotaMutasiBarang, array_column($arrPersetujuan, 'idTokoNotaMutasiBarang'));
            $keyBarangSKU->JUMLAHPERSETUJUANDRAFT   =   $indexPersetujuan !== false ? $arrPersetujuan[$indexPersetujuan]->jumlah : 0;
        }

        $dataDokumen            =   [
            'cssStyle'          =>  view('pdf/CssStyle'),
            'infoTableStyle'    =>  view('pdf/style/InfoTableStyle'),
            'detailNotaMutasi'  =>  $detailNotaMutasi,
            'dataBarangSKU'     =>  $dataBarangSKU
        ];

        $html       =   view('pdf/CheckListStokGudang', $dataDokumen);
        $filename   =   'CHECK_LIST_STOK_GUDANG_' . $detailNotaMutasi['NOTAMUTASINOMOR'] . '_' . date('YmdHis') . '.pdf';
        $pdfContent =   $mPDFGenerator->generatePDFFileOutput($html, $filename);

        return $this->response
                    ->setBody($pdfContent) 
                    ->setContentType('application/pdf') 
                    ->setHeader('Access-Control-Expose-Headers', 'Content-Disposition') 
                    ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                    ->setHeader('Content-Length', strlen($pdfContent));
    }

    public function saveProsesNotaPengajuanStok()
    {
        $rulesPembayaran=   $this->getArrRulesPembayaran();
        $rules          =   array_merge($rulesPembayaran, [
            'idTokoNotaMutasiRekap'                     =>  ['label' => 'Id Nota Mutasi Rekap', 'rules' => 'required|alpha_numeric'],
            'basisHitungTermin'                         =>  ['label' => 'Basis Hitung Termin', 'rules' => 'required|in_list[N,P]'],
            'arrDataBarangNota.*.idTokoNotaMutasiBarang'=>  ['label' => 'Id Toko Nota Mutasi Barang ', 'rules' => 'required|alpha_numeric'],
            'arrDataBarangNota.*.idBarangSKU'           =>  ['label' => 'Id Barang SKU', 'rules' => 'required|alpha_numeric'],
            'arrDataBarangNota.*.idDiskonGrosir'        =>  ['label' => 'Id Diskon Grosir', 'rules' => 'permit_empty|alpha_numeric'],
            'arrDataBarangNota.*.jumlahDisetujui'       =>  ['label' => 'Jumlah Barang Disetujui', 'rules' => 'required|integer'],
            'keterangan'                                =>  ['label' => 'Keterangan', 'rules' => 'permit_empty|alpha_numeric_punct']
        ]);

        $messagesPembayaran =   $this->getArrRulesMessagePembayaran();
        $messages           =   array_merge($messagesPembayaran, [
            'idTokoNotaMutasiRekap'  => [
                'required'      => 'Data kiriman tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak valid, silakan periksa kembali'
            ],
            'basisHitungTermin' => [
                'in_list'       => 'Data basis hitung termin tidak valid, silakan periksa kembali [Basis Hitung Termin]'
            ],
            'arrDataBarangNota.*.idTokoNotaMutasiBarang'  =>  [
                'required'      =>  'Data barang yang dikirim tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data barang yang dikirim tidak valid, silakan periksa kembali'
            ],
            'arrDataBarangNota.*.idBarangSKU'   => [
                'required'      => 'Data kiriman tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak valid, silakan periksa kembali'
            ],
            'arrDataBarangNota.*.idDiskonGrosir'   => [
                'alpha_numeric' => 'Data kiriman tidak valid, silakan periksa kembali'
            ]
        ]);

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation          =   new MainOperation();
        $stokTokoModel          =   new StokTokoModel();
        $pengaturanDiskonModel  =   new PengaturanDiskonModel();

        $idTokoNotaMutasiRekap      =   hashidDecode($this->request->getVar('idTokoNotaMutasiRekap'));
        $idCaraPelunasan            =   $this->request->getVar('idCaraPelunasan');
        $idCaraPelunasan            =   isset($idCaraPelunasan) && $idCaraPelunasan != "" ? hashidDecode($idCaraPelunasan) : 0;
        $arrDataBarangNota          =   $this->request->getVar('arrDataBarangNota');
        $arrDataBarangNota          =   isset($arrDataBarangNota) && is_array($arrDataBarangNota) ? $arrDataBarangNota : [];
        $arrDataPembayaran          =   $this->request->getVar('arrDataPembayaran');
        $arrDataPembayaran          =   isset($arrDataPembayaran) && is_array($arrDataPembayaran) ? $arrDataPembayaran : [];
        $basisHitungTermin          =   $this->request->getVar('basisHitungTermin');
        $keterangan                 =   $this->request->getVar('keterangan');
        $idGudang                   =   $this->idGudang;
        $arrDataStokTidakCukup      =   $arrDataDiskonTidakValid    =   $arrDataBarangNotaSKU   =   [];
        $totalStokDisetujui         =   $totalNominalBarang =   0;
        $detailTokoNotaMutasiRekap  =	$stokTokoModel->getDetailTokoNotaMutasiRekap($idTokoNotaMutasiRekap);
        
        if(!$detailTokoNotaMutasiRekap) return throwResponseNotFound('Detail nota mutasi toko tidak ditemukan');
        
        $idToko                 =   isset($detailTokoNotaMutasiRekap['IDTOKO']) && $detailTokoNotaMutasiRekap['IDTOKO'] != "" ? $detailTokoNotaMutasiRekap['IDTOKO'] : 0;
        $dataKelompokHargaGrosir=   $stokTokoModel->getDataKelompokHargaGrosir($idToko);
        $idKelompokHargaGrosir  =   $dataKelompokHargaGrosir['IDKELOMPOKHARGAGROSIR'] ?? 0;
        
        foreach($arrDataBarangNota as $keyDataBarangNota){
            $idTokoNotaMutasiBarangEncode   =   isset($keyDataBarangNota->idTokoNotaMutasiBarang) && $keyDataBarangNota->idTokoNotaMutasiBarang != "" ? $keyDataBarangNota->idTokoNotaMutasiBarang : 0;
            $idTokoNotaMutasiBarang         =   $idTokoNotaMutasiBarangEncode != 0 ? hashidDecode($idTokoNotaMutasiBarangEncode) : 0;
            $idBarangSKUEncode              =   isset($keyDataBarangNota->idBarangSKU) && $keyDataBarangNota->idBarangSKU != "" ? $keyDataBarangNota->idBarangSKU : 0;
            $idBarangSKU                    =   $idBarangSKUEncode != 0 ? hashidDecode($idBarangSKUEncode) : 0;
            $idDiskonGrosir                 =   isset($keyDataBarangNota->idDiskonGrosir) && $keyDataBarangNota->idDiskonGrosir != "" ? hashidDecode($keyDataBarangNota->idDiskonGrosir) : 0;
            $jumlahDisetujui                =   isset($keyDataBarangNota->jumlahDisetujui) && $keyDataBarangNota->jumlahDisetujui != "" ? $keyDataBarangNota->jumlahDisetujui : 0;
            $dataStokBarangGudang           =   $stokTokoModel->getStokBarangGudang($idGudang, $idBarangSKU);
            $idBarangSatuan                 =   $dataStokBarangGudang->IDBARANGSATUAN ?? 0;
            $stokBarangGudang               =   $dataStokBarangGudang->STOK ?? 0;
            $hargaJualBarangGrosir          =   $stokTokoModel->getHargaJualBarangGrosir($idBarangSKU, $idBarangSatuan, $idKelompokHargaGrosir);
            $hargaDiskonSebelum             =   $hargaJualBarangGrosir;
            $hargaDiskonNominal             =   0;

            if($idDiskonGrosir > 0){
                $detailDiskonGrosir =   $pengaturanDiskonModel->getDetailDiskonBarangGrosir($idDiskonGrosir, $idToko);
                if(!$detailDiskonGrosir) {
                    $arrDataDiskonTidakValid[]  = [
                        'idTokoNotaMutasiBarang'=> $idTokoNotaMutasiBarangEncode,
                        'idBarangSKU'           => $idBarangSKUEncode,
                        'message'               => 'Detail diskon grosir tidak valid (sudah kadaluarsa atau tidak aktif)'
                    ];
                }

                $tipeDiskon         =   isset($detailDiskonGrosir['TIPEDISKON']) && $detailDiskonGrosir['TIPEDISKON'] != "" ? $detailDiskonGrosir['TIPEDISKON'] : 1;
                $jumlahDiskon       =   isset($detailDiskonGrosir['JUMLAHDISKON']) && $detailDiskonGrosir['JUMLAHDISKON'] != "" ? $detailDiskonGrosir['JUMLAHDISKON'] : 0;
                $minimalItemDiskon  =   isset($detailDiskonGrosir['MINIMALITEM']) && $detailDiskonGrosir['MINIMALITEM'] != "" ? $detailDiskonGrosir['MINIMALITEM'] : 1;
                
                if($jumlahDisetujui < $minimalItemDiskon) {
                    $arrDataDiskonTidakValid[]  = [
                        'idTokoNotaMutasiBarang'=> $idTokoNotaMutasiBarangEncode,
                        'idBarangSKU'           => $idBarangSKUEncode,
                        'message'               => 'Diskon grosir yang diterapkan tidak valid (tidak memenuhi syarat minimal <b>'.$minimalItemDiskon.'</b> item)'
                    ];
                }

                $hargaDiskonNominal     =   $tipeDiskon == 1 ? $hargaJualBarangGrosir * ($jumlahDiskon / 100) : $jumlahDiskon;
                $hargaJualBarangGrosir  =   $hargaDiskonSebelum - $hargaDiskonNominal;
            }

            if($stokBarangGudang < $jumlahDisetujui) {
                $arrDataStokTidakCukup[] = [
                    'idTokoNotaMutasiBarang'=>  $idTokoNotaMutasiBarangEncode,
                    'jumlahDisetujui'       =>  intval($jumlahDisetujui * 1),
                    'stok'                  =>  intval($stokBarangGudang * 1)
                ];
            } else {
                $arrDataBarangNotaSKU[] = [
                    'idTokoNotaMutasiBarang'=>  $idTokoNotaMutasiBarang,
                    'idDiskonGrosir'        =>  $idDiskonGrosir,
                    'jumlahDisetujui'       =>  intval($jumlahDisetujui * 1),
                    'hargaAwal'             =>  $hargaDiskonSebelum,
                    'hargaDiskon'           =>  $hargaDiskonNominal,
                    'hargaGrosir'           =>  $hargaJualBarangGrosir
                ];

                $totalStokDisetujui +=  $jumlahDisetujui;
            }

            $totalNominalBarang    +=   $hargaJualBarangGrosir * $jumlahDisetujui;
        }

        if(count($arrDataDiskonTidakValid) > 0) return throwResponseNotAcceptable('Diskon barang tidak valid untuk permintaan ini', ["dataBarangError" => $arrDataDiskonTidakValid]);
        if(count($arrDataStokTidakCukup) > 0) return throwResponseNotAcceptable('Stok barang tidak mencukupi untuk permintaan ini', ['dataStokTidakCukup' => $arrDataStokTidakCukup]);
        $this->checkDataPembayaranMutasiToko($idCaraPelunasan, $totalNominalBarang, $arrDataPembayaran);

        $statusNotaMutasiRekap  =   $totalStokDisetujui > 0 ? 1 : -1;
        $arrUpdateNotaRekap     =   [
            'BASISHITUNGTERMIN' =>  $basisHitungTermin,
            'TOTALNOMINALBARANG'=>  $totalNominalBarang,
            'PROSESUSER'        =>  $this->userData->name.' (Gudang - '.$this->userData->userLevelName.')',
            'PROSESKETERANGAN'  =>  $keterangan,
            'PROSESTANGGALWAKTU'=>  $this->currentDateTime,
            'STATUS'            =>  $statusNotaMutasiRekap
        ];

        $procUpdateNotaRekap    =   $mainOperation->updateDataTable('t_tokonotamutasirekap', $arrUpdateNotaRekap, ['IDTOKONOTAMUTASIREKAP' => $idTokoNotaMutasiRekap]);
        if(!$procUpdateNotaRekap['status']) return switchMySQLErrorCode($procUpdateNotaRekap['errCode']);
        
        $tokoModel              =   new TokoModel();
        $statusTokoEksternal    =   $tokoModel->select('STATUSEKSTERNAL')->where('IDTOKO', $idToko)->asArray()->first();
        $statusTokoEksternal    =   isset($statusTokoEksternal['STATUSEKSTERNAL']) && $statusTokoEksternal['STATUSEKSTERNAL'] != "" ? $statusTokoEksternal['STATUSEKSTERNAL'] : 1;

        foreach($arrDataBarangNotaSKU as $keyDataBarangNotaSKU){
            $idTokoNotaMutasiBarang =   $keyDataBarangNotaSKU['idTokoNotaMutasiBarang'];
            $idDiskonGrosir         =   $keyDataBarangNotaSKU['idDiskonGrosir'];
            $jumlahDisetujui        =   $keyDataBarangNotaSKU['jumlahDisetujui'];
            $hargaAwal              =   $keyDataBarangNotaSKU['hargaAwal'];
            $hargaDiskon            =   $keyDataBarangNotaSKU['hargaDiskon'];
            $hargaGrosir            =   $keyDataBarangNotaSKU['hargaGrosir'];

            if($jumlahDisetujui > 0) {
                $arrUpdateNotaMutasiBarang  =   [
                    'IDDISKONGROSIR'=>  $idDiskonGrosir,
                    'JUMLAH'        =>  $jumlahDisetujui,
                    'HARGAAWAL'     =>  $hargaAwal,
                    'HARGADISKON'   =>  $hargaDiskon,
                    'HARGAGROSIR'   =>  $hargaGrosir
                ];
                $mainOperation->updateDataTable('t_tokonotamutasibarang', $arrUpdateNotaMutasiBarang, ['IDTOKONOTAMUTASIBARANG' => $idTokoNotaMutasiBarang]);

                if($statusTokoEksternal == 0){
                    $arrInsertNotaMutasiInbound =   [
                        'IDTOKONOTAMUTASIBARANG'=>  $idTokoNotaMutasiBarang,
                        'JUMLAHINBOUND'         =>  0,
                        'PROSESKE'              =>  0
                    ];
                    $mainOperation->insertDataTable('t_tokonotamutasiinbound', $arrInsertNotaMutasiInbound);
                }
            }
        }

        $this->saveDetailPembayaranMutasiToko($idTokoNotaMutasiRekap, $idCaraPelunasan, $arrDataPembayaran, $mainOperation);
        $filePdfFakturPenjualan =   $this->generatePDFFakturPenjualan($idTokoNotaMutasiRekap);
        $mainOperation->updateDataTable('t_tokonotamutasirekap', ['FILEPDFFAKTURPENJUALAN' => $filePdfFakturPenjualan], ['IDTOKONOTAMUTASIREKAP' => $idTokoNotaMutasiRekap]);

        return throwResponseOK('Nota permintaan stok berhasil diproses', [
            'idTokoNotaMutasiRekap' => hashidEncode($idTokoNotaMutasiRekap)
        ]);
    }

    public function getDataHistoryNotaStok()
    {
        $rules  =   [
            'idToko'        =>  ['label' => 'Id Toko', 'rules' => 'permit_empty|alpha_numeric'],
            'searchKeyword' =>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct']
        ];

        $messages   =   [
            'idToko'  =>  [
                'alpha_numeric' =>  'Data toko tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation  =   new MainOperation();
        $stokTokoModel  =   new StokTokoModel();
        $idGudang       =   $this->idGudang;
        $idToko         =   $this->request->getVar('idToko');
        $idToko         =   isset($idToko) && $idToko != "" ? hashidDecode($idToko) : 0;
        $searchKeyword  =   $this->request->getVar('searchKeyword');
        $dataPerPage    =   $this->request->getVar('dataPerPage');
        $pageNumber     =   $this->request->getVar('pageNumber');

        $baseData       =	$stokTokoModel->getDataNotaStokHistori($idGudang, $idToko, $searchKeyword);
        $totalNumberData=   $baseData->countAllResults(false);
        $pageProperty   =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0) {
            $dataNotaStokHistory=   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $dataNotaStokHistory=   encodeDatabaseObjectResultKey($dataNotaStokHistory, ['IDTOKONOTAMUTASIREKAP']);
            
            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $dataNotaStokHistory,
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn     =   [
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

        $stokTokoModel              =   new StokTokoModel();
        $idTokoNotaMutasiRekap      =   hashidDecode($this->request->getVar('idTokoNotaMutasiRekap'));
        $detailTokoNotaMutasiRekap  =	$stokTokoModel->getDetailTokoNotaMutasiRekapHistori($idTokoNotaMutasiRekap);

        if(!$detailTokoNotaMutasiRekap) return throwResponseNotFound('Detail nota pembelian yang ditemukan');

        $dataBarangSKU  =	$stokTokoModel->getDataBarangSKUNotaPengajuanStokHistori($idTokoNotaMutasiRekap);
        $dataPembayaran =	$stokTokoModel->getDataPembayaranNotaMutasiToko($idTokoNotaMutasiRekap);

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
                        "urlPDFFakturPenjualan"     =>  'https://roketo.id/public/warehouse/suratJalan/generatePDFSuratJalan',
                        "baseURLBuktiPembayaran"    =>  URL_BUKTI_PEMBAYARAN
                    ]);
    }

    private function checkDataPembayaranMutasiToko($idCaraPelunasan, $totalNominalBarang, $arrDataPembayaran)
    {
        $jumlahPembayaran       =   count($arrDataPembayaran);
        $nomorPembayaran1       =   isset($arrDataPembayaran[0]->pembayaranKe) && $arrDataPembayaran[0]->pembayaranKe != "" ? $arrDataPembayaran[0]->pembayaranKe : null;
        $persentasePembayaran1  =   isset($arrDataPembayaran[0]->persentase) && $arrDataPembayaran[0]->persentase != "" ? $arrDataPembayaran[0]->persentase : null;
        $nominalPembayaran1     =   isset($arrDataPembayaran[0]->nominal) && $arrDataPembayaran[0]->nominal != "" ? $arrDataPembayaran[0]->nominal : null;
        $jatuhTempoPembayaran1  =   isset($arrDataPembayaran[0]->jatuhTempo) && $arrDataPembayaran[0]->jatuhTempo != "" ? $arrDataPembayaran[0]->jatuhTempo : null;
        $statusPembayaran1      =   isset($arrDataPembayaran[0]->status) && $arrDataPembayaran[0]->status != "" ? $arrDataPembayaran[0]->status : null;
        
        switch($idCaraPelunasan){
            // LANGSUNG LUNAS
            case 1:
                if($jumlahPembayaran != 1) return throwResponseNotAcceptable('Untuk cara pelunasan langsung lunas, harus ada 1 data pembayaran dengan status lunas');
                if($nomorPembayaran1 != 1) return throwResponseNotAcceptable('Untuk cara pelunasan langsung lunas, pembayaran ke harus 1');
                if($persentasePembayaran1 != 100) return throwResponseNotAcceptable('Untuk cara pelunasan langsung lunas, persentase pembayaran harus 100%');
                if($nominalPembayaran1 != $totalNominalBarang) return throwResponseNotAcceptable('Untuk cara pelunasan langsung lunas, nominal pembayaran harus sama dengan total nominal barang');
                if($jatuhTempoPembayaran1 != date('Y-m-d')) return throwResponseNotAcceptable('Untuk cara pelunasan langsung lunas, tanggal jatuh tempo harus diisi dengan tanggal hari ini');
                if($statusPembayaran1 != 1) return throwResponseNotAcceptable('Untuk cara pelunasan langsung lunas, status pembayaran harus lunas');
                break;
            // TEMPO
            case 2:
                if($jumlahPembayaran != 1) return throwResponseNotAcceptable('Untuk cara pelunasan tempo, harus ada 1 data pembayaran');
                if($nomorPembayaran1 != 1) return throwResponseNotAcceptable('Untuk cara pelunasan tempo, pembayaran ke harus 1');
                if($persentasePembayaran1 != 100) return throwResponseNotAcceptable('Untuk cara pelunasan tempo, persentase pembayaran harus 100%');
                if($nominalPembayaran1 != $totalNominalBarang) return throwResponseNotAcceptable('Untuk cara pelunasan tempo, nominal pembayaran harus sama dengan total nominal barang');
                if(strtotime($jatuhTempoPembayaran1) <= strtotime(date('Y-m-d'))) return throwResponseNotAcceptable('Untuk cara pelunasan tempo, tanggal jatuh tempo tidak boleh diisi dengan tanggal kurang dari/sama dengan hari ini');
                if($statusPembayaran1 != 0) return throwResponseNotAcceptable('Untuk cara pelunasan tempo, status pembayaran tidak boleh lunas');
                break;
            // TERMIN
            case 3:
                $totalPersentase    = array_reduce($arrDataPembayaran, function ($sum, $item) {
                    return $sum + $item->persentase;
                }, 0);

                $totalNominalPembayaran  = array_reduce($arrDataPembayaran, function ($sum, $item) {
                    return $sum + $item->nominal;
                }, 0);

                if($jumlahPembayaran == 1) return throwResponseNotAcceptable('Untuk cara pelunasan termin, harus ada lebih dari 1 data pembayaran');
                if($totalPersentase != 100) return throwResponseNotAcceptable('Total persentase pembayaran termin harus 100%');
                if($totalNominalPembayaran != $totalNominalBarang) return throwResponseNotAcceptable('Total nominal pembayaran termin harus sama dengan total nominal barang');
                $jatuhTempoPembayaranMin    =   strtotime(date('Y-m-d'));

                foreach($arrDataPembayaran as $keyDataPembayaran){
                    $jatuhTempoPembayaran   =   isset($keyDataPembayaran->jatuhTempo) && $keyDataPembayaran->jatuhTempo != "" ? $keyDataPembayaran->jatuhTempo : null;
                    $jatuhTempoPembayaran   =   strtotime($jatuhTempoPembayaran);
                    if($jatuhTempoPembayaran < $jatuhTempoPembayaranMin) return throwResponseNotAcceptable('Jatuh tempo pembayaran termin ke-'.$keyDataPembayaran->pembayaranKe.' tidak valid');
                    $jatuhTempoPembayaranMin=   $jatuhTempoPembayaran;
                }
                break;
            default:
                return true;
        }

        return true;
    }

    private function saveDetailPembayaranMutasiToko($idNotaMutasiRekap, $idCaraPelunasan, $arrDataPembayaran, $mainOperation)
    {
        if(count($arrDataPembayaran) > 0){
            foreach($arrDataPembayaran as $keyDataPembayaran){
                $pembayaranKe   =   isset($keyDataPembayaran->pembayaranKe) && $keyDataPembayaran->pembayaranKe != "" ? $keyDataPembayaran->pembayaranKe : 0;
                $keterangan     =   isset($keyDataPembayaran->keterangan) && $keyDataPembayaran->keterangan != "" ? $keyDataPembayaran->keterangan : null;
                $persentase     =   isset($keyDataPembayaran->persentase) && $keyDataPembayaran->persentase != "" ? $keyDataPembayaran->persentase : 0;
                $nominal        =   isset($keyDataPembayaran->nominal) && $keyDataPembayaran->nominal != "" ? $keyDataPembayaran->nominal : 0;
                $jatuhTempo     =   isset($keyDataPembayaran->jatuhTempo) && $keyDataPembayaran->jatuhTempo != "" ? $keyDataPembayaran->jatuhTempo : null;
                $buktiBayar     =   isset($keyDataPembayaran->buktiBayar) && $keyDataPembayaran->buktiBayar != "" ? $keyDataPembayaran->buktiBayar : '';
                $status         =   isset($keyDataPembayaran->status) && $keyDataPembayaran->status != "" ? $keyDataPembayaran->status : 0;

                $arrInsertNotaMutasiPembayaran    =   [
                    'IDTOKONOTAMUTASIREKAP'     =>  $idNotaMutasiRekap,
                    'IDCARAPELUNASAN'           =>  $idCaraPelunasan,
                    'NOTAMUTASIPEMBAYARANNOMOR' =>  $this->generateNotaMutasiPembayaranNomor(),
                    'PEMBAYARANKE'              =>  $pembayaranKe,
                    'KETERANGAN'                =>  $keterangan,
                    'PERSENTASE'                =>  $persentase,
                    'NOMINAL'                   =>  $nominal,
                    'JATUHTEMPO'                =>  $jatuhTempo,
                    'BUKTIBAYAR'                =>  $buktiBayar,
                    'STATUS'                    =>  $status,
                    'INPUTUSER'                 =>  $this->userData->name.' (Gudang - '.$this->userData->userLevelName.')',
                    'INPUTTANGGALWAKTU'         =>  $this->currentDateTime
                ];
    
                $mainOperation->insertDataTable('t_tokonotamutasipembayaran', $arrInsertNotaMutasiPembayaran);
            }
        }

        return true;
    }

    private function generateNotaMutasiPembayaranNomor()
    {
        return 'NMTP-' . strtoupper(bin2hex(random_bytes(2))) . date('ymd');
    }

    public function generatePDFFakturPenjualan()
    {
        $idTokoNotaMutasiRekap = 1;
        $mPDFGenerator      =   new MPDFGenerator();
        $stokTokoModel      =   new StokTokoModel();
        $mainOperation      =   new MainOperation();
        
        $detailNotaMutasi       =	$stokTokoModel->getDetailTokoNotaMutasiRekap($idTokoNotaMutasiRekap);
        $dataBarang             =	$stokTokoModel->getDataBarangSKUNotaMutasiToko($idTokoNotaMutasiRekap);
        $idGudang               =   isset($detailNotaMutasi['IDGUDANG']) && $detailNotaMutasi['IDGUDANG'] != "" ? $detailNotaMutasi['IDGUDANG'] : 0;
        $detailGudang           =   $mainOperation->getDetailGudang($idGudang);
        $idCaraPelunasan        =   isset($detailNotaMutasi['IDCARAPELUNASAN']) && $detailNotaMutasi['IDCARAPELUNASAN'] != "" ? $detailNotaMutasi['IDCARAPELUNASAN'] : 0;
        $strCaraPelunasanDB     =   isset($detailNotaMutasi['CARAPELUNASAN']) && $detailNotaMutasi['CARAPELUNASAN'] != "" ? $detailNotaMutasi['CARAPELUNASAN'] : '-';
        $tanggalJatuhTempoDB    =   isset($detailNotaMutasi['JATUHTEMPO']) && $detailNotaMutasi['JATUHTEMPO'] != "" ? $detailNotaMutasi['JATUHTEMPO'] : '-';
        $totalPembayaranDB      =   isset($detailNotaMutasi['TOTALPEMBAYARAN']) && $detailNotaMutasi['TOTALPEMBAYARAN'] != "" ? $detailNotaMutasi['TOTALPEMBAYARAN'] : '-';
        $strCaraPelunasan       =   '-';
        $totalBayarDownPayment  =   $totalBayarPelunasan    =   0;

        switch(intval($idCaraPelunasan)){
            case 1: $strCaraPelunasan   =   $strCaraPelunasanDB;
                    $totalBayarPelunasan=   isset($detailNotaMutasi['TOTALPEMBAYARANNOMINAL']) && $detailNotaMutasi['TOTALPEMBAYARANNOMINAL'] != "" ? $detailNotaMutasi['TOTALPEMBAYARANNOMINAL'] : 0;
                    break;
            case 2: $strCaraPelunasan   =   $strCaraPelunasanDB." (".$tanggalJatuhTempoDB.")"; break;
            case 3: $strCaraPelunasan   =   $strCaraPelunasanDB." ".$totalPembayaranDB."x";
                    $dataPembayaran     =   $stokTokoModel->getDataPembayaranNotaMutasiToko($idTokoNotaMutasiRekap);
                    if(count($dataPembayaran) > 0){
                        $firstPayment           =   $dataPembayaran[0];
                        $totalBayarPertama      =   isset($firstPayment->NOMINAL) && $firstPayment->NOMINAL != "" ? $firstPayment->NOMINAL : 0;
                        $statusBayarPertama     =   isset($firstPayment->STATUS) && $firstPayment->STATUS != "" ? $firstPayment->STATUS : 0;
                        $totalBayarDownPayment  =   intval($statusBayarPertama) === 1 ? $totalBayarPertama : 0;
                    }
                    break;
            default: $strCaraPelunasan  =   '-'; break;
        }

        $dataSurat          =   [
            'cssStyle'              =>  view('pdf/style/CssStyle'),
            'infoTableStyle'        =>  view('pdf/style/InfoTableStyle'),
            'kopSurat'              =>  view('pdf/KopSurat', $detailGudang),
            'nomorSurat'            =>  $detailNotaMutasi['NOTAMUTASINOMOR'],
            'tanggalSurat'          =>  $detailNotaMutasi['PROSESTANGGALWAKTU'],
            'pelangganNama'         =>  $detailNotaMutasi['NAMATOKO'],
            'pelangganAlamat'       =>  $detailNotaMutasi['ALAMATTOKO'],
            'departemen'            =>  $detailGudang['NAMA'],
            'userAdmin'             =>  $detailNotaMutasi['PROSESUSER'],
            'dataBarang'            =>  $dataBarang,
            'keterangan'            =>  $detailNotaMutasi['KETERANGAN'],
            'caraPelunasan'         =>  $strCaraPelunasan,
            'totalBayarDownPayment' =>  $totalBayarDownPayment,
            'totalBayarPelunasan'   =>  $totalBayarPelunasan
        ];

        $html       =   view('pdf/FakturPenjualan', $dataSurat);
        $filename   =   'FAKTUR_PENJUALAN_' . $dataSurat['nomorSurat'] . '_' . date('YmdHis') . '.pdf';
        // $pdfContent =   $mPDFGenerator->generatePDFFileOutput($html, $filename);
        // return $pdfContent;

        $pdfContent =   $mPDFGenerator->generatePDFFileOutput($html, $filename);
        return $this->response
                    ->setBody($pdfContent) 
                    ->setContentType('application/pdf') 
                    ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
                    ->setHeader('Content-Length', strlen($pdfContent));
    }
}