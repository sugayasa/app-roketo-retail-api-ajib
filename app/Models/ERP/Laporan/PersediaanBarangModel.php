<?php

namespace App\Models\ERP\Laporan;
use CodeIgniter\Model;

class PersediaanBarangModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_gudangstok';
    protected $primaryKey       = 'IDGUDANGSTOK';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [];

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

    public function getDataPersediaanBarangGudang($idGudang, $idBarangKategori, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian)
    {	
        // Optimasi: Pre-calculate nilai persediaan di SELECT untuk menghindari kalkulasi berulang
        $this->select("A.IDBARANGSKU, C.NAMAKATEGORI, B.NAMABARANG, CONCAT(B.KODEBARANG, '-', A.KODESKU) AS KODESKU, 
                    A.DESKRIPSI AS DESKRIPSISKU, D.KODESATUAN, 
                    IFNULL(F.STOKAWAL, 0) AS STOKAWAL, 
                    IFNULL(E.STOKMASUK, 0) AS STOKMASUK, 
                    IFNULL(E.STOKKELUAR, 0) AS STOKKELUAR,
                    IFNULL(E.STOKAKHIR, 0) AS STOKAKHIR, 
                    IFNULL(G.HARGABELIRERATA, 0) AS HARGABELIRERATA, 
                    ROUND(IFNULL(E.STOKAKHIR, 0) * IFNULL(G.HARGABELIRERATA, 0), 2) AS NILAIPERSEDIAAN");
        $this->from('m_barangsku A', true);
        $this->join('m_barang AS B', 'A.IDBARANG = B.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS C', 'B.IDBARANGKATEGORI = C.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangsatuan AS D', 'A.IDBARANGSATUAN = D.IDBARANGSATUAN', 'LEFT');
        
        // Optimasi 1: Pre-agregasi stok dalam subquery untuk menghindari SUM di main query
        // Optimasi 2: Gunakan range pada INPUTTANGGALWAKTU tanpa DATE() untuk menggunakan index
        $subQueryStokPeriode = $this->db->table('t_gudangstok');
        $subQueryStokPeriode->select('IDBARANGSKU, 
                                      SUM(JUMLAHMASUK) AS STOKMASUK, 
                                      SUM(JUMLAHKELUAR) AS STOKKELUAR,
                                      SUM(JUMLAHMASUK - JUMLAHKELUAR) AS STOKAKHIR');
        $subQueryStokPeriode->where('IDGUDANG', $this->db->escape($idGudang));
        $subQueryStokPeriode->where('INPUTTANGGALWAKTU >=', $tanggalAwal . ' 00:00:00');
        $subQueryStokPeriode->where('INPUTTANGGALWAKTU <=', $tanggalAkhir . ' 23:59:59');
        $subQueryStokPeriode->groupBy('IDBARANGSKU');
        $compiledStokPeriode = $subQueryStokPeriode->getCompiledSelect();
        
        $this->join('(' . $compiledStokPeriode . ') AS E', 'A.IDBARANGSKU = E.IDBARANGSKU', 'LEFT');

        // Optimasi 3: Subquery stok awal dengan range datetime yang lebih efisien
        $subQueryStokAwal = $this->db->table('t_gudangstok');
        $subQueryStokAwal->select('IDBARANGSKU, IDBARANGSATUAN, SUM(JUMLAHMASUK - JUMLAHKELUAR) AS STOKAWAL');
        $subQueryStokAwal->where('IDGUDANG', $this->db->escape($idGudang));
        $subQueryStokAwal->where('INPUTTANGGALWAKTU <', $tanggalAwal . ' 00:00:00');
        $subQueryStokAwal->groupBy('IDBARANGSKU, IDBARANGSATUAN');
        $compiledStokAwal = $subQueryStokAwal->getCompiledSelect();

        $this->join('(' . $compiledStokAwal . ') AS F', 'A.IDBARANGSKU = F.IDBARANGSKU AND A.IDBARANGSATUAN = F.IDBARANGSATUAN', 'LEFT');

        // Optimasi 4: Subquery harga beli rerata dengan datetime range
        $subQueryHarga = $this->db->table('t_notapembelianinbound AS GA');
        $subQueryHarga->select('GB.IDBARANGSKU, AVG(GB.HARGABELI) AS HARGABELIRERATA');
        $subQueryHarga->join('t_notapembelianbarang AS GB', 'GA.IDNOTAPEMBELIANBARANG = GB.IDNOTAPEMBELIANBARANG', 'LEFT');
        $subQueryHarga->where('GA.IDGUDANG', $this->db->escape($idGudang));
        $subQueryHarga->where('GA.PROSESTANGGALWAKTU <=', $tanggalAkhir . ' 23:59:59');
        $subQueryHarga->groupBy('GB.IDBARANGSKU');
        $compiledHarga = $subQueryHarga->getCompiledSelect();

        $this->join('(' . $compiledHarga . ') AS G', 'A.IDBARANGSKU = G.IDBARANGSKU', 'LEFT');

        // Filter kategori barang
        if(isset($idBarangKategori) && $idBarangKategori != 0 && $idBarangKategori != "") {
            $this->where('B.IDBARANGKATEGORI', $idBarangKategori);
        }
        
        // Optimasi 5: Search dengan FULLTEXT jika tersedia, atau minimal gunakan index pada kolom
        if(isset($kataKunciPencarian) && $kataKunciPencarian != "") {
            $this->groupStart();
            $this->orLike('C.NAMAKATEGORI', $kataKunciPencarian, 'both')
            ->orLike('B.NAMABARANG', $kataKunciPencarian, 'both')
            ->orLike('B.KODEBARANG', $kataKunciPencarian, 'both')
            ->orLike('A.DESKRIPSI', $kataKunciPencarian, 'both')
            ->orLike('A.KODESKU', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }
        
        // Optimasi 6: GROUP BY hanya sekali di level akhir
        $this->groupBy('A.IDBARANGSKU, A.IDBARANGSATUAN');

        return $this;
	}

    public function getDataPersediaanBarangToko($idToko, $idBarangKategori, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian)
    {	
        // Optimasi: Pre-calculate nilai persediaan di SELECT untuk menghindari kalkulasi berulang
        $this->select("A.IDBARANGSKU, C.NAMAKATEGORI, B.NAMABARANG, CONCAT(B.KODEBARANG, '-', A.KODESKU) AS KODESKU, 
                    A.DESKRIPSI AS DESKRIPSISKU, D.KODESATUAN, 
                    IFNULL(F.STOKAWAL, 0) AS STOKAWAL, 
                    IFNULL(E.STOKMASUK, 0) AS STOKMASUK, 
                    IFNULL(E.STOKKELUAR, 0) AS STOKKELUAR,
                    IFNULL(E.STOKAKHIR, 0) AS STOKAKHIR, 
                    IFNULL(G.HARGABELIRERATA, 0) AS HARGABELIRERATA, 
                    ROUND(IFNULL(E.STOKAKHIR, 0) * IFNULL(G.HARGABELIRERATA, 0), 2) AS NILAIPERSEDIAAN");
        $this->from('m_barangsku A', true);
        $this->join('m_barang AS B', 'A.IDBARANG = B.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS C', 'B.IDBARANGKATEGORI = C.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangsatuan AS D', 'A.IDBARANGSATUAN = D.IDBARANGSATUAN', 'LEFT');
        
        // Optimasi 1: Pre-agregasi stok dalam subquery untuk menghindari SUM di main query
        // Optimasi 2: Gunakan range pada INPUTTANGGALWAKTU tanpa DATE() untuk menggunakan index
        $subQueryStokPeriode = $this->db->table('t_tokostok');
        $subQueryStokPeriode->select('IDBARANGSKU, 
                                      SUM(JUMLAHMASUK) AS STOKMASUK, 
                                      SUM(JUMLAHKELUAR) AS STOKKELUAR,
                                      SUM(JUMLAHMASUK - JUMLAHKELUAR) AS STOKAKHIR');
        $subQueryStokPeriode->where('IDTOKO', $this->db->escape($idToko));
        $subQueryStokPeriode->where('INPUTTANGGALWAKTU >=', $tanggalAwal . ' 00:00:00');
        $subQueryStokPeriode->where('INPUTTANGGALWAKTU <=', $tanggalAkhir . ' 23:59:59');
        $subQueryStokPeriode->groupBy('IDBARANGSKU');
        $compiledStokPeriode = $subQueryStokPeriode->getCompiledSelect();
        
        $this->join('(' . $compiledStokPeriode . ') AS E', 'A.IDBARANGSKU = E.IDBARANGSKU', 'LEFT');

        // Optimasi 3: Subquery stok awal dengan range datetime yang lebih efisien
        $subQueryStokAwal = $this->db->table('t_tokostok');
        $subQueryStokAwal->select('IDBARANGSKU, IDBARANGSATUAN, SUM(JUMLAHMASUK - JUMLAHKELUAR) AS STOKAWAL');
        $subQueryStokAwal->where('IDTOKO', $this->db->escape($idToko));
        $subQueryStokAwal->where('INPUTTANGGALWAKTU <', $tanggalAwal . ' 00:00:00');
        $subQueryStokAwal->groupBy('IDBARANGSKU, IDBARANGSATUAN');
        $compiledStokAwal = $subQueryStokAwal->getCompiledSelect();

        $this->join('(' . $compiledStokAwal . ') AS F', 'A.IDBARANGSKU = F.IDBARANGSKU AND A.IDBARANGSATUAN = F.IDBARANGSATUAN', 'LEFT');

        // Optimasi 4: Subquery harga grosir rerata dengan datetime range
        $subQueryHarga = $this->db->table('t_tokonotamutasiinbound AS GA');
        $subQueryHarga->select('GB.IDBARANGSKU, AVG(GB.HARGAGROSIR) AS HARGABELIRERATA');
        $subQueryHarga->join('t_tokonotamutasibarang AS GB', 'GA.IDTOKONOTAMUTASIBARANG = GB.IDTOKONOTAMUTASIBARANG', 'LEFT');
        $subQueryHarga->join('t_tokonotamutasirekap AS GC', 'GB.IDTOKONOTAMUTASIREKAP = GC.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $subQueryHarga->where('GC.IDTOKO', $this->db->escape($idToko));
        $subQueryHarga->where('GA.PROSESTANGGALWAKTU <=', $tanggalAkhir . ' 23:59:59');
        $subQueryHarga->groupBy('GB.IDBARANGSKU');
        $compiledHarga = $subQueryHarga->getCompiledSelect();

        $this->join('(' . $compiledHarga . ') AS G', 'A.IDBARANGSKU = G.IDBARANGSKU', 'LEFT');

        // Filter kategori barang
        if(isset($idBarangKategori) && $idBarangKategori != 0 && $idBarangKategori != "") {
            $this->where('B.IDBARANGKATEGORI', $idBarangKategori);
        }
        
        // Optimasi 5: Search dengan FULLTEXT jika tersedia, atau minimal gunakan index pada kolom
        if(isset($kataKunciPencarian) && $kataKunciPencarian != "") {
            $this->groupStart();
            $this->orLike('C.NAMAKATEGORI', $kataKunciPencarian, 'both')
            ->orLike('B.NAMABARANG', $kataKunciPencarian, 'both')
            ->orLike('B.KODEBARANG', $kataKunciPencarian, 'both')
            ->orLike('A.DESKRIPSI', $kataKunciPencarian, 'both')
            ->orLike('A.KODESKU', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }
        
        // Optimasi 6: GROUP BY hanya sekali di level akhir
        $this->groupBy('A.IDBARANGSKU, A.IDBARANGSATUAN');

        return $this;
	}
}