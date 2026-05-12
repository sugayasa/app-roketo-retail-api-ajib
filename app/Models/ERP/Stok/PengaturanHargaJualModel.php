<?php

namespace App\Models\ERP\Stok;
use CodeIgniter\Model;

class PengaturanHargaJualModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_baranghargajual';
    protected $primaryKey       = 'IDBARANGHARGAJUAL';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDBARANGHARGAJUAL', 'IDBARANG', 'IDBARANGSKU', 'IDGUDANG', 'IDTOKO', 'JUMLAHSATUAN', 'HARGA'];

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

    public function getDataHargaJualBarang($idBarangKategori, $idBarangMerk, $searchKeyword)
    {	
        $this->select("A.IDBARANG, B.NAMAKATEGORI, C.NAMAMERK, CONCAT('[', IFNULL(A.KODEBARANG, '-'), '] ', A.NAMABARANG) AS NAMABARANG, COUNT(DISTINCT(D.IDBARANGSKU)) AS JUMLAHSKU,
                IFNULL(GROUP_CONCAT(DISTINCT(FORMAT(E.HARGA, 0)) ORDER BY E.HARGA ASC SEPARATOR ' | '), '-') AS DAFTARHARGARETAIL,
                IFNULL(GROUP_CONCAT(DISTINCT(FORMAT(F.HARGA, 0)) ORDER BY F.HARGA ASC SEPARATOR ' | '), '-') AS DAFTARHARGAGROSIR,
                '[]' AS ARRIDBARANGSATUAN");
        $this->from('m_barang A', true);
        $this->join('m_barangkategori AS B', 'A.IDBARANGKATEGORI = B.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS C', 'A.IDBARANGMERK = C.IDBARANGMERK', 'LEFT');
        $this->join('m_barangsku AS D', 'A.IDBARANG = D.IDBARANG', 'LEFT');
        $this->join('t_baranghargajual AS E', 'A.IDBARANG = E.IDBARANG', 'LEFT');
        $this->join('t_baranghargajualgrosir AS F', 'A.IDBARANG = F.IDBARANG', 'LEFT');

        if($idBarangKategori != 0) $this->where('A.IDBARANGKATEGORI', $idBarangKategori);
        if($idBarangMerk != 0) $this->where('A.IDBARANGMERK', $idBarangMerk);
        if(isset($searchKeyword) && !is_null($searchKeyword)){
            $this->groupStart();
            $this->like('B.NAMAKATEGORI', $searchKeyword, 'both')
            ->orLike('C.NAMAMERK', $searchKeyword, 'both')
            ->orLike('A.NAMABARANG', $searchKeyword, 'both')
            ->orLike('A.KODEBARANG', $searchKeyword, 'both');
            $this->groupEnd();
        }

        $this->groupBy('A.IDBARANG');
        $this->orderBy('B.NAMAKATEGORI, C.NAMAMERK, NAMABARANG');

        return $this;
	}

    public function getDataKelompokHargaGrosir()
    {	
        $this->select("IDKELOMPOKHARGAGROSIR, KELOMPOKHARGAGROSIR, DESKRIPSI");
        $this->from('m_kelompokhargagrosir', true);
        $this->where('STATUS', 1);

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}

    public function getDataDetailBarang($idBarang)
    {	
        $this->select("B.NAMAKATEGORI, C.NAMAMERK, CONCAT(A.NAMABARANG, ' | ', A.KODEBARANG) AS NAMABARANG");
        $this->from('m_barang A', true);
        $this->join('m_barangkategori AS B', 'A.IDBARANGKATEGORI = B.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS C', 'A.IDBARANGMERK = C.IDBARANGMERK', 'LEFT');
        $this->where('A.IDBARANG', $idBarang);
        $this->limit(1);

        $result =   $this->get()->getRowObject();
        if(is_null($result)) return ['NAMAKATEGORI' => '-', 'NAMAMERK' => '-', 'NAMABARANG' => '-'];
        return $result;
	}

    public function getDataDetailBarangSKU($idBarang)
    {	
        $this->select("IDBARANGSKU, KODESKU, DESKRIPSI, '[]' AS ATRIBUTSKUSTR");
        $this->from('m_barangsku', true);
        $this->where('IDBARANG', $idBarang);
        $this->groupBy('IDBARANGSKU');
        $this->orderBy('KODESKU');

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}

    public function getHistoryHargaBeliPerSKU($idBarangSKU)
    {	
        $this->select("DATE_FORMAT(B.INPUTTANGGALWAKTU, '%d %b %Y') AS TANGGAL, B.NOTAPEMBELIANNOMOR, A.HARGABELI");
        $this->from('t_notapembelianbarang AS A', true);
        $this->join('t_notapembelianrekap AS B', 'A.IDNOTAPEMBELIANREKAP = B.IDNOTAPEMBELIANREKAP', 'LEFT');
        $this->where('A.IDBARANGSKU', $idBarangSKU);
        $this->orderBy('B.INPUTTANGGALWAKTU DESC');

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}

    public function getDataHargaJualBarangPerSKU($idToko, $idBarang, $idBarangSatuan)
    {	
        $this->select("IDBARANGSKU, HARGA");
        $this->from('t_baranghargajual', true);
        $this->where('IDTOKO', $idToko)->where('IDBARANG', $idBarang)->where('IDBARANGSATUAN', $idBarangSatuan)->where('JUMLAHSATUAN', 1);

        $result =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}

    public function getHargaBarangSKUGrosir($idBarangSKU, $idBarangSatuan)
    {	
        $this->select("A.IDKELOMPOKHARGAGROSIR, IFNULL(B.HARGA, '0') AS HARGA");
        $this->from('m_kelompokhargagrosir AS A', true);
        $this->join('t_baranghargajualgrosir AS B', 'A.IDKELOMPOKHARGAGROSIR = B.IDKELOMPOKHARGAGROSIR AND B.IDBARANGSKU = ' . $idBarangSKU. ' AND B.IDBARANGSATUAN = ' . $idBarangSatuan. ' AND B.JUMLAHSATUAN = 1', 'LEFT');
        $this->where('A.STATUS', 1);
        $this->orderBy('A.IDKELOMPOKHARGAGROSIR');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}

    public function isHargaBarangGrosirExist($idKelompokHargaGrosir, $idBarangSKU, $idBarangSatuan)
    {	
        $this->select("IDBARANGHARGAJUALGROSIR");
        $this->from('t_baranghargajualgrosir', true);
        $this->where('IDKELOMPOKHARGAGROSIR', $idKelompokHargaGrosir)->where('IDBARANGSKU', $idBarangSKU)->where('IDBARANGSATUAN', $idBarangSatuan)->where('JUMLAHSATUAN', 1);
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return false;
        return $result;
	}
}