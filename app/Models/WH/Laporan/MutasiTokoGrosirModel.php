<?php

namespace App\Models\WH\Laporan;
use CodeIgniter\Model;

class MutasiTokoGrosirModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_tokonotamutasirekap';
    protected $primaryKey       = 'IDTOKONOTAMUTASIREKAP';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDTOKO', 'IDGUDANG', 'NOTAMUTASINOMOR', 'TOTALSKU', 'TOTALNOMINALBARANG', 'PERSENPENYELESAIANINBOUND', 'KETERANGAN', 'REQUESTUSER', 'REQUESTTANGGALWAKTU', 'PROSESUSER', 'PROSESKETERANGAN', 'PROSESTANGGALWAKTU', 'STATUS'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
    
    public function getDataLaporanRekapPerTanggal($idGudang, $tanggalAwal, $tanggalAkhir)
    {	
        $this->select("DATE_FORMAT(A.PROSESTANGGALWAKTU, '%Y-%m-%d') AS TANGGALTRANSAKSI, COUNT(A.IDTOKONOTAMUTASIREKAP) AS TOTALNOTA, 
                    SUM(A.TOTALSKU) AS TOTALJENISBARANGSKU, SUM(B.JUMLAH) AS TOTALITEM, SUM(B.HARGAAWAL) AS TOTALHARGABARANG,
                    SUM(B.HARGADISKON) AS TOTALHARGADISKON, SUM(B.HARGAGROSIR) AS TOTALHARGAAKHIR");
        $this->from('t_tokonotamutasirekap A', true);
        $this->join('t_tokonotamutasibarang B', 'A.IDTOKONOTAMUTASIREKAP = B.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $this->where('A.IDGUDANG', $idGudang);
        $this->where('DATE(A.PROSESTANGGALWAKTU) >= ', $tanggalAwal);
        $this->where('DATE(A.PROSESTANGGALWAKTU) <=', $tanggalAkhir);
        $this->groupBy('DATE(A.PROSESTANGGALWAKTU)');
        $this->orderBy('A.PROSESTANGGALWAKTU ASC');

        return $this;
	}
    
    public function getDataLaporanRekapPerNota($idGudang, $idToko, $idCaraPelunasan, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian)
    {	
        $this->select("A.IDTOKONOTAMUTASIREKAP, A.NOTAMUTASINOMOR, E.NAMA AS TOKONAMA, E.ALAMAT AS TOKOALAMAT, COUNT(DISTINCT(C.IDTOKONOTAMUTASIPEMBAYARAN)) AS JUMLAHPEMBAYARAN,
                    IFNULL(GROUP_CONCAT(DISTINCT(D.CARAPELUNASAN)), '-') AS CARAPELUNASAN, A.TOTALSKU, SUM(B.JUMLAH * B.HARGAAWAL) AS TOTALNOMINALBARANG,
                    SUM(B.JUMLAH * B.HARGADISKON) AS TOTALHARGADISKON, SUM(B.JUMLAH * B.HARGAGROSIR) AS TOTALHARGAAKHIR, A.REQUESTUSER,
                    DATE_FORMAT(A.REQUESTTANGGALWAKTU, '%d %b %Y %H:%i') AS REQUESTTANGGALWAKTU, A.KETERANGAN, A.PROSESKETERANGAN, A.PROSESUSER,
                    DATE_FORMAT(A.PROSESTANGGALWAKTU, '%d %b %Y %H:%i') AS PROSESTANGGALWAKTU");
        $this->from('t_tokonotamutasirekap A', true);
        $this->join('t_tokonotamutasibarang B', 'A.IDTOKONOTAMUTASIREKAP = B.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $this->join('t_tokonotamutasipembayaran C', 'A.IDTOKONOTAMUTASIREKAP = C.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $this->join('a_carapelunasan D', 'C.IDCARAPELUNASAN = D.IDCARAPELUNASAN', 'LEFT');
        $this->join('m_toko E', 'A.IDTOKO = E.IDTOKO', 'LEFT');
        $this->where('A.IDGUDANG', $idGudang);
        $this->where('DATE(A.PROSESTANGGALWAKTU) >= ', $tanggalAwal);
        $this->where('DATE(A.PROSESTANGGALWAKTU) <=', $tanggalAkhir);
        
        if(isset($idToko) && $idToko > 0) $this->where('A.IDTOKO', $idToko);
        if(isset($idCaraPelunasan) && $idCaraPelunasan > 0) $this->where('C.IDCARAPELUNASAN', $idCaraPelunasan);
        if(isset($kataKunciPencarian) && !is_null($kataKunciPencarian) && $kataKunciPencarian != ''){
            $this->groupStart();
            $this->like('A.NOTAMUTASINOMOR', $kataKunciPencarian, 'both')
            ->orLike('E.KODE', $kataKunciPencarian, 'both')
            ->orLike('E.NAMA', $kataKunciPencarian, 'both')
            ->orLike('E.ALAMAT', $kataKunciPencarian, 'both')
            ->orLike('A.KETERANGAN', $kataKunciPencarian, 'both')
            ->orLike('A.REQUESTUSER', $kataKunciPencarian, 'both')
            ->orLike('A.PROSESKETERANGAN', $kataKunciPencarian, 'both')
            ->orLike('A.PROSESUSER', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }
        $this->groupBy('A.IDTOKONOTAMUTASIREKAP');

        return $this;
	}

    public function getDataLaporanDetailPerNota($idGudang, $idToko, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian)
    {	
        $this->select("A.IDTOKONOTAMUTASIREKAP, A.NOTAMUTASINOMOR, B.NAMA AS NAMATOKO, B.ALAMAT AS ALAMATTOKO, A.KETERANGAN, 
                DATE_FORMAT(A.PROSESTANGGALWAKTU, '%d-%m-%Y') AS PROSESTANGGALWAKTU, A.PROSESUSER, A.TOTALSKU, A.TOTALNOMINALBARANG,
                '[]' AS DAFTARBARANGSKU");
        $this->from('t_tokonotamutasirekap A', true);
        $this->join('m_toko AS B', 'A.IDTOKO = B.IDTOKO', 'LEFT');
        $this->where('A.IDGUDANG', $idGudang);
        $this->where('DATE(A.PROSESTANGGALWAKTU) >=', $tanggalAwal);
        $this->where('DATE(A.PROSESTANGGALWAKTU) <=', $tanggalAkhir);
        
        if(isset($idToko) && $idToko != "" && $idToko > 0) $this->where('A.IDTOKO', $idToko);
        if(isset($kataKunciPencarian) && $kataKunciPencarian != "") {
            $this->groupStart();
            $this->like('A.NOTAMUTASINOMOR', $kataKunciPencarian, 'both')
            ->orLike('B.NAMA', $kataKunciPencarian, 'both')
            ->orLike('B.ALAMAT', $kataKunciPencarian, 'both')
            ->orLike('A.KETERANGAN', $kataKunciPencarian, 'both')
            ->orLike('A.PROSESUSER', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }
        $this->orderBy('A.PROSESTANGGALWAKTU', 'ASC');
        return $this;
	}

    public function getDataBarangSKUMutasiGrosir($idTokoNotaMutasiRekap)
    {	
        $this->select("CONCAT(C.KODEBARANG, '-', B.KODESKU) AS KODESKU, B.DESKRIPSI AS DESKRIPSISKU, A.JUMLAH, D.NAMASATUAN, A.HARGAAWAL, A.HARGADISKON,
                (A.JUMLAH * A.HARGAGROSIR) AS TOTALHARGAGROSIR");
        $this->from('t_tokonotamutasibarang AS A', true);
        $this->join('m_barangsku AS B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->join('m_barang AS C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangsatuan AS D', 'B.IDBARANGSATUAN = D.IDBARANGSATUAN', 'LEFT');
        $this->where('A.IDTOKONOTAMUTASIREKAP', $idTokoNotaMutasiRekap);
        $this->orderBy('C.KODEBARANG');

        $result =   $this->get()->getResultObject();

        if(is_null($result)) return [];
        return $result;
	}

    public function getDataLaporanRekapPerBarang($idGudang, $idBarangKategori, $idBarangMerk, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian)
    {	
        $this->select("E.NAMAKATEGORI, F.NAMAMERK, CONCAT(C.KODEBARANG, '-', B.KODESKU) AS KODESKU, B.DESKRIPSI AS DESKRIPSISKU, D.NAMASATUAN,
                    SUM(A.JUMLAH) AS JUMLAHTERJUAL, ROUND(AVG(A.HARGAAWAL)) AS HARGAAWALRERATA, ROUND(AVG(A.HARGADISKON)) AS HARGADISKONRERATA,
                    ROUND(AVG(A.HARGAGROSIR)) AS HARGAGROSIRRERATA, ROUND((SUM(A.JUMLAH) * AVG(A.HARGAGROSIR))) AS TOTALHARGAJUAL");
        $this->from('t_tokonotamutasibarang A', true);
        $this->join('m_barangsku AS B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->join('m_barang AS C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangsatuan AS D', 'B.IDBARANGSATUAN = D.IDBARANGSATUAN', 'LEFT');
        $this->join('m_barangkategori AS E', 'C.IDBARANGKATEGORI = E.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS F', 'C.IDBARANGMERK = F.IDBARANGMERK', 'LEFT');
        $this->join('t_tokonotamutasirekap AS G', 'A.IDTOKONOTAMUTASIREKAP = G.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $this->where('G.IDGUDANG', $idGudang);
        $this->where('DATE(G.PROSESTANGGALWAKTU) >=', $tanggalAwal);
        $this->where('DATE(G.PROSESTANGGALWAKTU) <=', $tanggalAkhir);

        if($idBarangKategori != 0) $this->where('C.IDBARANGKATEGORI', $idBarangKategori);
        if($idBarangMerk != 0) $this->where('C.IDBARANGMERK', $idBarangMerk);
        if(isset($kataKunciPencarian) && $kataKunciPencarian != "") {
            $this->groupStart();
            $this->like('E.NAMAKATEGORI', $kataKunciPencarian, 'both')
            ->orLike('F.NAMAMERK', $kataKunciPencarian, 'both')
            ->orLike('C.NAMABARANG', $kataKunciPencarian, 'both')
            ->orLike('C.KODEBARANG', $kataKunciPencarian, 'both')
            ->orLike('B.KODESKU', $kataKunciPencarian, 'both')
            ->orLike('B.DESKRIPSI', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }

        $this->groupBy('A.IDBARANGSKU, B.IDBARANGSATUAN');
        $this->orderBy('E.NAMAKATEGORI, F.NAMAMERK, C.KODEBARANG, B.KODESKU', 'ASC');
        return $this;
	}
    
    public function getDataLaporanDetailPerBarang($idGudang, $idToko, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian)
    {	
        $this->select("B.NOTAMUTASINOMOR, F.NAMAKATEGORI, E.NAMAMERK, D.NAMABARANG, C.KODESKU, '[]' AS ATRIBUTSKUSTR, C.DESKRIPSI AS DESKRIPSISKU, A.HARGAAWAL,
                    G.NAMASATUAN, A.JUMLAH, A.HARGADISKON, A.HARGAGROSIR, (A.HARGAGROSIR * A.JUMLAH) AS TOTALHARGA,
                    DATE_FORMAT(B.PROSESTANGGALWAKTU, '%d %b %Y %H:%i') AS PROSESTANGGALWAKTU, A.IDBARANGSKU");
        $this->from('t_tokonotamutasibarang A', true);
        $this->join('t_tokonotamutasirekap B', 'A.IDTOKONOTAMUTASIREKAP = B.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $this->join('m_barangsku C', 'A.IDBARANGSKU = C.IDBARANGSKU', 'LEFT');
        $this->join('m_barang D', 'C.IDBARANG = D.IDBARANG', 'LEFT');
        $this->join('m_barangmerk E', 'D.IDBARANGMERK = E.IDBARANGMERK', 'LEFT');
        $this->join('m_barangkategori F', 'D.IDBARANGKATEGORI = F.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangsatuan G', 'C.IDBARANGSATUAN = G.IDBARANGSATUAN', 'LEFT');
        $this->where('B.IDGUDANG', $idGudang);
        $this->where('DATE(B.PROSESTANGGALWAKTU) >= ', $tanggalAwal);
        $this->where('DATE(B.PROSESTANGGALWAKTU) <=', $tanggalAkhir);
        $this->orderBy('B.PROSESTANGGALWAKTU', 'DESC');

        if(isset($idToko) && $idToko > 0) $this->where('B.IDTOKO', $idToko);
        if(isset($kataKunciPencarian) && !is_null($kataKunciPencarian) && $kataKunciPencarian != ''){
            $this->groupStart();
            $this->like('B.NOTAMUTASINOMOR', $kataKunciPencarian, 'both')
            ->orLike('F.NAMAKATEGORI', $kataKunciPencarian, 'both')
            ->orLike('E.NAMAMERK', $kataKunciPencarian, 'both')
            ->orLike('D.NAMABARANG', $kataKunciPencarian, 'both')
            ->orLike('C.KODESKU', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }

        return $this;
	}
}