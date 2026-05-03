<?php

namespace App\Models\ERP\Master;
use CodeIgniter\Model;

class TokoModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'm_toko';
    protected $primaryKey       = 'IDTOKO';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDTOKO', 'IDGUDANG', 'IDUSERADMINKEPALATOKO', 'ARRIDBARANGKATEGORI', 'ARRIDTOKOTERDEKAT', 'KODE', 'NAMA', 'ALAMAT', 'STATUSEKSTERNAL'];

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

    public function getListToko($statusEksternal, $idGudang, $idKelompokHargaGrosir, $searchKeyword)
    {	
        $subQueryKategoriBarang =   $this->db->table('m_toko EA')
                                    ->select("EA.IDTOKO,
                                        COALESCE(
                                            JSON_ARRAYAGG(IF(EB.IDBARANGKATEGORI IS NULL, NULL, JSON_OBJECT('IDBARANGKATEGORI', EC.IDBARANGKATEGORI, 'NAMAKATEGORI', EC.NAMAKATEGORI))),
                                            JSON_ARRAY()
                                        ) AS ARRIDBARANGKATEGORI", false)
                                    ->join("JSON_TABLE(EA.ARRIDBARANGKATEGORI, '$[*]' COLUMNS (IDBARANGKATEGORI INT PATH '$')) EB", '1=1', 'LEFT', false)
                                    ->join('m_barangkategori EC', 'EB.IDBARANGKATEGORI = EC.IDBARANGKATEGORI', 'LEFT')
                                    ->groupBy('EA.IDTOKO')
                                    ->getCompiledSelect();
        $subQueryTokoTerdekat   =   $this->db->table('m_toko FA')
                                    ->select("FA.IDTOKO,
                                        COALESCE(
                                            JSON_ARRAYAGG(IF(FB.IDTOKO IS NULL, NULL, JSON_OBJECT('IDTOKO', FC.IDTOKO, 'NAMATOKO', FC.NAMA))),
                                            JSON_ARRAY()
                                        ) AS ARRIDTOKOTERDEKAT", false)
                                    ->join("JSON_TABLE(FA.ARRIDTOKOTERDEKAT, '$[*]' COLUMNS (IDTOKO INT PATH '$')) FB", '1=1', 'LEFT', false)
                                    ->join('m_toko FC', 'FB.IDTOKO = FC.IDTOKO', 'LEFT')
                                    ->groupBy('FA.IDTOKO')
                                    ->getCompiledSelect();

        $this->select("A.IDTOKO, A.IDGUDANG, A.IDUSERADMINKEPALATOKO, A.IDKELOMPOKHARGAGROSIR, B.NAMA AS NAMAGUDANG, D.KELOMPOKHARGAGROSIR, A.STATUSEKSTERNAL,
                    IFNULL(E.ARRIDBARANGKATEGORI, JSON_ARRAY()) AS ARRIDBARANGKATEGORI, IFNULL(F.ARRIDTOKOTERDEKAT, JSON_ARRAY()) AS ARRIDTOKOTERDEKAT,
                    IFNULL(C.NAME, '') AS NAMAKEPALATOKO, A.KODE, A.NAMA, A.ALAMAT");
        $this->from('m_toko A', true);
        $this->join('m_gudang AS B', 'A.IDGUDANG = B.IDGUDANG', 'LEFT');
        $this->join('m_useradmin AS C', 'A.IDUSERADMINKEPALATOKO = C.IDUSERADMIN', 'LEFT');
        $this->join('m_kelompokhargagrosir AS D', 'A.IDKELOMPOKHARGAGROSIR = D.IDKELOMPOKHARGAGROSIR', 'LEFT');
        $this->join("($subQueryKategoriBarang) E", 'A.IDTOKO = E.IDTOKO', 'LEFT', false);
        $this->join("($subQueryTokoTerdekat) F", 'A.IDTOKO = F.IDTOKO', 'LEFT', false);
        
        if(isset($statusEksternal) && !is_null($statusEksternal) && $statusEksternal != '') $this->where('A.STATUSEKSTERNAL', $statusEksternal);
        if(isset($idGudang) && !is_null($idGudang) && $idGudang > 0) $this->where('A.IDGUDANG', $idGudang);
        if(isset($idKelompokHargaGrosir) && !is_null($idKelompokHargaGrosir) && $idKelompokHargaGrosir > 0) $this->where('A.IDKELOMPOKHARGAGROSIR', $idKelompokHargaGrosir);
        if(isset($searchKeyword) && !is_null($searchKeyword)){
            $this->groupStart();
            $this->like('B.NAMA', $searchKeyword, 'both')
            ->orLike('C.NAME', $searchKeyword, 'both')
            ->orLike('A.NAMA', $searchKeyword, 'both')
            ->orLike('A.KODE', $searchKeyword, 'both')
            ->orLike('A.ALAMAT', $searchKeyword, 'both');
            $this->groupEnd();
        }
        $this->groupBy('A.IDTOKO');
        $this->orderBy('A.STATUSEKSTERNAL, B.NAMA, A.NAMA');

        return $this;
	}

    public function getListAllTokoHargaJual()
    {	
        $this->select("A.IDTOKO, A.STATUSEKSTERNAL, B.NAMA AS NAMAGUDANG, C.NAME AS NAMAKEPALATOKO, A.KODE, A.NAMA, '[]' AS ARRHARGAJUALPERSKU");
        $this->from('m_toko A', true);
        $this->join('m_gudang AS B', 'A.IDGUDANG = B.IDGUDANG', 'LEFT');
        $this->join('m_useradmin AS C', 'A.IDUSERADMINKEPALATOKO = C.IDUSERADMIN', 'LEFT');
        $this->orderBy('B.NAMA, A.NAMA');

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}
}