<?php

namespace App\Models\WH\Stok;
use CodeIgniter\Model;

class StokTokoModel extends Model
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

    public function getDataKelompokHargaGrosir($idToko)
    {
        $this->select("A.IDKELOMPOKHARGAGROSIR, B.KELOMPOKHARGAGROSIR, B.DESKRIPSI AS DESKRIPSIKELOMPOKHARGA");
        $this->from('m_toko AS A', true);
        $this->join('m_kelompokhargagrosir B', 'A.IDKELOMPOKHARGAGROSIR = B.IDKELOMPOKHARGAGROSIR', 'LEFT');
        $this->where('A.IDTOKO', $idToko);
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return [
            'IDKELOMPOKHARGAGROSIR'    =>  0,
            'KELOMPOKHARGAGROSIR'      =>  '',
            'DESKRIPSIKELOMPOKHARGA'   =>  ''
        ];
        return $result;
    }
    
    public function getDataBarangStokPenjualan($idGudang, $idToko, $idKelompokHargaGrosir, $idBarangKategori, $idBarangMerk, $searchKeyword, $sortCondition)
    {
        $mainOperation      =   model('MainOperation');
        $arrIdKategoriBarang=   $mainOperation->getArrIdKategoriBarangToko($idToko);
        $this->select("A.IDBARANGSKU, A.IDBARANGSATUAN, D.NAMAMERK, C.NAMAKATEGORI, CONCAT(B.NAMABARANG, ' | ', B.KODEBARANG) AS NAMABARANG, A.KODESKU, '[]' AS ATRIBUTSKUSTR,
                    A.DESKRIPSI AS DESKRIPSISKU, '' AS IDDISKONGROSIR, '' AS DISKONDESKRIPSI, '' AS DISKONJUMLAH, 0 AS DISKONMINIMALITEM, IFNULL(E.HARGA, 0) AS HARGAGROSIR,
                    IFNULL(E.HARGA, 0) AS HARGASETELAHDISKON, B.STOKMINIMALTOKO, IFNULL(F.STOK, 0) AS STOKGUDANG, IFNULL(G.STOK, 0) AS STOKTOKO, IFNULL(H.PENJUALAN, 0) AS PENJUALAN");
        $this->from('m_barangsku A', true);
        $this->join('m_barang AS B', 'A.IDBARANG = B.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS C', 'B.IDBARANGKATEGORI = C.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS D', 'B.IDBARANGMERK = D.IDBARANGMERK', 'LEFT');
        $this->join('t_baranghargajualgrosir AS E', 'A.IDBARANGSKU = E.IDBARANGSKU AND A.IDBARANGSATUAN = E.IDBARANGSATUAN AND E.JUMLAHSATUAN = 1 AND E.IDKELOMPOKHARGAGROSIR = '.$idKelompokHargaGrosir, 'LEFT');
        $this->join(
            '(SELECT IDBARANGSKU, IDBARANGSATUAN, IFNULL(SUM(JUMLAHMASUK - JUMLAHKELUAR), 0) AS STOK
              FROM t_gudangstok
              WHERE IDGUDANG = '.$idGudang.'
              GROUP BY IDBARANGSKU, IDBARANGSATUAN) AS F',
            'A.IDBARANGSKU = F.IDBARANGSKU AND F.IDBARANGSATUAN = A.IDBARANGSATUAN',
            'LEFT'
        );
        $this->join(
            '(SELECT IDBARANGSKU, IDBARANGSATUAN, IFNULL(SUM(JUMLAHMASUK - JUMLAHKELUAR), 0) AS STOK
              FROM t_tokostok
              WHERE IDTOKO = '.$idToko.'
              GROUP BY IDBARANGSKU, IDBARANGSATUAN) AS G',
            'A.IDBARANGSKU = G.IDBARANGSKU AND A.IDBARANGSATUAN = G.IDBARANGSATUAN',
            'LEFT'
        );
        $this->join(
            '(SELECT HA.IDBARANGSKU, HA.IDBARANGSATUAN, IFNULL(SUM(JUMLAH), 0) AS PENJUALAN
              FROM t_penjualanbarang HA
              LEFT JOIN t_penjualanrekap HB ON HA.IDPENJUALANREKAP = HB.IDPENJUALANREKAP
              WHERE HB.IDTOKO = '.$idToko.'
              GROUP BY HA.IDBARANGSKU, HA.IDBARANGSATUAN) AS H',
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

        switch($sortCondition){
            case 'stok_asc':
                $this->orderBy('STOK', 'ASC');
                break;
            case 'stok_desc':
                $this->orderBy('STOK', 'DESC');
                break;
            case 'penjualan_asc':
                $this->orderBy('PENJUALAN', 'ASC');
                break;
            case 'penjualan_desc':
                $this->orderBy('PENJUALAN', 'DESC');
                break;
            default:
                $this->orderBy('D.NAMAMERK, C.NAMAKATEGORI, NAMABARANG');
                break;
        }

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}

    public function getStokBarangGudang($idGudang, $idBarangSKU)
    {
        $this->select("A.IDBARANGSATUAN, A.IDBARANG, IFNULL(SUM(B.JUMLAHMASUK - B.JUMLAHKELUAR), 0) AS STOK");
        $this->from('m_barangsku A', true);
        $this->join('t_gudangstok B', 'A.IDBARANGSKU = B.IDBARANGSKU AND A.IDBARANGSATUAN = B.IDBARANGSATUAN AND B.IDGUDANG = '.$idGudang, 'LEFT');
        $this->where('A.IDBARANGSKU', $idBarangSKU);
        $this->groupBy('A.IDBARANGSKU');

        $result =   $this->get()->getRowObject();
        if(is_null($result)) return ['IDBARANGSATUAN' => 0, 'IDBARANG' => 0, 'STOK' => 0];
        return $result;
    }

    public function getHargaJualBarangGrosir($idBarangSKU, $idBarangSatuan, $idKelompokHargaGrosir)
    {
        $this->select("HARGA");
        $this->from('t_baranghargajualgrosir', true);
        $this->where('IDBARANGSKU', $idBarangSKU);
        $this->where('IDBARANGSATUAN', $idBarangSatuan);
        $this->where('IDKELOMPOKHARGAGROSIR', $idKelompokHargaGrosir);
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return 0;
        return $result['HARGA'];
    }
    
    public function getDataNotaPengajuanStokAktif($idGudang)
    {	
        $this->select("A.IDTOKONOTAMUTASIREKAP, B.NAMA AS NAMATOKO, A.NOTAMUTASINOMOR");
        $this->from('t_tokonotamutasirekap A', true);
        $this->join('m_toko AS B', 'A.IDTOKO = B.IDTOKO', 'LEFT');
        $this->where('A.IDGUDANG', $idGudang);
        $this->where('A.STATUS', 0);
        $this->orderBy('A.REQUESTTANGGALWAKTU', 'DESC');

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function getDetailTokoNotaMutasiRekap($idTokoNotaMutasiRekap)
    {	
        $this->select("A.IDGUDANG, A.IDTOKO, A.NOTAMUTASINOMOR, F.CARAPELUNASAN, COUNT(E.IDTOKONOTAMUTASIPEMBAYARAN) AS TOTALPEMBAYARAN, MIN(DATE_FORMAT(E.JATUHTEMPO, '%d %b %y')) AS JATUHTEMPO,
                    B.NAMA AS NAMATOKO, A.TOTALSKU, A.KETERANGAN, A.REQUESTUSER, DATE_FORMAT(A.REQUESTTANGGALWAKTU, '%d %b %Y %H:%i') AS REQUESTTANGGALWAKTU, A.PROSESUSER,
                    DATE_FORMAT(A.PROSESTANGGALWAKTU, '%d %b %Y') AS PROSESTANGGALWAKTU, C.NAME AS NAMAKEPALATOKO, B.ALAMAT AS ALAMATTOKO, D.KELOMPOKHARGAGROSIR,
                    SUM(E.NOMINAL) AS TOTALPEMBAYARANNOMINAL, E.IDCARAPELUNASAN");
        $this->from('t_tokonotamutasirekap A', true);
        $this->join('m_toko AS B', 'A.IDTOKO = B.IDTOKO', 'LEFT');
        $this->join('m_useradmin AS C', 'B.IDUSERADMINKEPALATOKO = C.IDUSERADMIN', 'LEFT');
        $this->join('m_kelompokhargagrosir AS D', 'B.IDKELOMPOKHARGAGROSIR = D.IDKELOMPOKHARGAGROSIR', 'LEFT');
        $this->join('t_tokonotamutasipembayaran AS E', 'A.IDTOKONOTAMUTASIREKAP = E.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $this->join('a_carapelunasan AS F', 'E.IDCARAPELUNASAN = F.IDCARAPELUNASAN', 'LEFT');
        $this->where('A.IDTOKONOTAMUTASIREKAP', $idTokoNotaMutasiRekap);
        $this->groupBy('A.IDTOKONOTAMUTASIREKAP');
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function getDataBarangSKUNotaPengajuanStok($idTokoNotaMutasiRekap, $idKelompokHargaGrosir, $idGudangToko)
    {	
        $this->select("A.IDTOKONOTAMUTASIBARANG, A.IDBARANGSKU, E.IDBARANGSATUAN, C.NAMAKATEGORI, D.NAMAMERK, B.NAMABARANG, E.KODESKU,
                    E.DESKRIPSI AS DESKRIPSISKU, '' AS IDDISKONGROSIR, '' AS DISKONDESKRIPSI, '' AS DISKONJUMLAH, 0 AS DISKONMINIMALITEM,
                    '[]' AS ATRIBUTSKUSTR, IFNULL(SUM(G.JUMLAHMASUK - G.JUMLAHKELUAR), 0) AS STOKGUDANG, A.JUMLAHREQUEST, H.KODESATUAN,
                    0 AS JUMLAHPERSETUJUANDRAFT, IFNULL(F.HARGA, 0) AS HARGAGROSIR, IFNULL(F.HARGA, 0) AS HARGASETELAHDISKON");
        $this->from('t_tokonotamutasibarang A', true);
        $this->join('m_barang AS B', 'A.IDBARANG = B.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS C', 'B.IDBARANGKATEGORI = C.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS D', 'B.IDBARANGMERK = D.IDBARANGMERK', 'LEFT');
        $this->join('m_barangsku AS E', 'A.IDBARANGSKU = E.IDBARANGSKU', 'LEFT');
        $this->join('t_baranghargajualgrosir AS F', 'A.IDBARANGSKU = F.IDBARANGSKU AND E.IDBARANGSATUAN = F.IDBARANGSATUAN AND F.JUMLAHSATUAN = 1 AND F.IDKELOMPOKHARGAGROSIR = '.$idKelompokHargaGrosir, 'LEFT');
        $this->join('t_gudangstok AS G', 'A.IDBARANGSKU = G.IDBARANGSKU AND G.IDBARANGSATUAN = E.IDBARANGSATUAN AND G.IDGUDANG = '.$idGudangToko, 'LEFT');
        $this->join('m_barangsatuan AS H', 'E.IDBARANGSATUAN = H.IDBARANGSATUAN', 'LEFT');
        $this->where('A.IDTOKONOTAMUTASIREKAP', $idTokoNotaMutasiRekap);
        $this->groupBy('A.IDTOKONOTAMUTASIBARANG');
        
        $result =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}

    public function getDataNotaStokHistori($idGudang, $idToko, $searchKeyword)
    {	
        $this->select("A.IDTOKONOTAMUTASIREKAP, A.NOTAMUTASINOMOR, B.NAMA AS NAMATOKO, DATE_FORMAT(A.REQUESTTANGGALWAKTU, '%d %b %Y %H:%i') AS REQUESTTANGGALWAKTU,
                    A.TOTALSKU, A.PERSENPENYELESAIANINBOUND");
        $this->from('t_tokonotamutasirekap A', true);
        $this->join('m_toko AS B', 'A.IDTOKO = B.IDTOKO', 'LEFT');
        $this->where('A.IDGUDANG', $idGudang);
        if(isset($idToko) && $idToko != 0 && $idToko != "") $this->where('A.IDTOKO', $idToko);
        if(isset($searchKeyword) && $searchKeyword != "") {
            $this->groupStart();
            $this->like('A.NOTAMUTASINOMOR', $searchKeyword);
            $this->orLike('B.NAMA', $searchKeyword);
            $this->groupEnd();
        }
        $this->where('A.STATUS !=', 0);
        $this->orderBy('A.REQUESTTANGGALWAKTU', 'DESC');

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
        $this->select("A.IDBARANGSKU, D.NAMAKATEGORI, E.NAMAMERK, C.NAMABARANG, F.KODESKU, F.DESKRIPSI AS DESKRIPSISKU, '[]' AS ATRIBUTSKUSTR, A.JUMLAHREQUEST, A.JUMLAH AS JUMLAHDISETUJUI, B.JUMLAHINBOUND");
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
        $this->select("NOTAMUTASIPEMBAYARANNOMOR, STATUS, DATE_FORMAT(JATUHTEMPO, '%d %b %Y') AS TANGGALJATUHTEMPO, PEMBAYARANKE, KETERANGAN, NOMINAL, BUKTIBAYAR");
        $this->from('t_tokonotamutasipembayaran A', true);
        $this->where('IDTOKONOTAMUTASIREKAP', $idTokoNotaMutasiRekap);
        $this->orderBy('PEMBAYARANKE', 'ASC');

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
    }
    
    public function getDataBarangSKUNotaMutasiToko($idTokoNotaMutasiRekap)
    {	
        $this->select("CONCAT(B.KODEBARANG, '-', C.KODESKU) AS KODEBARANGSKU, C.DESKRIPSI AS DESKRIPSISKU, A.JUMLAH, D.KODESATUAN, A.HARGAAWAL, A.HARGADISKON, A.HARGAGROSIR");
        $this->from('t_tokonotamutasibarang A', true);
        $this->join('m_barang AS B', 'A.IDBARANG = B.IDBARANG', 'LEFT');
        $this->join('m_barangsku AS C', 'A.IDBARANGSKU = C.IDBARANGSKU', 'LEFT');
        $this->join('m_barangsatuan AS D', 'C.IDBARANGSATUAN = D.IDBARANGSATUAN', 'LEFT');
        $this->where('A.IDTOKONOTAMUTASIREKAP', $idTokoNotaMutasiRekap);
        
        $result =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}
    
}