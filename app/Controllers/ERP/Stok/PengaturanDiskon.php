<?php

namespace App\Controllers\ERP\Stok;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\MainOperation;
use App\Models\ERP\Stok\PengaturanDiskonModel;
use App\Models\ERP\Master\BarangSKUModel;

class PengaturanDiskon extends ResourceController
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

    public function getListDiskonRetail()
    {
        $rules  =   [
            'idBarangKategori'  =>  ['label' => 'Id Kategori', 'rules' => 'permit_empty|alpha_numeric'],
            'idBarangMerk'      =>  ['label' => 'Id Merk', 'rules' => 'permit_empty|alpha_numeric'],
            'tipeDiskon'        =>  ['label' => 'Tipe Diskon', 'rules' => 'permit_empty|in_list[1,2]'],
            'statusBerlaku'     =>  ['label' => 'Status Berlaku', 'rules' => 'required|in_list[1,-1]'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idBarangKategori'  =>  [
                'alpha_numeric' =>  'Data kategori tidak valid, silakan periksa kembali'
            ],
            'idBarangMerk'      =>  [
                'alpha_numeric' =>  'Data merk tidak valid, silakan periksa kembali'
            ],
            'tipeDiskon'        =>  [
                'in_list'       =>  'Tipe diskon tidak valid, silakan periksa kembali'
            ],
            'statusBerlaku'     =>  [
                'in_list'       =>  'Status berlaku tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation          =   new MainOperation();
        $pengaturanDiskonModel  =   new PengaturanDiskonModel();
        $idBarangKategori       =   $this->request->getVar('idBarangKategori');
        $idBarangKategori       =   isset($idBarangKategori) && $idBarangKategori != "" ? hashidDecode($idBarangKategori) : 0;
        $idBarangMerk           =   $this->request->getVar('idBarangMerk');
        $idBarangMerk           =   isset($idBarangMerk) && $idBarangMerk != "" ? hashidDecode($idBarangMerk) : 0;
        $tipeDiskon             =   $this->request->getVar('tipeDiskon');
        $statusBerlaku          =   $this->request->getVar('statusBerlaku');
        $kataKunciPencarian     =   $this->request->getVar('kataKunciPencarian');
        $dataPerPage            =   $this->request->getVar('dataPerPage');
        $pageNumber             =   $this->request->getVar('pageNumber');
        $baseData               =   $pengaturanDiskonModel->getListDiskonRetail($idBarangKategori, $idBarangMerk, $tipeDiskon, $statusBerlaku, $kataKunciPencarian);
        $totalNumberData        =   $baseData->countAllResults(false);
        $pageProperty           =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0) {
            $barangSKUModel =   new BarangSKUModel();
            $listData       =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            
            foreach($listData as $keyData){
                $idBarangSKU        =   isset($keyData->IDBARANGSKU) && $keyData->IDBARANGSKU != "" ? $keyData->IDBARANGSKU : 0;
                $idBarangSatuan     =   isset($keyData->IDBARANGSATUAN) && $keyData->IDBARANGSATUAN != "" ? $keyData->IDBARANGSATUAN : 0;
                $tipeDiskon         =   isset($keyData->TIPEDISKON) && $keyData->TIPEDISKON != "" ? $keyData->TIPEDISKON : 0;
                $jumlahDiskon       =   isset($keyData->JUMLAHDISKON) && $keyData->JUMLAHDISKON != "" ? $keyData->JUMLAHDISKON : 0;
                $rerataHargaBeli    =   $pengaturanDiskonModel->getRerataHargaBeliBarangSKU($idBarangSKU);
                $rerataHargaJual    =   $pengaturanDiskonModel->getRerataHargaJualBarangSKU($idBarangSKU, $idBarangSatuan);
                $nominalDiskon      =   $tipeDiskon == 1 ? ($jumlahDiskon / 100) * $rerataHargaJual : $jumlahDiskon;
                $rerataHargaFinal   =   $rerataHargaJual - $nominalDiskon;

                $keyData->ATRIBUTSKU        =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                $keyData->RERATAHARGABELI   =   intval($rerataHargaBeli);
                $keyData->RERATAHARGAJUAL   =   intval($rerataHargaJual);
                $keyData->RERATAHARGAFINAL  =   intval($rerataHargaFinal);
            }

            $listData   =   encodeDatabaseObjectResultKey($listData, ['IDBARANGSKU', 'IDBARANGSATUAN', 'IDDISKONRETAIL']);
            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $listData,
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn     =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty
            ];
            return throwResponseNotFound('Tidak ada data diskon retail yang ditemukan', $dataReturn);
        }
    }

    public function saveDataDiskonRetail()
    {
        $idDiskonRetail =   $this->request->getVar('idDiskonRetail');
        $idDiskonRetail =   $idDiskonRetail != "" ? hashidDecode($idDiskonRetail) : 0;
        $validation     =   $idDiskonRetail == 0 ? $this->parametersValidatorRetail() : $this->parametersValidatorRetail(true);
        
        if($validation !== true) return $this->fail($validation);

        $idBarangSKU    =   $this->request->getVar('idBarangSKU');
        $idBarangSatuan =   $this->request->getVar('idBarangSatuan');
        $tanggalBatas   =   $this->request->getVar('tanggalBatas');
        $arrCheckData   =   [
            'idBarangSKU'     => $idBarangSKU,
            'idBarangSatuan'  => $idBarangSatuan,
            'tanggalBatas'    => $tanggalBatas
        ];
        $idDiskonRetail == 0 ? $this->isDataDiskonRetailValid($arrCheckData) : $this->isDataDiskonRetailValid($arrCheckData, $idDiskonRetail);

        $mainOperation          =   new MainOperation();
        $statusDiskon           =   strtotime($tanggalBatas) > strtotime($this->currentDateTime) ? 1 : -1;
        $arrDataInsertUpdate    =   $this->generateArrayInsertUpdateRetail($statusDiskon);
        $procInsertUpdateData   =   $idDiskonRetail == 0 ?
                                    $mainOperation->insertDataTable('t_diskonretail', $arrDataInsertUpdate) :
                                    $mainOperation->updateDataTable('t_diskonretail', $arrDataInsertUpdate, ['IDDISKONRETAIL' => $idDiskonRetail]);

        if(!$procInsertUpdateData['status']) return switchMySQLErrorCode($procInsertUpdateData['errCode']);
        return throwResponseOK(
            $idDiskonRetail == 0 ? 'Data diskon retail baru telah disimpan' : 'Data diskon retail telah diperbarui'
        );
    }

    private function parametersValidatorRetail($isUpdate = false)
    {
        $rules  =   [
            'idBarangSKU'   =>  ['label' => 'Id Barang SKU', 'rules' => 'required|alpha_numeric'],
            'idBarangSatuan'=>  ['label' => 'Id Barang Satuan', 'rules' => 'required|alpha_numeric'],
            'tipeDiskon'    =>  ['label' => 'Tipe Diskon', 'rules' => 'permit_empty|in_list[1,2]'],
            'jumlahDiskon'  =>  ['label' => 'Jumlah Diskon', 'rules' => 'required|integer|greater_than[0]'],
            'deskripsi'     =>  ['label' => 'Deskripsi', 'rules' => 'required|alpha_numeric_punct|min_length[8]|max_length[150]'],
            'tanggalBatas'  =>  ['label' => 'Tanggal Batas', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]']
        ];

        $messages   =   [
            'idBarangSKU'   => [
                'required'      => 'Harap pilih SKU Barang terlebih dahulu',
                'alpha_numeric' => 'Data kiriman [SKU Barang] tidak lengkap, silakan periksa kembali'
            ],
            'idBarangSatuan'=> [
                'required'      => 'Harap pilih Satuan Barang terlebih dahulu',
                'alpha_numeric' => 'Data kiriman [Satuan Barang] tidak lengkap, silakan periksa kembali'
            ],
            'tipeDiskon'    => [
                'in_list'   => 'Data kiriman [Tipe Diskon] tidak valid, silakan periksa kembali'
            ],
            'tanggalBatas'  => [
                'in_list'   => 'Data kiriman [Tanggal Batas] tidak valid, format tidak sesuai'
            ],
        ];

        if($isUpdate) {
            $rules['idDiskonRetail']['rules']             =   'required|alpha_numeric';
            $messages['idDiskonRetail']['required']       =   'Data kiriman tidak lengkap, silakan periksa kembali';
            $messages['idDiskonRetail']['alpha_numeric']  =   'Data kiriman tidak lengkap, silakan periksa kembali';
        }

        if(!$this->validate($rules, $messages)) return $this->validator->getErrors();

        $tipeDiskon     =   $this->request->getVar('tipeDiskon');
        $jumlahDiskon   =   $this->request->getVar('jumlahDiskon');

        switch($tipeDiskon){
            case 1:
                if($jumlahDiskon > 100) return throwResponseNotAcceptable('Jumlah diskon persentase harus di antara 1 sampai dengan 100'); break;
            case 2:
                if($jumlahDiskon < 1000) return throwResponseNotAcceptable('Jumlah diskon nominal harus lebih besar dari 1.000 rupiah'); break;
            default:
                break;
        }
        return true;
    }
    
    private function isDataDiskonRetailValid($arrCheckData, $idDiskonRetail = 0)
    {
        $pengaturanDiskonModel  =   new PengaturanDiskonModel();
        $idBarangSKU            =   $arrCheckData['idBarangSKU'] ?? 0;
        $idBarangSatuan         =   $arrCheckData['idBarangSatuan'] ?? 0;
        $tanggalBatas           =   $arrCheckData['tanggalBatas'] ?? 0;
        $isDataDiskonRetailValid=   $pengaturanDiskonModel->isDataDiskonRetailValid($idBarangSKU, $idBarangSatuan, $tanggalBatas, $idDiskonRetail);

        if(!$isDataDiskonRetailValid) return throwResponseNotAcceptable('Data diskon retail untuk barang dan satuan tersebut dengan tanggal batas lebih dari/sama dengan yang dimasukkan sudah ada.<br/>Harap periksa kembali data inputan');
    }

    private function generateArrayInsertUpdateRetail($status): array
    {
        $idBarangSKU    =   $this->request->getVar('idBarangSKU');
        $idBarangSKU    =   $idBarangSKU != "" ? hashidDecode($idBarangSKU) : 0;
        $idBarangSatuan =   $this->request->getVar('idBarangSatuan');
        $idBarangSatuan =   $idBarangSatuan != "" ? hashidDecode($idBarangSatuan) : 0;
        $tipeDiskon     =   $this->request->getVar('tipeDiskon');
        $jumlahDiskon   =   $this->request->getVar('jumlahDiskon');
        $deskripsi      =   $this->request->getVar('deskripsi');
        $tanggalBatas   =   $this->request->getVar('tanggalBatas');

        return [
            'IDBARANGSKU'       =>  $idBarangSKU,
            'IDBARANGSATUAN'    =>  $idBarangSatuan,
            'TIPEDISKON'        =>  $tipeDiskon,
            'JUMLAHDISKON'      =>  $jumlahDiskon,
            'DESKRIPSI'         =>  $deskripsi,
            'TANGGALBATAS'      =>  $tanggalBatas,
            'INPUTUSER'         =>  $this->userData->name.' ('.$this->userData->userLevelName.')',
            'INPUTTANGGALWAKTU' =>  $this->currentDateTime,
            'STATUS'            =>  $status
        ];
    }

    public function getListDiskonEvent()
    {
        $rules  =   [
            'tipeDiskon'        =>  ['label' => 'Tipe Diskon', 'rules' => 'permit_empty|in_list[1,2]'],
            'levelDiskon'       =>  ['label' => 'Level Diskon', 'rules' => 'permit_empty|in_list[0,1]'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'tipeDiskon'        =>  [
                'in_list'       =>  'Tipe diskon tidak valid, silakan periksa kembali'
            ],
            'levelDiskon'       =>  [
                'in_list'       =>  'Level diskon tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation          =   new MainOperation();
        $pengaturanDiskonModel  =   new PengaturanDiskonModel();
        $tipeDiskon             =   $this->request->getVar('tipeDiskon');
        $levelDiskon            =   $this->request->getVar('levelDiskon');
        $kataKunciPencarian     =   $this->request->getVar('kataKunciPencarian');
        $dataPerPage            =   $this->request->getVar('dataPerPage');
        $pageNumber             =   $this->request->getVar('pageNumber');
        $baseData               =   $pengaturanDiskonModel->getListDiskonEvent($tipeDiskon, $levelDiskon, $kataKunciPencarian);
        $totalNumberData        =   $baseData->countAllResults(false);
        $pageProperty           =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0) {
            $listData       =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            foreach($listData as $keyData){
                $arrIdToko      =   isset($keyData->ARRIDTOKO) && $keyData->ARRIDTOKO != "" ? json_decode($keyData->ARRIDTOKO) : [];
                $arrDataIdToko  =   [];
                foreach($arrIdToko as $keyIdToko){
                    $detailToko     =   $mainOperation->getDetailToko($keyIdToko);
                    $idTokoEncode   =   $keyIdToko != "" ? hashidEncode($keyIdToko) : "";
                    if($idTokoEncode != "") {
                        $arrDataIdToko[]    =   [
                            'IDTOKO'    =>  $idTokoEncode,
                            'NAMATOKO'  =>  isset($detailToko['NAMA']) ? $detailToko['NAMA'] : ""
                        ];
                    }
                }
                $keyData->ARRIDTOKO     =   $arrDataIdToko;
                $keyData->TIPEDISKON    =   intval($keyData->TIPEDISKON);
                $keyData->JUMLAHDISKON  =   intval($keyData->JUMLAHDISKON);
                $keyData->LEVELDISKON   =   intval($keyData->LEVELDISKON);
            }

            $listData   =   encodeDatabaseObjectResultKey($listData, ['IDDISKONEVENT']);
            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $listData,
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn     =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty
            ];
            return throwResponseNotFound('Tidak ada data diskon event yang ditemukan', $dataReturn);
        }
    }

    public function saveDataDiskonEvent()
    {
        $idDiskonEvent  =   $this->request->getVar('idDiskonEvent');
        $idDiskonEvent  =   $idDiskonEvent != "" ? hashidDecode($idDiskonEvent) : 0;
        $validation     =   $idDiskonEvent == 0 ? $this->parametersValidatorEvent() : $this->parametersValidatorEvent(true);

        if($validation !== true) return $this->fail($validation);

        $mainOperation          =   new MainOperation();
        $arrDataInsertUpdate    =   $this->generateArrayInsertUpdateEvent();
        $procInsertUpdateData   =   $idDiskonEvent == 0 ?
                                    $mainOperation->insertDataTable('t_diskonevent', $arrDataInsertUpdate) :
                                    $mainOperation->updateDataTable('t_diskonevent', $arrDataInsertUpdate, ['IDDISKONEVENT' => $idDiskonEvent]);

        if(!$procInsertUpdateData['status']) return switchMySQLErrorCode($procInsertUpdateData['errCode']);
        return throwResponseOK(
            $idDiskonEvent == 0 ? 'Data diskon event baru telah disimpan' : 'Data diskon event telah diperbarui'
        );
    }

    private function parametersValidatorEvent($isUpdate = false)
    {
        $rules  =   [
            'arrIdTokoBerlaku'  =>  ['label' => 'Daftar Toko Berlaku', 'rules' => 'required|is_array'],
            'namaEvent'         =>  ['label' => 'Nama Event', 'rules' => 'required|alpha_numeric_punct|min_length[8]|max_length[100]'],
            'deskripsi'         =>  ['label' => 'Deskripsi', 'rules' => 'required|alpha_numeric_punct|min_length[8]|max_length[255]'],
            'tipeDiskon'        =>  ['label' => 'Tipe Diskon', 'rules' => 'required|in_list[1,2]'],
            'jumlahDiskon'      =>  ['label' => 'Jumlah Diskon', 'rules' => 'required|integer|greater_than[0]'],
            'tanggalAwal'       =>  ['label' => 'Tanggal Awal', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'      =>  ['label' => 'Tanggal Akhir', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'levelDiskon'       =>  ['label' => 'Level Diskon', 'rules' => 'required|in_list[0,1]'],
        ];

        $messages   =   [
            'arrIdTokoBerlaku'  => [
                'is_array'  => 'Daftar toko berlaku tidak valid, silakan periksa kembali'
            ],
            'tipeDiskon'    => [
                'in_list'   => 'Data kiriman [Tipe Diskon] tidak valid, silakan periksa kembali'
            ],
            'levelDiskon'   => [
                'in_list'   => 'Data kiriman [Level Diskon] tidak valid, silakan periksa kembali'
            ],
            'tanggalAwal'   => [
                'regex_match'   => 'Data kiriman [Tanggal Awal Berlaku] tidak valid, format tidak sesuai'
            ],
            'tanggalAkhir'  => [
                'regex_match'   => 'Data kiriman [Tanggal Akhir Berlaku] tidak valid, format tidak sesuai'
            ],
        ];

        if($isUpdate) {
            $rules['idDiskonEvent']['rules']             =   'required|alpha_numeric';
            $messages['idDiskonEvent']['required']       =   'Data kiriman tidak lengkap, silakan periksa kembali';
            $messages['idDiskonEvent']['alpha_numeric']  =   'Data kiriman tidak lengkap, silakan periksa kembali';
        }

        if(!$this->validate($rules, $messages)) return $this->validator->getErrors();

        $tipeDiskon     =   $this->request->getVar('tipeDiskon');
        $jumlahDiskon   =   $this->request->getVar('jumlahDiskon');

        switch($tipeDiskon){
            case 1:
                if($jumlahDiskon > 100) return throwResponseNotAcceptable('Jumlah diskon persentase harus di antara 1 sampai dengan 100'); break;
            case 2:
                if($jumlahDiskon < 1000) return throwResponseNotAcceptable('Jumlah diskon nominal harus lebih besar dari 1.000 rupiah'); break;
            default:
                break;
        }
        return true;
    }

    private function generateArrayInsertUpdateEvent(): array
    {
        $arrIdTokoBerlaku   =   $this->request->getVar('arrIdTokoBerlaku');
        $arrIdTokoBerlaku   =   $this->getJsonDataArrIdTokoBerlaku($arrIdTokoBerlaku);
        $namaEvent          =   $this->request->getVar('namaEvent');
        $deskripsi          =   $this->request->getVar('deskripsi');
        $tipeDiskon         =   $this->request->getVar('tipeDiskon');
        $jumlahDiskon       =   $this->request->getVar('jumlahDiskon');
        $tanggalAwal        =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir       =   $this->request->getVar('tanggalAkhir');
        $levelDiskon        =   $this->request->getVar('levelDiskon');

        return [
            'ARRIDTOKO'             =>  $arrIdTokoBerlaku,
            'NAMAEVENT'             =>  $namaEvent,
            'DESKRIPSI'             =>  $deskripsi,
            'TIPEDISKON'            =>  $tipeDiskon,
            'JUMLAHDISKON'          =>  $jumlahDiskon,
            'TANGGALBERLAKUAWAL'    =>  $tanggalAwal,
            'TANGGALBERLAKUAKHIR'   =>  $tanggalAkhir,
            'ISDISKONPERITEM'       =>  $levelDiskon,
            'INPUTUSER'             =>  $this->userData->name.' ('.$this->userData->userLevelName.')',
            'INPUTTANGGALWAKTU'     =>  $this->currentDateTime,
        ];
    }

    public function getListDiskonGrosir()
    {
        $rules  =   [
            'idBarangKategori'  =>  ['label' => 'Id Kategori', 'rules' => 'permit_empty|alpha_numeric'],
            'idBarangMerk'      =>  ['label' => 'Id Merk', 'rules' => 'permit_empty|alpha_numeric'],
            'tipeDiskon'        =>  ['label' => 'Tipe Diskon', 'rules' => 'permit_empty|in_list[1,2]'],
            'statusBerlaku'     =>  ['label' => 'Status Berlaku', 'rules' => 'required|in_list[1,-1]'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idBarangKategori'  =>  [
                'alpha_numeric' =>  'Data kategori tidak valid, silakan periksa kembali'
            ],
            'idBarangMerk'      =>  [
                'alpha_numeric' =>  'Data merk tidak valid, silakan periksa kembali'
            ],
            'tipeDiskon'        =>  [
                'in_list'       =>  'Tipe diskon tidak valid, silakan periksa kembali'
            ],
            'statusBerlaku'     =>  [
                'in_list'       =>  'Status berlaku tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation          =   new MainOperation();
        $pengaturanDiskonModel  =   new PengaturanDiskonModel();
        $idBarangKategori       =   $this->request->getVar('idBarangKategori');
        $idBarangKategori       =   isset($idBarangKategori) && $idBarangKategori != "" ? hashidDecode($idBarangKategori) : 0;
        $idBarangMerk           =   $this->request->getVar('idBarangMerk');
        $idBarangMerk           =   isset($idBarangMerk) && $idBarangMerk != "" ? hashidDecode($idBarangMerk) : 0;
        $tipeDiskon             =   $this->request->getVar('tipeDiskon');
        $statusBerlaku          =   $this->request->getVar('statusBerlaku');
        $kataKunciPencarian     =   $this->request->getVar('kataKunciPencarian');
        $dataPerPage            =   $this->request->getVar('dataPerPage');
        $pageNumber             =   $this->request->getVar('pageNumber');
        $baseData               =   $pengaturanDiskonModel->getListDiskonGrosir($idBarangKategori, $idBarangMerk, $tipeDiskon, $statusBerlaku, $kataKunciPencarian);
        $totalNumberData        =   $baseData->countAllResults(false);
        $pageProperty           =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0) {
            $barangSKUModel =   new BarangSKUModel();
            $listData       =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            
            foreach($listData as $keyData){
                $idBarangSKU        =   isset($keyData->IDBARANGSKU) && $keyData->IDBARANGSKU != "" ? $keyData->IDBARANGSKU : 0;
                $idBarangSatuan     =   isset($keyData->IDBARANGSATUAN) && $keyData->IDBARANGSATUAN != "" ? $keyData->IDBARANGSATUAN : 0;
                $tipeDiskon         =   isset($keyData->TIPEDISKON) && $keyData->TIPEDISKON != "" ? $keyData->TIPEDISKON : 0;
                $jumlahDiskon       =   isset($keyData->JUMLAHDISKON) && $keyData->JUMLAHDISKON != "" ? $keyData->JUMLAHDISKON : 0;
                $rerataHargaBeli    =   $pengaturanDiskonModel->getRerataHargaBeliBarangSKU($idBarangSKU);
                $rerataHargaJual    =   $pengaturanDiskonModel->getRerataHargaJualGrosirBarangSKU($idBarangSKU, $idBarangSatuan);
                $nominalDiskon      =   $tipeDiskon == 1 ? ($jumlahDiskon / 100) * $rerataHargaJual : $jumlahDiskon;
                $rerataHargaFinal   =   $rerataHargaJual - $nominalDiskon;

                $keyData->ATRIBUTSKU        =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                $keyData->RERATAHARGABELI   =   intval($rerataHargaBeli);
                $keyData->RERATAHARGAJUAL   =   intval($rerataHargaJual);
                $keyData->RERATAHARGAFINAL  =   intval($rerataHargaFinal);

                $arrIdTokoBerlaku   =   isset($keyData->ARRIDTOKOBERLAKU) && $keyData->ARRIDTOKOBERLAKU != "" ? json_decode($keyData->ARRIDTOKOBERLAKU) : [];
                $arrDataIdToko      =   [];
                foreach($arrIdTokoBerlaku as $keyIdToko){
                    $detailToko     =   $mainOperation->getDetailToko($keyIdToko);
                    $idTokoEncode   =   $keyIdToko != "" ? hashidEncode($keyIdToko) : "";
                    if($idTokoEncode != "") {
                        $arrDataIdToko[]    =   [
                            'IDTOKO'    =>  $idTokoEncode,
                            'NAMATOKO'  =>  isset($detailToko['NAMA']) ? $detailToko['NAMA'] : ""
                        ];
                    }
                }
                $keyData->ARRIDTOKOBERLAKU  =   $arrDataIdToko;
            }

            $listData   =   encodeDatabaseObjectResultKey($listData, ['IDBARANGSKU', 'IDBARANGSATUAN', 'IDDISKONGROSIR']);
            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $listData,
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn     =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty
            ];
            return throwResponseNotFound('Tidak ada data diskon grosir yang ditemukan', $dataReturn);
        }
    }

    public function saveDataDiskonGrosir()
    {
        $idDiskonGrosir =   $this->request->getVar('idDiskonGrosir');
        $idDiskonGrosir =   $idDiskonGrosir != "" ? hashidDecode($idDiskonGrosir) : 0;
        $validation     =   $idDiskonGrosir == 0 ? $this->parametersValidatorGrosir() : $this->parametersValidatorGrosir(true);

        if($validation !== true) return $this->fail($validation);

        $idBarangSKU        =   $this->request->getVar('idBarangSKU');
        $idBarangSatuan     =   $this->request->getVar('idBarangSatuan');
        $arrIdTokoBerlaku   =   $this->request->getVar('arrIdTokoBerlaku');
        $arrIdTokoBerlaku   =   $this->getJsonDataArrIdTokoBerlaku($arrIdTokoBerlaku);
        $tanggalBatas       =   $this->request->getVar('tanggalBatas');
        $arrCheckData       =   [
            'idBarangSKU'     => $idBarangSKU,
            'idBarangSatuan'  => $idBarangSatuan,
            'arrIdTokoBerlaku'=> $arrIdTokoBerlaku,
            'tanggalBatas'    => $tanggalBatas
        ];
        $idDiskonGrosir == 0 ? $this->isDataDiskonGrosirValid($arrCheckData) : $this->isDataDiskonGrosirValid($arrCheckData, $idDiskonGrosir);

        $mainOperation          =   new MainOperation();
        $statusDiskon           =   strtotime($tanggalBatas) > strtotime($this->currentDateTime) ? 1 : -1;
        $arrDataInsertUpdate    =   $this->generateArrayInsertUpdateGrosir($statusDiskon);
        $procInsertUpdateData   =   $idDiskonGrosir == 0 ? $mainOperation->insertDataTable('t_diskongrosir', $arrDataInsertUpdate) : $mainOperation->updateDataTable('t_diskongrosir', $arrDataInsertUpdate, ['IDDISKONGROSIR' => $idDiskonGrosir]);

        if(!$procInsertUpdateData['status']) return switchMySQLErrorCode($procInsertUpdateData['errCode']);
        return throwResponseOK(
            $idDiskonGrosir == 0 ? 'Data diskon grosir baru telah disimpan' : 'Data diskon grosir telah diperbarui'
        );
    }

    private function parametersValidatorGrosir($isUpdate = false)
    {
        $rules  =   [
            'idBarangSKU'       =>  ['label' => 'Id Barang SKU', 'rules' => 'required|alpha_numeric'],
            'idBarangSatuan'    =>  ['label' => 'Id Barang Satuan', 'rules' => 'required|alpha_numeric'],
            'arrIdTokoBerlaku'  =>  ['label' => 'Daftar Toko Berlaku', 'rules' => 'required|is_array'],
            'tipeDiskon'        =>  ['label' => 'Tipe Diskon', 'rules' => 'required|in_list[1,2]'],
            'jumlahDiskon'      =>  ['label' => 'Jumlah Diskon', 'rules' => 'required|integer|greater_than[0]'],
            'deskripsi'         =>  ['label' => 'Deskripsi', 'rules' => 'required|alpha_numeric_punct|min_length[8]|max_length[150]'],
            'minimalItem'       =>  ['label' => 'Minimal Item', 'rules' => 'required|integer|greater_than[0]|less_than[999]'],
            'tanggalBatas'      =>  ['label' => 'Tanggal Batas', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]']
        ];

        $messages   =   [
            'idBarangSKU'   => [
                'required'      => 'Harap pilih SKU Barang terlebih dahulu',
                'alpha_numeric' => 'Data kiriman [SKU Barang] tidak lengkap, silakan periksa kembali'
            ],
            'idBarangSatuan'=> [
                'required'      => 'Harap pilih Satuan Barang terlebih dahulu',
                'alpha_numeric' => 'Data kiriman [Satuan Barang] tidak lengkap, silakan periksa kembali'
            ],
            'arrIdTokoBerlaku'=> [
                'is_array'      => 'Daftar toko berlaku tidak valid, silakan periksa kembali'
            ],
            'tipeDiskon'    => [
                'in_list'   => 'Data kiriman [Tipe Diskon] tidak valid, silakan periksa kembali'
            ],
            'tanggalBatas'  => [
                'in_list'   => 'Data kiriman [Tanggal Batas] tidak valid, format tidak sesuai'
            ],
        ];

        if($isUpdate) {
            $rules['idDiskonGrosir']['rules']             =   'required|alpha_numeric';
            $messages['idDiskonGrosir']['required']       =   'Data kiriman tidak lengkap, silakan periksa kembali';
            $messages['idDiskonGrosir']['alpha_numeric']  =   'Data kiriman tidak lengkap, silakan periksa kembali';
        }

        if(!$this->validate($rules, $messages)) return $this->validator->getErrors();

        $tipeDiskon     =   $this->request->getVar('tipeDiskon');
        $jumlahDiskon   =   $this->request->getVar('jumlahDiskon');

        switch($tipeDiskon){
            case 1:
                if($jumlahDiskon > 100) return throwResponseNotAcceptable('Jumlah diskon persentase harus di antara 1 sampai dengan 100'); break;
            case 2:
                if($jumlahDiskon < 1000) return throwResponseNotAcceptable('Jumlah diskon nominal harus lebih besar dari 1.000 rupiah'); break;
            default:
                break;
        }
        return true;
    }
    
    private function isDataDiskonGrosirValid($arrCheckData, $idDiskonGrosir = 0)
    {
        $pengaturanDiskonModel  =   new PengaturanDiskonModel();
        $idBarangSKU            =   $arrCheckData['idBarangSKU'] ?? 0;
        $idBarangSatuan         =   $arrCheckData['idBarangSatuan'] ?? 0;
        $arrIdTokoBerlaku       =   $arrCheckData['arrIdTokoBerlaku'] ?? '[]';
        $tanggalBatas           =   $arrCheckData['tanggalBatas'] ?? 0;
        $isDataDiskonGrosirValid=   $pengaturanDiskonModel->isDataDiskonGrosirValid($idBarangSKU, $idBarangSatuan, $arrIdTokoBerlaku, $tanggalBatas, $idDiskonGrosir);

        if(!$isDataDiskonGrosirValid) return throwResponseNotAcceptable('Data diskon grosir untuk barang dan satuan tersebut dengan tanggal batas lebih dari/sama dengan yang dimasukkan sudah ada.<br/>Harap periksa kembali data inputan');
    }

    private function generateArrayInsertUpdateGrosir($status): array
    {
        $idBarangSKU        =   $this->request->getVar('idBarangSKU');
        $idBarangSKU        =   $idBarangSKU != "" ? hashidDecode($idBarangSKU) : 0;
        $idBarangSatuan     =   $this->request->getVar('idBarangSatuan');
        $idBarangSatuan     =   $idBarangSatuan != "" ? hashidDecode($idBarangSatuan) : 0;
        $arrIdTokoBerlaku   =   $this->request->getVar('arrIdTokoBerlaku');
        $arrIdTokoBerlaku   =   $this->getJsonDataArrIdTokoBerlaku($arrIdTokoBerlaku);
        $tipeDiskon         =   $this->request->getVar('tipeDiskon');
        $jumlahDiskon       =   $this->request->getVar('jumlahDiskon');
        $deskripsi          =   $this->request->getVar('deskripsi');
        $minimalItem        =   $this->request->getVar('minimalItem');
        $tanggalBatas       =   $this->request->getVar('tanggalBatas');

        return [
            'IDBARANGSKU'       =>  $idBarangSKU,
            'IDBARANGSATUAN'    =>  $idBarangSatuan,
            'ARRIDTOKOBERLAKU'  =>  $arrIdTokoBerlaku,
            'TIPEDISKON'        =>  $tipeDiskon,
            'JUMLAHDISKON'      =>  $jumlahDiskon,
            'DESKRIPSI'         =>  $deskripsi,
            'MINIMALITEM'       =>  $minimalItem,
            'TANGGALBATAS'      =>  $tanggalBatas,
            'INPUTUSER'         =>  $this->userData->name.' ('.$this->userData->userLevelName.')',
            'INPUTTANGGALWAKTU' =>  $this->currentDateTime,
            'STATUS'            =>  $status
        ];
    }

    private function getJsonDataArrIdTokoBerlaku($arrIdTokoBerlaku)
    {
        if(is_array($arrIdTokoBerlaku) && count($arrIdTokoBerlaku) > 0) {
            $arrIdTokoBerlaku   =   array_map(function($idTokoBerlaku) {
                $idTokoBerlaku  =   hashidDecode($idTokoBerlaku);
                if(!$idTokoBerlaku) return throwResponseNotAcceptable('Data kiriman [Daftar Toko Berlaku] tidak valid, silakan periksa kembali');
                return $idTokoBerlaku;
            }, $arrIdTokoBerlaku);
        } else {
            $arrIdTokoBerlaku   =   [];
        }

        return json_encode($arrIdTokoBerlaku);
    }
}