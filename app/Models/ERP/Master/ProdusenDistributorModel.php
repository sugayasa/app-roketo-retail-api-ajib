<?php

namespace App\Models\ERP\Master;
use CodeIgniter\Model;

class ProdusenDistributorModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'm_produsendistributor';
    protected $primaryKey       = 'IDPRODUSENDISTRIBUTOR';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDPRODUSENDISTRIBUTOR', 'TIPEPRODUSENDISTRIBUTOR', 'ARRIDMERK', 'NAMA', 'TELPON', 'ALAMAT', 'CATATAN'];

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
}