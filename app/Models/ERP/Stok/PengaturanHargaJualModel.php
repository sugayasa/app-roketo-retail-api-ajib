<?php

namespace App\Models\ERP\Stok;
use CodeIgniter\Model;

class PengaturanHargaJualModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_baranghargajual';
    protected $primaryKey       = 'IDBARANGHARGAJUAL';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDBARANGHARGAJUAL', 'IDBARANG', 'IDBARANGSKU', 'IDGUDANG', 'IDTOKO', 'JUMLAHSATUAN', 'HARGA'];

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

    public function getDataHargaJualBarang($idBarangKategori, $idBarangMerk, $searchKeyword)
    {	
        // Optimasi 1: Pre-agregasi COUNT SKU dalam subquery
        $subQuerySKU = $this->db->table('m_barangsku');
        $subQuerySKU->select('IDBARANG, COUNT(IDBARANGSKU) AS JUMLAHSKU');
        $subQuerySKU->groupBy('IDBARANG');
        $compiledSKU = $subQuerySKU->getCompiledSelect();
        
        // Optimasi 2: Pre-agregasi harga retail dalam subquery
        $subQueryHargaRetail = $this->db->table('t_baranghargajual');
        $subQueryHargaRetail->select('IDBARANG, GROUP_CONCAT(DISTINCT FORMAT(HARGA, 0) ORDER BY HARGA ASC SEPARATOR \' | \') AS DAFTARHARGARETAIL');
        $subQueryHargaRetail->groupBy('IDBARANG');
        $compiledHargaRetail = $subQueryHargaRetail->getCompiledSelect();
        
        // Optimasi 3: Pre-agregasi harga grosir dalam subquery
        $subQueryHargaGrosir = $this->db->table('t_baranghargajualgrosir');
        $subQueryHargaGrosir->select('IDBARANG, GROUP_CONCAT(DISTINCT FORMAT(HARGA, 0) ORDER BY HARGA ASC SEPARATOR \' | \') AS DAFTARHARGAGROSIR');
        $subQueryHargaGrosir->groupBy('IDBARANG');
        $compiledHargaGrosir = $subQueryHargaGrosir->getCompiledSelect();
        
        // Main query dengan subquery yang sudah di-agregasi
        $this->select("A.IDBARANG, B.NAMAKATEGORI, C.NAMAMERK, 
                    CONCAT('[', IFNULL(A.KODEBARANG, '-'), '] ', A.NAMABARANG) AS NAMABARANG, 
                    IFNULL(D.JUMLAHSKU, 0) AS JUMLAHSKU,
                    IFNULL(E.DAFTARHARGARETAIL, '-') AS DAFTARHARGARETAIL,
                    IFNULL(F.DAFTARHARGAGROSIR, '-') AS DAFTARHARGAGROSIR,
                    '[]' AS ARRIDBARANGSATUAN");
        $this->from('m_barang A', true);
        $this->join('m_barangkategori AS B', 'A.IDBARANGKATEGORI = B.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS C', 'A.IDBARANGMERK = C.IDBARANGMERK', 'LEFT');
        
        // Join dengan subquery yang sudah di-agregasi (lebih efisien)
        $this->join('(' . $compiledSKU . ') AS D', 'A.IDBARANG = D.IDBARANG', 'LEFT');
        $this->join('(' . $compiledHargaRetail . ') AS E', 'A.IDBARANG = E.IDBARANG', 'LEFT');
        $this->join('(' . $compiledHargaGrosir . ') AS F', 'A.IDBARANG = F.IDBARANG', 'LEFT');

        // Optimasi 4: SQL Injection protection dengan escape
        if($idBarangKategori != 0) {
            $this->where('A.IDBARANGKATEGORI', $idBarangKategori);
        }
        if($idBarangMerk != 0) {
            $this->where('A.IDBARANGMERK', $idBarangMerk);
        }
        
        // Optimasi 5: Search dengan prepared statement
        if(isset($searchKeyword) && !is_null($searchKeyword) && $searchKeyword !== ''){
            $this->groupStart();
            $this->like('B.NAMAKATEGORI', $searchKeyword, 'both')
            ->orLike('C.NAMAMERK', $searchKeyword, 'both')
            ->orLike('A.NAMABARANG', $searchKeyword, 'both')
            ->orLike('A.KODEBARANG', $searchKeyword, 'both');
            $this->groupEnd();
        }

        // Tidak perlu GROUP BY karena sudah di-agregasi di subquery
        $this->orderBy('B.NAMAKATEGORI, C.NAMAMERK, A.NAMABARANG');

        return $this;
	}

    public function getDataKelompokHargaGrosir()
    {	
        $this->select("IDKELOMPOKHARGAGROSIR, KELOMPOKHARGAGROSIR, DESKRIPSI");
        $this->from('m_kelompokhargagrosir', true);
        $this->where('STATUS', 1);

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}

    public function getDataDetailBarang($idBarang)
    {	
        // Optimasi: SQL Injection protection dengan escape parameter
        $this->select("B.NAMAKATEGORI, C.NAMAMERK, CONCAT(A.NAMABARANG, ' | ', A.KODEBARANG) AS NAMABARANG");
        $this->from('m_barang A', true);
        $this->join('m_barangkategori AS B', 'A.IDBARANGKATEGORI = B.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS C', 'A.IDBARANGMERK = C.IDBARANGMERK', 'LEFT');
        $this->where('A.IDBARANG', $idBarang);
        $this->limit(1);

        $result =   $this->get()->getRowObject();
        if(is_null($result)) return ['NAMAKATEGORI' => '-', 'NAMAMERK' => '-', 'NAMABARANG' => '-'];
        return $result;
	}

    public function getDataDetailBarangSKU($idBarang)
    {	
        // Optimasi: SQL Injection protection dengan escape parameter
        // Tidak perlu GROUP BY karena IDBARANGSKU adalah unique per row
        $this->select("IDBARANGSKU, KODESKU, DESKRIPSI, '[]' AS ATRIBUTSKUSTR");
        $this->from('m_barangsku', true);
        $this->where('IDBARANG', $idBarang);
        $this->orderBy('KODESKU');

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}

    public function getHistoryHargaBeliPerSKU($idBarangSKU, $limit = 50)
    {	
        // Optimasi: Tambahkan limit untuk performa dan gunakan index pada INPUTTANGGALWAKTU
        $this->select("DATE_FORMAT(B.INPUTTANGGALWAKTU, '%d %b %Y') AS TANGGAL, B.NOTAPEMBELIANNOMOR, A.HARGABELI");
        $this->from('t_notapembelianbarang AS A', true);
        $this->join('t_notapembelianrekap AS B', 'A.IDNOTAPEMBELIANREKAP = B.IDNOTAPEMBELIANREKAP', 'LEFT');
        $this->where('A.IDBARANGSKU', $idBarangSKU);
        $this->orderBy('B.INPUTTANGGALWAKTU', 'DESC');
        $this->limit($limit); // Batasi hasil untuk performa

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}

    public function getDataHargaJualBarangPerSKU($idToko, $idBarang, $idBarangSatuan)
    {	
        $this->select("IDBARANGSKU, HARGA");
        $this->from('t_baranghargajual', true);
        $this->where('IDTOKO', $idToko)->where('IDBARANG', $idBarang)->where('IDBARANGSATUAN', $idBarangSatuan)->where('JUMLAHSATUAN', 1);

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}

    public function getHargaBarangSKUGrosir($idBarangSKU, $idBarangSatuan)
    {	
        // Optimasi: Subquery dengan proper escaping untuk SQL injection protection
        $subQueryHarga = $this->db->table('t_baranghargajualgrosir AS B');
        $subQueryHarga->select('B.IDKELOMPOKHARGAGROSIR, B.HARGA');
        $subQueryHarga->where('B.IDBARANGSKU', $idBarangSKU);
        $subQueryHarga->where('B.IDBARANGSATUAN', $idBarangSatuan);
        $subQueryHarga->where('B.JUMLAHSATUAN', 1);
        $compiledHarga = $subQueryHarga->getCompiledSelect();
        
        $this->select("A.IDKELOMPOKHARGAGROSIR, IFNULL(B.HARGA, '0') AS HARGA");
        $this->from('m_kelompokhargagrosir AS A', true);
        $this->join('(' . $compiledHarga . ') AS B', 'A.IDKELOMPOKHARGAGROSIR = B.IDKELOMPOKHARGAGROSIR', 'LEFT');
        $this->where('A.STATUS', 1);
        $this->orderBy('A.IDKELOMPOKHARGAGROSIR');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}

    public function isHargaBarangGrosirExist($idKelompokHargaGrosir, $idBarangSKU, $idBarangSatuan)
    {	
        $this->select("IDBARANGHARGAJUALGROSIR");
        $this->from('t_baranghargajualgrosir', true);
        $this->where('IDKELOMPOKHARGAGROSIR', $idKelompokHargaGrosir)->where('IDBARANGSKU', $idBarangSKU)->where('IDBARANGSATUAN', $idBarangSatuan)->where('JUMLAHSATUAN', 1);
        $this->where('JUMLAHSATUAN', 1);
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return false;
        return $result;
	}

    public function getDaftarHargaJualRetail($idToko, $idBarangKategori, $idBarangMerk)
    {	
        $this->select("A.IDBARANGSKU, C.NAMAKATEGORI, D.NAMAMERK, B.KODEBARANG, B.NAMABARANG, A.KODESKU, A.DESKRIPSI AS DESKRIPSISKU, E.NAMASATUAN, '[]' AS ATRIBUTSKUSTR,
                    IFNULL(G.HARGABELIRERATA, 0) AS HARGABELIRERATA, IFNULL(F.HARGA, 0) AS HARGAJUAL");
        $this->from('m_barangsku A', true);
        $this->join('m_barang AS B', 'A.IDBARANG = B.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS C', 'B.IDBARANGKATEGORI = C.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS D', 'B.IDBARANGMERK = D.IDBARANGMERK', 'LEFT');
        $this->join('m_barangsatuan AS E', 'A.IDBARANGSATUAN = E.IDBARANGSATUAN', 'LEFT');
        $this->join('t_baranghargajual AS F', 'A.IDBARANGSKU = F.IDBARANGSKU AND A.IDBARANGSATUAN = F.IDBARANGSATUAN AND F.IDTOKO = ' . $idToko, 'INNER');

        $subQuery   =   $this->db->table('t_tokonotamutasibarang AS GA');
        $subQuery->select('GA.IDBARANGSKU, AVG(GA.HARGAGROSIR) AS HARGABELIRERATA');
        $subQuery->join('t_tokonotamutasirekap AS GB', 'GA.IDTOKONOTAMUTASIREKAP = GB.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $subQuery->where('GB.IDTOKO', $idToko);
        $subQuery->groupBy('GA.IDBARANGSKU');
        $subQuery   =   $subQuery->getCompiledSelect();

        $this->join('(' . $subQuery . ') AS G', 'A.IDBARANGSKU = G.IDBARANGSKU', 'LEFT');

        if(isset($idBarangKategori) && $idBarangKategori != 0 && $idBarangKategori != "") $this->where('B.IDBARANGKATEGORI', $idBarangKategori);
        if(isset($idBarangMerk) && $idBarangMerk != 0 && $idBarangMerk != "") $this->where('B.IDBARANGMERK', $idBarangMerk);
        $this->groupBy('A.IDBARANGSKU, F.IDBARANGSATUAN');
        $this->orderBy('C.NAMAKATEGORI, D.NAMAMERK, B.NAMABARANG, A.KODESKU', 'ASC');

        return $this;
	}

    public function getDaftarHargaJualGrosir($idBarangKategori, $idBarangMerk)
    {	
        $this->select("A.IDBARANGSKU, A.IDBARANGSATUAN, C.NAMAKATEGORI, D.NAMAMERK, B.KODEBARANG, B.NAMABARANG, A.KODESKU, A.DESKRIPSI AS DESKRIPSISKU, E.NAMASATUAN, '[]' AS ATRIBUTSKUSTR");
        $this->from('m_barangsku A', true);
        $this->join('m_barang AS B', 'A.IDBARANG = B.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS C', 'B.IDBARANGKATEGORI = C.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS D', 'B.IDBARANGMERK = D.IDBARANGMERK', 'LEFT');
        $this->join('m_barangsatuan AS E', 'A.IDBARANGSATUAN = E.IDBARANGSATUAN', 'LEFT');

        if(isset($idBarangKategori) && $idBarangKategori != 0 && $idBarangKategori != "") $this->where('B.IDBARANGKATEGORI', $idBarangKategori);
        if(isset($idBarangMerk) && $idBarangMerk != 0 && $idBarangMerk != "") $this->where('B.IDBARANGMERK', $idBarangMerk);
        $this->groupBy('A.IDBARANGSKU, A.IDBARANGSATUAN');
        $this->orderBy('C.NAMAKATEGORI, D.NAMAMERK, B.NAMABARANG, A.KODESKU', 'ASC');

        return $this;
	}
}