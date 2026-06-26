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
            $dataDetail['ARRIDTOKO']    =   encodeDataArrayId($arrIdTokoBerlaku, true);
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
        $rules      =   [
            'arrIdTokoBerlaku'              =>  ['label' => 'Daftar Toko Berlaku', 'rules' => 'required|is_array'],
            'namaPaket'                     =>  ['label' => 'Nama Paket', 'rules' => 'required|min_length[8]|max_length[100]'],
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

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $arrIdTokoBerlaku   =   $this->request->getVar('arrIdTokoBerlaku');
        $arrIdTokoBerlaku   =   $this->getJsonDataArrIdTokoBerlaku($arrIdTokoBerlaku);
        $namaPaket          =   $this->request->getVar('namaPaket');
        $deskripsi          =   $this->request->getVar('deskripsi');
        $jumlahBarang       =   $this->request->getVar('jumlahBarang');
        $arrDataBarang      =   $this->request->getVar('arrDataBarang');

        if(!is_array($arrIdTokoBerlaku) || count($arrIdTokoBerlaku) <= 0) return throwResponseNotAcceptable('Data kiriman [Daftar Toko Berlaku] tidak valid, silakan periksa kembali');
        if(!is_array($arrDataBarang) || count($arrDataBarang) <= 0) return throwResponseNotAcceptable('Data kiriman [Daftar Barang Paket] tidak valid, silakan periksa kembali');

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
}