<?php

namespace App\Controllers\ERP\Stok;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\ERP\Stok\PengaturanHargaJualPaketModel;
use App\Models\ERP\Master\BarangSKUModel;
use App\Models\MainOperation;

class PengaturanHargaJualPaket extends ResourceController
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

    public function getListPaket()
    {
        $rules  =   [
            'searchKeyword'     =>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
        ];

        $messages   =   [];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation                  =   new MainOperation();
        $PengaturanHargaJualPaketModel  =   new PengaturanHargaJualPaketModel();
        $searchKeyword                  =   $this->request->getVar('searchKeyword');
        $dataPerPage                    =   $this->request->getVar('dataPerPage');
        $pageNumber                     =   $this->request->getVar('pageNumber');
        $baseData                       =   $PengaturanHargaJualPaketModel->getDataHargaJualPaket($searchKeyword);
        $totalNumberData                =   $baseData->countAllResults(false);
        $pageProperty                   =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $dataHargaJualPaket =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);

            if($dataHargaJualPaket && count($dataHargaJualPaket) > 0) {
                foreach($dataHargaJualPaket as $keyHargaJualPaket){
                    $status                         =   $keyHargaJualPaket->STATUS;
                    $keyHargaJualPaket->STATUSSTR   =   $status == 1 ? 'Aktif' : 'Tidak Aktif';
                }
            }

            $dataHargaJualPaket =   encodeDatabaseObjectResultKey($dataHargaJualPaket, ['IDHARGARETAILPAKET']);

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $dataHargaJualPaket,
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn =   [
                "listData"          =>  [],
                "pageProperty"      =>  $pageProperty
            ];
            return throwResponseNotFound('Data harga jual paket tidak ditemukan', $dataReturn);
        }
    }

    public function getDetailPaket()
    {
        $rules  =   [
            'namaPaket' =>  ['label' => 'Nama Paket', 'rules' => 'required'],
        ];

        $messages   =   [];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $pengaturanHargaJualPaketModel  =   new PengaturanHargaJualPaketModel();
        $namaPaket                      =   $this->request->getVar('namaPaket');
        $dataDetail                     =   $pengaturanHargaJualPaketModel->getDetailHargaJualPaket($namaPaket);

        if($dataDetail){
            $idHargaRetailPaket         =   $dataDetail['IDHARGARETAILPAKET'];
            $arrIdTokoBerlaku           =   explode(',', $dataDetail['ARRIDTOKO']);
            $dataDetail['ARRIDTOKO']    =   encodeDataArrayId($arrIdTokoBerlaku);
            $daftarBarangPaket          =   $pengaturanHargaJualPaketModel->getDataBarangPaket($idHargaRetailPaket);
            
            $barangSKUModel         =   new BarangSKUModel();
            if(!is_null($daftarBarangPaket) && count($daftarBarangPaket) > 0) {
                foreach($daftarBarangPaket as $keyBarangPaket){
                    $idBarangSKU                    =   $keyBarangPaket->IDBARANGSKU;
                    $dataHargaJualBarangSKU         =   $pengaturanHargaJualPaketModel->getHargaJualBarangSKU($idBarangSKU);
                    $keyBarangPaket->HARGATERENDAH  =   $dataHargaJualBarangSKU['HARGATERENDAH'];
                    $keyBarangPaket->HARGATERTINGGI =   $dataHargaJualBarangSKU['HARGATERTINGGI'];
                    $keyBarangPaket->ATRIBUTSKUSTR  =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                }
                $daftarBarangPaket  =   encodeDatabaseObjectResultKey($daftarBarangPaket, ['IDBARANGSKU']);                
            }

            return $this->setResponseFormat('json')
                        ->respond([
                            "dataDetail"        =>  $dataDetail,
                            "daftarBarangPaket" =>  $daftarBarangPaket
                        ]);
        } else {
            return throwResponseNotFound('Tidak ditemukan data detail harga jual paket dengan nama paket <b>' . $namaPaket . '</b>');
        }
    }

    public function getDetailBarangHarga()
    {
        $rules  =   [
            'idBarangSKU'   =>  ['label' => 'Data Barang', 'rules' => 'required|alpha_numeric'],
        ];

        $messages   =   [];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $barangSKUModel                 =   new BarangSKUModel();
        $pengaturanHargaJualPaketModel  =   new PengaturanHargaJualPaketModel();

        $idBarangSKU            =   $this->request->getVar('idBarangSKU');
        $idBarangSKU            =   isset($idBarangSKU) && $idBarangSKU != "" ? hashidDecode($idBarangSKU) : 0;
        $atributSKU             =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
        $dataHargaJualBarangSKU =   $pengaturanHargaJualPaketModel->getHargaJualBarangSKU($idBarangSKU);

        return $this->setResponseFormat('json')
                    ->respond([
                        "idBarangSKU"   =>  $idBarangSKU,
                        "atributSKU"    =>  $atributSKU,
                        "hargaTerendah" =>  $dataHargaJualBarangSKU['HARGATERENDAH'],
                        "hargaTertinggi"=>  $dataHargaJualBarangSKU['HARGATERTINGGI']
                    ]);
    }

    public function addHargaJualPaket()
    {
        $validation         =   $this->parametersDataValidator();
        if($validation !== true) return $this->fail($validation);

        $arrIdTokoBerlaku   =   $this->request->getVar('arrIdTokoBerlaku');
        $arrIdTokoBerlaku   =   $this->getJsonDataArrIdTokoBerlaku($arrIdTokoBerlaku);
        $namaPaket          =   $this->request->getVar('namaPaket');
        $deskripsi          =   $this->request->getVar('deskripsi');
        $jumlahBarang       =   $this->request->getVar('jumlahBarang');
        $arrDataBarang      =   $this->request->getVar('arrDataBarang');

        if(!is_array($arrIdTokoBerlaku) || count($arrIdTokoBerlaku) <= 0) return throwResponseNotAcceptable('Data kiriman [Daftar Toko Berlaku] tidak valid, silakan periksa kembali');
        if(!is_array($arrDataBarang) || count($arrDataBarang) <= 0) return throwResponseNotAcceptable('Data kiriman [Daftar Barang Paket] tidak valid, silakan periksa kembali');
        $this->validateNamaPaketToko($namaPaket, $arrIdTokoBerlaku);

        $mainOperation                  =   new MainOperation();
        $arrInsertDataDetailPaketPool   =   [];
        foreach($arrDataBarang as $keyDataBarang){
            $idBarangSKU    =   hashidDecode($keyDataBarang->idBarangSKU);
            $jumlahBarangSKU=   $keyDataBarang->jumlah;
            $harga          =   $keyDataBarang->harga;

            $arrInsertDataDetailPaketPool[] =   [
                'IDHARGARETAILPAKET'=>  null,
                'IDBARANGSKU'       =>  $idBarangSKU,
                'IDBARANGSATUAN'    =>  100,
                'JUMLAH'            =>  $jumlahBarangSKU,
                'HARGA'             =>  $harga
            ];
        }

        foreach($arrIdTokoBerlaku as $idTokoBerlaku){
            $arrInsertDataPaket =   [
                'IDTOKO'                =>  $idTokoBerlaku,
                'NAMAHARGARETAILPAKET'  =>  $namaPaket,
                'DESKRIPSI'             =>  $deskripsi,
                'JUMLAHBARANG'          =>  $jumlahBarang,
                'STATUS'                =>  1
            ];
            $procInsertDataPaket=   $mainOperation->insertDataTable('t_hargaretailpaket', $arrInsertDataPaket);

            if($procInsertDataPaket['status']) {
                $idHargaRetailPaket   =   $procInsertDataPaket['insertID'];

                foreach($arrInsertDataDetailPaketPool as $arrInsertDataDetailPaket){
                    $arrInsertDataDetailPaket['IDHARGARETAILPAKET'] =   $idHargaRetailPaket;
                    $mainOperation->insertDataTable('t_hargaretailpaketsku', $arrInsertDataDetailPaket);
                }
            }
        }

        return throwResponseOK(
            'Data harga jual paket berhasil ditambahkan'
        );
    }

    public function updateHargaJualPaket()
    {
        $validation         =   $this->parametersDataValidator(true);
        if($validation !== true) return $this->fail($validation);

        $arrIdTokoBerlaku   =   $this->request->getVar('arrIdTokoBerlaku');
        $arrIdTokoBerlaku   =   $this->getJsonDataArrIdTokoBerlaku($arrIdTokoBerlaku);
        $namaPaket          =   $this->request->getVar('namaPaket');
        $namaPaketOrigin    =   $this->request->getVar('namaPaketOrigin');
        $deskripsi          =   $this->request->getVar('deskripsi');
        $jumlahBarang       =   $this->request->getVar('jumlahBarang');
        $arrDataBarang      =   $this->request->getVar('arrDataBarang');

        if(!is_array($arrIdTokoBerlaku) || count($arrIdTokoBerlaku) <= 0) return throwResponseNotAcceptable('Data kiriman [Daftar Toko Berlaku] tidak valid, silakan periksa kembali');
        if(!is_array($arrDataBarang) || count($arrDataBarang) <= 0) return throwResponseNotAcceptable('Data kiriman [Daftar Barang Paket] tidak valid, silakan periksa kembali');
        if($namaPaket != $namaPaketOrigin) $this->validateNamaPaketToko($namaPaket, $arrIdTokoBerlaku);

        //Collect semua idBarangSKU kiriman masukkan ke $arrIdBarangSKU
        $arrIdBarangSKU     =   [];
        foreach($arrDataBarang as $keyDataBarang){
            $arrIdBarangSKU[]   =   hashidDecode($keyDataBarang->idBarangSKU);
        }
        
        //Ambil data detail harga jual paket
        $pengaturanHargaJualPaketModel  =   new PengaturanHargaJualPaketModel();
        $detailHagaJualPaket            =   $pengaturanHargaJualPaketModel->getDetailHargaJualPaket($namaPaketOrigin, true);
        if(!$detailHagaJualPaket) return throwResponseNotAcceptable('Data kiriman [Nama Paket Awal] tidak valid, silakan periksa kembali');
        
        //Definisikan data detail, compare toko berlaku kiriman dengan origin (bertambah & berkurang)
        $mainOperation              =   new MainOperation();
        $idHargaRetailPaketOrigin   =   $detailHagaJualPaket['IDHARGARETAILPAKET'];
        $deskripsiOrigin            =   $detailHagaJualPaket['DESKRIPSI'];
        $jumlahBarangOrigin         =   $detailHagaJualPaket['JUMLAHBARANG'];
        $arrIdTokoBerlakuStr        =   $detailHagaJualPaket['ARRIDTOKO'];
        $arrIdTokoBerlakuOrigin     =   explode(',', $arrIdTokoBerlakuStr);
        $arrIdTokoBerlakuBertambah  =   array_diff($arrIdTokoBerlaku, $arrIdTokoBerlakuOrigin);
        $arrIdTokoBerlakuBerkurang  =   array_diff($arrIdTokoBerlakuOrigin, $arrIdTokoBerlaku);

        //Ambil data barang SKU paket, compare barang SKU kiriman dengan origin (bertambah & berkurang)
        $dataBarangPaket        =   $pengaturanHargaJualPaketModel->getDataBarangPaket($idHargaRetailPaketOrigin);
        $arrIdBarangSKUOrigin   =   [];

        if(count($dataBarangPaket) <= 0) return throwResponseNotAcceptable('Data barang SKU awal tidak ditemukan, silakan coba lagi nanti');
        foreach($dataBarangPaket as $keyBarangPaket){
            $arrIdBarangSKUOrigin[] =   $keyBarangPaket->IDBARANGSKU;
        }

        $arrIdBarangSKUBertambah    =   array_diff($arrIdBarangSKU, $arrIdBarangSKUOrigin);
        $arrIdBarangSKUBerkurang    =   array_diff($arrIdBarangSKUOrigin, $arrIdBarangSKU);

        //PROSES 1 - Hapus SKU yang berkurang
        $arrIdHargaRetailPaketStr   =   $detailHagaJualPaket['ARRIDHARGARETAILPAKET'];
        $arrIdHargaRetailPaket      =   explode(',', $arrIdHargaRetailPaketStr);
        if(count($arrIdBarangSKUBerkurang) > 0){
            foreach($arrIdBarangSKUBerkurang as $idBarangSKUBerkurang){
                $pengaturanHargaJualPaketModel->deleteDataIdBarangSKUPaket($idBarangSKUBerkurang, $arrIdHargaRetailPaket);
            }
        }

        //PROSES 2 - Insert SKU yang bertambah
        if(count($arrIdBarangSKUBertambah) > 0){
            foreach($arrIdBarangSKUBertambah as $idBarangSKUBertambah){
                $jumlahBarangSKU=   0;
                $hargaBarangSKU =   0;

                foreach($arrDataBarang as $keyDataBarang){
                    if(hashidDecode($keyDataBarang->idBarangSKU) == $idBarangSKUBertambah){
                        $jumlahBarangSKU = $keyDataBarang->jumlah;
                        $hargaBarangSKU  = $keyDataBarang->harga;
                        break;
                    }
                }

                foreach($arrIdHargaRetailPaket as $idHargaRetailPaket){
                    $arrInsertDataBarangSKU =   [
                        'IDHARGARETAILPAKET'=>  $idHargaRetailPaket,
                        'IDBARANGSKU'       =>  $idBarangSKUBertambah,
                        'IDBARANGSATUAN'    =>  100,
                        'JUMLAH'            =>  $jumlahBarangSKU,
                        'HARGA'             =>  $hargaBarangSKU
                    ];
                    $mainOperation->insertDataTable('t_hargaretailpaketsku', $arrInsertDataBarangSKU);
                }
            }
        }

        //PROSES 3 - Update non aktif untuk toko yang berkurang
        if(count($arrIdTokoBerlakuBerkurang) > 0){
            foreach($arrIdTokoBerlakuBerkurang as $idTokoBerlakuBerkurang){
                $mainOperation->updateDataTable(
                    't_hargaretailpaket',
                    ['STATUS' => -1],
                    [
                        'IDTOKO'                =>  $idTokoBerlakuBerkurang,
                        'NAMAHARGARETAILPAKET'  =>  $namaPaketOrigin
                    ]
                );
            }
        }

        //PROSES 4 - Insert data harga paket untuk toko yang bertambah
        if(count($arrIdTokoBerlakuBertambah) > 0){
            $arrInsertDataDetailPaketPool   =   [];
            foreach($arrDataBarang as $keyDataBarang){
                $idBarangSKU    =   hashidDecode($keyDataBarang->idBarangSKU);
                $jumlahBarangSKU=   $keyDataBarang->jumlah;
                $harga          =   $keyDataBarang->harga;

                $arrInsertDataDetailPaketPool[] =   [
                    'IDHARGARETAILPAKET'=>  null,
                    'IDBARANGSKU'       =>  $idBarangSKU,
                    'IDBARANGSATUAN'    =>  100,
                    'JUMLAH'            =>  $jumlahBarangSKU,
                    'HARGA'             =>  $harga
                ];
            }

            foreach($arrIdTokoBerlakuBertambah as $idTokoBerlakuBertambah){
                $isPaketTokoExist   =   $pengaturanHargaJualPaketModel->isPaketTokoExist($idTokoBerlakuBertambah, $namaPaket);

                if(!$isPaketTokoExist){
                    $arrInsertDataPaket =   [
                        'IDTOKO'                =>  $idTokoBerlakuBertambah,
                        'NAMAHARGARETAILPAKET'  =>  $namaPaket,
                        'DESKRIPSI'             =>  $deskripsi,
                        'JUMLAHBARANG'          =>  $jumlahBarang,
                        'STATUS'                =>  1
                    ];
                    $procInsertDataPaket=   $mainOperation->insertDataTable('t_hargaretailpaket', $arrInsertDataPaket);

                    if($procInsertDataPaket['status']) {
                        $idHargaRetailPaket   =   $procInsertDataPaket['insertID'];

                        foreach($arrInsertDataDetailPaketPool as $arrInsertDataDetailPaket){
                            $arrInsertDataDetailPaket['IDHARGARETAILPAKET'] =   $idHargaRetailPaket;
                            $mainOperation->insertDataTable('t_hargaretailpaketsku', $arrInsertDataDetailPaket);
                        }
                    }
                } else {
                    $idHargaRetailPaketUpdate   =   $isPaketTokoExist['IDHARGARETAILPAKET'];
                    $mainOperation->updateDataTable('t_hargaretailpaket', ['STATUS' => 1], ['IDHARGARETAILPAKET' => $idHargaRetailPaketUpdate]);
                }
            }
        }

        //PROSES 5 - Jika tidak ada barang bertambah/berkurang, cek perbedaan jumlah dan harga per SKU. Jika ada yang berbeda, maka update
        if(count($arrIdBarangSKUBerkurang) <= 0 && count($arrIdBarangSKUBertambah) <= 0){
            $dataBarangSKUPaket =   $pengaturanHargaJualPaketModel->getDataBarangPaket($idHargaRetailPaketOrigin);

            foreach($arrDataBarang as $keyDataBarang){
                $idBarangSKU    =   hashidDecode($keyDataBarang->idBarangSKU);
                $jumlah         =   $keyDataBarang->jumlah;
                $harga          =   $keyDataBarang->harga;
                $arrUpdateSKU   =   [];

                foreach($dataBarangSKUPaket as $keyBarangPaket){
                    if($keyBarangPaket->IDBARANGSKU == $idBarangSKU){
                        if($jumlah != $keyBarangPaket->JUMLAH) $arrUpdateSKU['JUMLAH']  =   $jumlah;
                        if($harga != $keyBarangPaket->HARGAPAKET) $arrUpdateSKU['HARGA']    =   $harga;
                        break;
                    }
                }

                if(count($arrUpdateSKU) > 0){
                    foreach($arrIdHargaRetailPaket as $idHargaRetailPaket){
                        $mainOperation->updateDataTable(
                            't_hargaretailpaketsku',
                            $arrUpdateSKU,
                            [
                                'IDHARGARETAILPAKET'=>  $idHargaRetailPaket,
                                'IDBARANGSKU'       =>  $idBarangSKU
                            ]
                        );
                    }
                }
            }
        }

        //PROSES 6 - Cek perbedaan nama paket, deskripsi, jumlah barang. Jika ada yang berbeda, maka update
        $arrUpdateHargaRetailPaket  =   [];
        if($namaPaket != $namaPaketOrigin) $arrUpdateHargaRetailPaket['NAMAHARGARETAILPAKET']   =   $namaPaket;
        if($deskripsi != $deskripsiOrigin) $arrUpdateHargaRetailPaket['DESKRIPSI']  =   $deskripsi;
        if($jumlahBarang != $jumlahBarangOrigin) $arrUpdateHargaRetailPaket['JUMLAHBARANG'] =   $jumlahBarang;

        if(count($arrUpdateHargaRetailPaket) > 0){
            $mainOperation->updateDataTable('t_hargaretailpaket', $arrUpdateHargaRetailPaket, ['NAMAHARGARETAILPAKET' => $namaPaketOrigin]);
        }

        return throwResponseOK(
            'Data harga jual paket berhasil diperbarui'
        );
    }

    private function parametersDataValidator($isUpdate = false){
        $rules      =   [
            'arrIdTokoBerlaku'              =>  ['label' => 'Daftar Toko Berlaku', 'rules' => 'required|is_array'],
            'namaPaket'                     =>  ['label' => 'Nama Paket', 'rules' => 'required|string|min_length[8]|max_length[100]'],
            'deskripsi'                     =>  ['label' => 'Deskripsi', 'rules' => 'required|min_length[8]|max_length[255]'],
            'jumlahBarang'                  =>  ['label' => 'Jumlah Barang', 'rules' => 'required|integer|greater_than[1]'],
            'arrDataBarang.*.idBarangSKU'   =>  ['label' => 'Id Harga Retail Paket', 'rules' => 'required|alpha_numeric'],
            'arrDataBarang.*.jumlah'        =>  ['label' => 'Jumlah Barang', 'rules' => 'required|numeric|greater_than[0]|min_length[1]|max_length[4]'],
            'arrDataBarang.*.harga'         =>  ['label' => 'Harga', 'rules' => 'required|numeric|greater_than_equal_to[0]|min_length[1]|max_length[10]'],
        ];

        $messages   =   [
            'arrIdTokoBerlaku'  =>  [
                'required'  =>  'Harap pilih minimal 1 toko berlaku',
                'is_array'  =>  'Daftar toko berlaku tidak valid, silakan periksa kembali'
            ],
            'jumlahBarang'  =>  [
                'integer'   =>  'Data kiriman [Jumlah Barang] tidak valid, silakan periksa kembali'
            ],
            'arrDataBarang.*.idBarangSKU'   => [
                'alpha_numeric' =>  'Barang yang dipilih tidak valid, silakan periksa kembali'
            ]
        ];
        
        if($isUpdate) $rules['namaPaketOrigin']['rules']  =   'required|string';

        if(!$this->validate($rules, $messages)) return $this->validator->getErrors();
        return true;
    }

    private function getJsonDataArrIdTokoBerlaku($arrIdTokoBerlaku)
    {
        $result =   [];

        if(is_array($arrIdTokoBerlaku) && count($arrIdTokoBerlaku) > 0) {
            foreach($arrIdTokoBerlaku as $idTokoBerlaku) {
                $idTokoBerlaku  =   hashidDecode($idTokoBerlaku);
                if(!$idTokoBerlaku || !is_numeric($idTokoBerlaku) || $idTokoBerlaku <= 0) {
                    return false;
                }
                $result[] = $idTokoBerlaku;
            }
        }

        return $result;
    }

    private function validateNamaPaketToko($namaPaket, $arrIdTokoBerlaku = []){
        $mainOperation                  =   new MainOperation();
        $pengaturanHargaJualPaketModel  =   new PengaturanHargaJualPaketModel();

        foreach($arrIdTokoBerlaku as $idTokoBerlaku){
            $checkDataPaket =   $pengaturanHargaJualPaketModel->where('IDTOKO', $idTokoBerlaku)
                                ->where('NAMAHARGARETAILPAKET', $namaPaket)
                                ->first();

            if($checkDataPaket) {
                $namaToko   =   $mainOperation->getDetailToko($idTokoBerlaku)['NAMA'];
                return throwResponseNotAcceptable('Nama paket <b>' . $namaPaket . '</b> sudah digunakan pada toko <b>' . $namaToko . '</b>, silakan periksa kembali');
            }
        }

        return true;
    }
}