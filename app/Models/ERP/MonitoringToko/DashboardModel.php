<?php

namespace App\Models\ERP\MonitoringToko;
use CodeIgniter\Model;

class DashboardModel extends Model
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
    
    public function getDataPenjualanPerTanggal($idToko, $tglAwalBulanTahun, $tglAkhirBulanTahun)
    {	
        $this->select("DATE_FORMAT(B.INPUTTANGGALWAKTU, '%d') AS TANGGAL, DATE_FORMAT(B.INPUTTANGGALWAKTU, '%d %b') AS TANGGALDM, E.NAMAKATEGORI,
                    COUNT(DISTINCT(A.IDPENJUALANREKAP)) AS JUMLAHTRANSAKSI, IFNULL(SUM(A.JUMLAH), 0) AS JUMLAHITEM, SUM(A.JUMLAH * A.HARGASATUAN) AS TOTALPENJUALAN");
        $this->from('t_penjualanbarang AS A', true);
        $this->join('t_penjualanrekap B', 'A.IDPENJUALANREKAP = B.IDPENJUALANREKAP', 'LEFT');
        $this->join('m_barangsku C', 'A.IDBARANGSKU = C.IDBARANGSKU', 'LEFT');
        $this->join('m_barang D', 'C.IDBARANG = D.IDBARANG', 'LEFT');
        $this->join('m_barangkategori E', 'D.IDBARANGKATEGORI = E.IDBARANGKATEGORI', 'LEFT');
        $this->where('DATE(B.INPUTTANGGALWAKTU) >= ', $tglAwalBulanTahun);
        $this->where('DATE(B.INPUTTANGGALWAKTU) <=', $tglAkhirBulanTahun);
        $this->where('B.IDTOKO', $idToko);
        $this->where('E.STATUS =', 1);
        $this->groupBy('DATE(B.INPUTTANGGALWAKTU), E.NAMAKATEGORI');
        $this->orderBy('TANGGAL');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}
    
    public function getDataRekapPenjualanPeriode($idToko, $tglHariIni, $tglAwalBulanTahun, $tglAkhirBulanTahun, $tglAwalBulanLalu, $tglAkhirBulanLalu)
    {	
        $this->select("SUM(IF(DATE(B.INPUTTANGGALWAKTU) = '{$tglHariIni}', B.TOTALHARGABARANG, 0)) AS TOTALNOMINALHARIINI,
                       SUM(IF(DATE(B.INPUTTANGGALWAKTU) = '{$tglHariIni}', 1, 0)) AS TOTALTRANSAKSIHARIINI,
                       SUM(IF(DATE(B.INPUTTANGGALWAKTU) = '{$tglHariIni}', A.JUMLAH, 0)) AS TOTALITEMHARIINI,
                       SUM(IF(DATE(B.INPUTTANGGALWAKTU) BETWEEN '{$tglAwalBulanTahun}' AND '{$tglAkhirBulanTahun}', B.TOTALHARGABARANG, 0)) AS TOTALNOMINALBULANINI,
                       SUM(IF(DATE(B.INPUTTANGGALWAKTU) BETWEEN '{$tglAwalBulanTahun}' AND '{$tglAkhirBulanTahun}', 1, 0)) AS TOTALTRANSAKSIBULANINI,
                       SUM(IF(DATE(B.INPUTTANGGALWAKTU) BETWEEN '{$tglAwalBulanTahun}' AND '{$tglAkhirBulanTahun}', A.JUMLAH, 0)) AS TOTALITEMBULANINI,
                       SUM(IF(DATE(B.INPUTTANGGALWAKTU) BETWEEN '{$tglAwalBulanLalu}' AND '{$tglAkhirBulanLalu}', B.TOTALHARGABARANG, 0)) AS TOTALNOMINALBULANLALU,
                       SUM(IF(DATE(B.INPUTTANGGALWAKTU) BETWEEN '{$tglAwalBulanLalu}' AND '{$tglAkhirBulanLalu}', 1, 0)) AS TOTALTRANSAKSIBULANLALU,
                       SUM(IF(DATE(B.INPUTTANGGALWAKTU) BETWEEN '{$tglAwalBulanLalu}' AND '{$tglAkhirBulanLalu}', A.JUMLAH, 0)) AS TOTALITEMBULANLALU");
        $this->from('t_penjualanbarang AS A', true);
        $this->join('t_penjualanrekap B', 'A.IDPENJUALANREKAP = B.IDPENJUALANREKAP', 'LEFT');
        $this->where('B.IDTOKO', $idToko);
        $this->groupBy('B.IDTOKO');

        $result     =   $this->get()->getRowArray();
        if(is_null($result)) return [
            'TOTALNOMINALHARIINI'       =>  0,
            'TOTALTRANSAKSIHARIINI'     =>  0,
            'TOTALITEMHARIINI'          =>  0,
            'TOTALNOMINALBULANINI'      =>  0,
            'TOTALTRANSAKSIBULANINI'    =>  0,
            'TOTALITEMBULANINI'         =>  0,
            'TOTALNOMINALBULANLALU'     =>  0,
            'TOTALTRANSAKSIBULANLALU'   =>  0,
            'TOTALITEMBULANLALU'        =>  0
        ];
        return $result;
	}
    
    public function getDataRerataPenjualan($idToko, $tglAkhirBulanLalu)
    {	
        $this->select("COUNT(DISTINCT(DATE(B.INPUTTANGGALWAKTU))) AS TOTALHARITRANSAKSI, COUNT(DISTINCT(LEFT(B.INPUTTANGGALWAKTU, 7))) AS TOTALBULANTRANSAKSI,
                       IFNULL(SUM(B.TOTALHARGABARANG), 0) AS TOTALNOMINALHARGABARANG");
        $this->from('t_penjualanbarang AS A', true);
        $this->join('t_penjualanrekap B', 'A.IDPENJUALANREKAP = B.IDPENJUALANREKAP', 'LEFT');
        $this->where('B.IDTOKO', $idToko);
        $this->where('B.INPUTTANGGALWAKTU <= ', $tglAkhirBulanLalu);
        $this->groupBy('B.IDTOKO');

        $result     =   $this->get()->getRowArray();
        if(is_null($result)) return [
            'TOTALHARITRANSAKSI'        =>  0,
            'TOTALBULANTRANSAKSI'       =>  0,
            'TOTALNOMINALHARGABARANG'   =>  0
        ];
        return $result;
	}
    
    public function getDataRekapPenjualanBulanan($idToko, $tglAwalPeriode, $tglAkhirPeriode)
    {	
        $this->select("LEFT(B.INPUTTANGGALWAKTU, 7) AS BULANTAHUN, IFNULL(SUM(B.TOTALHARGABARANG), 0) AS TOTALNOMINAL,
                       COUNT(DISTINCT(A.IDPENJUALANREKAP)) AS TOTALTRANSAKSI, IFNULL(SUM(A.JUMLAH), 0) AS TOTALITEM");
        $this->from('t_penjualanbarang AS A', true);
        $this->join('t_penjualanrekap B', 'A.IDPENJUALANREKAP = B.IDPENJUALANREKAP', 'LEFT');
        $this->where('B.IDTOKO', $idToko);
        $this->where('B.INPUTTANGGALWAKTU >= ', $tglAwalPeriode);
        $this->where('B.INPUTTANGGALWAKTU <= ', $tglAkhirPeriode);
        $this->groupBy('LEFT(B.INPUTTANGGALWAKTU, 7)');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}

    public function getDataStokBarangToko($idToko, $tanggalAkhir, $orderStokTipe)
    {	
        $this->select("IFNULL(C.FOTOBARANG, '') AS FOTOBARANG, CONCAT(D.NAMAMERK, ' - ', C.NAMABARANG) AS NAMABARANG, E.NAMAKATEGORI, COUNT(DISTINCT(A.IDBARANGSKU)) AS JUMLAHBARANGSKU,
                    F.NAMASATUAN, IFNULL(SUM(A.JUMLAHMASUK - A.JUMLAHKELUAR), 0) AS TOTALSTOKBARANG");
        $this->from('t_tokostok AS A', true);
        $this->join('m_barangsku B', 'A.IDBARANGSKU = B.IDBARANGSKU AND A.IDBARANGSATUAN = B.IDBARANGSATUAN', 'LEFT');
        $this->join('m_barang C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangmerk D', 'C.IDBARANGMERK = D.IDBARANGMERK', 'LEFT');
        $this->join('m_barangkategori E', 'C.IDBARANGKATEGORI = E.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangsatuan F', 'A.IDBARANGSATUAN = F.IDBARANGSATUAN', 'LEFT');
        $this->where('A.IDTOKO', $idToko);
        $this->where('A.INPUTTANGGALWAKTU <= ', $tanggalAkhir);
        $this->groupBy('C.IDBARANG');

        switch($orderStokTipe){
            case 'SB'   : 
                $this->having('TOTALSTOKBARANG >', 0);
                $this->orderBy('TOTALSTOKBARANG', 'DESC');
                break;
            case 'SS'   : 
                $this->having('TOTALSTOKBARANG >', 0);
                $this->orderBy('TOTALSTOKBARANG', 'ASC');
                break;
            case 'S0'   :
                $this->having('TOTALSTOKBARANG =', 0);
                break;
            default     :
                $this->orderBy('TOTALSTOKBARANG', 'DESC');
        }
        $this->limit(25);

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}

    public function getDataMutasiTerbanyak($idToko, $tanggalAwal, $tanggalAkhir)
    {	
        $this->select("IFNULL(C.FOTOBARANG, '') AS FOTOBARANG, CONCAT(D.NAMAMERK, ' - ', C.NAMABARANG) AS NAMABARANG, E.NAMAKATEGORI, COUNT(DISTINCT(A.IDBARANGSKU)) AS JUMLAHBARANGSKU,
                    F.NAMASATUAN, SUM(A.JUMLAHMASUK) AS TOTALMUTASIMASUK, SUM(A.JUMLAHKELUAR) AS TOTALMUTASIKELUAR, SUM(A.JUMLAHMASUK + A.JUMLAHKELUAR) AS TOTALMUTASI");
        $this->from('t_tokostok AS A', true);
        $this->join('m_barangsku B', 'A.IDBARANGSKU = B.IDBARANGSKU AND A.IDBARANGSATUAN = B.IDBARANGSATUAN', 'LEFT');
        $this->join('m_barang C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangmerk D', 'C.IDBARANGMERK = D.IDBARANGMERK', 'LEFT');
        $this->join('m_barangkategori E', 'C.IDBARANGKATEGORI = E.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangsatuan F', 'A.IDBARANGSATUAN = F.IDBARANGSATUAN', 'LEFT');
        $this->where('A.IDTOKO', $idToko);
        $this->where('A.INPUTTANGGALWAKTU >= ', $tanggalAwal);
        $this->where('A.INPUTTANGGALWAKTU <= ', $tanggalAkhir);
        $this->groupBy('C.IDBARANG');
        $this->orderBy('TOTALMUTASI', 'DESC');
        $this->orderBy('NAMAKATEGORI', 'ASC');
        $this->orderBy('NAMABARANG', 'ASC');
        $this->limit(10);

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function getDataStokBarangKategori($idToko, $tglAkhirBulanTahun)
    {	
        $this->select("D.NAMAKATEGORI, SUM(A.JUMLAHMASUK - A.JUMLAHKELUAR) AS TOTALSTOK");
        $this->from('t_tokostok AS A', true);
        $this->join('m_barangsku B', 'A.IDBARANGSKU = B.IDBARANGSKU AND A.IDBARANGSATUAN = B.IDBARANGSATUAN', 'LEFT');
        $this->join('m_barang C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangkategori D', 'C.IDBARANGKATEGORI = D.IDBARANGKATEGORI', 'LEFT');
        $this->where('A.IDTOKO', $idToko);
        $this->where('A.INPUTTANGGALWAKTU <= ', $tglAkhirBulanTahun);
        $this->groupBy('C.IDBARANGKATEGORI');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}
}