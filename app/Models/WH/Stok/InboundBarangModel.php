<?php

namespace App\Models\WH\Stok;
use CodeIgniter\Model;

class InboundBarangModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_notapembelianinbound';
    protected $primaryKey       = 'IDNOTAPEMBELIANINBOUND';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDNOTAPEMBELIANBARANG', 'IDGUDANG', 'INBOUNDJATAH', 'INBOUNDJUMLAH', 'PROSESUSER', 'PROSESTANGGALWAKTU'];

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
    
    public function getListNotaAktif($idGudang)
    {	
        $this->select("C.IDNOTAPEMBELIANREKAP, C.NOTAPEMBELIANNOMOR");
        $this->from('t_notapembelianinbound A', true);
        $this->join('t_notapembelianbarang AS B', 'A.IDNOTAPEMBELIANBARANG = B.IDNOTAPEMBELIANBARANG', 'LEFT');
        $this->join('t_notapembelianrekap AS C', 'B.IDNOTAPEMBELIANREKAP = C.IDNOTAPEMBELIANREKAP', 'LEFT');
        $this->where('A.IDGUDANG', $idGudang);
        $this->where('A.INBOUNDJATAH > A.INBOUNDJUMLAH');
        $this->groupBy('C.IDNOTAPEMBELIANREKAP');
        $this->orderBy('C.INPUTTANGGALWAKTU', 'DESC');

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function getDetailNotaPembelianRekap($idNotaPembelianRekap)
    {	
        $this->select("A.NOTAPEMBELIANNOMOR, A.TOTALJENISBARANG, A.TOTALSKU, DATE_FORMAT(A.INPUTTANGGALWAKTU, '%d %b %Y') AS INPUTTANGGAL,
                    B.NAMA AS NAMAPRODUSENDISTRIBUTOR, A.PERSENPENYELESAIANINBOUND, A.KETERANGAN, A.INPUTUSER");
        $this->from('t_notapembelianrekap A', true);
        $this->join('m_produsendistributor AS B', 'A.IDPRODUSENDISTRIBUTOR = B.IDPRODUSENDISTRIBUTOR', 'LEFT');
        $this->where('A.IDNOTAPEMBELIANREKAP', $idNotaPembelianRekap);
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function getDataBarangSKUNotaPembelian($idNotaPembelianRekap, $idGudang)
    {	
        $this->select("A.IDNOTAPEMBELIANINBOUND, B.IDBARANGSKU, D.NAMAKATEGORI, E.NAMAMERK, C.NAMABARANG, F.KODESKU, F.DESKRIPSI AS DESKRIPSISKU, '[]' AS ATRIBUTSKUSTR,
                    A.INBOUNDJUMLAH, G.KODESATUAN, B.JUMLAH AS JATAHJUMLAH, A.PROSESALLOW");
        $this->from('t_notapembelianinbound A', true);
        $this->join('t_notapembelianbarang AS B', 'A.IDNOTAPEMBELIANBARANG = B.IDNOTAPEMBELIANBARANG', 'LEFT');
        $this->join('m_barang AS C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS D', 'C.IDBARANGKATEGORI = D.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS E', 'C.IDBARANGMERK = E.IDBARANGMERK', 'LEFT');
        $this->join('m_barangsku AS F', 'B.IDBARANGSKU = F.IDBARANGSKU', 'LEFT');
        $this->join('m_barangsatuan AS G', 'B.IDBARANGSATUAN = G.IDBARANGSATUAN', 'LEFT');
        $this->where('A.IDGUDANG', $idGudang);
        $this->where('B.IDNOTAPEMBELIANREKAP', $idNotaPembelianRekap);
        
        $result =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function getDetailNotaPembelianInbound($idNotaPembelianInbound)
    {	
        $this->select("B.IDNOTAPEMBELIANREKAP, A.IDNOTAPEMBELIANBARANG, B.IDBARANG, B.IDBARANGSKU, B.IDBARANGSATUAN, C.NOTAPEMBELIANNOMOR,
                    DATE_FORMAT(C.INPUTTANGGALWAKTU, '%d %b %Y') AS TANGGALNOTA, A.INBOUNDJATAH, A.PROSESKE, A.PROSESALLOW");
        $this->from('t_notapembelianinbound AS A', true);
        $this->join('t_notapembelianbarang AS B', 'A.IDNOTAPEMBELIANBARANG = B.IDNOTAPEMBELIANBARANG', 'LEFT');
        $this->join('t_notapembelianrekap AS C', 'B.IDNOTAPEMBELIANREKAP = C.IDNOTAPEMBELIANREKAP', 'LEFT');
        $this->where('A.IDNOTAPEMBELIANINBOUND', $idNotaPembelianInbound);
        $this->limit(1);
        
        $result =   $this->get()->getRowArray();
        if(is_null($result)) return false;
        return $result;
	}

    public function getDataPenyelesaianInbound($idNotaPembelianRekap)
    {
        $this->select("SUM(A.JUMLAH) AS JUMLAHPEMBELIAN, IFNULL(SUM(B.INBOUNDJUMLAH), 0) AS JUMLAHINBOUND");
        $this->from('t_notapembelianbarang AS A', true);
        $this->join('t_notapembelianinbound AS B', 'A.IDNOTAPEMBELIANBARANG = B.IDNOTAPEMBELIANBARANG', 'LEFT');
        $this->where('A.IDNOTAPEMBELIANREKAP', $idNotaPembelianRekap);
        $this->groupBy('A.IDNOTAPEMBELIANREKAP');
        $this->limit(1);
        
        $result =   $this->get()->getRowArray();
        if(is_null($result)) return [
            'JUMLAHPEMBELIAN'   =>  0,
            'JUMLAHINBOUND'     =>  0
        ];
        return $result;
    }
    
    public function getDataNotaHistori($idGudang, $searchKeyword)
    {	
        $this->select("C.IDNOTAPEMBELIANREKAP, C.NOTAPEMBELIANNOMOR, C.TOTALJENISBARANG, C.TOTALSKU, DATE_FORMAT(C.INPUTTANGGALWAKTU, '%d %b %Y') AS INPUTTANGGAL,
                    D.NAMA AS NAMAPRODUSENDISTRIBUTOR");
        $this->from('t_notapembelianinbound A', true);
        $this->join('t_notapembelianbarang AS B', 'A.IDNOTAPEMBELIANBARANG = B.IDNOTAPEMBELIANBARANG', 'LEFT');
        $this->join('t_notapembelianrekap AS C', 'B.IDNOTAPEMBELIANREKAP = C.IDNOTAPEMBELIANREKAP', 'LEFT');
        $this->join('m_produsendistributor AS D', 'C.IDPRODUSENDISTRIBUTOR = D.IDPRODUSENDISTRIBUTOR', 'LEFT');
        $this->where('A.IDGUDANG', $idGudang);
        $this->where('A.INBOUNDJATAH <= A.INBOUNDJUMLAH');

        if(isset($searchKeyword) && !is_null($searchKeyword)){
            $this->groupStart();
            $this->like('C.NOTAPEMBELIANNOMOR', $searchKeyword, 'both')
            ->orLike('D.NAMA', $searchKeyword, 'both');
            $this->groupEnd();
        }

        $this->groupBy('C.IDNOTAPEMBELIANREKAP');
        $this->orderBy('C.INPUTTANGGALWAKTU', 'DESC');

        return $this;
	}
}