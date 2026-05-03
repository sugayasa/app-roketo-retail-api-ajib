<?php

namespace App\Models\WH;
use CodeIgniter\Model;

class DashboardModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_tokonotamutasirekap';
    protected $primaryKey       = 'IDTOKONOTAMUTASIREKAP';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDTOKONOTAMUTASIREKAP'];

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
    
    public function getDataMutasiPerTanggal($idGudang, $tglAwalBulanTahun, $tglAkhirBulanTahun)
    {	
        $this->select("DATE_FORMAT(B.PROSESTANGGALWAKTU, '%d') AS TANGGAL, DATE_FORMAT(B.PROSESTANGGALWAKTU, '%d %b') AS TANGGALDM, E.NAMAKATEGORI,
                    COUNT(DISTINCT(A.IDTOKONOTAMUTASIREKAP)) AS JUMLAHTRANSAKSI, IFNULL(SUM(A.JUMLAH), 0) AS JUMLAHITEM, SUM(A.JUMLAH * A.HARGAGROSIR) AS TOTALPENJUALANGROSIR");
        $this->from('t_tokonotamutasibarang AS A', true);
        $this->join('t_tokonotamutasirekap B', 'A.IDTOKONOTAMUTASIREKAP = B.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $this->join('m_barangsku C', 'A.IDBARANGSKU = C.IDBARANGSKU', 'LEFT');
        $this->join('m_barang D', 'A.IDBARANG = D.IDBARANG', 'LEFT');
        $this->join('m_barangkategori E', 'D.IDBARANGKATEGORI = E.IDBARANGKATEGORI', 'LEFT');
        $this->where('DATE(B.PROSESTANGGALWAKTU) >= ', $tglAwalBulanTahun);
        $this->where('DATE(B.PROSESTANGGALWAKTU) <=', $tglAkhirBulanTahun);
        $this->where('B.IDGUDANG', $idGudang);
        $this->where('E.STATUS =', 1);
        $this->groupBy('DATE(B.PROSESTANGGALWAKTU), E.NAMAKATEGORI');
        $this->orderBy('TANGGAL');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}
    
    public function getDataRekapMutasiPeriode($idGudang, $tglHariIni, $tglAwalBulanTahun, $tglAkhirBulanTahun, $tglAwalBulanLalu, $tglAkhirBulanLalu)
    {	
        $this->select("SUM(IF(DATE(B.PROSESTANGGALWAKTU) = '{$tglHariIni}', B.TOTALNOMINALBARANG, 0)) AS TOTALNOMINALHARIINI,
                       SUM(IF(DATE(B.PROSESTANGGALWAKTU) = '{$tglHariIni}', 1, 0)) AS TOTALTRANSAKSIHARIINI,
                       SUM(IF(DATE(B.PROSESTANGGALWAKTU) = '{$tglHariIni}', A.JUMLAH, 0)) AS TOTALITEMHARIINI,
                       SUM(IF(DATE(B.PROSESTANGGALWAKTU) BETWEEN '{$tglAwalBulanTahun}' AND '{$tglAkhirBulanTahun}', B.TOTALNOMINALBARANG, 0)) AS TOTALNOMINALBULANINI,
                       SUM(IF(DATE(B.PROSESTANGGALWAKTU) BETWEEN '{$tglAwalBulanTahun}' AND '{$tglAkhirBulanTahun}', 1, 0)) AS TOTALTRANSAKSIBULANINI,
                       SUM(IF(DATE(B.PROSESTANGGALWAKTU) BETWEEN '{$tglAwalBulanTahun}' AND '{$tglAkhirBulanTahun}', A.JUMLAH, 0)) AS TOTALITEMBULANINI,
                       SUM(IF(DATE(B.PROSESTANGGALWAKTU) BETWEEN '{$tglAwalBulanLalu}' AND '{$tglAkhirBulanLalu}', B.TOTALNOMINALBARANG, 0)) AS TOTALNOMINALBULANLALU,
                       SUM(IF(DATE(B.PROSESTANGGALWAKTU) BETWEEN '{$tglAwalBulanLalu}' AND '{$tglAkhirBulanLalu}', 1, 0)) AS TOTALTRANSAKSIBULANLALU,
                       SUM(IF(DATE(B.PROSESTANGGALWAKTU) BETWEEN '{$tglAwalBulanLalu}' AND '{$tglAkhirBulanLalu}', A.JUMLAH, 0)) AS TOTALITEMBULANLALU");
        $this->from('t_tokonotamutasibarang AS A', true);
        $this->join('t_tokonotamutasirekap B', 'A.IDTOKONOTAMUTASIREKAP = B.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $this->where('B.IDGUDANG', $idGudang);
        $this->groupBy('B.IDGUDANG');

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
    
    public function getDataRerataMutasi($idGudang, $tglAkhirBulanLalu)
    {	
        $this->select("COUNT(DISTINCT(DATE(B.PROSESTANGGALWAKTU))) AS TOTALHARITRANSAKSI, COUNT(DISTINCT(LEFT(B.PROSESTANGGALWAKTU, 7))) AS TOTALBULANTRANSAKSI,
                       IFNULL(SUM(B.TOTALNOMINALBARANG), 0) AS TOTALNOMINALHARGABARANG");
        $this->from('t_tokonotamutasibarang AS A', true);
        $this->join('t_tokonotamutasirekap B', 'A.IDTOKONOTAMUTASIREKAP = B.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $this->where('B.IDGUDANG', $idGudang);
        $this->where('B.PROSESTANGGALWAKTU <= ', $tglAkhirBulanLalu);
        $this->groupBy('B.IDGUDANG');

        $result     =   $this->get()->getRowArray();
        if(is_null($result)) return [
            'TOTALHARITRANSAKSI'        =>  0,
            'TOTALBULANTRANSAKSI'       =>  0,
            'TOTALNOMINALHARGABARANG'   =>  0
        ];
        return $result;
	}
    
    public function getDataRekapMutasiBulanan($idGudang, $tglAwalPeriode, $tglAkhirPeriode)
    {	
        $this->select("LEFT(B.PROSESTANGGALWAKTU, 7) AS BULANTAHUN, IFNULL(SUM(B.TOTALNOMINALBARANG), 0) AS TOTALNOMINAL,
                       COUNT(DISTINCT(A.IDTOKONOTAMUTASIREKAP)) AS TOTALTRANSAKSI, IFNULL(SUM(A.JUMLAH), 0) AS TOTALITEM");
        $this->from('t_tokonotamutasibarang AS A', true);
        $this->join('t_tokonotamutasirekap B', 'A.IDTOKONOTAMUTASIREKAP = B.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $this->where('B.IDGUDANG', $idGudang);
        $this->where('B.PROSESTANGGALWAKTU >= ', $tglAwalPeriode);
        $this->where('B.PROSESTANGGALWAKTU <= ', $tglAkhirPeriode);
        $this->groupBy('LEFT(B.PROSESTANGGALWAKTU, 7)');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function getDataHistoriNota($idGudang, $limit)
    {	
        $this->select("A.NOTAMUTASINOMOR, B.NAMA AS NAMATOKO, A.PROSESUSER, DATE_FORMAT(A.PROSESTANGGALWAKTU, '%d %b %Y %H:%i') AS PROSESTANGGALWAKTU,
                    A.TOTALSKU, A.TOTALNOMINALBARANG");
        $this->from('t_tokonotamutasirekap AS A', true);
        $this->join('m_toko AS B', 'A.IDTOKO = B.IDTOKO', 'LEFT');
        $this->where('A.IDGUDANG', $idGudang);
        $this->where('A.STATUS', 1);
        $this->orderBy('A.PROSESTANGGALWAKTU DESC');
        $this->limit($limit);

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}

    public function getDataHistoriMutasiBarang($idGudang)
    {	
        $this->select("A.IDBARANGSKU, E.NAMAKATEGORI, D.NAMAMERK, C.NAMABARANG, B.KODESKU, '[]' AS ATRIBUTSKU, B.DESKRIPSI AS DESKRIPSISKU, F.NAMASATUAN,
                    IF(A.JUMLAHMASUK > 0 , A.JUMLAHMASUK, A.JUMLAHKELUAR * -1) AS JUMLAHMUTASI, A.MUTASIKETERANGAN, A.INPUTUSER,
                    DATE_FORMAT(A.INPUTTANGGALWAKTU, '%d %b %Y %H:%i') AS TANGGALWAKTUMUTASI, IFNULL(B.FOTOBARANGSKU, '') AS FOTOBARANGSKU");
        $this->from('t_gudangstok A', true);
        $this->join('m_barangsku B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->join('m_barang C', 'A.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangmerk D', 'C.IDBARANGMERK = D.IDBARANGMERK', 'LEFT');
        $this->join('m_barangkategori E', 'C.IDBARANGKATEGORI = E.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangsatuan F', 'A.IDBARANGSATUAN = F.IDBARANGSATUAN', 'LEFT');
        $this->where('A.IDGUDANG', $idGudang);
        $this->orderBy('A.INPUTTANGGALWAKTU DESC');
        $this->limit(10);

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}
}