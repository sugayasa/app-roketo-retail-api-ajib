<?php

namespace App\Models\ERP\Stok;
use CodeIgniter\Model;

class PembelianBarangModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_notapembelianrekap';
    protected $primaryKey       = 'IDNOTAPEMBELIANREKAP';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDNOTAPEMBELIANREKAP', 'IDPRODUSENDISTRIBUTOR', 'NOTAPEMBELIANNOMOR', 'TOTALJENISBARANG', 'TOTALSKU', 'PERSENPENYELESAIANINBOUND', 'KETERANGAN', 'INPUTUSER', 'INPUTTANGGALWAKTU'];

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

    public function getListNotaPembelian($tahun, $isStatusNotaAktif, $searchKeyword)
    {	
        $this->select("A.IDNOTAPEMBELIANREKAP, A.NOTAPEMBELIANNOMOR, A.TOTALJENISBARANG, A.TOTALSKU, DATE_FORMAT(A.INPUTTANGGALWAKTU, '%d %b %Y') AS INPUTTANGGAL,
                    B.NAMA AS NAMAPRODUSENDISTRIBUTOR, A.PERSENPENYELESAIANINBOUND, A.KETERANGAN, A.INPUTUSER");
        $this->from('t_notapembelianrekap A', true);
        $this->join('m_produsendistributor AS B', 'A.IDPRODUSENDISTRIBUTOR = B.IDPRODUSENDISTRIBUTOR', 'LEFT');
        $this->where('YEAR(A.INPUTTANGGALWAKTU)', $tahun);

        if(isset($isStatusNotaAktif) && !is_null($isStatusNotaAktif) && $isStatusNotaAktif == true){
            $this->where('A.PERSENPENYELESAIANINBOUND < ', 100);
        }
        
        if(isset($searchKeyword) && !is_null($searchKeyword)){
            $this->groupStart();
            $this->like('B.NAMA', $searchKeyword, 'both')
            ->orLike('A.NOTAPEMBELIANNOMOR', $searchKeyword, 'both')
            ->orLike('A.KETERANGAN', $searchKeyword, 'both')
            ->orLike('A.INPUTUSER', $searchKeyword, 'both');
            $this->groupEnd();
        }

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}

    public function getDataBarangSKUNotaPembelian($idNotaPembelianRekap)
    {	
        $this->select("A.IDNOTAPEMBELIANBARANG, A.IDBARANG, A.IDBARANGSKU, C.NAMAKATEGORI, D.NAMAMERK, B.NAMABARANG, E.KODESKU, E.DESKRIPSI AS DESKRIPSISKU,
                '[]' AS ATRIBUTSKUSTR, A.JUMLAH, F.KODESATUAN, A.HARGABELI");
        $this->from('t_notapembelianbarang A', true);
        $this->join('m_barang AS B', 'A.IDBARANG = B.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS C', 'B.IDBARANGKATEGORI = C.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS D', 'B.IDBARANGMERK = D.IDBARANGMERK', 'LEFT');
        $this->join('m_barangsku AS E', 'A.IDBARANGSKU = E.IDBARANGSKU', 'LEFT');
        $this->join('m_barangsatuan AS F', 'A.IDBARANGSATUAN = F.IDBARANGSATUAN', 'LEFT');
        $this->where('A.IDNOTAPEMBELIANREKAP', $idNotaPembelianRekap);
        $this->orderBy('C.NAMAKATEGORI, D.NAMAMERK, B.NAMABARANG, E.KODESKU');

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}

    public function getDataOptionSKUBarang($idBarang, $idNotaPembelianRekap = false)
    {	
        $this->select('A.IDBARANGSKU, A.IDBARANG, "[]" AS ARRIDBARANGSATUAN, D.NAMAKATEGORI, C.NAMAMERK, B.NAMABARANG, A.KODESKU, A.DESKRIPSI AS DESKRIPSISKU, "" AS ATRIBUTSKUSTR');
        $this->from('m_barangsku A', true);
        $this->join('m_barang B', 'B.IDBARANG = A.IDBARANG', 'LEFT');
        $this->join('m_barangmerk C', 'C.IDBARANGMERK = B.IDBARANGMERK', 'LEFT');
        $this->join('m_barangkategori D', 'D.IDBARANGKATEGORI = B.IDBARANGKATEGORI', 'LEFT');
        $this->where('A.IDBARANG', $idBarang);
        if((isset($idNotaPembelianRekap) && !is_null($idNotaPembelianRekap) && $idNotaPembelianRekap != false)) {
            $this->where('A.IDBARANGSKU NOT IN (SELECT IDBARANGSKU FROM t_notapembelianbarang WHERE IDNOTAPEMBELIANREKAP = '.$idNotaPembelianRekap.')');
        }
        $this->orderBy('D.NAMAKATEGORI, C.NAMAMERK, B.NAMABARANG, A.KODESKU');

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}

    public function getDetailDataNotaBarangSKU($idNotaPembelianBarang)
    {	
        $this->select("C.NAMAKATEGORI, D.NAMAMERK, B.NAMABARANG, E.KODESKU, E.DESKRIPSI AS DESKRIPSISKU, '[]' AS ATRIBUTSKUSTR, A.JUMLAH, A.HARGABELI");
        $this->from('t_notapembelianbarang A', true);
        $this->join('m_barang AS B', 'A.IDBARANG = B.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS C', 'B.IDBARANGKATEGORI = C.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS D', 'B.IDBARANGMERK = D.IDBARANGMERK', 'LEFT');
        $this->join('m_barangsku AS E', 'A.IDBARANGSKU = E.IDBARANGSKU', 'LEFT');
        $this->where('A.IDNOTAPEMBELIANBARANG', $idNotaPembelianBarang);
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return false;
        return $result;
	}

    public function getDataInboundGudangNotaPembelianBarang($idNotaPembelianBarang)
    {	
        $this->select("A.IDGUDANG, B.NAMA AS NAMAGUDANG, A.INBOUNDJATAH, A.INBOUNDJUMLAH");
        $this->from('t_notapembelianinbound A', true);
        $this->join('m_gudang AS B', 'A.IDGUDANG = B.IDGUDANG', 'LEFT');
        $this->where('A.IDNOTAPEMBELIANBARANG', $idNotaPembelianBarang);

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}
}