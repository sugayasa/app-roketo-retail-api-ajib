<?php

namespace App\Models\POS\Laporan;
use CodeIgniter\Model;

class TagihanModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_tokonotamutasipembayaran';
    protected $primaryKey       = 'IDTOKONOTAMUTASIPEMBAYARAN';
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
    
    public function getDetailTagihanMutasiToko($idToko, $statusPelunasan, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian, $tampilTagihanBelumLunas, $compileOnly = false)
    {	
        $baseQuery  =   $this->db->table("t_tokonotamutasipembayaran A", true);
        $baseQuery->select("B.NOTAMUTASINOMOR, A.STATUS, DATE_FORMAT(A.JATUHTEMPO, '%d %b %Y') AS TANGGALJATUHTEMPO, A.PEMBAYARANKE, A.KETERANGAN, A.NOMINAL,
                    IF(A.BUKTIBAYAR IS NULL OR A.BUKTIBAYAR = '', '', CONCAT('".URL_BUKTI_PEMBAYARAN."', A.BUKTIBAYAR)) AS BUKTIBAYAR");
        $baseQuery->join('t_tokonotamutasirekap B', 'A.IDTOKONOTAMUTASIREKAP = B.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $baseQuery->where('B.IDTOKO', $idToko);

        if(!isset($tampilTagihanBelumLunas) || $tampilTagihanBelumLunas != 1 || $tampilTagihanBelumLunas == '') {
            if(isset($statusPelunasan) && !is_null($statusPelunasan) && $statusPelunasan != '' && $statusPelunasan != -1) $baseQuery->where('A.STATUS', $statusPelunasan);
            $baseQuery->where('DATE(A.JATUHTEMPO) >= ', $tanggalAwal);
            $baseQuery->where('DATE(A.JATUHTEMPO) <=', $tanggalAkhir);
        } else {
            $baseQuery->where('A.STATUS', 0);
        }
        
        if(isset($kataKunciPencarian) && !is_null($kataKunciPencarian) && $kataKunciPencarian != ''){
            $baseQuery->groupStart();
            $baseQuery->like('A.KETERANGAN', $kataKunciPencarian, 'both')
            ->orLike('B.NOTAMUTASINOMOR', $kataKunciPencarian, 'both');
            $baseQuery->groupEnd();
        }
        $baseQuery->orderBy('A.JATUHTEMPO ASC');
        
        if(!$compileOnly) return $baseQuery;
        if($compileOnly) return $baseQuery->getCompiledSelect();
	}
    
    public function getDataRekapitulasiTagihan($baseQueryString){
        $query  =   $this->db->query(
                        "SELECT IFNULL(SUM(NOMINAL), 0) AS NOMINALTAGIHAN,
                                IFNULL(SUM(CASE WHEN STATUS = 1 THEN NOMINAL ELSE 0 END), 0) AS NOMINALLUNAS,
                                IFNULL(SUM(CASE WHEN STATUS = 0 THEN NOMINAL ELSE 0 END), 0) AS NOMINALBELUMLUNAS,
                                IFNULL(COUNT(NOTAMUTASINOMOR), 0) AS JUMLAHNOTATAGIHAN,
                                IFNULL(COUNT(CASE WHEN STATUS = 1 THEN NOTAMUTASINOMOR ELSE NULL END), 0) AS JUMLAHNOTALUNAS,
                                IFNULL(COUNT(CASE WHEN STATUS = 0 THEN NOTAMUTASINOMOR ELSE NULL END), 0) AS JUMLAHNOTABELUMLUNAS
                        FROM ({$baseQueryString}) AS A"
                    );
        $result =   $query->getRowArray();
        if(is_null($result)) return ['NOMINALTAGIHAN' => 0, 'NOMINALLUNAS' => 0, 'NOMINALBELUMLUNAS' => 0, 'JUMLAHNOTATAGIHAN' => 0, 'JUMLAHNOTALUNAS' => 0, 'JUMLAHNOTABELUMLUNAS' => 0];
        return $result;
    }
}