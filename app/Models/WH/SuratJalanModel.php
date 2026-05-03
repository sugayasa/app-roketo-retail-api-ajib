<?php

namespace App\Models\WH;
use CodeIgniter\Model;

class SuratJalanModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_suratjalanrekap';
    protected $primaryKey       = 'IDSURATJALANREKAP';
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
    
    public function getDaftarSuratJalan($idGudang, $kataKunciPencarian)
    {	
        $this->select("IDSURATJALANREKAP, NOMORREKAPSURAT, NAMAPENGIRIM, JUMLAHNOTAMUTASI, CATATAN, DATE_FORMAT(INPUTTANGGALWAKTU, '%d %b %Y') AS INPUTTANGGALWAKTUSTR");
        $this->from('t_suratjalanrekap', true);
        $this->where('IDGUDANG', $idGudang);

        if(isset($kataKunciPencarian) && !is_null($kataKunciPencarian) && $kataKunciPencarian != ''){
            $this->groupStart();
            $this->like('NOMORREKAPSURAT', $kataKunciPencarian, 'both')
            ->orLike('NAMAPENGIRIM', $kataKunciPencarian, 'both')
            ->orLike('CATATAN', $kataKunciPencarian, 'both')
            ->orLike('INPUTUSER', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }

        $this->orderBy('INPUTTANGGALWAKTU DESC');
        return $this;
	}
    
    public function getDaftarNotaMutasiRekap($idSuratJalanRekap)
    {	
        $this->select("A.IDTOKONOTAMUTASIREKAP, C.NAMA AS NAMATOKO, C.ALAMAT, B.NOTAMUTASINOMOR, B.TOTALSKU, B.KETERANGAN, B.PROSESKETERANGAN,
                    DATE_FORMAT(B.PROSESTANGGALWAKTU, '%d %b %Y %H:%i') AS PROSESTANGGALWAKTU");
        $this->from('t_suratjalandetail A', true);
        $this->join('t_tokonotamutasirekap AS B', 'A.IDTOKONOTAMUTASIREKAP = B.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $this->join('m_toko AS C', 'B.IDTOKO = C.IDTOKO', 'LEFT');
        $this->where('A.IDSURATJALANREKAP', $idSuratJalanRekap);
        $this->orderBy('A.URUTANSURAT', 'DESC');

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function getDaftarNotaMutasiRekapNonSuratJalan($idGudang)
    {	
        $this->select("A.IDTOKONOTAMUTASIREKAP, B.NAMA AS NAMATOKO, B.ALAMAT, A.NOTAMUTASINOMOR, A.TOTALSKU, A.KETERANGAN, A.PROSESKETERANGAN,
                    DATE_FORMAT(A.PROSESTANGGALWAKTU, '%d %b %Y %H:%i') AS PROSESTANGGALWAKTU");
        $this->from('t_tokonotamutasirekap A', true);
        $this->join('m_toko AS B', 'A.IDTOKO = B.IDTOKO', 'LEFT');
        $this->join('t_suratjalandetail AS C', 'A.IDTOKONOTAMUTASIREKAP = C.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $this->where('A.IDGUDANG', $idGudang);
        $this->where('A.STATUS', 1);
        $this->where('C.IDSURATJALANREKAP', NULL);
        $this->orderBy('A.PROSESTANGGALWAKTU', 'DESC');

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function getDataBarangSKUNotaMutasi($idTokoNotaMutasiRekap)
    {	
        $this->select("A.IDBARANGSKU, D.NAMAKATEGORI, E.NAMAMERK, C.NAMABARANG, B.KODESKU, B.DESKRIPSI AS DESKRIPSISKU, '[]' AS ATRIBUTSKUSTR, A.JUMLAH");
        $this->from('t_tokonotamutasibarang A', true);
        $this->join('m_barangsku AS B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->join('m_barang AS C', 'A.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS D', 'C.IDBARANGKATEGORI = D.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS E', 'C.IDBARANGMERK = E.IDBARANGMERK', 'LEFT');
        $this->where('A.IDTOKONOTAMUTASIREKAP', $idTokoNotaMutasiRekap);
        $this->orderBy('D.NAMAKATEGORI, E.NAMAMERK, C.NAMABARANG, B.KODESKU');
        
        $result =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function isNotaMutasiRekapValidForSuratJalan($idTokoNotaMutasiRekap)
    {	
        $this->select("B.NOTAMUTASINOMOR, C.NOMORREKAPSURAT, DATE_FORMAT(C.INPUTTANGGALWAKTU, '%d %b %Y %H:%i') AS INPUTTANGGALWAKTU");
        $this->from('t_suratjalandetail A', true);
        $this->join('t_tokonotamutasirekap AS B', 'A.IDTOKONOTAMUTASIREKAP = B.IDTOKONOTAMUTASIREKAP', 'LEFT');
        $this->join('t_suratjalanrekap AS C', 'A.IDSURATJALANREKAP  = C.IDSURATJALANREKAP ', 'LEFT');
        $this->where('A.IDTOKONOTAMUTASIREKAP', $idTokoNotaMutasiRekap);
        $this->limit(1);
        
        $result =   $this->get()->getRowArray();
        if(is_null($result)) return true;
        return $result;
	}
}