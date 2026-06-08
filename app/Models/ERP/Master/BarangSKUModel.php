<?php

namespace App\Models\ERP\Master;
use CodeIgniter\Model;

class BarangSKUModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'm_barangsku';
    protected $primaryKey       = 'IDBARANGSKU';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDBARANGSKU', 'IDBARANG', 'KODESKU', 'FOTOBARANGSKU', 'DESKRIPSI'];

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
    
    public function getAtributSKU($idBarangSKU)
    {	
        $this->select("A.IDBARANGATRIBUT, A.NILAIATRIBUT, CONCAT(B.NAMAATRIBUT, ' : ', A.NILAIATRIBUT) AS NAMAATRIBUT");
        $this->from('m_barangskuatribut A', true);
        $this->join('m_barangatribut AS B', 'A.IDBARANGATRIBUT = B.IDBARANGATRIBUT', 'LEFT');
        $this->where('A.IDBARANGSKU', $idBarangSKU);

        $result =   $this->get()->getResultObject();

        if(is_null($result)) return false;
        return $result;
	}

    public function getArrAtributSKU($idBarangSKU)
    {	
        $dataAtributSKU =   $this->getAtributSKU($idBarangSKU);
        $arrAtributSKU  =   [];

        if($dataAtributSKU && count($dataAtributSKU) > 0) {
            foreach($dataAtributSKU as $keyAtribut) {
                $arrAtributSKU[]    =   $keyAtribut->NAMAATRIBUT;
            }
        }

        return $arrAtributSKU;
	}

    public function getDataBarangSatuan($idBarang)
    {	
        $subQuery1  =   $this->db->table('t_barangkonversiaturan AS A');
        $subQuery1->select('A.IDSATUANTURUNAN AS IDBARANGSATUAN, C.NAMASATUAN');
        $subQuery1->join('m_barangsku AS B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $subQuery1->join('m_barangsatuan AS C', 'A.IDSATUANTURUNAN = C.IDBARANGSATUAN', 'LEFT');
        $subQuery1->where('B.IDBARANG', $idBarang);
        $subQuery1->groupBy('A.IDSATUANTURUNAN');

        $subQuery2  =   $this->db->table('m_barangsku AS A');
        $subQuery2->select('A.IDBARANGSATUAN, B.NAMASATUAN');
        $subQuery2->join('m_barangsatuan AS B', 'A.IDBARANGSATUAN = B.IDBARANGSATUAN', 'LEFT');
        $subQuery2->where('A.IDBARANG', $idBarang);
        $subQuery2->groupBy('A.IDBARANGSATUAN');

        $subQuery1  =   $subQuery1->getCompiledSelect();
        $subQuery2  =   $subQuery2->getCompiledSelect();

        $unionQuery =   "({$subQuery1}) UNION ALL ({$subQuery2})";
        $finalQuery =   $this->db->query(
                            "SELECT IDBARANGSATUAN, NAMASATUAN
                            FROM ({$unionQuery}) AS A
                            WHERE IDBARANGSATUAN IS NOT NULL
                            GROUP BY IDBARANGSATUAN, NAMASATUAN"
                        );
        $result     =   $finalQuery->getResultObject();

        if(is_null($result)) return [];
        return $result;
	}
    
    public function getListDetailBarangSKU($idBarang)
    {	
        $this->select("A.IDBARANGSKU, A.IDBARANGSATUAN, IFNULL(CONCAT('[', B.KODESATUAN, '] ', B.NAMASATUAN), '-') AS NAMASATUAN, A.KODESKU,
                '[]' AS ATRIBUTSKUDATA, '' AS ATRIBUTSKUSTR, A.FOTOBARANGSKU, A.DESKRIPSI AS DESKRIPSISKU");
        $this->from('m_barangsku A', true);
        $this->join('m_barangsatuan AS B', 'A.IDBARANGSATUAN = B.IDBARANGSATUAN', 'LEFT');
        $this->where('A.IDBARANG', $idBarang);
        $this->orderBy('A.KODESKU', 'ASC');

        $result =   $this->get()->getResultObject();

        if(is_null($result)) return [];
        return $result;
	}
    
    public function getDataBarangKonversiAturan($idBarangSKU)
    {	
        $this->select("A.IDBARANGKONVERSIATURAN, A.IDSATUANASLI, A.IDSATUANTURUNAN, B.KODESATUAN AS KODESATUANASLI, B.NAMASATUAN AS NAMASATUANASLI,
                        C.KODESATUAN AS KODESATUANTURUNAN, C.NAMASATUAN AS NAMASATUANTURUNAN, A.JUMLAHTURUNAN");
        $this->from('t_barangkonversiaturan A', true);
        $this->join('m_barangsatuan AS B', 'A.IDSATUANASLI = B.IDBARANGSATUAN', 'LEFT');
        $this->join('m_barangsatuan AS C', 'A.IDSATUANTURUNAN = C.IDBARANGSATUAN', 'LEFT');
        $this->where('A.IDBARANGSKU', $idBarangSKU);

        $result =   $this->get()->getResultObject();

        if(is_null($result)) return [];
        return $result;
	}
}