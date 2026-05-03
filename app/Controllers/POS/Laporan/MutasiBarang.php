<?php

namespace App\Controllers\POS\Laporan;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;

use Psr\Log\LoggerInterface;
use App\Models\POS\Laporan\MutasiBarangModel;
use App\Models\ERP\Master\BarangSKUModel;
use App\Models\MainOperation;
use App\Libraries\SpreadsheetGenerator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class MutasiBarang extends ResourceController
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
            $this->currentDateTime  =   $request->currentDateTime;
            $this->idToko           =   $this->userData->idToko;
        } catch (\Throwable $th) {
        }
    }

    public function index()
    {
        return $this->failForbidden('[E-AUTH-000] Forbidden Access');
    }

    public function getLaporanDetailMutasiBarang()
    {
        $rules  =   [
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]'],
            'tanggalAwal'       =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'      =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
        ];

        $messages   =   [
            'tanggalAwal' => [
                'regex_match'   => 'Tanggal awal periode tidak valid, silakan periksa kembali',
            ],
            'tanggalAkhir' => [
                'regex_match'   => 'Tanggal akhir periode tidak valid, silakan periksa kembali',
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $mutasiBarangModel  =   new MutasiBarangModel();
        $mainOperation      =   new MainOperation();
        $tanggalAwal        =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir       =   $this->request->getVar('tanggalAkhir');
        $kataKunciPencarian =   $this->request->getVar('kataKunciPencarian');
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $pageNumber         =   $this->request->getVar('pageNumber');
        $tanggalAwalDT      =   new \DateTime($tanggalAwal);
        $tanggalAkhirDT     =   new \DateTime($tanggalAkhir);
        $tanggalInterval    =   $tanggalAwalDT->diff($tanggalAkhirDT);
        $jumlahHari         =   intval($tanggalInterval->days) + 1;
        
        if($jumlahHari > 31) return throwResponseNotAcceptable('Rentang tanggal maksimal adalah 31 hari');

        //DATA GRAPH
        $mainOperation  =   new MainOperation();
        $dataGraphMutasi=   $mutasiBarangModel->getRekapMutasiBarangPerTanggal($this->idToko, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
        $dataGraph      =   [];

        while ($tanggalAwalDT <= $tanggalAkhirDT) {
            $dataGraph[]    =   [
                'Tanggal'       =>    $tanggalAwalDT->format('d M'),
                'Jumlah Masuk'  =>    0,
                'Jumlah Keluar' =>    0
            ];

            $tanggalAwalDT->modify('+1 day');
        }
        
        $jumlahMutasi   =   $totalMasuk =   $totalKeluar    =   0;
        foreach($dataGraphMutasi as $keyGraphMutasi){
            $tanggalGraph   =   $keyGraphMutasi->TANGGAL;
            $indexDataGraph =   array_search($tanggalGraph, array_column($dataGraph, 'Tanggal'));

            if($indexDataGraph && isset($dataGraph[$indexDataGraph])){
                $dataGraph[$indexDataGraph]['Jumlah Masuk'] =   intval($keyGraphMutasi->JUMLAHMASUK);
                $dataGraph[$indexDataGraph]['Jumlah Keluar']=   intval($keyGraphMutasi->JUMLAHKELUAR);
                $jumlahMutasi                               +=  intval($keyGraphMutasi->JUMLAHMUTASI);
                $totalMasuk                                 +=  intval($keyGraphMutasi->JUMLAHMASUK);
                $totalKeluar                                +=  intval($keyGraphMutasi->JUMLAHKELUAR);
            }
        }

        //DATA TABEL DETAIL
        $baseData           =   $mutasiBarangModel->getDetailMutasiBarang($this->idToko, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
        $totalNumberData    =   $baseData->countAllResults(false);
        $pageProperty       =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $dataMutasiBarang   =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $barangSKUModel     =   new BarangSKUModel();

            foreach($dataMutasiBarang as $keyMutasiBarang){
                $idBarangSKU                    =   isset($keyMutasiBarang->IDBARANGSKU) && $keyMutasiBarang->IDBARANGSKU != "" ? $keyMutasiBarang->IDBARANGSKU : 0;
                $keyMutasiBarang->ATRIBUTSKUSTR =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                unset($keyMutasiBarang->IDBARANGSKU);
            }

            $arrParameters  =   [
                'idToko'            =>  $this->idToko,
                'tanggalAwal'       =>  $tanggalAwal,
                'tanggalAkhir'      =>  $tanggalAkhir,
                'kataKunciPencarian'=>  $kataKunciPencarian
            ];
            $arrParametersEncode    =   encodeJWTToken($arrParameters);
            $urlExcelMutasiBarang   =   base_url(URL_EXCEL_DATA_MUTASI_BARANG).$arrParametersEncode;

            return $this->setResponseFormat('json')->respond([
                "jumlahMutasi"      =>  $jumlahMutasi,
                "totalMasuk"        =>  $totalMasuk,
                "totalKeluar"       =>  $totalKeluar,
                "dataGraph"         =>  $dataGraph,
                "dataMutasiBarang"  =>  $dataMutasiBarang,
                "pageProperty"      =>  $pageProperty,
                "urlExcel"          =>  $urlExcelMutasiBarang
            ]);
        } else {
            $dataReturn     =   [
                "jumlahMutasi"      =>  $jumlahMutasi,
                "totalMasuk"        =>  $totalMasuk,
                "totalKeluar"       =>  $totalKeluar,
                "dataGraph"         =>  $dataGraph,
                "dataMutasiBarang"  =>  [],
                "pageProperty"      =>  $pageProperty,
                "urlExcel"          =>  ""
            ];
            return throwResponseNotFound('Tidak ada data mutasi barang yang ditemukan', $dataReturn);
        }
    }

    public function excelDataMutasiBarang($arrParametersEncode = null)
    {
        if(!isset($arrParametersEncode) || $arrParametersEncode == "") return $this->failForbidden('[E-AUTH-000] Forbidden Access');

        try {
            helper(['firebaseJWT']);
            $arrParameters  =   (array) decodeJWTToken($arrParametersEncode);
            if(!isset($arrParameters['tanggalAwal']) || !isset($arrParameters['tanggalAkhir'])) return $this->failForbidden('[E-AUTH-000] Forbidden Access');

            $mainOperation      =   new MainOperation();
            $mutasiBarangModel  =   new MutasiBarangModel();

            $idToko             =   $arrParameters['idToko'];
            $tanggalAwal        =   $arrParameters['tanggalAwal'];
            $tanggalAwalDT      =   Time::parse($tanggalAwal);
            $tanggalAwalStr     =   $tanggalAwalDT->format('d M Y');
            $tanggalAkhir       =   $arrParameters['tanggalAkhir'];
            $tanggalAkhirDT     =   Time::parse($tanggalAkhir);
            $tanggalAkhirStr    =   $tanggalAkhirDT->format('d M Y');
            $kataKunciPencarian =   $arrParameters['kataKunciPencarian'];
            $baseData           =   $mutasiBarangModel->getDetailMutasiBarang($idToko, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
            $dataLaporan        =   $baseData->asObject()->findAll(99999, 0);

            $spreadsheet            =   new Spreadsheet();
            $spreadsheetGenerator   =   new SpreadsheetGenerator();
            $activeWorksheet        =   $spreadsheet->getActiveSheet();

            $arrTitleData   =   ['Laporan Detail Mutasi Barang'];
            $arrFilterData  =   [
                ['Toko', $mainOperation->getDetailToko($idToko)['NAMA']],
                ['Periode', $tanggalAwalStr.' s/d '.$tanggalAkhirStr],
                ['Kata Pencarian', $kataKunciPencarian == "" ? '-' : $kataKunciPencarian],
                ['Waktu Proses', date('d M Y H:i')]
            ];
            $arrHeaderData  =   [
                [['A'], 1, 'Tanggal Waktu', 16, 'center'],
                [['B'], 1, 'Kategori', 16, 'center'],
                [['C'], 1, 'Merk', 16, 'center'],
                [['D'], 1, 'Nama Barang', 25, 'center'],
                [['E'], 1, 'Kode SKU', 16, 'center'],
                [['F'], 1, 'Deskripsi SKU', 30, 'center'],
                [['G'], 1, 'Detail Atribut', 35, 'center'],
                [['H'], 1, 'Keterangan', 80, 'center'],
                [['I'], 1, 'Satuan', 12, 'center'],
                [['J'], 1, 'Masuk', 12, 'right'],
                [['K'], 1, 'Keluar', 12, 'right']
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
                $totalMasuk     =   0;
                $totalKeluar    =   0;
                foreach($dataLaporan as $keyDataLaporan){
                    $idBarangSKU=   isset($keyDataLaporan->IDBARANGSKU) && $keyDataLaporan->IDBARANGSKU != "" ? $keyDataLaporan->IDBARANGSKU : 0;
                    $atributSKU =   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                    $atributSKU =   isset($atributSKU) && !empty($atributSKU) ? implode(',', $atributSKU) : '-';

                    $activeWorksheet
                    ->setCellValue('A'.$rowNumberTableContent, $keyDataLaporan->TANGGALWAKTUMUTASI)
                    ->setCellValue('B'.$rowNumberTableContent, $keyDataLaporan->NAMAKATEGORI)
                    ->setCellValue('C'.$rowNumberTableContent, $keyDataLaporan->NAMAMERK)
                    ->setCellValue('D'.$rowNumberTableContent, $keyDataLaporan->NAMABARANG)
                    ->setCellValue('E'.$rowNumberTableContent, $keyDataLaporan->KODESKU)
                    ->setCellValue('F'.$rowNumberTableContent, $keyDataLaporan->DESKRIPSISKU)
                    ->setCellValue('G'.$rowNumberTableContent, $atributSKU)
                    ->setCellValue('H'.$rowNumberTableContent, $keyDataLaporan->MUTASIKETERANGAN)
                    ->setCellValue('I'.$rowNumberTableContent, $keyDataLaporan->NAMASATUAN)
                    ->setCellValue('J'.$rowNumberTableContent, intval($keyDataLaporan->JUMLAHMASUK))
                    ->setCellValue('K'.$rowNumberTableContent, intval($keyDataLaporan->JUMLAHKELUAR));

                    $totalMasuk     +=  intval($keyDataLaporan->JUMLAHMASUK);
                    $totalKeluar    +=  intval($keyDataLaporan->JUMLAHKELUAR);
                    $rowNumberTableContent++;
                }

                $activeWorksheet
                ->setCellValue('A'.$rowNumberTableContent, 'TOTAL MUTASI')
                ->setCellValue('J'.$rowNumberTableContent, intval($totalMasuk))
                ->setCellValue('K'.$rowNumberTableContent, intval($totalKeluar));
                $activeWorksheet->mergeCells('A'.$rowNumberTableContent.':I'.$rowNumberTableContent);
                $activeWorksheet->getStyle('A'.$rowNumberTableContent)->getAlignment()->setHorizontal('center');
                $activeWorksheet->getStyle('A'.$rowNumberTableContent.':K'.$rowNumberTableContent)->getFont()->setBold(true);
                $rowNumberTableContent++;
            } else {
                $activeWorksheet->setCellValue($columnFirstTable.$rowNumberTableContent, 'Tidak ada data detail mutasi barang yang ditemukan pada periode tersebut');
                $activeWorksheet->mergeCells($columnFirstTable.$rowNumberTableContent.':'.$columnLastTable.$rowNumberTableContent);
            }
		
            $spreadsheetGenerator->setDocumentTableStyle($activeWorksheet, $columnFirstTable, $columnLastTable, $rowFirstTable, $rowNumberTableContent);
            $spreadsheetGenerator->writeDocumentOutput($spreadsheet, 'Laporan_Mutasi_Barang_Toko');
        } catch (\Throwable $th) {
            return throwResponseInternalServerError('[E-AUTH-001] Internal server error', [$th]);
        }
    }
}