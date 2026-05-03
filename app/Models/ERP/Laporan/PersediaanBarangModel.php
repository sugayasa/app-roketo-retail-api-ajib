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
        $this->select("A.IDBARANGSKU, C.NAMAKATEGORI, B.NAMABARANG, CONCAT(B.KODEBARANG, '-', A.KODESKU) AS KODESKU, A.DESKRIPSI AS DESKRIPSISKU, D.KODESATUAN, 
                    IFNULL(F.STOKAWAL, 0) AS STOKAWAL, IFNULL(SUM(E.JUMLAHMASUK), 0) AS STOKMASUK, IFNULL(SUM(E.JUMLAHKELUAR), 0) AS STOKKELUAR,
                    IFNULL(SUM(E.JUMLAHMASUK - E.JUMLAHKELUAR), 0) AS STOKAKHIR, IFNULL(G.HARGABELIRERATA, 0) AS HARGABELIRERATA, 0 AS NILAIPERSEDIAAN");
        $this->from('m_barangsku A', true);
        $this->join('m_barang AS B', 'A.IDBARANG = B.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS C', 'B.IDBARANGKATEGORI = C.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangsatuan AS D', 'A.IDBARANGSATUAN = D.IDBARANGSATUAN', 'LEFT');
        $this->join('t_gudangstok AS E', 'A.IDBARANGSKU = E.IDBARANGSKU AND E.IDGUDANG = ' . $idGudang . ' AND DATE(E.INPUTTANGGALWAKTU) BETWEEN "' . $tanggalAwal . '" AND "' . $tanggalAkhir . '"', 'LEFT');

        $subQuery   =   $this->db->table('t_gudangstok');
        $subQuery->select('IDBARANGSKU, IDBARANGSATUAN, IFNULL(SUM(JUMLAHMASUK - JUMLAHKELUAR), 0) AS STOKAWAL');
        $subQuery->where('IDGUDANG', $idGudang);
        $subQuery->where('DATE(INPUTTANGGALWAKTU) <', $tanggalAwal);
        $subQuery->groupBy('IDBARANGSKU, IDBARANGSATUAN');
        $subQuery   =   $subQuery->getCompiledSelect();

        $this->join('(' . $subQuery . ') AS F', 'A.IDBARANGSKU = F.IDBARANGSKU AND A.IDBARANGSATUAN = F.IDBARANGSATUAN', 'LEFT');

        $subQuery   =   $this->db->table('t_notapembelianinbound AS GA');
        $subQuery->select('GB.IDBARANGSKU, AVG(GB.HARGABELI) AS HARGABELIRERATA');
        $subQuery->join('t_notapembelianbarang AS GB', 'GA.IDNOTAPEMBELIANBARANG = GB.IDNOTAPEMBELIANBARANG', 'LEFT');
        $subQuery->where('GA.IDGUDANG', $idGudang);
        $subQuery->where('DATE(GA.PROSESTANGGALWAKTU) <= ', $tanggalAkhir);
        $subQuery->groupBy('GB.IDBARANGSKU');
        $subQuery   =   $subQuery->getCompiledSelect();

        $this->join('(' . $subQuery . ') AS G', 'A.IDBARANGSKU = G.IDBARANGSKU', 'LEFT');

        if(isset($idBarangKategori) && $idBarangKategori != 0 && $idBarangKategori != "") $this->where('B.IDBARANGKATEGORI', $idBarangKategori);
        if(isset($kataKunciPencarian) && $kataKunciPencarian != "") {
            $this->groupStart();
            $this->orLike('C.NAMAKATEGORI', $kataKunciPencarian, 'both')
            ->orLike('B.NAMABARANG', $kataKunciPencarian, 'both')
            ->orLike('B.KODEBARANG', $kataKunciPencarian, 'both')
            ->orLike('A.DESKRIPSI', $kataKunciPencarian, 'both')
            ->orLike('A.KODESKU', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }
        
        $this->groupBy('A.IDBARANGSKU, A.IDBARANGSATUAN');

        return $this;
	}

    public function getDataPersediaanBarangToko($idToko, $idBarangKategori, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian)
    {	
        $this->select("A.IDBARANGSKU, C.NAMAKATEGORI, B.NAMABARANG, CONCAT(B.KODEBARANG, '-', A.KODESKU) AS KODESKU, A.DESKRIPSI AS DESKRIPSISKU, D.KODESATUAN, 
                    IFNULL(F.STOKAWAL, 0) AS STOKAWAL, IFNULL(SUM(E.JUMLAHMASUK), 0) AS STOKMASUK, IFNULL(SUM(E.JUMLAHKELUAR), 0) AS STOKKELUAR,
                    IFNULL(SUM(E.JUMLAHMASUK - E.JUMLAHKELUAR), 0) AS STOKAKHIR, IFNULL(G.HARGABELIRERATA, 0) AS HARGABELIRERATA, 0 AS NILAIPERSEDIAAN");
        $this->from('m_barangsku A', true);
        $this->join('m_barang AS B', 'A.IDBARANG = B.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS C', 'B.IDBARANGKATEGORI = C.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangsatuan AS D', 'A.IDBARANGSATUAN = D.IDBARANGSATUAN', 'LEFT');
        $this->join('t_tokostok AS E', 'A.IDBARANGSKU = E.IDBARANGSKU AND E.IDTOKO = ' . $idToko . ' AND DATE(E.INPUTTANGGALWAKTU) BETWEEN "' . $tanggalAwal . '" AND "' . $tanggalAkhir . '"', 'LEFT');

        $subQuery   =   $this->db->table('t_tokostok');
        $subQuery->select('IDBARANGSKU, IDBARANGSATUAN, IFNULL(SUM(JUMLAHMASUK - JUMLAHKELUAR), 0) AS STOKAWAL');
        $subQuery->where('IDTOKO', $idToko);
        $subQuery->where('DATE(INPUTTANGGALWAKTU) <', $tanggalAwal);
        $subQuery->groupBy('IDBARANGSKU, IDBARANGSATUAN');
        $subQuery   =   $subQuery->getCompiledSelect();

        $this->join('(' . $subQuery . ') AS F', 'A.IDBARANGSKU = F.IDBARANGSKU AND A.IDBARANGSATUAN = F.IDBARANGSATUAN', 'LEFT');

        $subQuery   =   $this->db->table('t_tokonotamutasiinbound AS GA');
        $subQuery->select('GB.IDBARANGSKU, AVG(GB.HARGAGROSIR) AS HARGABELIRERATA');
        $subQuery->join('t_tokonotamutasibarang AS GB', 'GA.IDTOKONOTAMUTASIBARANG = GB.IDTOKONOTAMUTASIBARANG', 'LEFT');
        $subQuery->join('t_tokonotamutasirekap AS GC', 'GB.IDTOKONOTAMUTASIREKAP = GC.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $subQuery->where('GC.IDTOKO', $idToko);
        $subQuery->where('DATE(GA.PROSESTANGGALWAKTU) <= ', $tanggalAkhir);
        $subQuery->groupBy('GB.IDBARANGSKU');
        $subQuery   =   $subQuery->getCompiledSelect();

        $this->join('(' . $subQuery . ') AS G', 'A.IDBARANGSKU = G.IDBARANGSKU', 'LEFT');

        if(isset($idBarangKategori) && $idBarangKategori != 0 && $idBarangKategori != "") $this->where('B.IDBARANGKATEGORI', $idBarangKategori);
        if(isset($kataKunciPencarian) && $kataKunciPencarian != "") {
            $this->groupStart();
            $this->orLike('C.NAMAKATEGORI', $kataKunciPencarian, 'both')
            ->orLike('B.NAMABARANG', $kataKunciPencarian, 'both')
            ->orLike('B.KODEBARANG', $kataKunciPencarian, 'both')
            ->orLike('A.DESKRIPSI', $kataKunciPencarian, 'both')
            ->orLike('A.KODESKU', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }
        
        $this->groupBy('A.IDBARANGSKU, A.IDBARANGSATUAN');

        return $this;
	}
}