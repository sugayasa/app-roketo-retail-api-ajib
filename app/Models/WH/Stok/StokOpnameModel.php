<?php

namespace App\Models\WH\Stok;
use CodeIgniter\Model;

class StokOpnameModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_stokopnamerekap';
    protected $primaryKey       = 'IDSTOKOPNAMEREKAP';
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
    
    public function getDaftarStokOpnameRekap($idGudang, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian, $tampilBelumProses)
    {	
        $this->select("IDSTOKOPNAMEREKAP, NOTAOPNAMENOMOR, JUMLAHBARANGSKU, KETERANGANOPNAME, INPUTUSER,
                    DATE_FORMAT(INPUTTANGGALWAKTU, '%d %b %Y %H:%i') AS INPUTTANGGALWAKTU, STATUS, '-' AS STATUSSTR,
                    '[]' AS DATABARANGSKU");
        $this->from('t_stokopnamerekap', true);
        $this->where('TIPEGUDANGTOKO', 'G');
        $this->where('IDGUDANG', $idGudang);

        if(intval($tampilBelumProses) == 1){
            $this->where('STATUS', 0);
        } else {
            $this->where('DATE(INPUTTANGGALWAKTU) >= ', $tanggalAwal);
            $this->where('DATE(INPUTTANGGALWAKTU) <=', $tanggalAkhir);
            
            if(isset($kataKunciPencarian) && !is_null($kataKunciPencarian) && $kataKunciPencarian != ''){
                $this->groupStart();
                $this->like('NOTAOPNAMENOMOR', $kataKunciPencarian, 'both')
                ->orLike('KETERANGANOPNAME', $kataKunciPencarian, 'both')
                ->orLike('INPUTUSER', $kataKunciPencarian, 'both');
                $this->groupEnd();
            }
        }
        $this->orderBy('INPUTTANGGALWAKTU DESC');

        return $this;
	}

    public function getDaftarStokOpnameBarang($idStokOpnameRekap)
    {	
        $this->select("A.IDSTOKOPNAMEBARANG, E.NAMAKATEGORI, F.NAMAMERK, C.NAMABARANG, CONCAT(C.KODEBARANG, '-', B.KODESKU) AS KODESKU,
                    B.DESKRIPSI AS DESKRIPSISKU, G.NAMASATUAN, A.STOKDATA, A.STOKFISIK, A.STOKSELISIH, A.KETERANGAN, A.OPNAMESTATUS");
        $this->from('t_stokopnamebarang AS A', true);
        $this->join('m_barangsku B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->join('m_barang C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangkategori E', 'C.IDBARANGKATEGORI = E.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk F', 'C.IDBARANGMERK = F.IDBARANGMERK', 'LEFT');
        $this->join('m_barangsatuan G', 'A.IDBARANGSATUAN = G.IDBARANGSATUAN', 'LEFT');
        $this->where('A.IDSTOKOPNAMEREKAP', $idStokOpnameRekap);
        $this->orderBy('E.NAMAKATEGORI, F.NAMAMERK, C.NAMABARANG');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}
    
    public function getDetailStokBarangOpname($idGudang, $idStokOpnameRekap, $idStokOpnameBarang)
    {	
        $this->select("C.IDBARANG, A.IDBARANGSKU, A.IDBARANGSATUAN, A.OPNAMESTATUS, CONCAT(D.KODEBARANG, '-', C.KODESKU) AS KODESKU, C.DESKRIPSI AS DESKRIPSISKU, E.NAMASATUAN");
        $this->from('t_stokopnamebarang A', true);
        $this->join('t_stokopnamerekap B', 'A.IDSTOKOPNAMEREKAP = B.IDSTOKOPNAMEREKAP', 'LEFT');
        $this->join('m_barangsku C', 'A.IDBARANGSKU = C.IDBARANGSKU', 'LEFT');
        $this->join('m_barang D', 'C.IDBARANG = D.IDBARANG', 'LEFT');
        $this->join('m_barangsatuan E', 'A.IDBARANGSATUAN = E.IDBARANGSATUAN', 'LEFT');
        $this->where('B.IDGUDANG', $idGudang);
        $this->where('A.IDSTOKOPNAMEREKAP', $idStokOpnameRekap);
        $this->where('A.IDSTOKOPNAMEBARANG', $idStokOpnameBarang);
        $this->limit(1);
        
        $result =   $this->get()->getRowArray();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function getJumlahStokDataBarang($idGudang, $idBarangSKU, $idBarangSatuan)
    {	
        $this->select("IFNULL(SUM(JUMLAHMASUK - JUMLAHKELUAR), 0) AS STOKBARANG");
        $this->from('t_gudangstok', true);
        $this->where('IDGUDANG', $idGudang);
        $this->where('IDBARANGSKU', $idBarangSKU);
        $this->where('IDBARANGSATUAN', $idBarangSatuan);
        $this->limit(1);
        
        $result =   $this->get()->getRowArray();
        if(is_null($result)) return 0;
        return $result['STOKBARANG'] ?? 0;
	}
    
    public function isStokOpnameFinish($idStokOpnameRekap)
    {	
        $this->select("IFNULL(COUNT(IDSTOKOPNAMEBARANG), 0) AS JUMLAHOPNAMEBELUMPROSES");
        $this->from('t_stokopnamebarang', true);
        $this->where('IDSTOKOPNAMEREKAP', $idStokOpnameRekap);
        $this->where('OPNAMESTATUS', 0);
        $this->limit(1);
        
        $result =   $this->get()->getRowArray();
        if(is_null($result)) return true;
        return $result['JUMLAHOPNAMEBELUMPROSES'] > 0 ? false : true;
	}
}