<?php

namespace App\Models\ERP\Master;
use CodeIgniter\Model;

class GudangModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'm_gudang';
    protected $primaryKey       = 'IDGUDANG';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDGUDANG', 'IDUSERADMINKEPALAGUDANG', 'KODE', 'NAMAPERUSAHAAN', 'NAMA', 'ALAMAT', 'KOTA', 'PROVINSI', 'LOGO'];

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

    public function getListGudang($searchKeyword)
    {	
        $this->select("A.IDGUDANG, A.IDUSERADMINKEPALAGUDANG, B.NAME AS NAMAKEPALAGUDANG, A.KODE, A.NAMAPERUSAHAAN, A.NAMA, A.ALAMAT, A.KOTA, A.PROVINSI,
                CONCAT('".URL_LOGO_PERUSAHAAN."', IF(A.LOGO = '' OR A.LOGO IS NULL, 'default.png', A.LOGO)) AS LOGOURL, A.LOGO");
        $this->from('m_gudang A', true);
        $this->join('m_useradmin AS B', 'A.IDUSERADMINKEPALAGUDANG = B.IDUSERADMIN', 'LEFT');
        if(isset($searchKeyword) && !is_null($searchKeyword)){
            $this->groupStart();
            $this->like('B.NAME', $searchKeyword, 'both')
            ->orLike('A.NAMA', $searchKeyword, 'both')
            ->orLike('A.KODE', $searchKeyword, 'both')
            ->orLike('A.NAMAPERUSAHAAN', $searchKeyword, 'both')
            ->orLike('A.ALAMAT', $searchKeyword, 'both')
            ->orLike('A.KOTA', $searchKeyword, 'both')
            ->orLike('A.PROVINSI', $searchKeyword, 'both');
            $this->groupEnd();
        }

        return $this;
	}
}