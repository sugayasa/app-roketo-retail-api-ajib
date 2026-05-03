<?php

namespace App\Models\ERP\Stok;
use CodeIgniter\Model;

class PengaturanDiskonModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = ['t_diskonretail', 't_diskongrosir'];
    protected $primaryKey       = ['IDDISKONRETAIL', 'IDDISKONGROSIR'];
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

    public function getListDiskonRetail($idBarangKategori, $idBarangMerk, $tipeDiskon, $statusBerlaku, $kataKunciPencarian)
    {	
        $this->select("CONCAT('[', D.NAMAKATEGORI, '] [', E.NAMAMERK, '] ', C.NAMABARANG) AS NAMABARANG, B.KODESKU, F.NAMASATUAN, B.DESKRIPSI AS DESKRIPSISKU,
                    '' AS ATRIBUTSKU, IF(A.TIPEDISKON = 1, 'Persentase', 'Nominal') AS TIPEDISKONSTR, A.TIPEDISKON, A.DESKRIPSI AS DESKRIPSIDISKON,
                    DATE_FORMAT(A.TANGGALBATAS, '%d %b %Y') AS TANGGALBATASSTR, A.TANGGALBATAS, 0 AS RERATAHARGABELI, 0 AS RERATAHARGAJUAL,
                    A.JUMLAHDISKON, 0 AS RERATAHARGAFINAL, IF(A.STATUS = 1, 'Aktif', 'Kadaluarsa') AS STATUSSTR, A.STATUS, A.IDBARANGSKU, A.IDBARANGSATUAN,
                    A.IDDISKONRETAIL");
        $this->from('t_diskonretail A', true);
        $this->join('m_barangsku AS B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->join('m_barang AS C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS D', 'C.IDBARANGKATEGORI = D.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS E', 'C.IDBARANGMERK = E.IDBARANGMERK', 'LEFT');
        $this->join('m_barangsatuan AS F', 'A.IDBARANGSATUAN = F.IDBARANGSATUAN', 'LEFT');
        
        $this->where('A.STATUS', $statusBerlaku);
        if($idBarangKategori != 0) $this->where('C.IDBARANGKATEGORI', $idBarangKategori);
        if($idBarangMerk != 0) $this->where('C.IDBARANGMERK', $idBarangMerk);
        if($tipeDiskon != 0 && $tipeDiskon != '') $this->where('A.TIPEDISKON', $tipeDiskon);
        if(isset($kataKunciPencarian) && !is_null($kataKunciPencarian)){
            $this->groupStart();
            $this->like('D.NAMAKATEGORI', $kataKunciPencarian, 'both')
            ->orLike('E.NAMAMERK', $kataKunciPencarian, 'both')
            ->orLike('C.NAMABARANG', $kataKunciPencarian, 'both')
            ->orLike('B.DESKRIPSI', $kataKunciPencarian, 'both')
            ->orLike('A.DESKRIPSI', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }

        $this->orderBy('D.NAMAKATEGORI, E.NAMAMERK, C.NAMABARANG, A.TIPEDISKON, A.TANGGALBATAS DESC');

        return $this;
	}

    public function getRerataHargaBeliBarangSKU($idBarangSKU)
    {	
        $this->select("AVG(HARGABELI) AS RERATAHARGABELI");
        $this->from('t_notapembelianbarang', true);
        $this->where('IDBARANGSKU', $idBarangSKU);
        $this->groupBy('IDBARANGSKU');
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return 0;
        return $result['RERATAHARGABELI'];
	}

    public function getRerataHargaJualBarangSKU($idBarangSKU, $idBarangSatuan)
    {	
        $this->select("AVG(HARGA) AS RERATAHARGAJUAL");
        $this->from('t_baranghargajual', true);
        $this->where('IDBARANGSKU', $idBarangSKU);
        $this->where('IDBARANGSATUAN', $idBarangSatuan);
        $this->groupBy('IDBARANGSKU');
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return 0;
        return $result['RERATAHARGAJUAL'];
	}

    public function isDataDiskonRetailValid($idBarangSKU, $idBarangSatuan, $tanggalBatas, $idDiskonRetail)
    {	
        $this->select("IDDISKONRETAIL");
        $this->from('t_diskonretail', true);
        $this->where('IDBARANGSKU', $idBarangSKU);
        $this->where('IDBARANGSATUAN', $idBarangSatuan);
        $this->where('TANGGALBATAS >= ', $tanggalBatas);
        if(isset($idDiskonRetail) && $idDiskonRetail != 0) $this->where('IDDISKONRETAIL != ', $idDiskonRetail);
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return true;
        return false;
	}

    public function getListDiskonGrosir($idBarangKategori, $idBarangMerk, $tipeDiskon, $statusBerlaku, $kataKunciPencarian)
    {	
        $this->select("CONCAT('[', D.NAMAKATEGORI, '] [', E.NAMAMERK, '] ', C.NAMABARANG) AS NAMABARANG, B.KODESKU, F.NAMASATUAN, B.DESKRIPSI AS DESKRIPSISKU,
                    '' AS ATRIBUTSKU, IF(A.TIPEDISKON = 1, 'Persentase', 'Nominal') AS TIPEDISKONSTR, A.TIPEDISKON, A.DESKRIPSI AS DESKRIPSIDISKON, A.ARRIDTOKOBERLAKU,
                    A.MINIMALITEM, DATE_FORMAT(A.TANGGALBATAS, '%d %b %Y') AS TANGGALBATASSTR, A.TANGGALBATAS, 0 AS RERATAHARGABELI, 0 AS RERATAHARGAJUAL,
                    A.JUMLAHDISKON, 0 AS RERATAHARGAFINAL, IF(A.STATUS = 1, 'Aktif', 'Kadaluarsa') AS STATUSSTR, A.STATUS, A.IDBARANGSKU, A.IDBARANGSATUAN,
                    A.IDDISKONGROSIR");
        $this->from('t_diskongrosir A', true);
        $this->join('m_barangsku AS B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->join('m_barang AS C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS D', 'C.IDBARANGKATEGORI = D.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS E', 'C.IDBARANGMERK = E.IDBARANGMERK', 'LEFT');
        $this->join('m_barangsatuan AS F', 'A.IDBARANGSATUAN = F.IDBARANGSATUAN', 'LEFT');
        
        $this->where('A.STATUS', $statusBerlaku);
        if($idBarangKategori != 0) $this->where('C.IDBARANGKATEGORI', $idBarangKategori);
        if($idBarangMerk != 0) $this->where('C.IDBARANGMERK', $idBarangMerk);
        if($tipeDiskon != 0 && $tipeDiskon != '') $this->where('A.TIPEDISKON', $tipeDiskon);
        if(isset($kataKunciPencarian) && !is_null($kataKunciPencarian)){
            $this->groupStart();
            $this->like('D.NAMAKATEGORI', $kataKunciPencarian, 'both')
            ->orLike('E.NAMAMERK', $kataKunciPencarian, 'both')
            ->orLike('C.NAMABARANG', $kataKunciPencarian, 'both')
            ->orLike('B.DESKRIPSI', $kataKunciPencarian, 'both')
            ->orLike('A.DESKRIPSI', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }

        $this->orderBy('D.NAMAKATEGORI, E.NAMAMERK, C.NAMABARANG, A.TIPEDISKON, A.TANGGALBATAS DESC');

        return $this;
	}

    public function getRerataHargaJualGrosirBarangSKU($idBarangSKU, $idBarangSatuan)
    {	
        $this->select("AVG(HARGA) AS RERATAHARGAJUAL");
        $this->from('t_baranghargajualgrosir', true);
        $this->where('IDBARANGSKU', $idBarangSKU);
        $this->where('IDBARANGSATUAN', $idBarangSatuan);
        $this->groupBy('IDBARANGSKU');
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return 0;
        return $result['RERATAHARGAJUAL'];
	}

    public function isDataDiskonGrosirValid($idBarangSKU, $idBarangSatuan, $arrIdTokoBerlaku, $tanggalBatas, $idDiskonGrosir)
    {	
        $this->select("IDDISKONGROSIR");
        $this->from('t_diskongrosir', true);
        $this->where('IDBARANGSKU', $idBarangSKU);
        $this->where('IDBARANGSATUAN', $idBarangSatuan);
        $this->where('ARRIDTOKOBERLAKU', $arrIdTokoBerlaku);
        $this->where('TANGGALBATAS >= ', $tanggalBatas);
        if(isset($idDiskonGrosir) && $idDiskonGrosir != 0) $this->where('IDDISKONGROSIR != ', $idDiskonGrosir);
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return true;
        return false;
	}

    public function getDataDiskonBarangSKUGrosir($idBarangSKU, $idBarangSatuan, $idToko)
    {	
        $this->select("IDDISKONGROSIR, DESKRIPSI, TIPEDISKON, JUMLAHDISKON, MINIMALITEM");
        $this->from('t_diskongrosir', true);
        $this->where('IDBARANGSKU', $idBarangSKU);
        $this->where('IDBARANGSATUAN', $idBarangSatuan);
        $this->groupStart();
            $this->where("JSON_CONTAINS(ARRIDTOKOBERLAKU, '".$idToko."', '$')");
            $this->orWhere("JSON_LENGTH(ARRIDTOKOBERLAKU) = 0", null, false);
        $this->groupEnd();
        $this->where('TANGGALBATAS >= ', date('Y-m-d'));
        $this->orderBy('TANGGALBATAS ASC');
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return false;
        return $result;
	}

    public function getDetailDiskonBarangGrosir($idDiskonGrosir, $idToko)
    {	
        $this->select("TIPEDISKON, JUMLAHDISKON, MINIMALITEM");
        $this->from('t_diskongrosir', true);
        $this->where('IDDISKONGROSIR', $idDiskonGrosir);
        $this->groupStart();
            $this->where("JSON_CONTAINS(ARRIDTOKOBERLAKU, '".$idToko."', '$')");
            $this->orWhere("JSON_LENGTH(ARRIDTOKOBERLAKU) = 0", null, false);
        $this->groupEnd();
        $this->where('TANGGALBATAS >= ', date('Y-m-d'));
        $this->orderBy('TANGGALBATAS ASC');
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return false;
        return $result;
	}

    public function getDataDiskonBarangSKURetail($idBarangSKU, $idBarangSatuan)
    {	
        $this->select("IDDISKONRETAIL, DESKRIPSI, TIPEDISKON, JUMLAHDISKON");
        $this->from('t_diskonretail', true);
        $this->where('IDBARANGSKU', $idBarangSKU);
        $this->where('IDBARANGSATUAN', $idBarangSatuan);
        $this->where('TANGGALBATAS >= ', date('Y-m-d'));
        $this->orderBy('TANGGALBATAS ASC');
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return false;
        return $result;
	}

    public function getDetailDiskonBarangRetail($idDiskonRetail)
    {	
        $this->select("TIPEDISKON, JUMLAHDISKON");
        $this->from('t_diskonretail', true);
        $this->where('IDDISKONRETAIL', $idDiskonRetail);
        $this->where('TANGGALBATAS >= ', date('Y-m-d'));
        $this->orderBy('TANGGALBATAS ASC');
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return false;
        return $result;
	}
}