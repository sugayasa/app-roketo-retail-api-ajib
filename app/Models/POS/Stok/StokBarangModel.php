<?php

namespace App\Models\POS\Stok;
use CodeIgniter\Model;

class StokBarangModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_tokostok';
    protected $primaryKey       = 'IDTOKOSTOK';
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

    public function getDaftarStokBarang($idToko, $arrIdKategoriBarang, $idBarangKategori, $idBarangMerk, $jenisStok, $kataKunciPencarian, $urutan)
    {	
        $this->select("A.IDBARANGSKU, C.NAMAKATEGORI, D.NAMAMERK, B.KODEBARANG, B.NAMABARANG, A.KODESKU, A.DESKRIPSI AS DESKRIPSISKU, E.NAMASATUAN, '[]' AS ATRIBUTSKUSTR,
                    IFNULL(SUM(F.JUMLAHMASUK - F.JUMLAHKELUAR), 0) AS STOK, IFNULL(G.HARGABELIRERATA, 0) AS HARGABELIRERATA");
        $this->from('m_barangsku A', true);
        $this->join('m_barang AS B', 'A.IDBARANG = B.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS C', 'B.IDBARANGKATEGORI = C.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS D', 'B.IDBARANGMERK = D.IDBARANGMERK', 'LEFT');
        $this->join('m_barangsatuan AS E', 'A.IDBARANGSATUAN = E.IDBARANGSATUAN', 'LEFT');
        $this->join('t_tokostok AS F', 'A.IDBARANGSKU = F.IDBARANGSKU AND F.IDTOKO = ' . $idToko, 'LEFT');

        $subQuery   =   $this->db->table('t_tokonotamutasibarang AS GA');
        $subQuery->select('GA.IDBARANGSKU, AVG(GA.HARGAGROSIR) AS HARGABELIRERATA');
        $subQuery->join('t_tokonotamutasirekap AS GB', 'GA.IDTOKONOTAMUTASIREKAP = GB.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $subQuery->where('GB.IDTOKO', $idToko);
        $subQuery->groupBy('GA.IDBARANGSKU');
        $subQuery   =   $subQuery->getCompiledSelect();

        $this->join('(' . $subQuery . ') AS G', 'A.IDBARANGSKU = G.IDBARANGSKU', 'LEFT');

        if(!empty($arrIdKategoriBarang)) $this->whereIn('B.IDBARANGKATEGORI', $arrIdKategoriBarang);
        if(isset($idBarangKategori) && $idBarangKategori != 0 && $idBarangKategori != "") $this->where('B.IDBARANGKATEGORI', $idBarangKategori);
        if(isset($idBarangMerk) && $idBarangMerk != 0 && $idBarangMerk != "") $this->where('B.IDBARANGMERK', $idBarangMerk);

        if(isset($kataKunciPencarian) && $kataKunciPencarian != "") {
            $this->groupStart();
            $this->like('D.NAMAMERK', $kataKunciPencarian, 'both')
            ->orLike('C.NAMAKATEGORI', $kataKunciPencarian, 'both')
            ->orLike('B.NAMABARANG', $kataKunciPencarian, 'both')
            ->orLike('B.KODEBARANG', $kataKunciPencarian, 'both')
            ->orLike('A.DESKRIPSI', $kataKunciPencarian, 'both')
            ->orLike('A.KODESKU', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }
        
        $this->groupBy('A.IDBARANGSKU');

        switch ($jenisStok) {
            case 'LDN': $this->having('STOK >', 0); break;
            case 'SDN': $this->having('STOK <=', 0); break;
        }

        switch ($urutan) {
            case 'AZ': $this->orderBy('C.NAMAKATEGORI, D.NAMAMERK, B.NAMABARANG, A.KODESKU', 'ASC'); break;
            case 'ZA': $this->orderBy('C.NAMAKATEGORI, D.NAMAMERK, B.NAMABARANG, A.KODESKU', 'DESC'); break;
            case 'ASC': $this->orderBy('STOK, C.NAMAKATEGORI, D.NAMAMERK, B.NAMABARANG, A.KODESKU', 'ASC'); break;
            case 'DESC': $this->orderBy('STOK, C.NAMAKATEGORI, D.NAMAMERK, B.NAMABARANG, A.KODESKU', 'DESC'); break;
        }

        return $this;
	}
}