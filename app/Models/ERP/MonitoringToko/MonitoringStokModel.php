<?php

namespace App\Models\ERP\MonitoringToko;
use CodeIgniter\Model;

class MonitoringStokModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_tokostok';
    protected $primaryKey       = 'IDTOKOSTOK';
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

    public function getRekapMutasiBarangPerTanggal($idToko, $tahunBulan)
    {	
        $this->select("DATE_FORMAT(INPUTTANGGALWAKTU, '%d') AS TANGGAL, IFNULL(SUM(JUMLAHMASUK), 0) AS JUMLAHMASUK, IFNULL(SUM(JUMLAHKELUAR), 0) AS JUMLAHKELUAR");
        $this->from('t_tokostok', true);
        $this->where('LEFT(INPUTTANGGALWAKTU, 7) =', $tahunBulan);
        $this->where('IDTOKO', $idToko);
        $this->groupBy('DATE(INPUTTANGGALWAKTU)');
        $this->orderBy('TANGGAL');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}
    
    public function getHistoriMutasiBarang($idToko, $tahunBulan)
    {	
        $this->select("A.IDBARANGSKU, DATE_FORMAT(A.INPUTTANGGALWAKTU, '%d %b %Y') AS TANGGALMUTASI, DATE_FORMAT(A.INPUTTANGGALWAKTU, '%H:%i') AS WAKTUMUTASI,
                    B.KODESKU, B.DESKRIPSI AS DESKRIPSISKU, '[]' AS ATRIBUTSKUSTR, A.MUTASIKETERANGAN AS KETERANGAN, C.KODESATUAN AS SATUAN, A.JUMLAHMASUK AS MASUK,
                    A.JUMLAHKELUAR AS KELUAR");
        $this->from('t_tokostok A', true);
        $this->join('m_barangsku B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->join('m_barangsatuan C', 'A.IDBARANGSATUAN = C.IDBARANGSATUAN', 'LEFT');
        $this->where('A.IDTOKO', $idToko);
        $this->where('LEFT(A.INPUTTANGGALWAKTU, 7)', $tahunBulan);
        $this->orderBy('A.INPUTTANGGALWAKTU ASC');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
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

    public function getDataStokMutasiBarangToko($idToko, $tanggalAwal, $tanggalAkhir, $orderTipe)
    {	
        $this->select("E.NAMAKATEGORI, D.NAMAMERK, C.NAMABARANG, COUNT(DISTINCT(A.IDBARANGSKU)) AS JUMLAHBARANGSKU,
                    SUM(IF(A.INPUTTANGGALWAKTU <= '".$tanggalAwal."', (A.JUMLAHMASUK - A.JUMLAHKELUAR), 0)) AS STOKAWAL,
                    SUM(IF(A.INPUTTANGGALWAKTU BETWEEN  '".$tanggalAwal."' AND '".$tanggalAkhir."', A.JUMLAHMASUK, 0)) AS TOTALMASUK,
                    SUM(IF(A.INPUTTANGGALWAKTU BETWEEN  '".$tanggalAwal."' AND '".$tanggalAkhir."', A.JUMLAHKELUAR, 0)) AS TOTALKELUAR,
                    SUM(A.JUMLAHMASUK - A.JUMLAHKELUAR) AS STOKAKHIR");
        $this->from('t_tokostok AS A', true);
        $this->join('m_barangsku B', 'A.IDBARANGSKU = B.IDBARANGSKU AND A.IDBARANGSATUAN = B.IDBARANGSATUAN', 'LEFT');
        $this->join('m_barang C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangmerk D', 'C.IDBARANGMERK = D.IDBARANGMERK', 'LEFT');
        $this->join('m_barangkategori E', 'C.IDBARANGKATEGORI = E.IDBARANGKATEGORI', 'LEFT');
        $this->where('A.IDTOKO', $idToko);
        $this->where('A.INPUTTANGGALWAKTU <= ', $tanggalAkhir);
        $this->groupBy('C.IDBARANG');
        
        switch($orderTipe){
            case 'SAWB': // Stok Awal Barang Banyak
                $this->orderBy('STOKAWAL DESC');
                break;
            case 'SAWS': // Stok Awal Barang Sedikit
                $this->orderBy('STOKAWAL ASC');
                break;
            case 'SAKB': // Stok Akhir Barang Banyak
                $this->orderBy('STOKAKHIR DESC');
                break;
            case 'SAKS': // Stok Akhir Barang Sedikit
                $this->orderBy('STOKAKHIR ASC');
                break;
            case 'MMB': // Mutasi Masuk Banyak
                $this->orderBy('TOTALMASUK DESC');
                break;
            case 'MMS': // Mutasi Masuk Sedikit
                $this->orderBy('TOTALMASUK ASC');
                break;
            case 'MKB': // Mutasi Keluar Banyak
                $this->orderBy('TOTALKELUAR DESC');
                break;
            case 'MKS': // Mutasi Keluar Sedikit
                $this->orderBy('TOTALKELUAR ASC');
                break;
            default: break;
        }

        $this->orderBy('E.NAMAKATEGORI ASC, D.NAMAMERK ASC, C.NAMABARANG ASC');
        $this->limit(50);

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}
}