<?php

namespace App\Models\ERP\Stok;

use CodeIgniter\Model;

class PengaturanHargaJualPaketModel extends Model
{
    protected $table            = 't_hargaretailpaket';
    protected $primaryKey       = 'IDHARGARETAILPAKET';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDTOKO', 'NAMAHARGARETAILPAKET', 'DESKRIPSI', 'JUMLAHBARANG', 'STATUS'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

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

    public function getDataHargaJualPaket($searchKeyword)
    {
        $this->select(
            "MIN(A.IDHARGARETAILPAKET) AS IDHARGARETAILPAKET, A.NAMAHARGARETAILPAKET, GROUP_CONCAT(DISTINCT A.DESKRIPSI) AS DESKRIPSI,
            MAX(A.JUMLAHBARANG) AS JUMLAHBARANG, MIN(A.STATUS) AS STATUS, '' AS STATUSSTR"
        );
        $this->from('t_hargaretailpaket AS A', true);

        if(isset($searchKeyword) && !is_null($searchKeyword) && $searchKeyword !== ''){
            $this->groupStart();
            $this->like('A.NAMAHARGARETAILPAKET', $searchKeyword, 'both')
            ->orLike('A.DESKRIPSI', $searchKeyword, 'both');
            $this->groupEnd();
        }

        $this->groupBy('A.NAMAHARGARETAILPAKET');
        $this->orderBy('A.NAMAHARGARETAILPAKET');

        return $this;
	}
}
