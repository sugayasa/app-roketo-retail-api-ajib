<?php

namespace App\Controllers\WH;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\MPDFGenerator;
use App\Models\ERP\Master\BarangSKUModel;
use App\Models\WH\SuratJalanModel;
use App\Models\MainOperation;

class SuratJalan extends ResourceController
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
            $this->idGudang         =   $this->userData->idGudang;
            $this->currentDateTime  =   $request->currentDateTime;
        } catch (\Throwable $th) {
        }
    }

    public function index()
    {
        return $this->failForbidden('[E-AUTH-000] Forbidden Access');
    }

    public function getDaftarSuratJalan()
    {
        $rules  =   [
            'kataKunciPencarian'    =>  ['label' => 'Kata Kunci Pencarian', 'rules' => 'permit_empty|alpha_numeric_punct'],
            'dataPerPage'           =>  ['label' => 'Data Per Halaman', 'rules' => 'required|integer|greater_than[0]|less_than_equal_to[100]'],
            'pageNumber'            =>  ['label' => 'Halaman', 'rules' => 'required|integer|greater_than[0]']
        ];

        if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());

        $mainOperation          =   new MainOperation();
        $suratJalanModel        =   new SuratJalanModel();
        $kataKunciPencarian     =   $this->request->getVar('kataKunciPencarian');
        $dataPerPage            =   $this->request->getVar('dataPerPage');
        $pageNumber             =   $this->request->getVar('pageNumber');
        $baseData               =   $suratJalanModel->getDaftarSuratJalan($this->idGudang, $kataKunciPencarian);
        $totalNumberData        =   $baseData->countAllResults(false);
        $pageProperty           =   $mainOperation->generatePageProperty($pageNumber, $dataPerPage, $totalNumberData);

        if($totalNumberData > 0) {
            $dataSuratJalan =   $baseData->asObject()->findAll($dataPerPage, ($pageNumber - 1) * $dataPerPage);
            $dataSuratJalan =   $dataSuratJalan == [] ? [] : encodeDatabaseObjectResultKey($dataSuratJalan, ['IDSURATJALANREKAP']);
            return $this->setResponseFormat('json')->respond([
                "dataSuratJalan"    =>  $dataSuratJalan,
                "pageProperty"      =>  $pageProperty
            ]);
        } else {
            $dataReturn     =   [
                "dataSuratJalan"    =>  [],
                "pageProperty"      =>  $pageProperty
            ];
            return throwResponseNotFound('Tidak ada data surat jalan yang ditemukan', $dataReturn);
        }
    }

    public function getDetailSuratJalan()
    {
        $rules      =   ['idSuratJalanRekap'    =>  ['label' => 'Id Surat Jalan Rekap', 'rules' => 'required|alpha_numeric']];
        $messages   =   [
            'idSuratJalanRekap'  => [
                'required'      => 'Data kiriman tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak valid, silakan periksa kembali'
            ]
        ];
        
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $suratJalanModel    =   new SuratJalanModel();
        $idSuratJalanRekap  =   hashidDecode($this->request->getVar('idSuratJalanRekap'));
        $dataNotaMutasiRekap=   $suratJalanModel->getDaftarNotaMutasiRekap($idSuratJalanRekap);

        if(!$dataNotaMutasiRekap) return throwResponseNotFound('Tidak ada data nota mutasi yang ditemukan', ['dataNotaMutasiRekap' =>  []]);

        $dataNotaMutasiRekap=   encodeDatabaseObjectResultKey($dataNotaMutasiRekap, ['IDTOKONOTAMUTASIREKAP']);
        return $this->setResponseFormat('json')->respond([
            "dataNotaMutasiRekap"   =>  $dataNotaMutasiRekap,
            "urlPDFSuratJalan"      =>  "https://roketo.id/public/warehouse/suratJalan/generatePDFSuratJalan"
        ]);
    }

    public function getDaftarNotaMutasiRekap()
    {
        $suratJalanModel=   new SuratJalanModel();
        $listData       =   $suratJalanModel->getDaftarNotaMutasiRekapNonSuratJalan($this->idGudang);

        if(!$listData) return throwResponseNotFound('Tidak ada nota mutasi barang yang ditemukan', ['listData' =>  []]);

        $listData       =   encodeDatabaseObjectResultKey($listData, ['IDTOKONOTAMUTASIREKAP']);
        return $this->setResponseFormat('json')->respond(["listData" =>  $listData]);
    }

    public function getDetailNotaMutasiBarang()
    {
        $rules      =   ['idTokoNotaMutasiRekap'    =>  ['label' => 'Id Toko Nota Mutasi Rekap', 'rules' => 'required|alpha_numeric']];
        $messages   =   [
            'idTokoNotaMutasiRekap'  => [
                'required'      => 'Data kiriman tidak valid, silakan periksa kembali',
                'alpha_numeric' => 'Data kiriman tidak valid, silakan periksa kembali'
            ]
        ];
        
        if(!$this->validate($rules, $messages)) return $this->fail($this->validator->getErrors());

        $suratJalanModel        =   new SuratJalanModel();
        $idTokoNotaMutasiRekap  =   hashidDecode($this->request->getVar('idTokoNotaMutasiRekap'));
        $dataBarangSKU          =   $suratJalanModel->getDataBarangSKUNotaMutasi($idTokoNotaMutasiRekap);
        
        if($dataBarangSKU && count($dataBarangSKU) > 0){
            $barangSKUModel =   new BarangSKUModel();
            foreach($dataBarangSKU as $keyBarangSKU){
                $idBarangSKU                =   isset($keyBarangSKU->IDBARANGSKU) && $keyBarangSKU->IDBARANGSKU != "" ? $keyBarangSKU->IDBARANGSKU : 0;
                $keyBarangSKU->ATRIBUTSKUSTR=   $barangSKUModel->getArrAtributSKU($idBarangSKU);
                unset($keyBarangSKU->IDBARANGSKU);
            }
        }

        return $this->setResponseFormat('json')->respond(["dataBarangSKU" => $dataBarangSKU]);
    }

    public function saveSuratJalan()
    {
        $rules  =   [
            'namaPengirim'              =>  ['label' => 'Nama Pengirim', 'rules' => 'required|alpha_numeric_punct'],
            'arrIdTokoNotaMutasiRekap.*'=>  ['label' => 'Id Toko Nota Mutasi', 'rules' => 'required|alpha_numeric'],
            'catatan'                   =>  ['label' => 'Catatan', 'rules' => 'permit_empty|alpha_numeric_punct']
        ];

        if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
 
        $mainOperation              =   new MainOperation();
        $suratJalanModel            =   new SuratJalanModel();
        $namaPengirim               =   $this->request->getVar('namaPengirim');
        $arrIdTokoNotaMutasiRekap   =   $this->request->getVar('arrIdTokoNotaMutasiRekap');
        $catatan                    =   $this->request->getVar('catatan');
        $nomorSuratJalan            =   $this->generateSuratJalanNomor();
        $arrNotaMutasiRekapProccess =   [];
        $jumlahNotaMutasi           =   0;
        
        foreach($arrIdTokoNotaMutasiRekap as $idTokoNotaMutasiRekap){
            $idTokoNotaMutasiRekap      =   hashidDecode($idTokoNotaMutasiRekap);
            if($idTokoNotaMutasiRekap && $idTokoNotaMutasiRekap != 0){
                $isNotaMutasiRekapValid =   $suratJalanModel->isNotaMutasiRekapValidForSuratJalan($idTokoNotaMutasiRekap);

                if($isNotaMutasiRekapValid !== true){
                    $nomorNotaMutasi    =   $isNotaMutasiRekapValid['NOTAMUTASINOMOR'];
                    $nomorSuratJalan    =   $isNotaMutasiRekapValid['NOMORREKAPSURAT'];
                    $tanggalSuratJalan  =   $isNotaMutasiRekapValid['INPUTTANGGALWAKTU'];
                    return throwResponseNotAcceptable('Nota mutasi barang dengan nomor : <b>'.$nomorNotaMutasi.'</b> telah diterbitkan surat jalan dengan nomor : <b>'.$nomorSuratJalan.'</b> pada '.$tanggalSuratJalan);
                }

                $arrNotaMutasiRekapProccess[] =   $idTokoNotaMutasiRekap;
                $jumlahNotaMutasi++;
            }
        }

        $arrInsertSuratJalanRekap   =   [
            'IDGUDANG'          =>  $this->idGudang,
            'NOMORREKAPSURAT'   =>  $nomorSuratJalan,
            'NAMAPENGIRIM'      =>  $namaPengirim,
            'JUMLAHNOTAMUTASI'  =>  $jumlahNotaMutasi,
            'CATATAN'           =>  $catatan,
            'INPUTUSER'         =>  $this->userData->name.' ('.$this->userData->userLevelName.')',
            'INPUTTANGGALWAKTU' =>  $this->currentDateTime
        ];

        $procInsertRekap    =   $mainOperation->insertDataTable('t_suratjalanrekap', $arrInsertSuratJalanRekap);
        if(!$procInsertRekap['status']) return switchMySQLErrorCode($procInsertRekap['errCode']);

        $idSuratJalanRekap  =   $procInsertRekap['insertID'];
        $urutanSuratJalan   =   1;
        foreach($arrNotaMutasiRekapProccess as $idTokoNotaMutasiRekap){
            $arrInsertSuratJalanDetail    =   [
                'IDSURATJALANREKAP'       =>  $idSuratJalanRekap,
                'IDTOKONOTAMUTASIREKAP'   =>  $idTokoNotaMutasiRekap,
                'URUTANSURAT'                  =>  $urutanSuratJalan
            ];

            $mainOperation->insertDataTable('t_suratjalandetail', $arrInsertSuratJalanDetail);
            $urutanSuratJalan++;
        }

        return throwResponseOK('Surat jalan telah diterbitkan dengan nomor : <b>'.$nomorSuratJalan.'</b> untuk '.$jumlahNotaMutasi.' nota mutasi barang');
    }

    private function generateSuratJalanNomor(){
        return 'SJG-' . strtoupper(bin2hex(random_bytes(2))) . date('ymd');
    }

    public function generatePDFSuratJalan()
    {
        $mPDFGenerator      =   new MPDFGenerator();
        $dataSurat = [
            'no_surat' => 'SJ/001/10/2025',
            'tanggal' => date('d F Y'),
            
            // Kop Surat/Perusahaan
            'kop_perusahaan' => [
                'nama' => 'PT LOGISTIK MAJU JAYA',
                'alamat' => 'Jl. Merdeka No. 45, Jakarta Pusat',
                'telepon' => '(021) 1234 5678',
                'email' => 'info@logistikmj.co.id',
            ],

            // Detail Gudang Asal
            'gudang_asal' => [
                'nama' => 'Gudang Utama Jakarta',
                'petugas' => 'Budi Santoso',
                'alamat' => 'Jl. Industri Raya Blok A5, Jakarta Utara'
            ],

            // Detail Toko Tujuan
            'toko_tujuan' => [
                'nama' => 'Toko Elektronik Mandiri',
                'penerima' => 'Siti Aisyah (Manager Toko)',
                'alamat' => 'Jl. Asia Afrika No. 101, Bandung'
            ],

            // Tabel Daftar Barang
            'daftar_barang' => [
                ['kode' => 'ELC001', 'nama' => 'Televisi LED 40 Inch', 'qty' => 5, 'unit' => 'Unit'],
                ['kode' => 'ELC005', 'nama' => 'Kulkas Dua Pintu', 'qty' => 3, 'unit' => 'Unit'],
                ['kode' => 'ELC012', 'nama' => 'Mesin Cuci Otomatis', 'qty' => 10, 'unit' => 'Unit'],
                ['kode' => 'ACC020', 'nama' => 'Kabel HDMI 3 Meter', 'qty' => 50, 'unit' => 'Pcs'],
            ],

            // Catatan
            'catatan' => 'Mohon barang diperiksa dengan baik saat diterima. Segala kerusakan setelah penandatanganan menjadi tanggung jawab penerima.'
        ];

        // 1. Render view HTML dengan data
        $html = view('pdf/SuratJalan', $dataSurat);

        // 2. Generate PDF
        $filename = 'SURAT_JALAN_' . $dataSurat['no_surat'] . '_' . date('YmdHis') . '.pdf';
        
        $pdfContent =   $mPDFGenerator->generatePDFFile($html, $filename);
        return $this->response
                    // Konten: Konten biner PDF
                    ->setBody($pdfContent) 
                    // Header 1: Tipe konten harus PDF
                    ->setContentType('application/pdf') 
                    // Header 2: Atur cara browser menangani file (Download / Inline)
                    // Gunakan 'attachment' untuk DOWNLOAD, atau 'inline' untuk VIEW
                    ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
                    // Header 3: Atur ukuran konten
                    ->setHeader('Content-Length', strlen($pdfContent));
    }
}