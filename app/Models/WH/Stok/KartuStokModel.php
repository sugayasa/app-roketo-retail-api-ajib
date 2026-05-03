<?php

namespace App\Models\WH\Stok;
use CodeIgniter\Model;

class KartuStokModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_gudangstok';
    protected $primaryKey       = 'IDGUDANGSTOK';
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

    public function getDetailKartuStokGudang($idGudang, $idBarangSKU, $tanggalAwal, $tanggalAkhir)
    {	
        $this->select("GROUP_CONCAT(IFNULL(D.NOTAPEMBELIANNOMOR, ''), IFNULL(G.NOTAMUTASINOMOR, '')) AS NOMORNOTA,
                DATE_FORMAT(A.INPUTTANGGALWAKTU, '%d %b %Y %H:%i') AS INPUTTANGGALWAKTU, A.INPUTUSER,
                B.MUTASIJENIS, A.MUTASIKETERANGAN, A.JUMLAHMASUK, A.JUMLAHKELUAR, 0 AS JUMLAHSALDO,
                GROUP_CONCAT(IFNULL(E.NAMA, ''), IFNULL(H.NAMA, '')) AS NAMAPRODUSENDISTRIBUTORTOKO,
                A.INPUTTANGGALWAKTU AS INPUTTANGGALWAKTUDB");
        $this->from('t_gudangstok A', true);
        $this->join('a_mutasijenisgudang AS B', 'A.IDMUTASIJENISGUDANG = B.IDMUTASIJENISGUDANG', 'LEFT');
        $this->join('t_notapembelianbarang AS C', 'A.IDNOTAPEMBELIANBARANG = C.IDNOTAPEMBELIANBARANG', 'LEFT');
        $this->join('t_notapembelianrekap AS D', 'C.IDNOTAPEMBELIANREKAP = D.IDNOTAPEMBELIANREKAP', 'LEFT');
        $this->join('m_produsendistributor AS E', 'D.IDPRODUSENDISTRIBUTOR = E.IDPRODUSENDISTRIBUTOR', 'LEFT');
        $this->join('t_tokonotamutasibarang AS F', 'A.IDTOKONOTAMUTASIBARANG = F.IDTOKONOTAMUTASIBARANG', 'LEFT');
        $this->join('t_tokonotamutasirekap AS G', 'F.IDTOKONOTAMUTASIREKAP = G.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $this->join('m_toko AS H', 'G.IDTOKO = H.IDTOKO', 'LEFT');
        $this->where('A.IDGUDANG', $idGudang);
        $this->where('A.IDBARANGSKU', $idBarangSKU);
        $this->where('DATE(A.INPUTTANGGALWAKTU) >=', $tanggalAwal);
        $this->where('DATE(A.INPUTTANGGALWAKTU) <=', $tanggalAkhir);
        $this->groupBy('A.IDGUDANGSTOK');
        $this->orderBy('A.INPUTTANGGALWAKTU');

        return $this;
	}

    public function getSaldoStokGudangByTanggalWaktu($idGudang, $idBarangSKU, $tanggalWaktu)
    {
        $this->select("IFNULL(SUM(JUMLAHMASUK), 0) - IFNULL(SUM(JUMLAHKELUAR), 0) AS SALDOSTOK");
        $this->where('IDGUDANG', $idGudang);
        $this->where('IDBARANGSKU', $idBarangSKU);
        $this->where('INPUTTANGGALWAKTU <', $tanggalWaktu);
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return 0;
        return isset($result['SALDOSTOK']) ? intval($result['SALDOSTOK']) : 0;
    }

    public function getDetailKartuStokToko($idToko, $idBarangSKU, $tanggalAwal, $tanggalAkhir)
    {	
        $this->select("GROUP_CONCAT(IFNULL(D.NOTAMUTASINOMOR, ''), IFNULL(G.NOTAPENJUALANNOMOR, '')) AS NOMORNOTA,
                DATE_FORMAT(A.INPUTTANGGALWAKTU, '%d %b %Y %H:%i') AS INPUTTANGGALWAKTU, A.INPUTUSER,
                B.MUTASIJENIS, A.MUTASIKETERANGAN, A.JUMLAHMASUK, A.JUMLAHKELUAR, 0 AS JUMLAHSALDO,
                GROUP_CONCAT(IFNULL(E.NAMA, ''), IFNULL(H.NAMA, '')) AS NAMAGUDANGCUSTOMER,
                A.INPUTTANGGALWAKTU AS INPUTTANGGALWAKTUDB");
        $this->from('t_tokostok A', true);
        $this->join('a_mutasijenistoko AS B', 'A.IDMUTASIJENISTOKO = B.IDMUTASIJENISTOKO', 'LEFT');
        $this->join('t_tokonotamutasibarang AS C', 'A.IDTOKONOTAMUTASIBARANG = C.IDTOKONOTAMUTASIBARANG', 'LEFT');
        $this->join('t_tokonotamutasirekap AS D', 'C.IDTOKONOTAMUTASIREKAP = D.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $this->join('m_gudang AS E', 'D.IDGUDANG = E.IDGUDANG', 'LEFT');
        $this->join('t_penjualanbarang AS F', 'A.IDPENJUALANBARANG = F.IDPENJUALANBARANG', 'LEFT');
        $this->join('t_penjualanrekap AS G', 'F.IDPENJUALANREKAP = G.IDPENJUALANREKAP', 'LEFT');
        $this->join('m_customer AS H', 'G.IDCUSTOMER = H.IDCUSTOMER', 'LEFT');
        $this->where('A.IDTOKO', $idToko);
        $this->where('A.IDBARANGSKU', $idBarangSKU);
        $this->where('DATE(A.INPUTTANGGALWAKTU) >=', $tanggalAwal);
        $this->where('DATE(A.INPUTTANGGALWAKTU) <=', $tanggalAkhir);
        $this->groupBy('A.IDTOKOSTOK');
        $this->orderBy('A.INPUTTANGGALWAKTU');

        return $this;
	}

    public function getSaldoStokTokoByTanggalWaktu($idToko, $idBarangSKU, $tanggalWaktu)
    {
        $this->select("IFNULL(SUM(JUMLAHMASUK), 0) - IFNULL(SUM(JUMLAHKELUAR), 0) AS SALDOSTOK");
        $this->where('IDTOKO', $idToko);
        $this->where('IDBARANGSKU', $idBarangSKU);
        $this->where('INPUTTANGGALWAKTU <', $tanggalWaktu);
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return 0;
        return isset($result['SALDOSTOK']) ? intval($result['SALDOSTOK']) : 0;
    }
}