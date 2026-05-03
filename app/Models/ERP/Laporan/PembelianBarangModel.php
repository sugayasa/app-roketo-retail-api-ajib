<?php

namespace App\Models\ERP\Laporan;
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

    public function getDataPembelianBarang($idProdusenDistributor, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian)
    {	
        $this->select("A.IDNOTAPEMBELIANREKAP, A.NOTAPEMBELIANNOMOR, B.NAMA AS NAMAPRODUSENDISTRIBUTOR, DATE_FORMAT(A.INPUTTANGGALWAKTU, '%d-%m-%Y') AS INPUTTANGGALWAKTU,
                A.INPUTUSER, A.KETERANGAN, '[]' AS DAFTARBARANGSKU");
        $this->from('t_notapembelianrekap A', true);
        $this->join('m_produsendistributor AS B', 'A.IDPRODUSENDISTRIBUTOR = B.IDPRODUSENDISTRIBUTOR', 'LEFT');
        $this->where('DATE(A.INPUTTANGGALWAKTU) >=', $tanggalAwal);
        $this->where('DATE(A.INPUTTANGGALWAKTU) <=', $tanggalAkhir);
        if(isset($idProdusenDistributor) && $idProdusenDistributor != 0 && $idProdusenDistributor != "") $this->where('A.IDPRODUSENDISTRIBUTOR', $idProdusenDistributor);

        if(isset($kataKunciPencarian) && $kataKunciPencarian != "") {
            $this->groupStart();
            $this->like('A.NOTAPEMBELIANNOMOR', $kataKunciPencarian, 'both')
            ->orLike('B.NAMA', $kataKunciPencarian, 'both')
            ->orLike('A.INPUTUSER', $kataKunciPencarian, 'both')
            ->orLike('A.KETERANGAN', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }
        $this->orderBy('A.INPUTTANGGALWAKTU', 'ASC');
        return $this;
	}

    public function getDataBarangSKUPembelian($idNotaPembelianRekap)
    {	
        $this->select("CONCAT(C.KODEBARANG, '-', B.KODESKU) AS KODESKU, B.DESKRIPSI AS DESKRIPSISKU, A.JUMLAH, D.NAMASATUAN, A.HARGABELI, (A.JUMLAH * A.HARGABELI) AS TOTALHARGABELI");
        $this->from('t_notapembelianbarang AS A', true);
        $this->join('m_barangsku AS B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->join('m_barang AS C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangsatuan AS D', 'B.IDBARANGSATUAN = D.IDBARANGSATUAN', 'LEFT');
        $this->where('A.IDNOTAPEMBELIANREKAP', $idNotaPembelianRekap);
        $this->orderBy('C.KODEBARANG');

        $result =   $this->get()->getResultObject();

        if(is_null($result)) return [];
        return $result;
	}
}