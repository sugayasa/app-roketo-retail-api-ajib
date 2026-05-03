<?php

namespace App\Models\POS\Stok;
use CodeIgniter\Model;

class PengaturanStokModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_tokonotamutasirekap';
    protected $primaryKey       = 'IDTOKONOTAMUTASIREKAP';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDTOKONOTAMUTASIREKAP', 'IDTOKO', 'IDGUDANG', 'NOTAMUTASINOMOR', 'TOTALSKU', 'PERSENPENYELESAIANINBOUND', 'KETERANGAN', 'REQUESTUSER', 'REQUESTTANGGALWAKTU', 'PROSESUSER', 'PROSESTANGGALWAKTU'];

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
    
    public function getDataBarangStokPenjualan($idGudangToko, $idToko, $idKelompokHargaGrosir, $idBarangKategori, $idBarangMerk, $searchKeyword, $sortCondition)
    {	
        $mainOperation      =   model('MainOperation');
        $arrIdKategoriBarang=   $mainOperation->getArrIdKategoriBarangToko($idToko);
        $last30DaysDate     =   date('Y-m-d', strtotime('-30 days'));
        $this->select("A.IDBARANGSKU, D.NAMAMERK, C.NAMAKATEGORI, CONCAT(B.NAMABARANG, ' | ', B.KODEBARANG) AS NAMABARANG, A.KODESKU, A.DESKRIPSI AS DESKRIPSISKU,
                '[]' AS ATRIBUTSKUSTR, IFNULL(SUM(F.JUMLAHMASUK - F.JUMLAHKELUAR), 0) AS STOKGUDANG, IFNULL(SUM(E.JUMLAHMASUK - E.JUMLAHKELUAR), 0) AS STOKTOKO,
                IFNULL(G.TOTALPENJUALAN, 0) AS TOTALPENJUALAN30HARI, IFNULL(H.HARGA, 0) AS HARGAGROSIR");
        $this->from('m_barangsku A', true);
        $this->join('m_barang AS B', 'A.IDBARANG = B.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS C', 'B.IDBARANGKATEGORI = C.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS D', 'B.IDBARANGMERK = D.IDBARANGMERK', 'LEFT');
        $this->join('t_tokostok AS E', 'A.IDBARANGSKU = E.IDBARANGSKU AND E.IDBARANGSATUAN = A.IDBARANGSATUAN AND E.IDTOKO = '.$idToko, 'LEFT');
        $this->join('t_gudangstok AS F', 'A.IDBARANGSKU = F.IDBARANGSKU AND F.IDBARANGSATUAN = A.IDBARANGSATUAN AND F.IDGUDANG = '.$idGudangToko, 'LEFT');
        $this->join(
            "(
                SELECT GA.IDBARANGSKU, GA.IDBARANGSATUAN, SUM(GA.JUMLAH) AS TOTALPENJUALAN 
                FROM t_penjualanbarang GA
                LEFT JOIN t_penjualanrekap GB ON GA.IDPENJUALANREKAP = GB.IDPENJUALANREKAP
                WHERE GB.IDTOKO = ".$idToko." AND GB.INPUTTANGGALWAKTU >= '".$last30DaysDate."'
                GROUP BY GA.IDBARANGSKU, GA.IDBARANGSATUAN
            ) AS G",
            'A.IDBARANGSKU = G.IDBARANGSKU AND A.IDBARANGSATUAN = G.IDBARANGSATUAN',
            'LEFT'
        );

        $this->join(
            "(
                SELECT IDBARANGSKU, IDBARANGSATUAN, HARGA
                FROM t_baranghargajualgrosir
                WHERE IDKELOMPOKHARGAGROSIR = ".$idKelompokHargaGrosir."
            ) AS H",
            'A.IDBARANGSKU = H.IDBARANGSKU AND A.IDBARANGSATUAN = H.IDBARANGSATUAN',
            'LEFT'
        );
        
        if($idBarangKategori != 0) {
            $this->where('B.IDBARANGKATEGORI', $idBarangKategori);
        } else {
            if(count($arrIdKategoriBarang) > 0) $this->whereIn('B.IDBARANGKATEGORI', $arrIdKategoriBarang);
        }
        
        if($idBarangMerk != 0) $this->where('B.IDBARANGMERK', $idBarangMerk);
        if($searchKeyword != "") {
            $this->groupStart();
            $this->like('D.NAMAMERK', $searchKeyword, 'both')
            ->orLike('C.NAMAKATEGORI', $searchKeyword, 'both')
            ->orLike('B.NAMABARANG', $searchKeyword, 'both')
            ->orLike('B.KODEBARANG', $searchKeyword, 'both')
            ->orLike('B.DESKRIPSI', $searchKeyword, 'both')
            ->orLike('A.KODESKU', $searchKeyword, 'both');
            $this->groupEnd();
        }

        if(isset($sortCondition) && $sortCondition != "") $this->orderBy($sortCondition);
        else $this->orderBy('B.NAMABARANG, A.DESKRIPSI');
        $this->groupBy('A.IDBARANGSKU');

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}

    public function getStokBarangGudangParent($idGudang, $idBarangSKU)
    {
        $this->select("B.IDBARANG, SUM(A.JUMLAHMASUK - A.JUMLAHKELUAR) AS STOK");
        $this->from('t_gudangstok A', true);
        $this->join('m_barangsku B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->where('A.IDGUDANG', $idGudang);
        $this->where('A.IDBARANGSKU', $idBarangSKU);
        $this->groupBy('A.IDBARANGSKU');

        $result =   $this->get()->getRowObject();
        if(is_null($result)) return ['IDBARANG' => 0, 'STOK' => 0];
        return $result;
    }
    
    public function getDataNotaPenerimaanStokAktif($idToko)
    {	
        $this->select("IDTOKONOTAMUTASIREKAP, NOTAMUTASINOMOR");
        $this->from('t_tokonotamutasirekap', true);
        $this->where('IDTOKO', $idToko);
        $this->where('STATUS', 1);
        $this->where('PERSENPENYELESAIANINBOUND <', 100);
        $this->orderBy('PROSESTANGGALWAKTU', 'DESC');

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function getDetailTokoNotaMutasiRekap($idTokoNotaMutasiRekap)
    {	
        $this->select("A.NOTAMUTASINOMOR, B.NAMA AS NAMATOKO, A.TOTALSKU, A.REQUESTUSER, DATE_FORMAT(A.REQUESTTANGGALWAKTU, '%d %b %Y %H:%i') AS REQUESTTANGGALWAKTU,
                    A.KETERANGAN, A.PROSESUSER, A.PROSESKETERANGAN, DATE_FORMAT(A.PROSESTANGGALWAKTU, '%d %b %Y %H:%i') AS PROSESTANGGALWAKTU");
        $this->from('t_tokonotamutasirekap A', true);
        $this->join('m_toko AS B', 'A.IDTOKO = B.IDTOKO', 'LEFT');
        $this->where('A.IDTOKONOTAMUTASIREKAP', $idTokoNotaMutasiRekap);
        $this->limit(1);
        
        $result =   $this->get()->getRowArray();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function getDataBarangSKUNotaPengajuanStok($idTokoNotaMutasiRekap)
    {	
        $this->select("A.IDTOKONOTAMUTASIINBOUND, B.IDBARANGSKU, D.NAMAKATEGORI, E.NAMAMERK, C.NAMABARANG, F.KODESKU, F.DESKRIPSI AS DESKRIPSISKU, '[]' AS ATRIBUTSKUSTR,
                    A.JUMLAHINBOUND, A.PROSESALLOW");
        $this->from('t_tokonotamutasiinbound A', true);
        $this->join('t_tokonotamutasibarang AS B', 'A.IDTOKONOTAMUTASIBARANG = B.IDTOKONOTAMUTASIBARANG', 'LEFT');
        $this->join('m_barang AS C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS D', 'C.IDBARANGKATEGORI = D.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS E', 'C.IDBARANGMERK = E.IDBARANGMERK', 'LEFT');
        $this->join('m_barangsku AS F', 'B.IDBARANGSKU = F.IDBARANGSKU', 'LEFT');
        $this->where('B.IDTOKONOTAMUTASIREKAP', $idTokoNotaMutasiRekap);
        
        $result =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}

    public function getDetailNotaTokoMutasiStok($idNotaTokoMutasiStok)
    {	
        $this->select("C.IDGUDANG, B.IDTOKONOTAMUTASIREKAP, A.IDTOKONOTAMUTASIBARANG, B.IDBARANG, B.IDBARANGSKU, D.IDBARANGSATUAN, C.NOTAMUTASINOMOR, DATE_FORMAT(C.REQUESTTANGGALWAKTU, '%d %b %Y') AS TANGGALNOTA,
        B.JUMLAH AS JUMLAHDISETUJUI, A.PROSESKE, A.PROSESALLOW");
        $this->from('t_tokonotamutasiinbound AS A', true);
        $this->join('t_tokonotamutasibarang AS B', 'A.IDTOKONOTAMUTASIBARANG = B.IDTOKONOTAMUTASIBARANG', 'LEFT');
        $this->join('t_tokonotamutasirekap AS C', 'B.IDTOKONOTAMUTASIREKAP = C.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $this->join('m_barangsku AS D', 'B.IDBARANGSKU = D.IDBARANGSKU', 'LEFT');
        $this->where('A.IDTOKONOTAMUTASIINBOUND', $idNotaTokoMutasiStok);
        $this->limit(1);
        
        $result =   $this->get()->getRowArray();
        if(is_null($result)) return false;
        return $result;
	}

    public function getDataPenyelesaianInbound($idTokoNotaMutasiRekap)
    {
        $this->select("SUM(A.JUMLAH) AS JUMLAHMUTASI, IFNULL(SUM(B.JUMLAHINBOUND), 0) AS JUMLAHINBOUND");
        $this->from('t_tokonotamutasibarang AS A', true);
        $this->join('t_tokonotamutasiinbound AS B', 'A.IDTOKONOTAMUTASIBARANG = B.IDTOKONOTAMUTASIBARANG', 'LEFT');
        $this->where('A.IDTOKONOTAMUTASIREKAP', $idTokoNotaMutasiRekap);
        $this->groupBy('A.IDTOKONOTAMUTASIREKAP');
        $this->limit(1);
        
        $result =   $this->get()->getRowArray();
        if(is_null($result)) return [
            'JUMLAHMUTASI'  =>  0,
            'JUMLAHINBOUND' =>  0
        ];
        return $result;
    }

    public function getDataNotaStokHistori($idToko, $searchKeyword)
    {	
        $this->select("IDTOKONOTAMUTASIREKAP, NOTAMUTASINOMOR, DATE_FORMAT(REQUESTTANGGALWAKTU, '%d %b %Y %H:%i') AS REQUESTTANGGALWAKTU,
                    TOTALSKU, PERSENPENYELESAIANINBOUND");
        $this->from('t_tokonotamutasirekap A', true);
        $this->where('IDTOKO', $idToko);
        if(isset($searchKeyword) && $searchKeyword != "") {
            $this->groupStart();
            $this->like('NOTAMUTASINOMOR', $searchKeyword);
            $this->groupEnd();
        }
        $this->where('STATUS !=', 0);
        $this->orderBy('REQUESTTANGGALWAKTU', 'DESC');

        return $this;
	}
    
    public function getDetailTokoNotaMutasiRekapHistori($idTokoNotaMutasiRekap)
    {	
        $this->select("A.NOTAMUTASINOMOR, B.NAMA AS NAMATOKO, A.TOTALSKU, A.REQUESTUSER, DATE_FORMAT(A.REQUESTTANGGALWAKTU, '%d %b %Y %H:%i') AS REQUESTTANGGALWAKTU,
                    A.KETERANGAN, A.PROSESUSER, A.PROSESKETERANGAN, DATE_FORMAT(A.PROSESTANGGALWAKTU, '%d %b %Y %H:%i') AS PROSESTANGGALWAKTU, A.PERSENPENYELESAIANINBOUND,
                    IFNULL(C.KELOMPOKHARGAGROSIR, '-') AS KELOMPOKHARGAGROSIR, IFNULL(E.CARAPELUNASAN, '-') AS CARAPELUNASAN, COUNT(D.IDTOKONOTAMUTASIPEMBAYARAN) AS JUMLAHPEMBAYARAN");
        $this->from('t_tokonotamutasirekap A', true);
        $this->join('m_toko AS B', 'A.IDTOKO = B.IDTOKO', 'LEFT');
        $this->join('m_kelompokhargagrosir AS C', 'A.IDKELOMPOKHARGAGROSIR = C.IDKELOMPOKHARGAGROSIR', 'LEFT');
        $this->join('t_tokonotamutasipembayaran AS D', 'A.IDTOKONOTAMUTASIREKAP = D.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $this->join('a_carapelunasan AS E', 'D.IDCARAPELUNASAN = E.IDCARAPELUNASAN', 'LEFT');
        $this->where('A.IDTOKONOTAMUTASIREKAP', $idTokoNotaMutasiRekap);
        $this->limit(1);
        
        $result =   $this->get()->getRowArray();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function getDataBarangSKUNotaPengajuanStokHistori($idTokoNotaMutasiRekap)
    {	
        $this->select("A.IDBARANGSKU, D.NAMAKATEGORI, E.NAMAMERK, C.NAMABARANG, F.KODESKU, F.DESKRIPSI AS DESKRIPSISKU, '[]' AS ATRIBUTSKUSTR, A.JUMLAHREQUEST,
                    A.JUMLAH AS JUMLAHDISETUJUI, B.JUMLAHINBOUND");
        $this->from('t_tokonotamutasibarang A', true);
        $this->join('t_tokonotamutasiinbound AS B', 'A.IDTOKONOTAMUTASIBARANG = B.IDTOKONOTAMUTASIBARANG', 'LEFT');
        $this->join('m_barang AS C', 'A.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS D', 'C.IDBARANGKATEGORI = D.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS E', 'C.IDBARANGMERK = E.IDBARANGMERK', 'LEFT');
        $this->join('m_barangsku AS F', 'A.IDBARANGSKU = F.IDBARANGSKU', 'LEFT');
        $this->where('A.IDTOKONOTAMUTASIREKAP', $idTokoNotaMutasiRekap);
        
        $result =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function getDataPembayaranNotaMutasiToko($idTokoNotaMutasiRekap)
    {	
        $this->select("NOTAMUTASIPEMBAYARANNOMOR, STATUS, DATE_FORMAT(JATUHTEMPO, '%d %b %Y') AS TANGGALJATUHTEMPO, PEMBAYARANKE, KETERANGAN, NOMINAL,
                BUKTIBAYAR");
        $this->from('t_tokonotamutasipembayaran A', true);
        $this->where('IDTOKONOTAMUTASIREKAP', $idTokoNotaMutasiRekap);

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
    }
}