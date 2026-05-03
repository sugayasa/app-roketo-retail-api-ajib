<?php

namespace App\Models\ERP\Master;
use CodeIgniter\Model;

class BarangSKUAtributModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'm_barangskuatribut';
    protected $primaryKey       = 'IDBARANGSKUATRIBUT';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDBARANGSKUATRIBUT', 'IDBARANGSKU', 'IDBARANGATRIBUT', 'NILAIATRIBUT'];

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

    public function getDataBarangSKUAtribut($idBarangSKU, $idBarangAtribut)
    {	
        $this->select("IDBARANGSKUATRIBUT");
        $this->from('m_barangskuatribut', true);
        $this->where('IDBARANGSKU', $idBarangSKU);
        $this->where('IDBARANGATRIBUT', $idBarangAtribut);
        $this->limit(1);

        $row    =   $this->get()->getRowArray();

        if(is_null($row)) return false;
        return $row;
	}
}