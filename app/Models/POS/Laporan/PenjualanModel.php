<?php

namespace App\Models\POS\Laporan;
use CodeIgniter\Model;

class PenjualanModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_penjualanrekap';
    protected $primaryKey       = 'IDPENJUALANREKAP';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDTOKO', 'IDCUSTOMER', 'IDMETODEBAYAR', 'NOTAPENJUALANNOMOR', 'TOTALJENISBARANGSKU', 'TOTALHARGABARANG', 'TOTALHARGALAIN', 'TOTALHARGAAKHIR', 'TOTALBAYAR', 'CATATAN', 'INPUTUSER', 'INPUTTANGGALWAKTU'];

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
    
    public function getDataLaporanRekapPerTanggal($idToko, $tanggalAwal, $tanggalAkhir)
    {	
        $this->select("DATE_FORMAT(A.INPUTTANGGALWAKTU, '%Y-%m-%d') AS TANGGALTRANSAKSI, DATE_FORMAT(A.INPUTTANGGALWAKTU, '%d %M %Y') AS TANGGALTRANSAKSISTR,
                    COUNT(A.IDPENJUALANREKAP) AS TOTALNOTA, SUM(A.TOTALJENISBARANGSKU) AS TOTALJENISBARANGSKU, SUM(B.JUMLAH) AS TOTALITEM,
                    SUM(A.TOTALHARGABARANG) AS TOTALHARGABARANG, SUM(A.TOTALHARGADISKON) AS TOTALHARGADISKON, SUM(A.TOTALHARGALAIN) AS TOTALHARGALAIN,
                    SUM(A.TOTALHARGAAKHIR) AS TOTALHARGAAKHIR");
        $this->from('t_penjualanrekap A', true);
        $this->join('t_penjualanbarang B', 'A.IDPENJUALANREKAP = B.IDPENJUALANREKAP', 'LEFT');
        $this->where('A.IDTOKO', $idToko);
        $this->where('DATE(A.INPUTTANGGALWAKTU) >= ', $tanggalAwal);
        $this->where('DATE(A.INPUTTANGGALWAKTU) <=', $tanggalAkhir);
        $this->groupBy('DATE(A.INPUTTANGGALWAKTU)');
        $this->orderBy('A.INPUTTANGGALWAKTU ASC');

        return $this;
	}
    
    public function getDataLaporanRekapPerNota($idToko, $tanggalAwal, $tanggalAkhir, $idMetodeBayar, $kataKunciPencarian)
    {	
        $this->select("A.IDPENJUALANREKAP, A.NOTAPENJUALANNOMOR, B.NAMA AS CUSTOMERNAMA, B.ALAMAT AS CUSTOMERALAMAT, B.TELPON AS CUSTOMERTELPON, C.METODEBAYAR,
                    A.TOTALJENISBARANGSKU, A.TOTALHARGABARANG, A.TOTALHARGADISKON, A.TOTALHARGALAIN, A.TOTALHARGAAKHIR, A.CATATAN, A.INPUTUSER,
                    DATE_FORMAT(A.INPUTTANGGALWAKTU, '%d %b %Y %H:%i') AS INPUTTANGGALWAKTU, '' AS URLPRINTNOTAPENJUALAN");
        $this->from('t_penjualanrekap A', true);
        $this->join('m_customer B', 'A.IDCUSTOMER = B.IDCUSTOMER', 'LEFT');
        $this->join('a_metodebayar C', 'A.IDMETODEBAYAR = C.IDMETODEBAYAR', 'LEFT');
        $this->where('A.IDTOKO', $idToko);
        $this->where('DATE(A.INPUTTANGGALWAKTU) >= ', $tanggalAwal);
        $this->where('DATE(A.INPUTTANGGALWAKTU) <=', $tanggalAkhir);

        if(isset($idMetodeBayar) && $idMetodeBayar > 0) {
            $this->where('A.IDMETODEBAYAR', $idMetodeBayar);
        }

        if(isset($kataKunciPencarian) && !is_null($kataKunciPencarian) && $kataKunciPencarian != ''){
            $this->groupStart();
            $this->like('A.NOTAPENJUALANNOMOR', $kataKunciPencarian, 'both')
            ->orLike('B.NAMA', $kataKunciPencarian, 'both')
            ->orLike('B.ALAMAT', $kataKunciPencarian, 'both')
            ->orLike('B.TELPON', $kataKunciPencarian, 'both')
            ->orLike('A.CATATAN', $kataKunciPencarian, 'both')
            ->orLike('A.INPUTUSER', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }

        return $this;
	}

    public function getDataLaporanDetailPerNota($idToko, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian)
    {	
        $this->select("A.IDPENJUALANREKAP, A.NOTAPENJUALANNOMOR, B.NAMA AS NAMACUSTOMER, C.METODEBAYAR, DATE_FORMAT(A.INPUTTANGGALWAKTU, '%d-%m-%Y') AS INPUTTANGGALWAKTU,
                A.INPUTUSER, A.CATATAN, A.TOTALHARGABARANG, A.TOTALHARGALAIN, A.TOTALHARGADISKON, A.TOTALHARGAAKHIR, '[]' AS DAFTARBARANGSKU");
        $this->from('t_penjualanrekap A', true);
        $this->join('m_customer AS B', 'A.IDCUSTOMER = B.IDCUSTOMER', 'LEFT');
        $this->join('a_metodebayar AS C', 'A.IDMETODEBAYAR = C.IDMETODEBAYAR', 'LEFT');
        $this->where('A.IDTOKO', $idToko);
        $this->where('DATE(A.INPUTTANGGALWAKTU) >=', $tanggalAwal);
        $this->where('DATE(A.INPUTTANGGALWAKTU) <=', $tanggalAkhir);

        if(isset($kataKunciPencarian) && $kataKunciPencarian != "") {
            $this->groupStart();
            $this->like('A.NOTAPENJUALANNOMOR', $kataKunciPencarian, 'both')
            ->orLike('B.NAMA', $kataKunciPencarian, 'both')
            ->orLike('A.INPUTUSER', $kataKunciPencarian, 'both')
            ->orLike('A.CATATAN', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }
        $this->orderBy('A.INPUTTANGGALWAKTU', 'ASC');
        return $this;
	}

    public function getDataBarangSKUPenjualan($idPenjualanRekap)
    {	
        $this->select("CONCAT(C.KODEBARANG, '-', B.KODESKU) AS KODESKU, B.DESKRIPSI AS DESKRIPSISKU, A.JUMLAH, D.NAMASATUAN, A.HARGAAWAL,
                A.HARGADISKON, (A.JUMLAH * A.HARGASATUAN) AS TOTALHARGAJUAL");
        $this->from('t_penjualanbarang AS A', true);
        $this->join('m_barangsku AS B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->join('m_barang AS C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangsatuan AS D', 'A.IDBARANGSATUAN = D.IDBARANGSATUAN', 'LEFT');
        $this->where('A.IDPENJUALANREKAP', $idPenjualanRekap);
        $this->orderBy('C.KODEBARANG');

        $result =   $this->get()->getResultObject();

        if(is_null($result)) return [];
        return $result;
	}

    public function getDataLaporanRekapPerBarang($idToko, $idBarangKategori, $idBarangMerk, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian)
    {	
        $this->select("E.NAMAKATEGORI, F.NAMAMERK, C.NAMABARANG, CONCAT(C.KODEBARANG, '-', B.KODESKU) AS KODESKU, B.DESKRIPSI AS DESKRIPSISKU, D.NAMASATUAN,
                    SUM(A.JUMLAH) AS JUMLAHTERJUAL, A.HARGAAWAL, A.HARGADISKON, A.HARGASATUAN, SUM(A.JUMLAH) * A.HARGASATUAN AS TOTALHARGAJUAL");
        $this->from('t_penjualanbarang A', true);
        $this->join('m_barangsku AS B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->join('m_barang AS C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangsatuan AS D', 'A.IDBARANGSATUAN = D.IDBARANGSATUAN', 'LEFT');
        $this->join('m_barangkategori AS E', 'C.IDBARANGKATEGORI = E.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS F', 'C.IDBARANGMERK = F.IDBARANGMERK', 'LEFT');
        $this->join('t_penjualanrekap AS G', 'A.IDPENJUALANREKAP = G.IDPENJUALANREKAP', 'LEFT');
        $this->where('G.IDTOKO', $idToko);
        $this->where('DATE(G.INPUTTANGGALWAKTU) >=', $tanggalAwal);
        $this->where('DATE(G.INPUTTANGGALWAKTU) <=', $tanggalAkhir);

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

        $this->groupBy('A.IDBARANGSKU, A.IDBARANGSATUAN, A.HARGAAWAL, A.HARGADISKON, A.HARGASATUAN');
        $this->orderBy('E.NAMAKATEGORI, F.NAMAMERK, C.KODEBARANG, B.KODESKU', 'ASC');
        return $this;
	}
    
    public function getDataLaporanDetailPerBarang($idToko, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian)
    {	
        $this->select("B.NOTAPENJUALANNOMOR, F.NAMAKATEGORI, E.NAMAMERK, D.NAMABARANG, C.KODESKU, '[]' AS ATRIBUTSKUSTR, C.DESKRIPSI AS DESKRIPSISKU, A.HARGASATUAN,
                    A.JUMLAH, A.HARGADISKON, G.NAMASATUAN, (A.HARGASATUAN * A.JUMLAH) AS TOTALHARGA, DATE_FORMAT(B.INPUTTANGGALWAKTU, '%d %b %Y %H:%i') AS INPUTTANGGALWAKTU,
                    A.IDBARANGSKU");
        $this->from('t_penjualanbarang A', true);
        $this->join('t_penjualanrekap B', 'A.IDPENJUALANREKAP = B.IDPENJUALANREKAP', 'LEFT');
        $this->join('m_barangsku C', 'A.IDBARANGSKU = C.IDBARANGSKU', 'LEFT');
        $this->join('m_barang D', 'C.IDBARANG = D.IDBARANG', 'LEFT');
        $this->join('m_barangmerk E', 'D.IDBARANGMERK = E.IDBARANGMERK', 'LEFT');
        $this->join('m_barangkategori F', 'D.IDBARANGKATEGORI = F.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangsatuan G', 'A.IDBARANGSATUAN = G.IDBARANGSATUAN', 'LEFT');
        $this->where('B.IDTOKO', $idToko);
        $this->where('DATE(B.INPUTTANGGALWAKTU) >= ', $tanggalAwal);
        $this->where('DATE(B.INPUTTANGGALWAKTU) <=', $tanggalAkhir);
        $this->orderBy('B.INPUTTANGGALWAKTU', 'DESC');

        if(isset($kataKunciPencarian) && !is_null($kataKunciPencarian) && $kataKunciPencarian != ''){
            $this->groupStart();
            $this->like('B.NOTAPENJUALANNOMOR', $kataKunciPencarian, 'both')
            ->orLike('F.NAMAKATEGORI', $kataKunciPencarian, 'both')
            ->orLike('E.NAMAMERK', $kataKunciPencarian, 'both')
            ->orLike('D.NAMABARANG', $kataKunciPencarian, 'both')
            ->orLike('C.KODESKU', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }

        return $this;
	}
    
    public function getDetailNotaPenjualan($idPenjualanRekap)
    {	
        $this->select("A.IDTOKO, A.NOTAPENJUALANNOMOR, B.NAMA AS NAMACUSTOMER, C.METODEBAYAR, A.CATATAN, A.INPUTUSER,
                    DATE_FORMAT(A.INPUTTANGGALWAKTU, '%d-%m-%y') AS INPUTTANGGAL, DATE_FORMAT(A.INPUTTANGGALWAKTU, '%H:%i:%s') AS INPUTWAKTU,
                    A.TOTALHARGABARANG, A.TOTALHARGADISKON + IFNULL(A.TOTALHARGADISKONEVENT, 0) AS TOTALHARGADISKON, A.TOTALHARGALAIN, A.TOTALHARGAAKHIR, A.TOTALBAYAR");
        $this->from('t_penjualanrekap A', true);
        $this->join('m_customer B', 'A.IDCUSTOMER = B.IDCUSTOMER', 'LEFT');
        $this->join('a_metodebayar C', 'A.IDMETODEBAYAR = C.IDMETODEBAYAR', 'LEFT');
        $this->where('A.IDPENJUALANREKAP', $idPenjualanRekap);
        $this->limit(1);

        $result =   $this->first();

        if(is_null($result)) return false;
        return $result;
	}
    
    public function getDaftarBarangNota($idPenjualanRekap)
    {	
        $this->select("B.KODESKU, D.NAMAMERK, B.DESKRIPSI AS DESKRIPSISKU, A.JUMLAH, E.KODESATUAN, A.HARGAAWAL, (A.HARGAAWAL * A.JUMLAH) AS HARGASUBTOTAL,
                    IFNULL(F.DESKRIPSI, '-') AS DESKRIPSIDISKON, IFNULL(F.TIPEDISKON, 0) AS TIPEDISKON, IFNULL(F.JUMLAHDISKON, 0) AS JUMLAHDISKON, A.HARGADISKON,
                    IFNULL(H.NAMAPAKETDISKON, '-') AS DESKRIPSIDISKONPAKET, IFNULL(G.TIPEDISKON, 0) AS TIPEDISKONPAKET, IFNULL(G.JUMLAHDISKON, 0) AS JUMLAHDISKONPAKET, A.HARGADISKON,
                    A.HARGASATUAN");
        $this->from('t_penjualanbarang A', true);
        $this->join('m_barangsku B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->join('m_barang C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangmerk D', 'C.IDBARANGMERK = D.IDBARANGMERK', 'LEFT');
        $this->join('m_barangsatuan E', 'A.IDBARANGSATUAN = E.IDBARANGSATUAN', 'LEFT');
        $this->join('t_diskonretail F', 'A.IDDISKONRETAIL = F.IDDISKONRETAIL', 'LEFT');
        $this->join('t_diskonretailpaketnominal G', 'A.IDDISKONRETAILPAKETNOMINAL = G.IDDISKONRETAILPAKETNOMINAL', 'LEFT');
        $this->join('t_diskonretailpaket H', 'G.IDDISKONRETAILPAKET = H.IDDISKONRETAILPAKET', 'LEFT');
        $this->where('A.IDPENJUALANREKAP', $idPenjualanRekap);
        $this->orderBy('A.IDPENJUALANBARANG');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}
    
    public function getDaftarBiayaLain($idPenjualanRekap)
    {	
        $this->select("JENISBIAYA, NOMINAL");
        $this->from('t_penjualanbiayalain', true);
        $this->where('IDPENJUALANREKAP', $idPenjualanRekap);
        $this->orderBy('IDPENJUALANBIAYALAIN');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}
    
    public function getDaftarHargaPaket($idPenjualanRekap)
    {	
        $this->select("C.NAMAHARGARETAILPAKET, CEIL(SUM(A.JUMLAH) / SUM(B.JUMLAH)) AS JUMLAHPAKET");
        $this->from('t_penjualanbarang AS A', true);
        $this->join('t_hargaretailpaketsku AS B', 'A.IDHARGARETAILPAKETSKU = B.IDHARGARETAILPAKETSKU', 'LEFT');
        $this->join('t_hargaretailpaket AS C', 'B.IDHARGARETAILPAKET = C.IDHARGARETAILPAKET', 'LEFT');
        $this->where('A.IDPENJUALANREKAP', $idPenjualanRekap);
        $this->where('A.IDHARGARETAILPAKETSKU !=', 0);
        $this->groupBy('B.IDHARGARETAILPAKET');
        $this->orderBy('C.NAMAHARGARETAILPAKET');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}

    public function getDaftarDiskonEventPenjualan($idPenjualanRekap)
    {	
        $this->select("B.NAMAEVENT, B.TIPEDISKON, A.NOMINAL, A.KETERANGAN");
        $this->from('t_penjualandiskonevent AS A', true);
        $this->join('t_diskonevent AS B', 'A.IDDISKONEVENT = B.IDDISKONEVENT', 'LEFT');
        $this->where('A.IDPENJUALANREKAP', $idPenjualanRekap);

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
    }
}