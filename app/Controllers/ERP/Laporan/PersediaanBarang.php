<?php

namespace App\Controllers\ERP\Laporan;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use CodeIgniter\I18n\Time;

use App\Models\ERP\Laporan\PersediaanBarangModel;
use App\Models\MainOperation;
use App\Models\ERP\Master\BarangSKUModel;
use App\Libraries\SpreadsheetGenerator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class PersediaanBarang extends ResourceController
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

    public function getDataPersediaanBarangGudang()
    {
        $rules  =   [
            'idGudang'          =>  ['label' => 'Gudang', 'rules' => 'required|alpha_numeric'],
            'idBarangKategori'  =>  ['label' => 'Kategori Barang', 'rules' => 'permit_empty|alpha_numeric'],
            'tanggalAwal'       =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'      =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idGudang'  =>  [
                'required'      =>  'Harap pilih gudang terlebih dahulu',
                'alpha_numeric' =>  'Data gudang tidak valid, silakan periksa kembali'
            ],
            'idBarangKategori'  =>  [
                'alpha_numeric' =>  'Data kategori barang tidak valid, silakan periksa kembali'
            ],
            'tanggalAwal' => [
                'regex_match'   => 'Tanggal awal periode tidak valid, silakan periksa kembali',
            ],
            'tanggalAkhir'=> [
                'regex_match'   => 'Tanggal akhir periode tidak valid, silakan periksa kembali',
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $idGudang           =   $this->request->getVar('idGudang');
        $idGudang           =   isset($idGudang) && $idGudang != "" ? hashidDecode($idGudang) : 0;
        $idBarangKategori   =   $this->request->getVar('idBarangKategori');
        $idBarangKategori   =   isset($idBarangKategori) && $idBarangKategori != "" ? hashidDecode($idBarangKategori) : 0;
        $tanggalAwal        =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir       =   $this->request->getVar('tanggalAkhir');
        $kataKunciPencarian =   $this->request->getVar('kataKunciPencarian');
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $pageNumber         =   $this->request->getVar('pageNumber');

        $mainOperation          =   new MainOperation();
        $persediaanBarangModel  =   new PersediaanBarangModel();

        $baseData       =   $persediaanBarangModel->getDataPersediaanBarangGudang($idGudang, $idBarangKategori, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
        $totalNumberData=   $baseData->countAllResults(false);
        $pageProperty   =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $dataPersediaanBarang   =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);

            foreach($dataPersediaanBarang as $keyPersediaanBarang){
                $stokAkhir          =   isset($keyPersediaanBarang->STOKAKHIR) && $keyPersediaanBarang->STOKAKHIR != "" ? $keyPersediaanBarang->STOKAKHIR : 0;
                $hargaBeliRerata    =   isset($keyPersediaanBarang->HARGABELIRERATA) && $keyPersediaanBarang->HARGABELIRERATA != "" ? $keyPersediaanBarang->HARGABELIRERATA : 0;
                $keyPersediaanBarang->NILAIPERSEDIAAN   =   number_format(($stokAkhir * $hargaBeliRerata), 0, '.', '');
                
                unset($keyPersediaanBarang->IDBARANGSKU);
                unset($keyPersediaanBarang->HARGABELIRERATA);
            }

            $arrParameters  =   [
                'idGudang'          =>  $idGudang,
                'idBarangKategori'  =>  $idBarangKategori,
                'tanggalAwal'       =>  $tanggalAwal,
                'tanggalAkhir'      =>  $tanggalAkhir,
                'kataKunciPencarian'=>  $kataKunciPencarian,
            ];
            $arrParametersEncode        =   encodeJWTToken($arrParameters);
            $urlExcelPersediaanGudang   =   base_url(URL_EXCEL_ERP_DATA_PERSEDIAAN_BARANG_GUDANG).$arrParametersEncode;

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $dataPersediaanBarang,
                "pageProperty"  =>  $pageProperty,
                "urlExcel"      =>  $urlExcelPersediaanGudang
            ]);
        } else {
            $dataReturn =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty,
                "urlExcel"      =>  ""
            ];
            return throwResponseNotFound('Tidak ada data persediaan barang yang ditemukan', $dataReturn);
        }
    }

    public function getDataPersediaanBarangToko()
    {
        $rules  =   [
            'idToko'            =>  ['label' => 'Toko', 'rules' => 'required|alpha_numeric'],
            'idBarangKategori'  =>  ['label' => 'Kategori Barang', 'rules' => 'permit_empty|alpha_numeric'],
            'tanggalAwal'       =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'      =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idToko'  =>  [
                'required'      =>  'Harap pilih toko terlebih dahulu',
                'alpha_numeric' =>  'Data toko tidak valid, silakan periksa kembali'
            ],
            'idBarangKategori'  =>  [
                'alpha_numeric' =>  'Data kategori barang tidak valid, silakan periksa kembali'
            ],
            'tanggalAwal' => [
                'regex_match'   => 'Tanggal awal periode tidak valid, silakan periksa kembali',
            ],
            'tanggalAkhir'=> [
                'regex_match'   => 'Tanggal akhir periode tidak valid, silakan periksa kembali',
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $idToko             =   $this->request->getVar('idToko');
        $idToko             =   isset($idToko) && $idToko != "" ? hashidDecode($idToko) : 0;
        $idBarangKategori   =   $this->request->getVar('idBarangKategori');
        $idBarangKategori   =   isset($idBarangKategori) && $idBarangKategori != "" ? hashidDecode($idBarangKategori) : 0;
        $tanggalAwal        =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir       =   $this->request->getVar('tanggalAkhir');
        $kataKunciPencarian =   $this->request->getVar('kataKunciPencarian');
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $pageNumber         =   $this->request->getVar('pageNumber');

        $mainOperation          =   new MainOperation();
        $persediaanBarangModel  =   new PersediaanBarangModel();

        $baseData       =   $persediaanBarangModel->getDataPersediaanBarangToko($idToko, $idBarangKategori, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
        $totalNumberData=   $baseData->countAllResults(false);
        $pageProperty   =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $dataPersediaanBarang   =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);

            foreach($dataPersediaanBarang as $keyPersediaanBarang){
                $stokAkhir          =   isset($keyPersediaanBarang->STOKAKHIR) && $keyPersediaanBarang->STOKAKHIR != "" ? $keyPersediaanBarang->STOKAKHIR : 0;
                $hargaBeliRerata    =   isset($keyPersediaanBarang->HARGABELIRERATA) && $keyPersediaanBarang->HARGABELIRERATA != "" ? $keyPersediaanBarang->HARGABELIRERATA : 0;
                $keyPersediaanBarang->NILAIPERSEDIAAN   =   number_format(($stokAkhir * $hargaBeliRerata), 0, '.', '');
                
                unset($keyPersediaanBarang->IDBARANGSKU);
                unset($keyPersediaanBarang->HARGABELIRERATA);
            }

            $arrParameters  =   [
                'idToko'            =>  $idToko,
                'idBarangKategori'  =>  $idBarangKategori,
                'tanggalAwal'       =>  $tanggalAwal,
                'tanggalAkhir'      =>  $tanggalAkhir,
                'kataKunciPencarian'=>  $kataKunciPencarian,
            ];
            $arrParametersEncode    =   encodeJWTToken($arrParameters);
            $urlExcelPersediaanToko =   base_url(URL_EXCEL_ERP_DATA_PERSEDIAAN_BARANG_TOKO).$arrParametersEncode;

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $dataPersediaanBarang,
                "pageProperty"  =>  $pageProperty,
                "urlExcel"      =>  $urlExcelPersediaanToko
            ]);
        } else {
            $dataReturn =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty,
                "urlExcel"      =>  ""
            ];
            return throwResponseNotFound('Tidak ada data persediaan barang yang ditemukan', $dataReturn);
        }
    }

    public function excelDataPersediaanBarangGudang($arrParametersEncode = null)
    {
        if(!isset($arrParametersEncode) || $arrParametersEncode == "") return $this->failForbidden('[E-AUTH-000] Forbidden Access');

        try {
            helper(['firebaseJWT']);
            $arrParameters  =   (array) decodeJWTToken($arrParametersEncode);
            if(!isset($arrParameters['tanggalAwal']) || !isset($arrParameters['tanggalAkhir'])) return $this->failForbidden('[E-AUTH-000] Forbidden Access');

            $mainOperation          =   new MainOperation();
            $persediaanBarangModel  =   new PersediaanBarangModel();

            $idGudang               =   $arrParameters['idGudang'];
            $idBarangKategori       =   $arrParameters['idBarangKategori'];
            $tanggalAwal            =   $arrParameters['tanggalAwal'];
            $tanggalAwalDT          =   Time::parse($tanggalAwal);
            $tanggalAwalStr         =   $tanggalAwalDT->format('d M Y');
            $tanggalAkhir           =   $arrParameters['tanggalAkhir'];
            $tanggalAkhirDT         =   Time::parse($tanggalAkhir);
            $tanggalAkhirStr        =   $tanggalAkhirDT->format('d M Y');
            $kataKunciPencarian     =   $arrParameters['kataKunciPencarian'];
            $baseData               =   $persediaanBarangModel->getDataPersediaanBarangGudang($idGudang, $idBarangKategori, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
            $dataPersediaan         =   $baseData->asObject()->findAll(99999, 0);

            $spreadsheet            =   new Spreadsheet();
            $spreadsheetGenerator   =   new SpreadsheetGenerator();
            $activeWorksheet        =   $spreadsheet->getActiveSheet();

            $arrTitleData   =   ['Laporan Detail Persediaan Barang Gudang'];
            $arrFilterData  =   [
                ['Gudang', $mainOperation->getDetailGudang($idGudang)['NAMA']],
                ['Kategori Barang', $idBarangKategori == "" || $idBarangKategori == 0 ? 'Semua Kategori' : $mainOperation->getDetailBarangKategori($idBarangKategori)['NAMAKATEGORI']],
                ['Periode', $tanggalAwalStr.' s/d '.$tanggalAkhirStr],
                ['Kata Pencarian', $kataKunciPencarian == "" ? '-' : $kataKunciPencarian],
                ['Waktu Proses', date('d M Y H:i')]
            ];

            $arrHeaderData  =   [
                [['A'], 2, 'Kategori Barang', 20, 'center'],
                [['B'], 2, 'Nama Barang', 25, 'center'],
                [['C'], 2, 'Kode SKU', 20, 'center'],
                [['D'], 2, 'Deskripsi SKU', 35, 'center'],
                [['E'], 2, 'Satuan', 10, 'center'],
                [['F', 'I'], 1, 'Detail Stok', false, 'center'],
                [['F'], 1, 'Stok Awal', 12, 'right', 1],
                [['G'], 1, 'Masuk', 12, 'right', 1],
                [['H'], 1, 'Keluar', 12, 'right', 1],
                [['I'], 1, 'Stok Akhir', 12, 'right', 1],
                [['J'], 2, 'Nilai Persediaan', 16, 'right']
            ];

            $rowStartDocument           =   1;
            $documentProperties         =   $spreadsheetGenerator->getDocumentProperties($rowStartDocument, $arrTitleData, $arrFilterData, $arrHeaderData);
            $rowStartFilter             =   $documentProperties['rowStartFilter'];
            $rowStartTableHeader        =   $documentProperties['rowStartTableHeader'];
            $rowNumberTableContent      =   $documentProperties['rowNumberTableContent'];
            $rowNumberTableContentStart =   $rowNumberTableContent;
            $rowFirstTable              =   $documentProperties['rowFirstTable'];
            $columnFirstTable           =   $arrHeaderData[0][0][0];
            $columnLastTable            =   end(end($arrHeaderData)[0]);

            $spreadsheetGenerator->setDocumentTitle($activeWorksheet, $arrTitleData, $columnFirstTable, $columnLastTable, $rowStartDocument);
            $spreadsheetGenerator->setDocumentFilter($activeWorksheet, $arrFilterData, $columnLastTable, $rowStartFilter);
            $spreadsheetGenerator->setDocumentTableHeader($activeWorksheet, $arrHeaderData, $rowStartTableHeader);

            if(isset($dataPersediaan) && !empty($dataPersediaan)){
                foreach($dataPersediaan as $keyPersediaan){
                    $stokAkhir          =   isset($keyPersediaan->STOKAKHIR) && $keyPersediaan->STOKAKHIR != "" ? $keyPersediaan->STOKAKHIR : 0;
                    $hargaBeliRerata    =   isset($keyPersediaan->HARGABELIRERATA) && $keyPersediaan->HARGABELIRERATA != "" ? $keyPersediaan->HARGABELIRERATA : 0;
                    $nilaiPersediaan    =   number_format(($stokAkhir * $hargaBeliRerata), 0, '.', '');
                    
                    $activeWorksheet
                    ->setCellValue('A'.$rowNumberTableContent, $keyPersediaan->NAMAKATEGORI)
                    ->setCellValue('B'.$rowNumberTableContent, $keyPersediaan->NAMABARANG)
                    ->setCellValue('C'.$rowNumberTableContent, $keyPersediaan->KODESKU)
                    ->setCellValue('D'.$rowNumberTableContent, $keyPersediaan->DESKRIPSISKU)
                    ->setCellValue('E'.$rowNumberTableContent, $keyPersediaan->KODESATUAN)
                    ->setCellValue('F'.$rowNumberTableContent, $keyPersediaan->STOKAWAL)
                    ->setCellValue('G'.$rowNumberTableContent, $keyPersediaan->STOKMASUK)
                    ->setCellValue('H'.$rowNumberTableContent, $keyPersediaan->STOKKELUAR)
                    ->setCellValue('I'.$rowNumberTableContent, $keyPersediaan->STOKAKHIR)
                    ->setCellValue('J'.$rowNumberTableContent, $nilaiPersediaan);
                    $rowNumberTableContent++;
                }
            } else {
                $activeWorksheet->setCellValue($columnFirstTable.$rowNumberTableContent, 'Tidak ada data detail persediaan barang ditemukan pada periode tersebut');
                $activeWorksheet->mergeCells($columnFirstTable.$rowNumberTableContent.':'.$columnLastTable.$rowNumberTableContent);
            }
		
            $spreadsheetGenerator->setDocumentTableStyle($activeWorksheet, $columnFirstTable, $columnLastTable, $rowFirstTable, $rowNumberTableContent);
            $spreadsheetGenerator->writeDocumentOutput($spreadsheet, 'Laporan_Persediaan_Barang_Gudang');
        } catch (\Throwable $th) {
            return throwResponseInternalServerError('[E-AUTH-001] Internal server error', [$th]);
        }
    }

    public function excelDataPersediaanBarangToko($arrParametersEncode = null)
    {
        if(!isset($arrParametersEncode) || $arrParametersEncode == "") return $this->failForbidden('[E-AUTH-000] Forbidden Access');

        try {
            helper(['firebaseJWT']);
            $arrParameters  =   (array) decodeJWTToken($arrParametersEncode);
            if(!isset($arrParameters['tanggalAwal']) || !isset($arrParameters['tanggalAkhir'])) return $this->failForbidden('[E-AUTH-000] Forbidden Access');

            $mainOperation          =   new MainOperation();
            $persediaanBarangModel  =   new PersediaanBarangModel();

            $idToko             =   $arrParameters['idToko'];
            $idBarangKategori   =   $arrParameters['idBarangKategori'];
            $tanggalAwal        =   $arrParameters['tanggalAwal'];
            $tanggalAwalDT      =   Time::parse($tanggalAwal);
            $tanggalAwalStr     =   $tanggalAwalDT->format('d M Y');
            $tanggalAkhir       =   $arrParameters['tanggalAkhir'];
            $tanggalAkhirDT     =   Time::parse($tanggalAkhir);
            $tanggalAkhirStr    =   $tanggalAkhirDT->format('d M Y');
            $kataKunciPencarian =   $arrParameters['kataKunciPencarian'];
            $baseData           =   $persediaanBarangModel->getDataPersediaanBarangToko($idToko, $idBarangKategori, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
            $dataPersediaan     =   $baseData->asObject()->findAll(99999, 0);

            $spreadsheet            =   new Spreadsheet();
            $spreadsheetGenerator   =   new SpreadsheetGenerator();
            $activeWorksheet        =   $spreadsheet->getActiveSheet();

            $arrTitleData   =   ['Laporan Detail Persediaan Barang Toko'];
            $arrFilterData  =   [
                ['Toko', $mainOperation->getDetailToko($idToko)['NAMA']],
                ['Kategori Barang', $idBarangKategori == "" || $idBarangKategori == 0 ? 'Semua Kategori' : $mainOperation->getDetailBarangKategori($idBarangKategori)['NAMAKATEGORI']],
                ['Periode', $tanggalAwalStr.' s/d '.$tanggalAkhirStr],
                ['Kata Pencarian', $kataKunciPencarian == "" ? '-' : $kataKunciPencarian],
                ['Waktu Proses', date('d M Y H:i')]
            ];

            $arrHeaderData  =   [
                [['A'], 2, 'Kategori Barang', 20, 'center'],
                [['B'], 2, 'Nama Barang', 25, 'center'],
                [['C'], 2, 'Kode SKU', 20, 'center'],
                [['D'], 2, 'Deskripsi SKU', 35, 'center'],
                [['E'], 2, 'Satuan', 10, 'center'],
                [['F', 'I'], 1, 'Detail Stok', false, 'center'],
                [['F'], 1, 'Stok Awal', 12, 'right', 1],
                [['G'], 1, 'Masuk', 12, 'right', 1],
                [['H'], 1, 'Keluar', 12, 'right', 1],
                [['I'], 1, 'Stok Akhir', 12, 'right', 1],
                [['J'], 2, 'Nilai Persediaan', 16, 'right']
            ];

            $rowStartDocument           =   1;
            $documentProperties         =   $spreadsheetGenerator->getDocumentProperties($rowStartDocument, $arrTitleData, $arrFilterData, $arrHeaderData);
            $rowStartFilter             =   $documentProperties['rowStartFilter'];
            $rowStartTableHeader        =   $documentProperties['rowStartTableHeader'];
            $rowNumberTableContent      =   $documentProperties['rowNumberTableContent'];
            $rowNumberTableContentStart =   $rowNumberTableContent;
            $rowFirstTable              =   $documentProperties['rowFirstTable'];
            $columnFirstTable           =   $arrHeaderData[0][0][0];
            $columnLastTable            =   end(end($arrHeaderData)[0]);

            $spreadsheetGenerator->setDocumentTitle($activeWorksheet, $arrTitleData, $columnFirstTable, $columnLastTable, $rowStartDocument);
            $spreadsheetGenerator->setDocumentFilter($activeWorksheet, $arrFilterData, $columnLastTable, $rowStartFilter);
            $spreadsheetGenerator->setDocumentTableHeader($activeWorksheet, $arrHeaderData, $rowStartTableHeader);

            if(isset($dataPersediaan) && !empty($dataPersediaan)){
                foreach($dataPersediaan as $keyPersediaan){
                    $stokAkhir          =   isset($keyPersediaan->STOKAKHIR) && $keyPersediaan->STOKAKHIR != "" ? $keyPersediaan->STOKAKHIR : 0;
                    $hargaBeliRerata    =   isset($keyPersediaan->HARGABELIRERATA) && $keyPersediaan->HARGABELIRERATA != "" ? $keyPersediaan->HARGABELIRERATA : 0;
                    $nilaiPersediaan    =   number_format(($stokAkhir * $hargaBeliRerata), 0, '.', '');
                    
                    $activeWorksheet
                    ->setCellValue('A'.$rowNumberTableContent, $keyPersediaan->NAMAKATEGORI)
                    ->setCellValue('B'.$rowNumberTableContent, $keyPersediaan->NAMABARANG)
                    ->setCellValue('C'.$rowNumberTableContent, $keyPersediaan->KODESKU)
                    ->setCellValue('D'.$rowNumberTableContent, $keyPersediaan->DESKRIPSISKU)
                    ->setCellValue('E'.$rowNumberTableContent, $keyPersediaan->KODESATUAN)
                    ->setCellValue('F'.$rowNumberTableContent, $keyPersediaan->STOKAWAL)
                    ->setCellValue('G'.$rowNumberTableContent, $keyPersediaan->STOKMASUK)
                    ->setCellValue('H'.$rowNumberTableContent, $keyPersediaan->STOKKELUAR)
                    ->setCellValue('I'.$rowNumberTableContent, $keyPersediaan->STOKAKHIR)
                    ->setCellValue('J'.$rowNumberTableContent, $nilaiPersediaan);
                    $rowNumberTableContent++;
                }
            } else {
                $activeWorksheet->setCellValue($columnFirstTable.$rowNumberTableContent, 'Tidak ada data detail persediaan barang ditemukan pada periode tersebut');
                $activeWorksheet->mergeCells($columnFirstTable.$rowNumberTableContent.':'.$columnLastTable.$rowNumberTableContent);
            }
		
            $spreadsheetGenerator->setDocumentTableStyle($activeWorksheet, $columnFirstTable, $columnLastTable, $rowFirstTable, $rowNumberTableContent);
            $spreadsheetGenerator->writeDocumentOutput($spreadsheet, 'Laporan_Persediaan_Barang_Toko');
        } catch (\Throwable $th) {
            return throwResponseInternalServerError('[E-AUTH-001] Internal server error', [$th]);
        }
    }
}