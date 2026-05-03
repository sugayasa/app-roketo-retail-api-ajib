<?php

namespace App\Controllers\POS\Laporan;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;

use Psr\Log\LoggerInterface;
use App\Models\POS\Laporan\TagihanModel;
use App\Models\MainOperation;
use App\Libraries\SpreadsheetGenerator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Tagihan extends ResourceController
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

    public function getLaporanDetailTagihan()
    {
        $rules  =   [
            'statusPelunasan'           =>  ['label' => 'Status Pelunasan', 'rules' => 'permit_empty|in_list[0,1]'],
            'tanggalAwal'               =>  ['label' => 'Tanggal Awal Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'tanggalAkhir'              =>  ['label' => 'Tanggal Akhir Periode', 'rules' => 'required|valid_date|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
            'kataKunciPencarian'        =>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'tampilTagihanBelumLunas'   =>  ['label' => 'Tampilkan Tagihan Belum Lunas', 'rules' => 'required|in_list[0,1]'],
            'dataPerPage'               =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'                =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]'],
        ];

        $messages   =   [
            'statusPelunasan'   => [
                'in_list'   => 'Status pelunasan tidak valid, silakan periksa kembali',
            ],
            'tanggalAwal'   => [
                'regex_match'   => 'Tanggal awal periode tidak valid, silakan periksa kembali',
            ],
            'tanggalAkhir' => [
                'regex_match'   => 'Tanggal akhir periode tidak valid, silakan periksa kembali',
            ],
            'tampilTagihanBelumLunas' => [
                'in_list'   => 'Tampilkan tagihan belum lunas tidak valid, silakan periksa kembali',
            ]
        ];

        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $tagihanModel           =   new TagihanModel();
        $mainOperation          =   new MainOperation();
        $statusPelunasan        =   $this->request->getVar('statusPelunasan');
        $tanggalAwal            =   $this->request->getVar('tanggalAwal');
        $tanggalAkhir           =   $this->request->getVar('tanggalAkhir');
        $kataKunciPencarian     =   $this->request->getVar('kataKunciPencarian');
        $tampilTagihanBelumLunas=   $this->request->getVar('tampilTagihanBelumLunas');
        $dataPerPage            =   $this->request->getVar('dataPerPage');
        $pageNumber             =   $this->request->getVar('pageNumber');
        $baseData               =   $tagihanModel->getDetailTagihanMutasiToko($this->idToko, $statusPelunasan, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian, $tampilTagihanBelumLunas);
        $totalNumberData        =   $baseData->countAllResults(false);
        $pageProperty           =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0) {
            $baseDataQueryString=   $tagihanModel->getDetailTagihanMutasiToko($this->idToko, $statusPelunasan, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian, $tampilTagihanBelumLunas, true);
            $rekapNotaTagihan   =   $tagihanModel->getDataRekapitulasiTagihan($baseDataQueryString);
            $dataNotaTagihan    =   $baseData->limit($dataPerPage, ($pageNumber - 1) * $dataPerPage)->get()->getResultObject();
            
            $arrParameters  =   [
                'idToko'            =>  $this->idToko,
                'statusPelunasan'   =>  $statusPelunasan,
                'tanggalAwal'       =>  $tanggalAwal,
                'tanggalAkhir'      =>  $tanggalAkhir,
                'kataKunciPencarian'=>  $kataKunciPencarian,
                'tampilBelumLunas'  =>  $tampilTagihanBelumLunas
            ];
            $arrParametersEncode    =   encodeJWTToken($arrParameters);
            $urlExcelTagihan        =   base_url(URL_EXCEL_DATA_TAGIHAN).$arrParametersEncode;

            return $this->setResponseFormat('json')->respond([
                "nominalTagihan"        =>  $rekapNotaTagihan['NOMINALTAGIHAN'],
                "nominalLunas"          =>  $rekapNotaTagihan['NOMINALLUNAS'],
                "nominalBelumLunas"     =>  $rekapNotaTagihan['NOMINALBELUMLUNAS'],
                "jumlahNotaTagihan"     =>  $rekapNotaTagihan['JUMLAHNOTATAGIHAN'],
                "jumlahNotaLunas"       =>  $rekapNotaTagihan['JUMLAHNOTALUNAS'],
                "jumlahNotaBelumLunas"  =>  $rekapNotaTagihan['JUMLAHNOTABELUMLUNAS'],
                "dataNotaTagihan"       =>  $dataNotaTagihan,
                "pageProperty"          =>  $pageProperty,
                "urlExcel"              =>  $urlExcelTagihan
            ]);
        } else {
            $dataReturn     =   [
                "nominalTagihan"        =>  0,
                "nominalLunas"          =>  0,
                "nominalBelumLunas"     =>  0,
                "jumlahNotaTagihan"     =>  0,
                "jumlahNotaLunas"       =>  0,
                "jumlahNotaBelumLunas"  =>  0,
                "dataNotaTagihan"       =>  [],
                "pageProperty"          =>  $pageProperty,
                "urlExcel"              =>  ""
            ];
            return throwResponseNotFound('Tidak ada data nota tagihan yang ditemukan', $dataReturn);
        }
    }

    public function excelDataTagihan($arrParametersEncode = null)
    {
        if(!isset($arrParametersEncode) || $arrParametersEncode == "") return $this->failForbidden('[E-AUTH-000] Forbidden Access');

        try {
            helper(['firebaseJWT']);
            $arrParameters  =   (array) decodeJWTToken($arrParametersEncode);
            if(!isset($arrParameters['tanggalAwal']) || !isset($arrParameters['tanggalAkhir'])) return $this->failForbidden('[E-AUTH-000] Forbidden Access');

            $mainOperation      =   new MainOperation();
            $tagihanModel       =   new TagihanModel();

            $idToko             =   $arrParameters['idToko'];
            $statusPelunasan    =   $arrParameters['statusPelunasan'];
            $tanggalAwal        =   $arrParameters['tanggalAwal'];
            $tanggalAwalDT      =   Time::parse($tanggalAwal);
            $tanggalAwalStr     =   $tanggalAwalDT->format('d M Y');
            $tanggalAkhir       =   $arrParameters['tanggalAkhir'];
            $tanggalAkhirDT     =   Time::parse($tanggalAkhir);
            $tanggalAkhirStr    =   $tanggalAkhirDT->format('d M Y');
            $kataKunciPencarian =   $arrParameters['kataKunciPencarian'];
            $tampilBelumLunas   =   $arrParameters['tampilBelumLunas'];
            $baseData           =   $tagihanModel->getDetailTagihanMutasiToko($idToko, $statusPelunasan, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian, $tampilBelumLunas);
            $dataLaporan        =   $baseData->limit(999999, 0)->get()->getResultObject();

            $spreadsheet            =   new Spreadsheet();
            $spreadsheetGenerator   =   new SpreadsheetGenerator();
            $activeWorksheet        =   $spreadsheet->getActiveSheet();
            $periodeStr             =   $tanggalAwalStr.' s/d '.$tanggalAkhirStr;
            $statusPelunasanStr     =   'Semua Status';
            $tampilBelumLunasStr    =   'Tidak';

            if($tampilBelumLunas == 1){
                $periodeStr         =   '-';
                $tampilBelumLunasStr=   'Ya';
            }

            switch($statusPelunasan){
                case '0': $statusPelunasanStr =   'Belum Lunas'; break;
                case '1': $statusPelunasanStr =   'Lunas'; break;
            }

            $arrTitleData   =   ['Laporan Detail Tagihan Mutasi Barang Toko'];
            $arrFilterData  =   [
                ['Toko', $mainOperation->getDetailToko($idToko)['NAMA']],
                ['Status Pelunasan', $statusPelunasanStr],
                ['Periode', $periodeStr],
                ['Belum Lunas Saja', $tampilBelumLunasStr],
                ['Kata Pencarian', $kataKunciPencarian == "" ? '-' : $kataKunciPencarian],
                ['Waktu Proses', date('d M Y H:i')]
            ];
            $arrHeaderData  =   [
                [['A'], 1, 'Nomor Nota', 18, 'center'],
                [['B'], 1, 'Status', 12, 'center'],
                [['C'], 1, 'Jatuh Tempo', 16, 'center'],
                [['D'], 1, 'Pembayaran Ke', 14, 'right'],
                [['E'], 1, 'Keterangan', 40, 'center'],
                [['F'], 1, 'Nominal', 12, 'right']
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
                foreach($dataLaporan as $keyDataLaporan){
                    $statusTagihanStr   =   '-';
                    switch($keyDataLaporan->STATUS){
                        case '0': $statusTagihanStr =   'Belum Lunas'; break;
                        case '1': $statusTagihanStr =   'Lunas'; break;
                    }

                    $activeWorksheet
                    ->setCellValue('A'.$rowNumberTableContent, $keyDataLaporan->NOTAMUTASINOMOR)
                    ->setCellValue('B'.$rowNumberTableContent, $statusTagihanStr)
                    ->setCellValue('C'.$rowNumberTableContent, $keyDataLaporan->TANGGALJATUHTEMPO)
                    ->setCellValue('D'.$rowNumberTableContent, $keyDataLaporan->PEMBAYARANKE)
                    ->setCellValue('E'.$rowNumberTableContent, $keyDataLaporan->KETERANGAN)
                    ->setCellValue('F'.$rowNumberTableContent, intval($keyDataLaporan->NOMINAL));
                    $rowNumberTableContent++;
                }
            } else {
                $activeWorksheet->setCellValue($columnFirstTable.$rowNumberTableContent, 'Tidak ada data tagihan mutasi barang toko yang ditemukan pada periode tersebut');
                $activeWorksheet->mergeCells($columnFirstTable.$rowNumberTableContent.':'.$columnLastTable.$rowNumberTableContent);
            }
		
            $spreadsheetGenerator->setDocumentTableStyle($activeWorksheet, $columnFirstTable, $columnLastTable, $rowFirstTable, $rowNumberTableContent);
            $spreadsheetGenerator->writeDocumentOutput($spreadsheet, 'Laporan_Tagihan_Mutasi_Barang_Toko');
        } catch (\Throwable $th) {
            return throwResponseInternalServerError('[E-AUTH-001] Internal server error', [$th]);
        }
    }
}