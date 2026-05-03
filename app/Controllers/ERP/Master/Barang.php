<?php

namespace App\Controllers\ERP\Master;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\MainOperation;
use App\Models\ERP\Master\BarangModel;
use App\Models\ERP\Master\BarangSKUModel;
use App\Models\ERP\Master\BarangSKUAtributModel;
use App\Models\ERP\Master\BarangKonversiAturanModel;

class Barang extends ResourceController
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
        $rules  =   [
            'arrIdBarangKategori'   =>  ['label' => 'Id Kategori', 'rules' => 'is_array'],
            'arrIdBarangMerk'       =>  ['label' => 'Id Merk', 'rules' => 'is_array'],
            'arrIdBarangKategori.*' =>  ['label' => 'Id Kategori', 'rules' => 'permit_empty|alpha_numeric'],
            'arrIdBarangMerk.*'     =>  ['label' => 'Id Merk', 'rules' => 'permit_empty|alpha_numeric'],
            'searchKeyword'         =>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct']
        ];

        $messages   =   [
            'arrIdBarangKategori'   =>  ['is_array' =>  'Data kategori yang anda pilih tidak valid, silakan periksa kembali'],
            'arrIdBarangMerk'       =>  ['is_array' =>  'Data merk yang anda pilih tidak valid, silakan periksa kembali'],
            'arrIdBarangKategori.*' =>  ['is_array' =>  'Data kategori yang anda pilih tidak valid, silakan periksa kembali'],
            'arrIdBarangMerk.*'     =>  ['is_array' =>  'Data merk yang anda pilih tidak valid, silakan periksa kembali']
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $barangModel        =   new BarangModel();
        $mainOperation      =   new MainOperation();
        $arrIdBarangKategori=   $this->request->getVar('arrIdBarangKategori');
        $arrIdBarangMerk    =   $this->request->getVar('arrIdBarangMerk');
        $searchKeyword      =   $this->request->getVar('searchKeyword');
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $pageNumber         =   $this->request->getVar('pageNumber');

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

        $baseData           =	$barangModel->getListBarang($arrIdBarangKategori, $arrIdBarangMerk, $searchKeyword);
        $totalNumberData    =   $baseData->countAllResults(false);
        $pageProperty       =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $listData   =   $baseData->orderBy('C.NAMAKATEGORI, B.NAMAMERK, A.NAMABARANG, A.KODEBARANG')->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            foreach($listData as $keyData) {
                $keyData->FOTOBARANG    =  isset($keyData->FOTOBARANG) && $keyData->FOTOBARANG != "" ? json_decode($keyData->FOTOBARANG) : [];
            }

            $listData   =   encodeDatabaseObjectResultKey($listData, ['IDBARANG', 'IDBARANGMERK', 'IDBARANGKATEGORI']);
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

    public function getListSKU()
    {
        $barangSKUModel =   new BarangSKUModel();
        $rules          =   ['idBarang' =>  ['label' => 'Id Barang', 'rules' => 'required|alpha_numeric']];
        $messages       =   [
            'idBarang'          => [
                'required'      => 'Data kiriman tidak lengkap, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak lengkap, silakan periksa kembali'
            ]
        ];
        
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $idBarang       =   hashidDecode($this->request->getVar('idBarang'));
        $listDataSKU    =	$barangSKUModel->getListDetailBarangSKU($idBarang);

        if(empty($listDataSKU) || count($listDataSKU) == 0) {
            $listDataSKU    =   [];
        } else {
            foreach($listDataSKU as $keySKU) {
                $idBarangSKU    =   isset($keySKU->IDBARANGSKU) && $keySKU->IDBARANGSKU != "" ? $keySKU->IDBARANGSKU : 0;
                $dataAtributSKU =   $barangSKUModel->getAtributSKU($idBarangSKU);
                $strAtributSKU  =   [];

                if(!$dataAtributSKU || count($dataAtributSKU) == 0) {
                    $dataAtributSKU =   [];
                } else {
                    foreach($dataAtributSKU as $keyAtribut) {
                        $strAtributSKU[]    =   $keyAtribut->NAMAATRIBUT;
                        unset($keyAtribut->NAMAATRIBUT);
                    }
                    $dataAtributSKU = encodeDatabaseObjectResultKey($dataAtributSKU, ['IDBARANGATRIBUT']);
                }

                $keySKU->ATRIBUTSKUDATA =   $dataAtributSKU;
                $keySKU->ATRIBUTSKUSTR  =   $strAtributSKU;
                $keySKU->FOTOBARANGSKU  =   isset($keySKU->FOTOBARANGSKU) && $keySKU->FOTOBARANGSKU != "" ? json_decode($keySKU->FOTOBARANGSKU) : [];
            }

            $listDataSKU    =   encodeDatabaseObjectResultKey($listDataSKU, ['IDBARANGSKU', 'IDBARANGSATUAN']);
        }
        return $this->setResponseFormat('json')->respond(["listDataSKU" =>  $listDataSKU, "baseURLFotoBarang" => URL_FOTO_BARANG]);
    }

    public function uploadImageBarang()
    {
        $imageBarang    =   $this->request->getFile('imageBarang');
        $jenisImage     =   $this->request->getVar('jenisImage');
        $allowedExtensions  =   ['jpg', 'jpeg'];
        if ($imageBarang && !$imageBarang->hasMoved()) {
            $extension = strtolower($imageBarang->getExtension());
            if (!in_array($extension, $allowedExtensions)) {
                return $this->failValidationErrors('Ekstensi file tidak diizinkan. Hanya jpg dan jpeg yang diperbolehkan.');
            }
        }
        
        if ($imageBarang && $imageBarang->isValid() && !$imageBarang->hasMoved()) {
            $imageBarangName    =   $jenisImage."_".$imageBarang->getRandomName();
            $imageBarang->move(PATH_STORAGE_FOTO_BARANG, $imageBarangName);

            return $this->setResponseFormat('json')->respond([
                'message'   =>  'Gambar barang berhasil diunggah',
                'imageName' =>  $imageBarangName
            ]);
        }
    }  

    public function saveDataBarang()
    {
        $idBarang   =   $this->request->getVar('idBarang');
        $idBarang   =   isset($idBarang) && $idBarang != "" ? hashidDecode($idBarang) : 0;
        $validation =   $idBarang == 0 ? $this->parametersValidatorBarang() : $this->parametersValidatorBarang($idBarang > 0, $idBarang);

        if($validation !== true) return $this->fail($validation);
        return $idBarang == 0 ? $this->insertDataBarang() : $this->updateDataBarang($idBarang);
    }

    private function insertDataBarang()
    {
        $mainOperation  =   new MainOperation();
        $arrInsertData  =   $this->generateArrayInsertUpdateBarang(true);
        $procInsertData =   $mainOperation->insertDataTable('m_barang', $arrInsertData);

        if(!$procInsertData['status']) return switchMySQLErrorCode($procInsertData['errCode']);
        $idBarang       =   $procInsertData['insertID'];
        return throwResponseOK(
            'Data barang telah disimpan',
            ['idBarang'  =>  hashidEncode($idBarang)]
        );
    }

    private function updateDataBarang($idBarang)
    {
        $mainOperation  =   new MainOperation();
        $arrUpdateData  =   $this->generateArrayInsertUpdateBarang();
        $procUpdateData =   $mainOperation->updateDataTable('m_barang', $arrUpdateData, ['IDBARANG' => $idBarang]);

        if(!$procUpdateData['status']) return switchMySQLErrorCode($procUpdateData['errCode']);
        return throwResponseOK(
            'Data barang telah diperbarui',
            ['idBarang'  =>  hashidEncode($idBarang)]
        );
    }

    private function parametersValidatorBarang($isUpdate = false, $idBarang = null)
    {
        $rules  =   [
            'idBarangMerk'      =>  ['label' => 'Merk', 'rules' => 'required|alpha_numeric'],
            'idBarangKategori'  =>  ['label' => 'Kategori', 'rules' => 'required|alpha_numeric'],
            'kodeBarang'        =>  ['label' => 'Kode Barang', 'rules' => 'required|alpha_numeric|min_length[3]|max_length[10]'],
            'namaBarang'        =>  ['label' => 'Nama Barang', 'rules' => 'required|regex_match[/^[a-zA-Z0-9\s\p{P}]+$/u]|min_length[5]|max_length[150]'],
            'deskripsi'         =>  ['label' => 'Deskripsi', 'rules' => 'permit_empty|regex_match[/^[a-zA-Z0-9\s\p{P}]+$/u]|max_length[255]'],
        ];

        $messages   =   [
            'idBarang'          => [
                'required'      => 'Data kiriman tidak lengkap, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak lengkap, silakan periksa kembali'
            ],
            'idBarangMerk'      => [
                'required'      => 'Data kiriman tidak lengkap, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak lengkap, silakan periksa kembali'
            ],
            'idBarangKategori'  => [
                'required'      => 'Data kiriman tidak lengkap, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak lengkap, silakan periksa kembali'
            ]
        ];

        if($isUpdate) {
            $rules['idBarang']['rules']             =   'required|alpha_numeric';
            $rules['kodeBarang']['rules']           .=  '|is_unique[m_barang.KODEBARANG, IDBARANG, '.$idBarang.']';
            $messages['idBarang']['required']       =   'Data kiriman tidak lengkap, silakan periksa kembali';
            $messages['idBarang']['alpha_numeric']  =   'Data kiriman tidak lengkap, silakan periksa kembali';
            $messages['kodeBarang']['is_unique']    =   'Duplikasi untuk kode barang, silakan gunakan kode barang lainnya';
        } else {
            $rules['kodeBarang']['rules']           .=  '|is_unique[m_barang.KODEBARANG]';
        }

        if(!$this->validate($rules, $messages)) return $this->validator->getErrors();
        return true;
    }

    private function generateArrayInsertUpdateBarang($isInsert = false): array
    {
        $idBarangMerk       =   hashidDecode($this->request->getVar('idBarangMerk'));
        $idBarangKategori   =   hashidDecode($this->request->getVar('idBarangKategori'));
        $kodeBarang         =   $this->request->getVar('kodeBarang');
        $namaBarang         =   $this->request->getVar('namaBarang');
        $fotoBarang         =   $this->request->getVar('fotoBarang');
        $deskripsi          =   $this->request->getVar('deskripsi');

        if(isset($fotoBarang) && $fotoBarang != "" && is_array($fotoBarang)) {
            $fotoBarang =   json_encode($fotoBarang);
        } else {
            $fotoBarang =   '[]';
        }

        $arrInsertUpdateData    =   [
            'IDBARANGMERK'      =>  $idBarangMerk,
            'IDBARANGKATEGORI'  =>  $idBarangKategori,
            'KODEBARANG'        =>  $kodeBarang,
            'NAMABARANG'        =>  $namaBarang,
            'FOTOBARANG'        =>  $fotoBarang,
            'DESKRIPSI'         =>  $deskripsi
        ];

        return $arrInsertUpdateData;
    }

    public function saveDataBarangSKU()
    {
        helper(['form']);
        $idBarangSKU    =   $this->request->getVar('idBarangSKU');
        $idBarangSKU    =   isset($idBarangSKU) && $idBarangSKU != "" ? hashidDecode($idBarangSKU) : 0;
        $atributSKU     =   $this->request->getVar('atributSKU');
        $validation     =   $idBarangSKU == 0 ? $this->parametersValidatorBarangSKU() : $this->parametersValidatorBarangSKU($idBarangSKU > 0, $idBarangSKU);

        if($validation !== true) return $this->fail($validation);
        if(!isset($atributSKU) || !is_array($atributSKU) || count($atributSKU) <= 0) return throwResponseNotAcceptable("Harap masukkan setidaknya 1 atribut SKU");
        return $idBarangSKU == 0 ? $this->insertDataBarangSKU($atributSKU) : $this->updateDataBarangSKU($atributSKU, $idBarangSKU);
    }

    private function insertDataBarangSKU($atributSKU)
    {
        $mainOperation  =   new MainOperation();
        $arrInsertData  =   $this->generateArrayInsertUpdateBarangSKU(true);
        $procInsertData =   $mainOperation->insertDataTable('m_barangsku', $arrInsertData);

        if(!$procInsertData['status']) return switchMySQLErrorCode($procInsertData['errCode']);
        $idBarangSKU    =   $procInsertData['insertID'];

        $this->insertUpdateDataSKUAtribut($mainOperation, $idBarangSKU, $atributSKU);
        return throwResponseOK(
            'Data SKU barang telah disimpan',
            ['idBarangSKU'  =>  hashidEncode($idBarangSKU)]
        );
    }

    private function updateDataBarangSKU($atributSKU, $idBarangSKU)
    {
        $mainOperation  =   new MainOperation();
        $arrUpdateData  =   $this->generateArrayInsertUpdateBarangSKU();
        $procUpdateData =   $mainOperation->updateDataTable('m_barangsku', $arrUpdateData, ['IDBARANGSKU' => $idBarangSKU]);

        // Jika ada error, dan selain dari error 1329 (MySQL Error Code: 1329 - Tidak ada data yang diubah)
        if(!$procUpdateData['status']) {
            if ($procUpdateData['errCode'] != 1329) return switchMySQLErrorCode($procUpdateData['errCode']);
        }

        $arrIdSKUAtribut    =   $this->insertUpdateDataSKUAtribut($mainOperation, $idBarangSKU, $atributSKU);
        if(isset($arrIdSKUAtribut) && is_array($arrIdSKUAtribut) && count($arrIdSKUAtribut) > 0) {
            $barangSKUAtributModel  =   new BarangSKUAtributModel();
            $barangSKUAtributModel->where('IDBARANGSKU', $idBarangSKU)->whereNotIn('IDBARANGATRIBUT', $arrIdSKUAtribut)->delete();
        }

        return throwResponseOK(
            'Data barang telah diperbarui',
            ['idBarangSKU'  =>  hashidEncode($idBarangSKU)]
        );
    }

    private function parametersValidatorBarangSKU($isUpdate = false, $idBarangSKU = '')
    {
        $rules  =   [
            'idBarang'      =>  ['label' => 'Id Barang', 'rules' => 'required|alpha_numeric'],
            'idBarangSatuan'=>  ['label' => 'Id Barang Satuan', 'rules' => 'required|alpha_numeric'],
            'kodeBarangSKU' =>  ['label' => 'Kode SKU', 'rules' => 'required|alpha_numeric|min_length[3]|max_length[10]'],
            'deskripsi'     =>  ['label' => 'Deskripsi', 'rules' => 'regex_match[/^[a-zA-Z0-9\s\p{P}]+$/u]|max_length[255]']
        ];

        $messages   =   [
            'idBarang'          => [
                'required'      => 'Data kiriman tidak lengkap, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak lengkap, silakan periksa kembali'
            ],
            'idBarangSatuan'          => [
                'required'      => 'Data kiriman tidak lengkap, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak lengkap, silakan periksa kembali'
            ]
        ];

        if($isUpdate) {
            $rules['kodeBarangSKU']['rules']            .=  '|is_unique[m_barangsku.KODESKU, IDBARANGSKU, '.$idBarangSKU.']';
            $rules['idBarangSKU']['rules']              =   'required|alpha_numeric';
            $messages['idBarangSKU']['required']        =   'Data kiriman tidak lengkap, silakan periksa kembali';
            $messages['idBarangSKU']['alpha_numeric']   =   'Data kiriman tidak lengkap, silakan periksa kembali';
        } else {
            $rules['kodeBarangSKU']['rules']            .=  '|is_unique[m_barangsku.KODESKU]';
        }

        if(!$this->validate($rules, $messages)) return $this->validator->getErrors();
        return true;
    }

    private function generateArrayInsertUpdateBarangSKU(): array
    {
        $idBarang       =   hashidDecode($this->request->getVar('idBarang'));
        $idBarangSatuan =   hashidDecode($this->request->getVar('idBarangSatuan'));
        $kodeBarangSKU  =   $this->request->getVar('kodeBarangSKU');
        $fotoBarangSKU  =   $this->request->getVar('fotoBarangSKU');
        $deskripsi      =   $this->request->getVar('deskripsi');

        if(isset($fotoBarangSKU) && $fotoBarangSKU != "" && is_array($fotoBarangSKU)) {
            $fotoBarangSKU =   json_encode($fotoBarangSKU);
        } else {
            $fotoBarangSKU =   '[]';
        }

        return [
            'IDBARANG'      =>  $idBarang,
            'IDBARANGSATUAN'=>  $idBarangSatuan,
            'KODESKU'       =>  $kodeBarangSKU,
            'FOTOBARANGSKU' =>  $fotoBarangSKU,
            'DESKRIPSI'     =>  $deskripsi
        ];
    }

    private function insertUpdateDataSKUAtribut($mainOperation, $idBarangSKU, $atributSKU) : array
    {
        $barangSKUAtributModel  =   new BarangSKUAtributModel();
        $arrIdSKUAtribut        =   [];
        foreach($atributSKU as $keyAtributSKU) {
            $idSKUAtribut           =   isset($keyAtributSKU[0]) && $keyAtributSKU[0] != "" ? hashidDecode($keyAtributSKU[0]) : 0;
            $nilaiSKUAtribut        =   isset($keyAtributSKU[1]) && $keyAtributSKU[1] != "" ? $keyAtributSKU[1] : "";
            $dataBarangSKUAtribut   =   $barangSKUAtributModel->getDataBarangSKUAtribut($idBarangSKU, $idSKUAtribut);

            $arrInsertUpdateSKUAtribut  =   [
                'IDBARANGSKU'      =>  $idBarangSKU,
                'IDBARANGATRIBUT'  =>  $idSKUAtribut,
                'NILAIATRIBUT'     =>  $nilaiSKUAtribut
            ];

            if($dataBarangSKUAtribut) $mainOperation->updateDataTable('m_barangskuatribut', $arrInsertUpdateSKUAtribut, ['IDBARANGSKUATRIBUT' => $dataBarangSKUAtribut['IDBARANGSKUATRIBUT']]);
            else $mainOperation->insertDataTable('m_barangskuatribut', $arrInsertUpdateSKUAtribut);
            $arrIdSKUAtribut[] =   $idSKUAtribut;
        }

        return $arrIdSKUAtribut;
    }

    public function getListAturanKonversiSKU()
    {
        $rules          =   ['idBarangSKU' =>  ['label' => 'Id Barang SKU', 'rules' => 'required|alpha_numeric']];
        $messages       =   [
            'idBarangSKU'   => [
                'required'      => 'Data kiriman tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak valid, silakan periksa kembali'
            ]
        ];
            
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());
            
        $barangSKUModel         =   new BarangSKUModel();
        $idBarangSKU            =   hashidDecode($this->request->getVar('idBarangSKU'));
        $listAturanKonversiSKU  =   $barangSKUModel->getDataBarangKonversiAturan($idBarangSKU);

        $listAturanKonversiSKU  =   empty($listAturanKonversiSKU) || count($listAturanKonversiSKU) == 0 ? [] : encodeDatabaseObjectResultKey($listAturanKonversiSKU, ['IDBARANGKONVERSIATURAN', 'IDSATUANASLI', 'IDSATUANTURUNAN']);
        return $this->setResponseFormat('json')->respond(["listAturanKonversiSKU" =>  $listAturanKonversiSKU]);
    }

    public function saveAturanKonversiSKU()
    {
        $rules          =   [
            'idBarangSKU'                                   =>  ['label' => 'Id Barang SKU', 'rules' => 'required|alpha_numeric'],
            'dataAturanKonversi.*.idBarangKonversiAturan'   =>  ['label' => 'Id Barang Konversi Aturan', 'rules' => 'permit_empty|alpha_numeric'],
            'dataAturanKonversi.*.idSatuanAsli'             =>  ['label' => 'Id Satuan Asli', 'rules' => 'permit_empty|alpha_numeric'],
            'dataAturanKonversi.*.idSatuanTurunan'          =>  ['label' => 'Id Satuan Turunan', 'rules' => 'permit_empty|alpha_numeric'],
            'dataAturanKonversi.*.jumlahTurunan'            =>  ['label' => 'Jumlah Turunan', 'rules' => 'permit_empty|numeric|greater_than[0]']
        ];
        $messages       =   [
            'idBarangSKU'   => [
                'required'      =>  'Data kiriman tidak valid, silakan periksa kembali',
                'alpha_numeric' =>  'Data kiriman tidak valid, silakan periksa kembali'
            ],
            'dataAturanKonversi.*.idBarangKonversiAturan'   => [
                'alpha_numeric' =>  'Data kiriman tidak valid, silakan periksa kembali'
            ],
            'dataAturanKonversi.*.idSatuanAsli'     => [
                'alpha_numeric' =>  'Data satuan asli yang dipilih tidak valid, silakan periksa kembali'
            ],
            'dataAturanKonversi.*.idSatuanTurunan'  => [
                'alpha_numeric' =>  'Data satuan turunan yang dipilih tidak valid, silakan periksa kembali'
            ],
        ];
            
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $idBarangSKU        =   $this->request->getVar('idBarangSKU');
        $idBarangSKU        =   isset($idBarangSKU) && $idBarangSKU != "" ? hashidDecode($idBarangSKU) : 0;
        $dataAturanKonversi =   $this->request->getVar('dataAturanKonversi');

        if(!isset($idBarangSKU) || $idBarangSKU <= 0) return throwResponseNotAcceptable("Data kiriman tidak valid, harap periksa kembali");
        if(!isset($dataAturanKonversi) || !is_array($dataAturanKonversi)) return throwResponseNotAcceptable("Data kiriman tidak valid, harap periksa kembali");

        $barangKonversiAturanModel  =   new BarangKonversiAturanModel();
        $arrIdBarangKonversiAturan  =   [0];
        $totalDataSave              =   0;
        $mainOperation              =   new MainOperation();

        foreach($dataAturanKonversi as $keyAturanKonversi){
            $idBarangKonversiAturan =   isset($keyAturanKonversi->idBarangKonversiAturan) && $keyAturanKonversi->idBarangKonversiAturan != "" ? hashidDecode($keyAturanKonversi->idBarangKonversiAturan) : null;
            $idSatuanAsli           =   isset($keyAturanKonversi->idSatuanAsli) ? hashidDecode($keyAturanKonversi->idSatuanAsli) : 0;
            $idSatuanTurunan        =   isset($keyAturanKonversi->idSatuanTurunan) ? hashidDecode($keyAturanKonversi->idSatuanTurunan) : 0;
            $jumlahTurunan          =   isset($keyAturanKonversi->jumlahTurunan) ? intval($keyAturanKonversi->jumlahTurunan) : 1;

            if($idSatuanAsli > 0 && $idSatuanTurunan > 0 && $jumlahTurunan > 0){
                $isBarangKonversiAturanExist    =   $mainOperation->isDataExist('t_barangkonversiaturan', [
                    'IDBARANGSKU'       =>  $idBarangSKU,
                    'IDSATUANASLI'      =>  $idSatuanAsli,
                    'IDSATUANTURUNAN'   =>  $idSatuanTurunan
                ]);

                $idBarangKonversiAturan =   isset($isBarangKonversiAturanExist['IDBARANGKONVERSIATURAN']) && $isBarangKonversiAturanExist['IDBARANGKONVERSIATURAN'] != "" ? $isBarangKonversiAturanExist['IDBARANGKONVERSIATURAN'] : $idBarangKonversiAturan;
                $idBarangKonversiAturan =   is_numeric($idBarangKonversiAturan) ? $idBarangKonversiAturan : null;
                $arrDataInsertUpdate    =   [
                    'IDBARANGKONVERSIATURAN'=>  $idBarangKonversiAturan,
                    'IDBARANGSKU'           =>  $idBarangSKU,
                    'IDSATUANASLI'          =>  $idSatuanAsli,
                    'IDSATUANTURUNAN'       =>  $idSatuanTurunan,
                    'JUMLAHTURUNAN'         =>  $jumlahTurunan
                ];

                $barangKonversiAturanModel->save($arrDataInsertUpdate);
                $affectedRows               =   $barangKonversiAturanModel->db->affectedRows();
                $arrIdBarangKonversiAturan[]=   isset($idBarangKonversiAturan) && $idBarangKonversiAturan != "" && !is_null($idBarangKonversiAturan) ? $idBarangKonversiAturan : $barangKonversiAturanModel->insertID();
                if($affectedRows > 0) $totalDataSave++; 
            }
        }

        $barangKonversiAturanModel->where('IDBARANGSKU', $idBarangSKU)->whereNotIn('IDBARANGKONVERSIATURAN', $arrIdBarangKonversiAturan)->delete();
        $numberOfDeletedRows    =   $barangKonversiAturanModel->db->affectedRows();

        if($totalDataSave <= 0 && $numberOfDeletedRows <= 0) return throwResponseNotAcceptable("Tidak ada perubahan data yang disimpan, harap periksa kembali data kiriman Anda");
        return throwResponseOK($totalDataSave." perubahan data aturan konversi berhasil disimpan");
    }
}