<?php

namespace App\Models\ERP\Master;
use CodeIgniter\Model;

class BarangModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'm_barang';
    protected $primaryKey       = 'IDBARANG';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDBARANG', 'IDBARANGMERK', 'IDBARANGKATEGORI', 'NAMABARANG', 'KODEBARANG', 'FOTOBARANG', 'DESKRIPSI'];

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
    
    public function getListBarang($arrIdBarangKategori, $arrIdBarangMerk, $searchKeyword)
    {	
        $this->select("A.IDBARANG, A.IDBARANGMERK, A.IDBARANGKATEGORI, B.NAMAMERK, C.NAMAKATEGORI, A.NAMABARANG, A.KODEBARANG, A.FOTOBARANG, A.DESKRIPSI");
        $this->from('m_barang A', true);
        $this->join('m_barangmerk AS B', 'A.IDBARANGMERK = B.IDBARANGMERK', 'LEFT');
        $this->join('m_barangkategori AS C', 'A.IDBARANGKATEGORI = C.IDBARANGKATEGORI', 'LEFT');

        if(isset($arrIdBarangKategori) && is_array($arrIdBarangKategori) && count($arrIdBarangKategori) > 0) {
            $this->whereIn('A.IDBARANGKATEGORI', $arrIdBarangKategori);
        }

        if(isset($arrIdBarangMerk) && is_array($arrIdBarangMerk) && count($arrIdBarangMerk) > 0) {
            $this->whereIn('A.IDBARANGMERK', $arrIdBarangMerk);
        }
        
        if(isset($searchKeyword) && !is_null($searchKeyword)){
            $this->groupStart();
            $this->like('B.NAMAMERK', $searchKeyword, 'both')
            ->orLike('C.NAMAKATEGORI', $searchKeyword, 'both')
            ->orLike('A.NAMABARANG', $searchKeyword, 'both')
            ->orLike('A.KODEBARANG', $searchKeyword, 'both')
            ->orLike('A.DESKRIPSI', $searchKeyword, 'both');
            $this->groupEnd();
        }

        return $this;
	}

    public function getIdBarangBaru()
    {	
        $this->select("IDBARANG");
        $this->from('m_barang', true);
        $this->orderBy('IDBARANG', 'DESC');
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return rand(2000, 50000);
        else return intval($result['IDBARANG']) + 1;
	}
}