<?php

namespace App\Controllers\WH\Stok;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use App\Models\WH\Stok\StokBarangModel;
use App\Models\MainOperation;
use App\Models\ERP\Master\BarangSKUModel;
use App\Libraries\SpreadsheetGenerator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class StokBarang extends ResourceController
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

    public function getDaftarStokBarangGudang()
    {
        $rules  =   [
            'idBarangKategori'  =>  ['label' => 'Kategori Barang', 'rules' => 'permit_empty|alpha_numeric'],
            'idBarangMerk'      =>  ['label' => 'Merk Barang', 'rules' => 'permit_empty|alpha_numeric'],
            'jenisStok'         =>  ['label' => 'Jenis Stok', 'rules' => 'required|in_list[ALL,LDN,SDN]'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'urutan'            =>  ['label' => 'Urutan', 'rules' => 'required|in_list[AZ,ZA,ASC,DESC]'],
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idBarangKategori'  =>  [
                'alpha_numeric' =>  'Data kategori barang tidak valid, silakan periksa kembali'
            ],
            'idBarangMerk'     =>  [
                'alpha_numeric' =>  'Data merk barang tidak valid, silakan periksa kembali'
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $idBarangKategori   =   $this->request->getVar('idBarangKategori');
        $idBarangKategori   =   isset($idBarangKategori) && $idBarangKategori != "" ? hashidDecode($idBarangKategori) : 0;
        $idBarangMerk       =   $this->request->getVar('idBarangMerk');
        $idBarangMerk       =   isset($idBarangMerk) && $idBarangMerk != "" ? hashidDecode($idBarangMerk) : 0;
        $jenisStok          =   $this->request->getVar('jenisStok');
        $kataKunciPencarian =   $this->request->getVar('kataKunciPencarian');
        $urutan             =   $this->request->getVar('urutan');
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $pageNumber         =   $this->request->getVar('pageNumber');

        $mainOperation      =   new MainOperation();
        $stokBarangModel    =   new StokBarangModel();

        $baseData       =   $stokBarangModel->getDaftarStokBarangGudang($this->idGudang, $idBarangKategori, $idBarangMerk, $jenisStok, $kataKunciPencarian, $urutan);
        $totalNumberData=   $baseData->countAllResults(false);
        $pageProperty   =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $dataStokBarang =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $barangSKUModel =   new BarangSKUModel();

            foreach($dataStokBarang as $keyBarangStok){
                $idBarangSKU                    =   isset($keyBarangStok->IDBARANGSKU) && $keyBarangStok->IDBARANGSKU != "" ? $keyBarangStok->IDBARANGSKU : 0;
                $keyBarangStok->ATRIBUTSKUSTR   =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                unset($keyBarangStok->IDBARANGSKU);
            }

            $arrParameters  =   [
                'idGudang'          =>  $this->idGudang,
                'idBarangKategori'  =>  $idBarangKategori,
                'idBarangMerk'      =>  $idBarangMerk,
                'jenisStok'         =>  $jenisStok,
                'kataKunciPencarian'=>  $kataKunciPencarian,
                'urutan'            =>  $urutan
            ];
            $arrParametersEncode    =   encodeJWTToken($arrParameters);
            $urlExcelStokGudang     =   base_url(URL_EXCEL_WH_DATA_STOK_GUDANG).$arrParametersEncode;

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $dataStokBarang,
                "pageProperty"  =>  $pageProperty,
                "urlExcel"      =>  $urlExcelStokGudang
            ]);
        } else {
            $dataReturn =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty,
                "urlExcel"      =>  ""
            ];
            return throwResponseNotFound('Tidak ada data stok barang yang ditemukan', $dataReturn);
        }
    }

    public function getDaftarStokBarangToko()
    {
        $rules  =   [
            'idToko'            =>  ['label' => 'Toko', 'rules' => 'required|alpha_numeric'],
            'idBarangKategori'  =>  ['label' => 'Kategori Barang', 'rules' => 'permit_empty|alpha_numeric'],
            'idBarangMerk'      =>  ['label' => 'Merk Barang', 'rules' => 'permit_empty|alpha_numeric'],
            'jenisStok'         =>  ['label' => 'Jenis Stok', 'rules' => 'required|in_list[ALL,LDN,SDN]'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'urutan'            =>  ['label' => 'Urutan', 'rules' => 'required|in_list[AZ,ZA,ASC,DESC]'],
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idToko'            =>  [
                'required'      =>  'Harap pilih salah satu toko terlebih dahulu',
                'alpha_numeric' =>  'Toko yang anda pilih tidak valid, silakan periksa kembali'
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
        $jenisStok          =   $this->request->getVar('jenisStok');
        $kataKunciPencarian =   $this->request->getVar('kataKunciPencarian');
        $urutan             =   $this->request->getVar('urutan');
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $pageNumber         =   $this->request->getVar('pageNumber');

        $mainOperation      =   new MainOperation();
        $stokBarangModel    =   new StokBarangModel();

        $baseData       =   $stokBarangModel->getDaftarStokBarangToko($idToko, $idBarangKategori, $idBarangMerk, $jenisStok, $kataKunciPencarian, $urutan);
        $totalNumberData=   $baseData->countAllResults(false);
        $pageProperty   =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $dataStokBarang =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $barangSKUModel =   new BarangSKUModel();

            foreach($dataStokBarang as $keyBarangStok){
                $idBarangSKU                    =   isset($keyBarangStok->IDBARANGSKU) && $keyBarangStok->IDBARANGSKU != "" ? $keyBarangStok->IDBARANGSKU : 0;
                $keyBarangStok->ATRIBUTSKUSTR   =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                unset($keyBarangStok->IDBARANGSKU);
            }

            $arrParameters  =   [
                'idToko'            =>  $idToko,
                'idBarangKategori'  =>  $idBarangKategori,
                'idBarangMerk'      =>  $idBarangMerk,
                'jenisStok'         =>  $jenisStok,
                'kataKunciPencarian'=>  $kataKunciPencarian,
                'urutan'            =>  $urutan
            ];
            $arrParametersEncode=   encodeJWTToken($arrParameters);
            $urlExcelStokToko   =   base_url(URL_EXCEL_WH_DATA_STOK_TOKO).$arrParametersEncode;

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $dataStokBarang,
                "pageProperty"  =>  $pageProperty,
                "urlExcel"      =>  $urlExcelStokToko
            ]);
        } else {
            $dataReturn =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty,
                "urlExcel"      =>  ""
            ];
            return throwResponseNotFound('Tidak ada data stok barang yang ditemukan', $dataReturn);
        }
    }

    public function excelDataStokGudang($arrParametersEncode = null)
    {
        if(!isset($arrParametersEncode) || $arrParametersEncode == "") return $this->failForbidden('[E-AUTH-000] Forbidden Access');

        try {
            helper(['firebaseJWT']);
            $arrParameters      =   (array) decodeJWTToken($arrParametersEncode);
            $mainOperation      =   new MainOperation();
            $stokBarangModel    =   new StokBarangModel();

            $idGudang           =   $arrParameters['idGudang'];
            $idBarangKategori   =   $arrParameters['idBarangKategori'];
            $idBarangMerk       =   $arrParameters['idBarangMerk'];
            $jenisStok          =   $arrParameters['jenisStok'];
            $kataKunciPencarian =   $arrParameters['kataKunciPencarian'];
            $urutan             =   $arrParameters['urutan'];
            $baseData           =   $stokBarangModel->getDaftarStokBarangGudang($idGudang, $idBarangKategori, $idBarangMerk, $jenisStok, $kataKunciPencarian, $urutan);
            $dataLaporan        =   $baseData->asObject()->findAll(99999, 0);

            $spreadsheet            =   new Spreadsheet();
            $spreadsheetGenerator   =   new SpreadsheetGenerator();
            $activeWorksheet        =   $spreadsheet->getActiveSheet();
            $jenisStokStr           =   $urutanStr  =   '-';

            switch($jenisStok){
                case 'LDN'  :   $jenisStokStr   =   'Stok Tersedia'; break;
                case 'SDN'  :   $jenisStokStr   =   'Stok Kosong'; break;
                default     :   $jenisStokStr   =   'Semua Stok'; break;
            }

            switch($urutan){
                case 'AZ'   :   $urutanStr   =   'Abjad A-Z'; break;
                case 'ZA'   :   $urutanStr   =   'Abjad Z-A'; break;
                case 'ASC'  :   $urutanStr   =   'Stok Paling Sedikit'; break;
                case 'DESC' :   $urutanStr   =   'Stok Paling Banyak'; break;
                default     :   $urutanStr   =   '-'; break;
            }

            $arrTitleData   =   ['Laporan Data Stok Barang Gudang'];
            $arrFilterData  =   [
                ['Gudang', $mainOperation->getDetailGudang($idGudang)['NAMA']],
                ['Kategori Barang', $mainOperation->getDetailBarangKategori($idBarangKategori)['NAMAKATEGORI']],
                ['Merk', $mainOperation->getDetailBarangMerk($idBarangMerk)['NAMAMERK']],
                ['Jenis Stok', $jenisStokStr],
                ['Kata Pencarian', $kataKunciPencarian == "" ? '-' : $kataKunciPencarian],
                ['Urutan', $urutanStr],
                ['Waktu Proses', date('d M Y H:i')]
            ];

            $arrHeaderData  =   [
                [['A'], 1, 'Kategori Barang', 20, 'center'],
                [['B'], 1, 'Merk', 16, 'center'],
                [['C'], 1, 'Nama Barang', 35, 'center'],
                [['D'], 1, 'Kode SKU', 18, 'center'],
                [['E'], 1, 'Deskripsi SKU', 40, 'center'],
                [['F'], 1, 'Detail Atribut', 45, 'center'],
                [['G'], 1, 'Satuan', 12, 'center'],
                [['H'], 1, 'Stok', 12, 'right'],
                [['I'], 1, 'Harga Beli', 12, 'right']
            ];

            $rowStartDocument           =   1;
            $documentProperties         =   $spreadsheetGenerator->getDocumentProperties($rowStartDocument, $arrTitleData, $arrFilterData, $arrHeaderData);
            $rowStartFilter             =   $documentProperties['rowStartFilter'];
            $rowStartTableHeader        =   $documentProperties['rowStartTableHeader'];
            $rowNumberTableContent      =   $documentProperties['rowNumberTableContent'];
            $rowFirstTable              =   $documentProperties['rowFirstTable'];
            $columnFirstTable           =   $arrHeaderData[0][0][0];
            $columnLastTable            =   end(end($arrHeaderData)[0]);

            $spreadsheetGenerator->setDocumentTitle($activeWorksheet, $arrTitleData, $columnFirstTable, $columnLastTable, $rowStartDocument);
            $spreadsheetGenerator->setDocumentFilter($activeWorksheet, $arrFilterData, $columnLastTable, $rowStartFilter);
            $spreadsheetGenerator->setDocumentTableHeader($activeWorksheet, $arrHeaderData, $rowStartTableHeader);

            if(isset($dataLaporan) && !empty($dataLaporan)){
                $barangSKUModel =   new BarangSKUModel();
                foreach($dataLaporan as $keyDataLaporan){
                    $idBarangSKU=   isset($keyDataLaporan->IDBARANGSKU) && $keyDataLaporan->IDBARANGSKU != "" ? $keyDataLaporan->IDBARANGSKU : 0;
                    $atributSKU =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                    $atributSKU =   isset($atributSKU) && !empty($atributSKU) ? implode(',', $atributSKU) : '-';

                    $activeWorksheet
                    ->setCellValue('A'.$rowNumberTableContent, $keyDataLaporan->NAMAKATEGORI)
                    ->setCellValue('B'.$rowNumberTableContent, $keyDataLaporan->NAMAMERK)
                    ->setCellValue('C'.$rowNumberTableContent, $keyDataLaporan->NAMABARANG)
                    ->setCellValue('D'.$rowNumberTableContent, $keyDataLaporan->KODEBARANG.'-'.$keyDataLaporan->KODESKU)
                    ->setCellValue('E'.$rowNumberTableContent, $keyDataLaporan->DESKRIPSISKU)
                    ->setCellValue('F'.$rowNumberTableContent, $atributSKU)
                    ->setCellValue('G'.$rowNumberTableContent, $keyDataLaporan->NAMASATUAN)
                    ->setCellValue('H'.$rowNumberTableContent, intval($keyDataLaporan->STOK))
                    ->setCellValue('I'.$rowNumberTableContent, intval($keyDataLaporan->HARGABELIRERATA));
                    $rowNumberTableContent++;
                }
            } else {
                $activeWorksheet->setCellValue($columnFirstTable.$rowNumberTableContent, 'Tidak ada data stok barang yang ditemukan');
                $activeWorksheet->mergeCells($columnFirstTable.$rowNumberTableContent.':'.$columnLastTable.$rowNumberTableContent);
            }
		
            $spreadsheetGenerator->setDocumentTableStyle($activeWorksheet, $columnFirstTable, $columnLastTable, $rowFirstTable, $rowNumberTableContent);
            $spreadsheetGenerator->writeDocumentOutput($spreadsheet, 'Laporan_Stok_Barang_Gudang');
        } catch (\Throwable $th) {
            return throwResponseInternalServerError('[E-AUTH-001] Internal server error', [$th]);
        }
    }

    public function excelDataStokToko($arrParametersEncode = null)
    {
        if(!isset($arrParametersEncode) || $arrParametersEncode == "") return $this->failForbidden('[E-AUTH-000] Forbidden Access');

        try {
            helper(['firebaseJWT']);
            $arrParameters      =   (array) decodeJWTToken($arrParametersEncode);
            $mainOperation      =   new MainOperation();
            $stokBarangModel    =   new StokBarangModel();

            $idToko             =   $arrParameters['idToko'];
            $idBarangKategori   =   $arrParameters['idBarangKategori'];
            $idBarangMerk       =   $arrParameters['idBarangMerk'];
            $jenisStok          =   $arrParameters['jenisStok'];
            $kataKunciPencarian =   $arrParameters['kataKunciPencarian'];
            $urutan             =   $arrParameters['urutan'];
            $baseData           =   $stokBarangModel->getDaftarStokBarangToko($idToko, $idBarangKategori, $idBarangMerk, $jenisStok, $kataKunciPencarian, $urutan);
            $dataLaporan        =   $baseData->asObject()->findAll(99999, 0);

            $spreadsheet            =   new Spreadsheet();
            $spreadsheetGenerator   =   new SpreadsheetGenerator();
            $activeWorksheet        =   $spreadsheet->getActiveSheet();
            $jenisStokStr           =   $urutanStr  =   '-';

            switch($jenisStok){
                case 'LDN'  :   $jenisStokStr   =   'Stok Tersedia'; break;
                case 'SDN'  :   $jenisStokStr   =   'Stok Kosong'; break;
                default     :   $jenisStokStr   =   'Semua Stok'; break;
            }

            switch($urutan){
                case 'AZ'   :   $urutanStr   =   'Abjad A-Z'; break;
                case 'ZA'   :   $urutanStr   =   'Abjad Z-A'; break;
                case 'ASC'  :   $urutanStr   =   'Stok Paling Sedikit'; break;
                case 'DESC' :   $urutanStr   =   'Stok Paling Banyak'; break;
                default     :   $urutanStr   =   '-'; break;
            }

            $arrTitleData   =   ['Laporan Data Stok Barang Toko'];
            $arrFilterData  =   [
                ['Toko', $mainOperation->getDetailToko($idToko)['NAMA']],
                ['Kategori Barang', $mainOperation->getDetailBarangKategori($idBarangKategori)['NAMAKATEGORI']],
                ['Merk', $mainOperation->getDetailBarangMerk($idBarangMerk)['NAMAMERK']],
                ['Jenis Stok', $jenisStokStr],
                ['Kata Pencarian', $kataKunciPencarian == "" ? '-' : $kataKunciPencarian],
                ['Urutan', $urutanStr],
                ['Waktu Proses', date('d M Y H:i')]
            ];

            $arrHeaderData  =   [
                [['A'], 1, 'Kategori Barang', 20, 'center'],
                [['B'], 1, 'Merk', 16, 'center'],
                [['C'], 1, 'Nama Barang', 35, 'center'],
                [['D'], 1, 'Kode SKU', 18, 'center'],
                [['E'], 1, 'Deskripsi SKU', 40, 'center'],
                [['F'], 1, 'Detail Atribut', 45, 'center'],
                [['G'], 1, 'Satuan', 12, 'center'],
                [['H'], 1, 'Stok', 12, 'right'],
                [['I'], 1, 'Harga Beli', 12, 'right']
            ];

            $rowStartDocument           =   1;
            $documentProperties         =   $spreadsheetGenerator->getDocumentProperties($rowStartDocument, $arrTitleData, $arrFilterData, $arrHeaderData);
            $rowStartFilter             =   $documentProperties['rowStartFilter'];
            $rowStartTableHeader        =   $documentProperties['rowStartTableHeader'];
            $rowNumberTableContent      =   $documentProperties['rowNumberTableContent'];
            $rowFirstTable              =   $documentProperties['rowFirstTable'];
            $columnFirstTable           =   $arrHeaderData[0][0][0];
            $columnLastTable            =   end(end($arrHeaderData)[0]);

            $spreadsheetGenerator->setDocumentTitle($activeWorksheet, $arrTitleData, $columnFirstTable, $columnLastTable, $rowStartDocument);
            $spreadsheetGenerator->setDocumentFilter($activeWorksheet, $arrFilterData, $columnLastTable, $rowStartFilter);
            $spreadsheetGenerator->setDocumentTableHeader($activeWorksheet, $arrHeaderData, $rowStartTableHeader);

            if(isset($dataLaporan) && !empty($dataLaporan)){
                $barangSKUModel =   new BarangSKUModel();
                foreach($dataLaporan as $keyDataLaporan){
                    $idBarangSKU=   isset($keyDataLaporan->IDBARANGSKU) && $keyDataLaporan->IDBARANGSKU != "" ? $keyDataLaporan->IDBARANGSKU : 0;
                    $atributSKU =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                    $atributSKU =   isset($atributSKU) && !empty($atributSKU) ? implode(',', $atributSKU) : '-';

                    $activeWorksheet
                    ->setCellValue('A'.$rowNumberTableContent, $keyDataLaporan->NAMAKATEGORI)
                    ->setCellValue('B'.$rowNumberTableContent, $keyDataLaporan->NAMAMERK)
                    ->setCellValue('C'.$rowNumberTableContent, $keyDataLaporan->NAMABARANG)
                    ->setCellValue('D'.$rowNumberTableContent, $keyDataLaporan->KODEBARANG.'-'.$keyDataLaporan->KODESKU)
                    ->setCellValue('E'.$rowNumberTableContent, $keyDataLaporan->DESKRIPSISKU)
                    ->setCellValue('F'.$rowNumberTableContent, $atributSKU)
                    ->setCellValue('G'.$rowNumberTableContent, $keyDataLaporan->NAMASATUAN)
                    ->setCellValue('H'.$rowNumberTableContent, intval($keyDataLaporan->STOK))
                    ->setCellValue('I'.$rowNumberTableContent, intval($keyDataLaporan->HARGABELIRERATA));
                    $rowNumberTableContent++;
                }
            } else {
                $activeWorksheet->setCellValue($columnFirstTable.$rowNumberTableContent, 'Tidak ada data stok barang yang ditemukan');
                $activeWorksheet->mergeCells($columnFirstTable.$rowNumberTableContent.':'.$columnLastTable.$rowNumberTableContent);
            }
		
            $spreadsheetGenerator->setDocumentTableStyle($activeWorksheet, $columnFirstTable, $columnLastTable, $rowFirstTable, $rowNumberTableContent);
            $spreadsheetGenerator->writeDocumentOutput($spreadsheet, 'Laporan_Stok_Barang_Toko');
        } catch (\Throwable $th) {
            return throwResponseInternalServerError('[E-AUTH-001] Internal server error', [$th]);
        }
    }
}