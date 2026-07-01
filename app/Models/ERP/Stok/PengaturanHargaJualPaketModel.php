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
            COUNT(DISTINCT IF(A.STATUS = 1, A.IDTOKO, NULL)) AS TOTALTOKO, MAX(A.JUMLAHBARANG) AS JUMLAHBARANG, MAX(A.STATUS) AS STATUS,
            '' AS STATUSSTR"
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

    public function getDetailHargaJualPaket($namaPaket, $includeArrIdHargaRetailPaket = false)
    {
        $arrIdHargaRetailPaketSelect  =   "";
        if($includeArrIdHargaRetailPaket) {
            $arrIdHargaRetailPaketSelect  =   ", GROUP_CONCAT(DISTINCT IDHARGARETAILPAKET) AS ARRIDHARGARETAILPAKET";
        }

        $this->select(
            "MIN(IDHARGARETAILPAKET) AS IDHARGARETAILPAKET, GROUP_CONCAT(DISTINCT IF(STATUS = 1, IDTOKO, NULL)) AS ARRIDTOKO, NAMAHARGARETAILPAKET,
            DESKRIPSI, MAX(JUMLAHBARANG) AS JUMLAHBARANG, MAX(STATUS) AS STATUS" . $arrIdHargaRetailPaketSelect
        );

        $this->from('t_hargaretailpaket', true);
        $this->where('NAMAHARGARETAILPAKET', $namaPaket);
        $this->groupBy('NAMAHARGARETAILPAKET, DESKRIPSI');

        $result =   $this->get()->getRowArray();

        if(is_null($result)) return false;
        return $result;
	}

    public function getDataBarangPaket($idHargaRetailPaket)
    {
        $this->select(
            "A.IDBARANGSKU, B.KODESKU, '[]' AS ATRIBUTSKUSTR, B.DESKRIPSI AS DESKRIPSISKU, 0 AS HARGATERENDAH,
            0 AS HARGATERTINGGI, A.JUMLAH, A.HARGA AS HARGAPAKET"
        );
        $this->from('t_hargaretailpaketsku AS A', true);
        $this->join('m_barangsku AS B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->where('A.IDHARGARETAILPAKET', $idHargaRetailPaket);

        $result =   $this->get()->getResultObject();

        if(is_null($result)) return [];
        return $result;
	}

    public function getHargaJualBarangSKU($idBarangSKU)
    {
        $this->select(
            "MIN(HARGA) AS HARGATERENDAH, MAX(HARGA) AS HARGATERTINGGI"
        );
        $this->from('t_baranghargajual', true);
        $this->where('IDBARANGSKU', $idBarangSKU);
        $this->groupBy('IDBARANGSKU');

        $result =   $this->get()->getRowArray();

        if(is_null($result)) return [
            "HARGATERENDAH" =>  0,
            "HARGATERTINGGI"=>  0
        ];
        return $result;
	}

    public function isPaketTokoExist($idTokoBerlakuBertambah, $namaPaket)
    {
        $this->select('IDHARGARETAILPAKET');
        $this->from('t_hargaretailpaket', true);
        $this->where('IDTOKO', $idTokoBerlakuBertambah);
        $this->where('NAMAHARGARETAILPAKET', $namaPaket);

        $result =   $this->get()->getRowArray();

        if(is_null($result)) return false;
        return $result;
	}

    public function deleteDataIdBarangSKUPaket($idBarangSKU, $arrIdHargaRetailPaket){
        if($idBarangSKU > 0 && count($arrIdHargaRetailPaket) > 0){
            return $this->db->table('t_hargaretailpaketsku')
                            ->where('IDBARANGSKU', $idBarangSKU)
                            ->whereIn('IDHARGARETAILPAKET', $arrIdHargaRetailPaket)
                            ->delete();
        }

        return false;
    }
}
