<?php

namespace App\Models\POS;
use CodeIgniter\Model;

class PenjualanModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_penjualan';
    protected $primaryKey       = 'IDTRANSAKSI';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDTRANSAKSI'];

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
    
    public function getListBarang($arrIdBarangKategori, $arrIdBarangMerk, $searchKeyword)
    {	
        $this->select("A.IDBARANG, B.NAMAMERK, C.NAMAKATEGORI, A.NAMABARANG, A.KODEBARANG, IFNULL(MIN(D.HARGA), 0) AS HARGATERENDAH, IFNULL(MAX(D.HARGA), 0) AS HARGATERTINGGI,
                    0 AS TOTALSTOK, A.FOTOBARANG, A.DESKRIPSI");
        $this->from('m_barang A', true);
        $this->join('m_barangmerk AS B', 'A.IDBARANGMERK = B.IDBARANGMERK', 'LEFT');
        $this->join('m_barangkategori AS C', 'A.IDBARANGKATEGORI = C.IDBARANGKATEGORI', 'LEFT');
        $this->join('t_baranghargajual AS D', 'A.IDBARANG = D.IDBARANG', 'LEFT');
        $this->join('m_barangsku AS E', 'A.IDBARANG = E.IDBARANG', 'LEFT');

        if(isset($arrIdBarangKategori) && is_array($arrIdBarangKategori) && count($arrIdBarangKategori) > 0) {
            $this->whereIn('A.IDBARANGKATEGORI', $arrIdBarangKategori);
        }

        if(isset($arrIdBarangMerk) && is_array($arrIdBarangMerk) && count($arrIdBarangMerk) > 0) {
            $this->whereIn('A.IDBARANGMERK', $arrIdBarangMerk);
        }

        if(isset($searchKeyword) && !is_null($searchKeyword)){
            $this->groupStart();
            $this->like('B.NAMAMERK', $searchKeyword, 'both')
            ->orLike('C.NAMAKATEGORI', $searchKeyword, 'both')
            ->orLike('E.KODESKU', $searchKeyword, 'both')
            ->orLike('E.DESKRIPSI', $searchKeyword, 'both')
            ->orLike('A.NAMABARANG', $searchKeyword, 'both')
            ->orLike('A.KODEBARANG', $searchKeyword, 'both')
            ->orLike('A.DESKRIPSI', $searchKeyword, 'both');
            $this->groupEnd();
        }
        $this->groupBy('A.IDBARANG');

        return $this;
	}
    
    public function getListPaket($idToko, $arrIdBarangKategori, $arrIdBarangMerk, $searchKeyword)
    {	
        $this->select("A.IDHARGARETAILPAKET, A.NAMAHARGARETAILPAKET, A.DESKRIPSI, SUM(B.HARGA) AS HARGA, GROUP_CONCAT(DISTINCT C.FOTOBARANGSKU) AS FOTOBARANG");
        $this->from('t_hargaretailpaket A', true);
        $this->join('t_hargaretailpaketsku AS B', 'A.IDHARGARETAILPAKET = B.IDHARGARETAILPAKET', 'LEFT');
        $this->join('m_barangsku AS C', 'B.IDBARANGSKU = C.IDBARANGSKU', 'LEFT');
        $this->where('A.STATUS', 1);
        $this->where('A.IDTOKO', $idToko);

        if(
            (isset($arrIdBarangKategori) && is_array($arrIdBarangKategori) && count($arrIdBarangKategori) > 0) ||
            (isset($arrIdBarangMerk) && is_array($arrIdBarangMerk) && count($arrIdBarangMerk) > 0) ||
            (isset($searchKeyword) && !is_null($searchKeyword) && $searchKeyword != '')
        ) {
            $arrIdHargaRetailPaket   =   $this->getArrIdHargaRetailPaket($idToko, $arrIdBarangKategori, $arrIdBarangMerk, $searchKeyword);
            $this->whereIn('A.IDHARGARETAILPAKET', $arrIdHargaRetailPaket);
        }
        $this->groupBy('A.IDHARGARETAILPAKET');

        return $this;
	}

    private function getArrIdHargaRetailPaket($idToko, $arrIdBarangKategori, $arrIdBarangMerk, $searchKeyword){
        $builder = $this->db->table('t_hargaretailpaket A');
        $builder->select("GROUP_CONCAT(DISTINCT A.IDHARGARETAILPAKET) AS STRARRIDHARGARETAILPAKET");
        $builder->join('t_hargaretailpaketsku AS B', 'A.IDHARGARETAILPAKET = B.IDHARGARETAILPAKET', 'LEFT');
        $builder->join('m_barangsku AS C', 'B.IDBARANGSKU = C.IDBARANGSKU', 'LEFT');
        $builder->join('m_barang AS D', 'C.IDBARANG = D.IDBARANG', 'LEFT');
        $builder->join('m_barangmerk AS E', 'D.IDBARANGMERK = E.IDBARANGMERK', 'LEFT');
        $builder->join('m_barangkategori AS F', 'D.IDBARANGKATEGORI = F.IDBARANGKATEGORI', 'LEFT');
        $builder->where('A.STATUS', 1);
        $builder->where('A.IDTOKO', $idToko);

        if(isset($arrIdBarangKategori) && is_array($arrIdBarangKategori) && count($arrIdBarangKategori) > 0) {
            $builder->whereIn('D.IDBARANGKATEGORI', $arrIdBarangKategori);
        }

        if(isset($arrIdBarangMerk) && is_array($arrIdBarangMerk) && count($arrIdBarangMerk) > 0) {
            $builder->whereIn('D.IDBARANGMERK', $arrIdBarangMerk);
        }

        if(isset($searchKeyword) && !is_null($searchKeyword)){
            $builder->groupStart();
            $builder->like('E.NAMAMERK', $searchKeyword, 'both');
            $builder->orLike('F.NAMAKATEGORI', $searchKeyword, 'both');
            $builder->orLike('C.KODESKU', $searchKeyword, 'both');
            $builder->orLike('C.DESKRIPSI', $searchKeyword, 'both');
            $builder->orLike('D.NAMABARANG', $searchKeyword, 'both');
            $builder->orLike('D.KODEBARANG', $searchKeyword, 'both');
            $builder->orLike('A.DESKRIPSI', $searchKeyword, 'both');
            $builder->groupEnd();
        }

        $result     =   $builder->get()->getRowArray();
        if(is_null($result)) return [];
        return $result['STRARRIDHARGARETAILPAKET'] != '' ? explode(',', $result['STRARRIDHARGARETAILPAKET']) : [];
    }
    
    public function getListBarangSKUStokHarga($idBarang)
    {	
        $this->select("IDBARANGSKU, KODESKU, DESKRIPSI, '[]' AS ATRIBUTSKUSTR, FOTOBARANGSKU, '[]' AS STOKHARGAJUAL");
        $this->from('m_barangsku', true);
        $this->where('IDBARANG', $idBarang);
        $this->orderBy('KODESKU');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}
    
    public function getListBarangSKUPaket($idHargaRetailPaket)
    {	
        $this->select("A.IDHARGARETAILPAKETSKU, A.IDBARANGSKU, A.IDBARANGSATUAN, B.NAMAHARGARETAILPAKET, C.KODESKU, C.DESKRIPSI, D.NAMASATUAN, A.JUMLAH,
                    A.HARGA, 0 AS STOK, '[]' AS ATRIBUTSKUSTR, '' AS FOTOBARANGSKU");
        $this->from('t_hargaretailpaketsku AS A', true);
        $this->join('t_hargaretailpaket AS B', 'A.IDHARGARETAILPAKET = B.IDHARGARETAILPAKET', 'LEFT');
        $this->join('m_barangsku AS C', 'A.IDBARANGSKU = C.IDBARANGSKU', 'LEFT');
        $this->join('m_barangsatuan AS D', 'A.IDBARANGSATUAN = D.IDBARANGSATUAN', 'LEFT');
        $this->where('A.IDHARGARETAILPAKET', $idHargaRetailPaket);
        $this->orderBy('C.KODESKU');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}

    public function getDataStokHargaJualBarangPerSKU($idToko, $idBarangSKU)
    {	
        $subQuery1  =   $this->db->table('t_baranghargajual AS A', true);
        $subQuery1->select("A.IDBARANGSATUAN, B.NAMASATUAN AS SATUAN, 0 AS STOK, A.HARGA");
        $subQuery1->join('m_barangsatuan AS B', 'A.IDBARANGSATUAN = B.IDBARANGSATUAN', 'LEFT');
        $subQuery1->where('A.IDTOKO', $idToko);
        $subQuery1->where('A.JUMLAHSATUAN', 1);
        $subQuery1->where('A.IDBARANGSKU', $idBarangSKU);

        $subQuery2  =   $this->db->table('t_tokostok AS A', true);
        $subQuery2->select("A.IDBARANGSATUAN, B.NAMASATUAN AS SATUAN, SUM(A.JUMLAHMASUK - A.JUMLAHKELUAR) AS STOK, 0 AS HARGA");
        $subQuery2->join('m_barangsatuan AS B', 'A.IDBARANGSATUAN = B.IDBARANGSATUAN', 'LEFT');
        $subQuery2->where('A.IDTOKO', $idToko);
        $subQuery2->where('A.IDBARANGSKU', $idBarangSKU);
        $subQuery2->groupBy('A.IDBARANGSATUAN');

        $subQuery1  =   $subQuery1->getCompiledSelect();
        $subQuery2  =   $subQuery2->getCompiledSelect();

        $unionQuery =   "({$subQuery1}) UNION ALL ({$subQuery2})";
        $finalQuery =   $this->db->query(
                            "SELECT IDBARANGSATUAN, '' AS IDDISKONRETAIL, SATUAN, SUM(STOK) AS STOK, SUM(HARGA) AS HARGA, '' AS DISKONDESKRIPSI,
                                    '' AS DISKONJUMLAH, SUM(HARGA) AS HARGASETELAHDISKON
                            FROM ({$unionQuery}) AS A
                            GROUP BY IDBARANGSATUAN"
                        );

        $result     =   $finalQuery->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}

    public function getDataStokBarangPerSKU($idToko, $idBarangSKU, $idBarangSatuan)
    {	
        $this->select("SUM(JUMLAHMASUK - JUMLAHKELUAR) AS STOK");
        $this->from('t_tokostok', true);
        $this->where('IDTOKO', $idToko);
        $this->where('IDBARANGSKU', $idBarangSKU);
        $this->where('IDBARANGSATUAN', $idBarangSatuan);
        $this->groupBy('IDBARANGSATUAN');

        $result     =   $this->get()->getRowArray();
        if(is_null($result)) return 0;
        return $result['STOK'];
	}
    
    public function getDataDiskonPaketRetailByBarangSKU($arrDataBarangSKU = [])
    {	
        $this->select("A.IDDISKONRETAILPAKET, B.NAMAPAKETDISKON");
        $this->from('t_diskonretailpaketkondisi AS A', true);
        $this->join('t_diskonretailpaket AS B', 'A.IDDISKONRETAILPAKET = B.IDDISKONRETAILPAKET', 'LEFT');
        $this->where('B.STATUS', 1);

        $this->groupStart();
        $this->where('DATE(B.TANGGALBATAS) >= CURDATE()');
        $this->orWhere('B.TANGGALBATAS', null);
        $this->groupEnd();

        if(count($arrDataBarangSKU) > 0){
            foreach($arrDataBarangSKU as $dataBarangSKU){
                $idBarangSKU    =   $dataBarangSKU['idBarangSKU'];
                $idBarangSatuan =   $dataBarangSKU['idBarangSatuan'];

                $this->orWhere("(A.IDBARANGSKU = ".$idBarangSKU." AND A.IDBARANGSATUAN = ".$idBarangSatuan.")");
            }
        }
        $this->groupBy('A.IDDISKONRETAILPAKET');
        $this->orderBy('B.INPUTTANGGALWAKTU', 'DESC');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function getDataDiskonPaketKondisi($idDiskonRetailPaket)
    {	
        $this->select("IDBARANGSKU, IDBARANGSATUAN, MINIMALJUMLAH");
        $this->from('t_diskonretailpaketkondisi', true);
        $this->where('IDDISKONRETAILPAKET', $idDiskonRetailPaket);

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function getDataDiskonPaketNominal($idDiskonRetailPaket, $idBarangSKU, $idBarangSatuan)
    {	
        $this->select("IDDISKONRETAILPAKETNOMINAL, TIPEDISKON, JUMLAHDISKON");
        $this->from('t_diskonretailpaketnominal', true);
        $this->where('IDDISKONRETAILPAKET', $idDiskonRetailPaket);
        $this->where('IDBARANGSKU', $idBarangSKU);
        $this->where('IDBARANGSATUAN', $idBarangSatuan);

        $result =   $this->get()->getRowObject();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function getListCustomer($idToko, $searchKeyword = null)
    {	
        $this->select("A.IDCUSTOMER, IFNULL(B.NAMA, '-') AS NAMATOKO, A.NAMA AS NAMACUSTOMER, A.ALAMAT, A.TELPON");
        $this->from('m_customer AS A', true);
        $this->join('m_toko AS B', 'A.IDTOKO = B.IDTOKO', 'LEFT');

        if(isset($searchKeyword) && !is_null($searchKeyword)){
            $this->groupStart();
            $this->like('A.NAMA', $searchKeyword, 'both')
            ->orLike('B.NAMA', $searchKeyword, 'both')
            ->orLike('A.ALAMAT', $searchKeyword, 'both')
            ->orLike('A.TELPON', $searchKeyword, 'both');
            $this->groupEnd();
        }
        $this->orderBy("FIELD(A.IDCUSTOMER, 0)");
        $this->limit(200);

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
	}
    
    public function getListDiskonEvent($idToko)
    {	
        $this->select("IDDISKONEVENT, NAMAEVENT, DESKRIPSI, TIPEDISKON, JUMLAHDISKON, ISDISKONPERITEM");
        $this->from('t_diskonevent', true);
        $this->where("JSON_CONTAINS(ARRIDTOKO, JSON_ARRAY($idToko))", null, false);
        $this->where('TANGGALBERLAKUAWAL <=', date('Y-m-d'));
        $this->where('TANGGALBERLAKUAKHIR >=', date('Y-m-d'));
        $this->orderBy('TANGGALBERLAKUAWAL', 'DESC');

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}

    public function getDetailDiskonEvent($idDiskonEvent)
    {	
        $this->select("NAMAEVENT, TIPEDISKON, JUMLAHDISKON, ISDISKONPERITEM");
        $this->from('t_diskonevent', true);
        $this->where('IDDISKONEVENT', $idDiskonEvent);
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return false;
        return $result;
    }

    public function getDaftarDiskonEventPenjualan($idPenjualanRekap)
    {	
        $this->select("B.NAMAEVENT, B.TIPEDISKON, A.NOMINAL, A.KETERANGAN");
        $this->from('t_penjualandiskonevent AS A', true);
        $this->join('t_diskonevent AS B', 'A.IDDISKONEVENT = B.IDDISKONEVENT', 'LEFT');
        $this->where('A.IDPENJUALANREKAP', $idPenjualanRekap);

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return false;
        return $result;
    }

    public function getStokBarangToko($idToko, $idBarangSKU)
    {
        $this->select("B.IDBARANG, CONCAT(D.NAMAMERK, ' ', C.NAMABARANG, ' - ', B.KODESKU) AS NAMABARANG, SUM(A.JUMLAHMASUK - A.JUMLAHKELUAR) AS STOK");
        $this->from('t_tokostok A', true);
        $this->join('m_barangsku B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->join('m_barang C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangmerk D', 'C.IDBARANGMERK = D.IDBARANGMERK', 'LEFT');
        $this->where('A.IDTOKO', $idToko);
        $this->where('A.IDBARANGSKU', $idBarangSKU);
        $this->groupBy('A.IDBARANGSKU');

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return ['NAMABARANG' => '-', 'STOK' => 0];
        return $result;
    }

    public function getHargaBarangSKUToko($idToko, $idBarangSKU, $idBarangSatuan)
    {
        $this->select("CONCAT(D.NAMAMERK, ' ', C.NAMABARANG, ' - ', B.KODESKU) AS NAMABARANG, A.HARGA");
        $this->from('t_baranghargajual A', true);
        $this->join('m_barangsku B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->join('m_barang C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangmerk D', 'C.IDBARANGMERK = D.IDBARANGMERK', 'LEFT');
        $this->where('A.IDTOKO', $idToko);
        $this->where('A.IDBARANGSATUAN', $idBarangSatuan);
        $this->where('A.IDBARANGSKU', $idBarangSKU);
        $this->groupBy('A.IDBARANGSKU');

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return ['NAMABARANG' => '-', 'HARGA' => 0];
        return $result;
    }
}