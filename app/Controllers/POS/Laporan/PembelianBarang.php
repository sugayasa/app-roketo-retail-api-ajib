<?php

namespace App\Controllers\POS\Laporan;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;

use Psr\Log\LoggerInterface;
use App\Models\POS\Laporan\PembelianBarangModel;
use App\Models\MainOperation;
use App\Libraries\SpreadsheetGenerator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class PembelianBarang extends ResourceController
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

    public function getDataPembelianBarang()
    {
        $rules  =   [
            'idGudang'          =>  ['label' => 'Gudang', 'rules' => 'permit_empty|alpha_numeric'],
            'tanggalAwal'       =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'      =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'kataKunciPencarian'=>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'dataPerPage'       =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'        =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        $messages   =   [
            'idGudang'      =>  [
                'alpha_numeric' =>  'Gudang yang anda pilih tidak valid, silakan periksa kembali'
            ],
            'tanggalAwal'   =>  [
                'regex_match'   => 'Tanggal awal periode tidak valid, silakan periksa kembali',
            ],
            'tanggalAkhir'  =>  [
                'regex_match'   => 'Tanggal akhir periode tidak valid, silakan periksa kembali',
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $idGudang           =   $this->request->getVar('idGudang');
        $idGudang           =   isset($idGudang) && $idGudang != "" ? hashidDecode($idGudang) : 0;
        $tanggalAwal        =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir       =   $this->request->getVar('tanggalAkhir');
        $kataKunciPencarian =   $this->request->getVar('kataKunciPencarian');
        $dataPerPage        =   $this->request->getVar('dataPerPage');
        $pageNumber         =   $this->request->getVar('pageNumber');

        $mainOperation          =   new MainOperation();
        $pembelianBarangModel   =   new PembelianBarangModel();

        $baseData               =   $pembelianBarangModel->getDataPembelianBarang($this->idToko, $idGudang, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
        $totalNumberData        =   $baseData->countAllResults(false);
        $pageProperty           =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0){
            $dataPembelianBarang=   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);

            foreach($dataPembelianBarang as $keyPembelianBarang){
                $idTokoNotaMutasiRekap  =   isset($keyPembelianBarang->IDTOKONOTAMUTASIREKAP) && $keyPembelianBarang->IDTOKONOTAMUTASIREKAP != "" ? $keyPembelianBarang->IDTOKONOTAMUTASIREKAP : 0;
                $dataBarangSKU          =   $pembelianBarangModel->getDataBarangSKUPembelian($idTokoNotaMutasiRekap);

                $keyPembelianBarang->DAFTARBARANGSKU    =   $dataBarangSKU;
                unset($keyPembelianBarang->IDTOKONOTAMUTASIREKAP);
            }

            $arrParameters  =   [
                'idGudang'          =>  $idGudang,
                'idToko'            =>  $this->idToko,
                'tanggalAwal'       =>  $tanggalAwal,
                'tanggalAkhir'      =>  $tanggalAkhir,
                'kataKunciPencarian'=>  $kataKunciPencarian
            ];
            $arrParametersEncode    =   encodeJWTToken($arrParameters);
            $urlExcelPembelianBarang=   base_url(URL_EXCEL_DATA_PEMBELIAN_BARANG).$arrParametersEncode;

            return $this->setResponseFormat('json')->respond([
                "listData"      =>  $dataPembelianBarang,
                "pageProperty"  =>  $pageProperty,
                "urlExcel"      =>  $urlExcelPembelianBarang
            ]);
        } else {
            $dataReturn =   [
                "listData"      =>  [],
                "pageProperty"  =>  $pageProperty,
                "urlExcel"      =>  ""
            ];
            return throwResponseNotFound('Tidak ada data pembelian barang yang ditemukan', $dataReturn);
        }
    }

    public function excelDataPembelianBarang($arrParametersEncode = null)
    {
        if(!isset($arrParametersEncode) || $arrParametersEncode == "") return $this->failForbidden('[E-AUTH-000] Forbidden Access');

        try {
            helper(['firebaseJWT']);
            $arrParameters  =   (array) decodeJWTToken($arrParametersEncode);
            if(!isset($arrParameters['tanggalAwal']) || !isset($arrParameters['tanggalAkhir'])) return $this->failForbidden('[E-AUTH-000] Forbidden Access');

            $mainOperation          =   new MainOperation();
            $pembelianBarangModel   =   new PembelianBarangModel();

            $idToko             =   $arrParameters['idToko'];
            $idGudang           =   $arrParameters['idGudang'];
            $tanggalAwal        =   $arrParameters['tanggalAwal'];
            $tanggalAwalDT      =   Time::parse($tanggalAwal);
            $tanggalAwalStr     =   $tanggalAwalDT->format('d M Y');
            $tanggalAkhir       =   $arrParameters['tanggalAkhir'];
            $tanggalAkhirDT     =   Time::parse($tanggalAkhir);
            $tanggalAkhirStr    =   $tanggalAkhirDT->format('d M Y');
            $kataKunciPencarian =   $arrParameters['kataKunciPencarian'];
            $baseData           =   $pembelianBarangModel->getDataPembelianBarang($idToko, $idGudang, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian);
            $dataLaporan        =   $baseData->asObject()->findAll(99999, 0);

            $spreadsheet            =   new Spreadsheet();
            $spreadsheetGenerator   =   new SpreadsheetGenerator();
            $activeWorksheet        =   $spreadsheet->getActiveSheet();

            $arrTitleData   =   ['Laporan Detail Pembelian Barang Per Nota'];
            $arrFilterData  =   [
                ['Toko', $mainOperation->getDetailToko($idToko)['NAMA']],
                ['Gudang', $mainOperation->getDetailGudang($idGudang)['NAMA']],
                ['Periode', $tanggalAwalStr.' s/d '.$tanggalAkhirStr],
                ['Kata Pencarian', $kataKunciPencarian == "" ? '-' : $kataKunciPencarian],
                ['Waktu Proses', date('d M Y H:i')]
            ];
            $arrHeaderData  =   [
                [['A'], 2, 'Nomor Nota/Gudang', 18, 'center'],
                [['B'], 2, 'Detail Input', 18, 'center'],
                [['C'], 2, 'Keterangan', 30, 'center'],
                [['D', 'I'], 1, 'Daftar Barang', false, 'center'],
                [['D'], 1, 'Kode SKU', 20, 'left', 1],
                [['E'], 1, 'Nama Barang SKU', 35, 'left', 1],
                [['F'], 1, 'Jumlah', 12, 'right', 1],
                [['G'], 1, 'Satuan', 10, 'left', 1],
                [['H'], 1, 'Harga', 12, 'right', 1],
                [['I'], 1, 'Harga Total', 12, 'right', 1],
                [['J'], 2, 'Grand Total', 12, 'right']
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

            if(isset($dataLaporan) && !empty($dataLaporan)){
                foreach($dataLaporan as $keyDataLaporan){
                    $activeWorksheet
                    ->setCellValue('A'.$rowNumberTableContent, $keyDataLaporan->NOTAMUTASINOMOR.PHP_EOL.$keyDataLaporan->NAMAGUDANG)
                    ->setCellValue('B'.$rowNumberTableContent, $keyDataLaporan->PROSESTANGGALWAKTU.PHP_EOL.$keyDataLaporan->PROSESUSER)
                    ->setCellValue('C'.$rowNumberTableContent, $keyDataLaporan->PROSESKETERANGAN);

                    $idTokoNotaMutasiRekap  =   isset($keyDataLaporan->IDTOKONOTAMUTASIREKAP) && $keyDataLaporan->IDTOKONOTAMUTASIREKAP != "" ? $keyDataLaporan->IDTOKONOTAMUTASIREKAP : 0;
                    $dataBarangSKU          =   $pembelianBarangModel->getDataBarangSKUPembelian($idTokoNotaMutasiRekap);
                    $rowNumberGrandTotal    =   $rowNumberTableContent;

                    if(!empty($dataBarangSKU) && !is_null($dataBarangSKU)){
                        $totalBarangSKU =   count($dataBarangSKU);
                        $grandTotal     =   0;
                        foreach($dataBarangSKU as $keyDataBarangSKU){
                            $activeWorksheet
                            ->setCellValue('D'.$rowNumberTableContent, $keyDataBarangSKU->KODESKU)
                            ->setCellValue('E'.$rowNumberTableContent, $keyDataBarangSKU->DESKRIPSISKU)
                            ->setCellValue('F'.$rowNumberTableContent, intval($keyDataBarangSKU->JUMLAH))
                            ->setCellValue('G'.$rowNumberTableContent, $keyDataBarangSKU->NAMASATUAN)
                            ->setCellValue('H'.$rowNumberTableContent, intval($keyDataBarangSKU->HARGAGROSIR))
                            ->setCellValue('I'.$rowNumberTableContent, intval($keyDataBarangSKU->TOTALHARGAGROSIR));

                            $grandTotal     +=  intval($keyDataBarangSKU->TOTALHARGAGROSIR);
                            $rowNumberTableContent++;
                        }

                        $activeWorksheet->setCellValue('J'.$rowNumberGrandTotal, $grandTotal);
                        if($totalBarangSKU > 1){
                            $rowNumberMergeStart=   $rowNumberTableContent - $totalBarangSKU;
                            $rowNumberMergeEnd  =   $rowNumberTableContent - 1;

                            $activeWorksheet->mergeCells('A'.$rowNumberMergeStart.':A'.$rowNumberMergeEnd);
                            $activeWorksheet->mergeCells('B'.$rowNumberMergeStart.':B'.$rowNumberMergeEnd);
                            $activeWorksheet->mergeCells('C'.$rowNumberMergeStart.':C'.$rowNumberMergeEnd);
                            $activeWorksheet->mergeCells('J'.$rowNumberMergeStart.':J'.$rowNumberMergeEnd);
                        }
                    } else {
                        $rowNumberTableContent++;
                    }
                }

                $activeWorksheet->getStyle('J'.$rowNumberTableContentStart.':J'.$rowNumberTableContent)->getFont()->setBold(true);
            } else {
                $activeWorksheet->setCellValue($columnFirstTable.$rowNumberTableContent, 'Tidak ada data detail pembelian barang per nota yang ditemukan pada periode tersebut');
                $activeWorksheet->mergeCells($columnFirstTable.$rowNumberTableContent.':'.$columnLastTable.$rowNumberTableContent);
            }
		
            $spreadsheetGenerator->setDocumentTableStyle($activeWorksheet, $columnFirstTable, $columnLastTable, $rowFirstTable, $rowNumberTableContent);
            $spreadsheetGenerator->writeDocumentOutput($spreadsheet, 'Laporan_Pembelian_Barang_Detail_Per_Nota');
        } catch (\Throwable $th) {
            return throwResponseInternalServerError('[E-AUTH-001] Internal server error', [$th]);
        }
    }
}