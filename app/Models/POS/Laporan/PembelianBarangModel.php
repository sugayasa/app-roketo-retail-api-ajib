<?php

namespace App\Models\POS\Laporan;
use CodeIgniter\Model;

class PembelianBarangModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_tokonotamutasirekap';
    protected $primaryKey       = 'IDTOKONOTAMUTASIREKAP';
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

    public function getDataPembelianBarang($idToko, $idGudang, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian)
    {	
        $this->select("A.IDTOKONOTAMUTASIREKAP, A.NOTAMUTASINOMOR, B.NAMA AS NAMAGUDANG, DATE_FORMAT(A.PROSESTANGGALWAKTU, '%d-%m-%Y') AS PROSESTANGGALWAKTU,
                A.PROSESUSER, A.PROSESKETERANGAN, A.TOTALSKU, A.TOTALNOMINALBARANG, '[]' AS DAFTARBARANGSKU");
        $this->from('t_tokonotamutasirekap A', true);
        $this->join('m_gudang AS B', 'A.IDGUDANG = B.IDGUDANG', 'LEFT');
        $this->where('A.IDTOKO', $idToko);
        $this->where('DATE(A.PROSESTANGGALWAKTU) >=', $tanggalAwal);
        $this->where('DATE(A.PROSESTANGGALWAKTU) <=', $tanggalAkhir);
        if(isset($idGudang) && $idGudang != 0 && $idGudang != "") $this->where('A.IDGUDANG', $idGudang);

        if(isset($kataKunciPencarian) && $kataKunciPencarian != "") {
            $this->groupStart();
            $this->like('A.NOTAMUTASINOMOR', $kataKunciPencarian, 'both')
            ->orLike('B.NAMA', $kataKunciPencarian, 'both')
            ->orLike('A.PROSESUSER', $kataKunciPencarian, 'both')
            ->orLike('A.PROSESKETERANGAN', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }
        $this->orderBy('A.PROSESTANGGALWAKTU', 'ASC');
        return $this;
	}

    public function getDataBarangSKUPembelian($idNotaMutasiTokoRekap)
    {	
        $this->select("CONCAT(C.KODEBARANG, '-', B.KODESKU) AS KODESKU, B.DESKRIPSI AS DESKRIPSISKU, A.JUMLAH, D.NAMASATUAN, A.HARGAGROSIR, (A.JUMLAH * A.HARGAGROSIR) AS TOTALHARGAGROSIR");
        $this->from('t_tokonotamutasibarang AS A', true);
        $this->join('m_barangsku AS B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->join('m_barang AS C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangsatuan AS D', 'B.IDBARANGSATUAN = D.IDBARANGSATUAN', 'LEFT');
        $this->where('A.IDTOKONOTAMUTASIREKAP', $idNotaMutasiTokoRekap);
        $this->orderBy('C.KODEBARANG');

        $result =   $this->get()->getResultObject();

        if(is_null($result)) return [];
        return $result;
	}
}