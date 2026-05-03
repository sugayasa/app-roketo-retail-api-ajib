<?php

namespace App\Controllers\ERP\Master;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\MainOperation;
use App\Models\ERP\Master\TokoModel;

class Toko extends ResourceController
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
            'idGudang'              =>  ['label' => 'Id Gudang', 'rules' => 'permit_empty|alpha_numeric'],
            'idKelompokHargaGrosir' =>  ['label' => 'Id Kelompok Harga Grosir', 'rules' => 'permit_empty|alpha_numeric'],
            'statusEksternal'       =>  ['label' => 'Status Eksternal', 'rules' => 'permit_empty|in_list[0,1]'],
            'searchKeyword'         =>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'dataPerPage'           =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'            =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idGudang'  =>  [
                'alpha_numeric' =>  'Gudang yang dipilih tidak valid, silakan periksa kembali'
            ],
            'idKelompokHargaGrosir'  =>  [
                'alpha_numeric' =>  'Kelompok harga grosir yang dipilih tidak valid, silakan periksa kembali'
            ],
            'statusEksternal'  =>  [
            'in_list' =>  'Status eksternal yang dipilih tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $tokoModel              =   new TokoModel();
        $mainOperation          =   new MainOperation();
        $idGudang               =   $this->request->getVar('idGudang');
        $idGudang               =   $idGudang != "" ? hashidDecode($idGudang) : 0;
        $idKelompokHargaGrosir  =   $this->request->getVar('idKelompokHargaGrosir');
        $idKelompokHargaGrosir  =   $idKelompokHargaGrosir != "" ? hashidDecode($idKelompokHargaGrosir) : 0;
        $statusEksternal        =   $this->request->getVar('statusEksternal');
        $searchKeyword          =   $this->request->getVar('searchKeyword');
        $dataPerPage            =   $this->request->getVar('dataPerPage');
        $pageNumber             =   $this->request->getVar('pageNumber');
        $baseData               =	$tokoModel->getListToko($statusEksternal, $idGudang, $idKelompokHargaGrosir, $searchKeyword);
        $totalNumberData        =   $baseData->countAllResults(false);
        $pageProperty           =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0) {
            $listData   =   $baseData->orderBy('NAMA', 'ASC')->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);

            foreach($listData as $data) {
                $arrIdBarangKategori=   json_decode($data->ARRIDBARANGKATEGORI, true);
                $arrIdTokoTerdekat  =   json_decode($data->ARRIDTOKOTERDEKAT, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $data->ARRIDBARANGKATEGORI  =   []; 
                } else {
                    $arrIdBarangKategoriReturned=   [];
                    foreach($arrIdBarangKategori as $arrBarangKategori) {
                        if(!is_null($arrBarangKategori) && gettype($arrBarangKategori) !== 'NULL') {
                            $arrIdBarangKategoriReturned[]  =   [
                                'IDBARANGKATEGORI'  =>  hashidEncode($arrBarangKategori['IDBARANGKATEGORI']),
                                'NAMAKATEGORI'      =>  $arrBarangKategori['NAMAKATEGORI']
                            ];
                        }
                    }
                    $data->ARRIDBARANGKATEGORI  =   $arrIdBarangKategoriReturned;
                }

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $data->ARRIDTOKOTERDEKAT  =   []; 
                } else {
                    $arrIdTokoTerdekatReturned=   [];
                    foreach($arrIdTokoTerdekat as $arrTokoTerdekat) {
                        if(!is_null($arrTokoTerdekat) && gettype($arrTokoTerdekat) !== 'NULL') {
                            $arrIdTokoTerdekatReturned[]  =   [
                                'IDTOKO'    =>  hashidEncode($arrTokoTerdekat['IDTOKO']),
                                'NAMATOKO'  =>  $arrTokoTerdekat['NAMATOKO']
                            ];
                        }
                    }
                    $data->ARRIDTOKOTERDEKAT    =   $arrIdTokoTerdekatReturned;
                }
            }

            $listData   =   encodeDatabaseObjectResultKey($listData, ['IDTOKO', 'IDGUDANG', 'IDUSERADMINKEPALATOKO', 'IDKELOMPOKHARGAGROSIR']);
            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $listData,
                "pageProperty"  =>  $pageProperty
            ]);
        } else {
            $dataReturn =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty
            ];
            return throwResponseNotFound('Tidak ada data yang ditemukan', $dataReturn);
        }
    }

    public function saveData()
    {
        helper(['form']);
        $idToko    =   $this->request->getVar('idToko');
        $idToko    =   $idToko != "" ? hashidDecode($idToko) : 0;
        $validation=   $idToko == 0 ? $this->parametersValidatorToko() : $this->parametersValidatorToko(true, $idToko);

        if($validation !== true) return $this->fail($validation);
        return $idToko == 0 ? $this->insertData() : $this->updateData($idToko);
    }

    private function insertData()
    {
        $tokoModel      =   new TokoModel();
        $mainOperation  =   new MainOperation();
        $arrInsertData  =   $this->generateArrayInsertUpdate();
        $procInsertData =   $mainOperation->insertDataTable('m_toko', $arrInsertData);

        if(!$procInsertData['status']) return switchMySQLErrorCode($procInsertData['errCode']);
        $idToko         =   $procInsertData['insertID'];
        $dataTokoAll    =   $tokoModel->select('IDTOKO, ARRIDTOKOTERDEKAT')->asObject()->findAll();

        foreach($dataTokoAll as $dataToko) {
            $arrIdTokoTerdekat  =   $dataToko->ARRIDTOKOTERDEKAT;
            $arrIdTokoTerdekat  =   $arrIdTokoTerdekat != null && $arrIdTokoTerdekat != "null" && $arrIdTokoTerdekat != "" ? $arrIdTokoTerdekat : [];
            $idTokoUpdate       =   $dataToko->IDTOKO;

            if($idTokoUpdate == $idToko) {
                $strArrTokoAll  =   $tokoModel->select('GROUP_CONCAT(IDTOKO) AS ARRTOKOALL')->where('IDTOKO != ', $idToko)->get()->getRowObject()->ARRTOKOALL;
                if($strArrTokoAll != null && $strArrTokoAll != "") $arrIdTokoTerdekat   =   array_map('intval', explode(',', $strArrTokoAll));
            } else {
                if($arrIdTokoTerdekat != null && $arrIdTokoTerdekat != "") $arrIdTokoTerdekat  =   json_decode($arrIdTokoTerdekat);
                if(!in_array($idToko, $arrIdTokoTerdekat)) $arrIdTokoTerdekat[] =   $idToko;
            }

            $tokoModel->update($idTokoUpdate, ['ARRIDTOKOTERDEKAT' => json_encode($arrIdTokoTerdekat)]);
        }

        return throwResponseOK(
            'Data toko telah disimpan',
            ['idToko'  =>  hashidEncode($idToko)]
        );
    }

    private function updateData($idToko)
    {
        $mainOperation  =   new MainOperation();
        $arrUpdateData  =   $this->generateArrayInsertUpdate();
        $procUpdateData =   $mainOperation->updateDataTable('m_toko', $arrUpdateData, ['IDTOKO' => $idToko]);

        if(!$procUpdateData['status']) return switchMySQLErrorCode($procUpdateData['errCode']);
        return throwResponseOK(
            'Data toko telah diperbarui',
            ['idToko'  =>  hashidEncode($idToko)]
        );
    }

    private function parametersValidatorToko($isUpdate = false, $idToko = null)
    {
        $rules  =   [
            'statusEksternal'       =>  ['label' => 'Status Eksternal', 'rules' => 'required|in_list[0,1]'],
            'idGudang'              =>  ['label' => 'Gudang', 'rules' => 'required|alpha_numeric'],
            'idUserAdminKepalaToko' =>  ['label' => 'Kepala Toko', 'rules' => 'required|alpha_numeric'],
            'idKelompokHargaGrosir' =>  ['label' => 'Kelompok Harga Grosir', 'rules' => 'required|alpha_numeric'],
            'kodeToko'              =>  ['label' => 'Kode Toko', 'rules' => 'required|alpha_numeric|min_length[1]|max_length[10]'],
            'namaToko'              =>  ['label' => 'Nama Toko', 'rules' => 'required|string|min_length[3]|max_length[50]'],
            'alamatToko'            =>  ['label' => 'Alamat Toko', 'rules' => 'required|string|min_length[20]|max_length[150]'],
            'arrIdBarangKategori'   =>  ['label' => 'Daftar Kategori Barang', 'rules' => 'required'],
            'arrIdBarangKategori.*' =>  ['label' => 'Daftar Kategori Barang', 'rules' => 'alpha_numeric'],
        ];

        $messages   =   [
            'statusEksternal'   =>   [
                'in_list' =>  'Status internal/eksternal yang dipilih tidak valid, silakan periksa kembali'
            ],
            'idGudang'  =>  [
                'required'      => 'Harap pilih gudang terlebih dahulu',
                'alpha_numeric' => 'Data kiriman tidak lengkap, silakan periksa kembali'
            ],
            'idUserAdminKepalaToko' =>    [
                'required'      => 'Harap pilih kepala toko terlebih dahulu',
                'alpha_numeric' => 'Data kiriman tidak lengkap, silakan periksa kembali'
            ],
            'idKelompokHargaGrosir' =>    [
                'required'      => 'Harap pilih kelompok harga grosir terlebih dahulu',
                'alpha_numeric' => 'Data kiriman tidak lengkap, silakan periksa kembali'
            ],
            'arrIdBarangKategori'   =>  [
                'required'      => 'Pilih minimal satu kategori barang untuk toko',
            ],
            'arrIdBarangKategori.*' =>  [
                'alpha_numeric' => 'Pilih minimal satu kategori barang untuk toko'
            ]
        ];

        if($isUpdate) {
            $rules['kodeToko']['rules']           .=  '|is_unique[m_toko.KODE, IDTOKO, '.$idToko.']';
            $rules['namaToko']['rules']           .=  '|is_unique[m_toko.NAMA, IDTOKO, '.$idToko.']';
            $rules['idToko']['rules']             =   'required|alpha_numeric';
            $messages['idToko']['required']       =   'Data kiriman tidak lengkap, silakan periksa kembali';
            $messages['idToko']['alpha_numeric']  =   'Data kiriman tidak lengkap, silakan periksa kembali';
        } else {
            $rules['kodeToko']['rules']           .=  '|is_unique[m_toko.KODE]';
            $rules['namaToko']['rules']           .=  '|is_unique[m_toko.NAMA]';
        }

        if(!$this->validate($rules, $messages)) return $this->validator->getErrors();
        return true;
    }

    private function generateArrayInsertUpdate(): array
    {
        $statusEksternal        =   $this->request->getVar('statusEksternal');
        $idGudang               =   $this->request->getVar('idGudang');
        $idGudang               =   $idGudang != "" ? hashidDecode($idGudang) : 0;
        $idUserAdmin            =   $this->request->getVar('idUserAdmin');
        $idUserAdmin            =   $idUserAdmin != "" ? hashidDecode($idUserAdmin) : 0;
        $idKelompokHargaGrosir  =   $this->request->getVar('idKelompokHargaGrosir');
        $idKelompokHargaGrosir  =   $idKelompokHargaGrosir != "" ? hashidDecode($idKelompokHargaGrosir) : 0;
        $kodeToko               =   $this->request->getVar('kodeToko');
        $namaToko               =   $this->request->getVar('namaToko');
        $alamatToko             =   $this->request->getVar('alamatToko');
        $arrIdBarangKategori    =   $this->request->getVar('arrIdBarangKategori');
        
        if(is_array($arrIdBarangKategori) && count($arrIdBarangKategori) > 0) {
            $arrIdBarangKategori=   array_map(function($idBarangKategori) {
                return hashidDecode($idBarangKategori);
            }, $arrIdBarangKategori);
        } else {
            $arrIdBarangKategori=   [];
        }

        return [
            'IDUSERADMINKEPALATOKO' =>  $idUserAdmin,
            'IDGUDANG'              =>  $idGudang,
            'IDKELOMPOKHARGAGROSIR' =>  $idKelompokHargaGrosir,
            'ARRIDBARANGKATEGORI'   =>  json_encode($arrIdBarangKategori),
            'KODE'                  =>  strtoupper($kodeToko),
            'NAMA'                  =>  $namaToko,
            'ALAMAT'                =>  $alamatToko,
            'STATUSEKSTERNAL'       =>  $statusEksternal
        ];
    }

    public function saveDataTokoTerdekat()
    {
        $rules  =   [
            'idToko'                =>  ['label' => 'Toko', 'rules' => 'required|alpha_numeric'],
            'arrIdTokoTerdekat'     =>  ['label' => 'Daftar Toko Terdekat', 'rules' => 'required'],
            'arrIdTokoTerdekat.*'   =>  ['label' => 'Daftar Toko Terdekat', 'rules' => 'alpha_numeric'],
        ];

        $messages   =   [
            'idToko'  => [
                'required'      => 'Data kiriman tidak lengkap, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak lengkap, silakan periksa kembali'
            ],
            'arrIdTokoTerdekat' => [
                'required'      => 'Data kiriman tidak valid, silakan periksa kembali'
            ],
            'arrIdTokoTerdekat.*' => [
                'alpha_numeric' => 'Data kiriman tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mainOperation      =   new MainOperation();
        $idToko             =   $this->request->getVar('idToko');
        $idToko             =   $idToko != "" ? hashidDecode($idToko) : 0;
        $arrIdTokoTerdekat  =   $this->request->getVar('arrIdTokoTerdekat');

        if(is_array($arrIdTokoTerdekat) && count($arrIdTokoTerdekat) > 0) {
            $arrIdTokoTerdekat    =   array_map(function($idTokoTerdekat) {
                return hashidDecode($idTokoTerdekat);
            }, $arrIdTokoTerdekat);
        } else {
            $arrIdTokoTerdekat  =   [];
        }

        $arrUpdateData  =   ['ARRIDTOKOTERDEKAT' =>  json_encode($arrIdTokoTerdekat)];
        $procUpdateData =   $mainOperation->updateDataTable('m_toko', $arrUpdateData, ['IDTOKO' => $idToko]);

        if(!$procUpdateData['status']) return switchMySQLErrorCode($procUpdateData['errCode']);
        return throwResponseOK('Data toko terdekat telah diperbarui');
    }
}