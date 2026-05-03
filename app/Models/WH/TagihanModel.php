<?php

namespace App\Models\WH;
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
    
    public function getDaftarTagihanToko($idGudang, $idToko, $statusTagihan, $tanggalJTAwal, $tanggalJTAkhir, $kataKunciPencarian, $tampilHanyaBelumLunas)
    {	
        $this->select("A.IDTOKONOTAMUTASIPEMBAYARAN, CONCAT('[', C.KODE, '] ', C.NAMA) AS NAMATOKO, B.NOTAMUTASINOMOR, A.NOTAMUTASIPEMBAYARANNOMOR, A.STATUS,
                    DATE_FORMAT(A.JATUHTEMPO, '%d %b %Y') AS TANGGALJATUHTEMPO, A.PEMBAYARANKE, A.KETERANGAN, A.NOMINAL,
                    IF(A.BUKTIBAYAR IS NULL OR A.BUKTIBAYAR = '', '', CONCAT('".URL_BUKTI_PEMBAYARAN."', A.BUKTIBAYAR)) AS BUKTIBAYAR");
        $this->from('t_tokonotamutasipembayaran A', true);
        $this->join('t_tokonotamutasirekap B', 'A.IDTOKONOTAMUTASIREKAP = B.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $this->join('m_toko C', 'B.IDTOKO = C.IDTOKO', 'LEFT');
        $this->where('B.IDGUDANG', $idGudang);

        if(!isset($tampilHanyaBelumLunas) || $tampilHanyaBelumLunas != 1 || $tampilHanyaBelumLunas == '') {
            if(isset($idToko) && !is_null($idToko) && $idToko != '' && $idToko != 0) $this->where('B.IDTOKO', $idToko);
            if(isset($statusTagihan) && !is_null($statusTagihan) && $statusTagihan != '' && $statusTagihan != -1) $this->where('A.STATUS', $statusTagihan);

            if(isset($kataKunciPencarian) && !is_null($kataKunciPencarian) && $kataKunciPencarian != ''){
                $this->groupStart();
                $this->like('C.KODE', $kataKunciPencarian, 'both')
                ->orLike('C.NAMA', $kataKunciPencarian, 'both')
                ->orLike('A.KETERANGAN', $kataKunciPencarian, 'both')
                ->orLike('B.NOTAMUTASINOMOR', $kataKunciPencarian, 'both');
                $this->groupEnd();
            }

            $this->where('DATE(A.JATUHTEMPO) >= ', $tanggalJTAwal);
            $this->where('DATE(A.JATUHTEMPO) <=', $tanggalJTAkhir);
        } else {
            $this->where('A.STATUS', 0);
        }
        
        $this->orderBy('A.JATUHTEMPO ASC');
        return $this;
	}
}