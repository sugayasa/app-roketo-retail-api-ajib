<?php

namespace App\Models\ERP\MonitoringToko;
use CodeIgniter\Model;

class MonitoringPenjualanModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_penjualan';
    protected $primaryKey       = 'IDTRANSAKSI';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDTRANSAKSI'];

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
    
    public function getDataPenjualanPerTanggal($idToko, $tahunBulan)
    {	
        $this->select("DATE_FORMAT(B.INPUTTANGGALWAKTU, '%d') AS TANGGAL, SUM(A.JUMLAH * A.HARGASATUAN) AS TOTALPENJUALAN");
        $this->from('t_penjualanbarang AS A', true);
        $this->join('t_penjualanrekap B', 'A.IDPENJUALANREKAP = B.IDPENJUALANREKAP', 'LEFT');
        $this->where('LEFT(B.INPUTTANGGALWAKTU, 7)', $tahunBulan);
        $this->where('B.IDTOKO', $idToko);
        $this->groupBy('DATE(B.INPUTTANGGALWAKTU)');
        $this->orderBy('TANGGAL');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}
    
    public function getDataPenjualanPerKategori($idToko, $tahunBulan)
    {	
        $this->select("E.NAMAKATEGORI, SUM(A.JUMLAH * A.HARGASATUAN) AS TOTALPENJUALAN");
        $this->from('t_penjualanbarang AS A', true);
        $this->join('t_penjualanrekap B', 'A.IDPENJUALANREKAP = B.IDPENJUALANREKAP', 'LEFT');
        $this->join('m_barangsku C', 'A.IDBARANGSKU = C.IDBARANGSKU', 'LEFT');
        $this->join('m_barang D', 'C.IDBARANG = D.IDBARANG', 'LEFT');
        $this->join('m_barangkategori E', 'D.IDBARANGKATEGORI = E.IDBARANGKATEGORI', 'LEFT');
        $this->where('LEFT(B.INPUTTANGGALWAKTU, 7)', $tahunBulan);
        $this->where('B.IDTOKO', $idToko);
        $this->where('E.STATUS =', 1);
        $this->groupBy('E.NAMAKATEGORI');
        $this->orderBy('E.NAMAKATEGORI');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}
    
    public function getRekapPenjualanPerTanggal($idToko, $tahunBulan)
    {	
        $this->select("DATE_FORMAT(A.INPUTTANGGALWAKTU, '%d') AS TANGGALTRANSAKSI, COUNT(A.IDPENJUALANREKAP) AS TOTALNOTA, SUM(B.JUMLAH) AS TOTALITEM,
                    SUM(A.TOTALHARGABARANG) AS TOTALHARGABARANG, SUM(A.TOTALHARGADISKON) AS TOTALHARGADISKON, SUM(A.TOTALHARGALAIN) AS TOTALHARGALAIN,
                    SUM(A.TOTALHARGAAKHIR) AS TOTALHARGAAKHIR");
        $this->from('t_penjualanrekap A', true);
        $this->join('t_penjualanbarang B', 'A.IDPENJUALANREKAP = B.IDPENJUALANREKAP', 'LEFT');
        $this->where('A.IDTOKO', $idToko);
        $this->where('LEFT(A.INPUTTANGGALWAKTU, 7)', $tahunBulan);
        $this->groupBy('DATE(A.INPUTTANGGALWAKTU)');
        $this->orderBy('A.INPUTTANGGALWAKTU ASC');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}
    
    public function getDataHistoriPenjualan($idToko, $tahunBulan)
    {	
        $this->select("A.IDBARANGSKU, IFNULL(C.FOTOBARANGSKU, '') AS FOTOBARANGSKU, F.NAMAKATEGORI, CONCAT(C.KODESKU, ' ', E.NAMAMERK, ' - ', D.NAMABARANG) AS NAMABARANG,
                    '[]' AS ATRIBUTSKU, H.NAMA AS NAMACUSTOMER, I.METODEBAYAR, G.NAMASATUAN, A.JUMLAH, A.HARGASATUAN, (A.JUMLAH * A.HARGASATUAN) AS TOTALHARGA,
                    DATE_FORMAT(B.INPUTTANGGALWAKTU, '%d %M %Y %H:%i') AS TANGGALWAKTU");
        $this->from('t_penjualanbarang AS A', true);
        $this->join('t_penjualanrekap B', 'A.IDPENJUALANREKAP = B.IDPENJUALANREKAP', 'LEFT');
        $this->join('m_barangsku C', 'A.IDBARANGSKU = C.IDBARANGSKU', 'LEFT');
        $this->join('m_barang D', 'C.IDBARANG = D.IDBARANG', 'LEFT');
        $this->join('m_barangmerk E', 'D.IDBARANGMERK = E.IDBARANGMERK', 'LEFT');
        $this->join('m_barangkategori F', 'D.IDBARANGKATEGORI = F.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangsatuan G', 'A.IDBARANGSATUAN = G.IDBARANGSATUAN', 'LEFT');
        $this->join('m_customer H', 'B.IDCUSTOMER = H.IDCUSTOMER', 'LEFT');
        $this->join('a_metodebayar I', 'B.IDMETODEBAYAR = I.IDMETODEBAYAR', 'LEFT');
        $this->where('B.IDTOKO', $idToko);
        $this->where('LEFT(B.INPUTTANGGALWAKTU, 7)', $tahunBulan);
        $this->orderBy('B.INPUTTANGGALWAKTU ASC');
        $this->limit(50);

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}

    public function getDataBarangTerlaris($idToko, $tahunBulan)
    {	
        $this->select("IFNULL(D.FOTOBARANG, '') AS FOTOBARANG, CONCAT(E.NAMAMERK, ' - ', D.NAMABARANG) AS NAMABARANG, F.NAMAKATEGORI, COUNT(DISTINCT(A.IDBARANGSKU)) AS JUMLAHBARANGSKU,
                    G.NAMASATUAN, SUM(A.JUMLAH) AS JUMLAHTERJUAL");
        $this->from('t_penjualanbarang AS A', true);
        $this->join('t_penjualanrekap B', 'A.IDPENJUALANREKAP = B.IDPENJUALANREKAP', 'LEFT');
        $this->join('m_barangsku C', 'A.IDBARANGSKU = C.IDBARANGSKU', 'LEFT');
        $this->join('m_barang D', 'C.IDBARANG = D.IDBARANG', 'LEFT');
        $this->join('m_barangmerk E', 'D.IDBARANGMERK = E.IDBARANGMERK', 'LEFT');
        $this->join('m_barangkategori F', 'D.IDBARANGKATEGORI = F.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangsatuan G', 'A.IDBARANGSATUAN = G.IDBARANGSATUAN', 'LEFT');
        $this->where('B.IDTOKO', $idToko);
        $this->where('LEFT(B.INPUTTANGGALWAKTU, 7)', $tahunBulan);
        $this->groupBy('C.IDBARANG, A.IDBARANGSATUAN');
        $this->orderBy('JUMLAHTERJUAL', 'DESC');
        $this->limit(20);

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}
}