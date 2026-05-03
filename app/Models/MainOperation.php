<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Libraries\FirebaseRTDB;

class MainOperation extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'ci_sessions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ip_address', 'timestamp', 'data'];

    public function execQueryWithLimit($queryString, $page, $dataPerPage)
    {
		$startid    =	($page * 1 - 1) * $dataPerPage;
        $query      =   $this->query($queryString." LIMIT ".$startid.", ".$dataPerPage);

        return $query->getResult();
    }

    public function generateResultPagination($result, $basequery, $keyfield, $page, $dataperpage)
    {
        $startid	=	($page * 1 - 1) * $dataperpage;
		$datastart	=	$startid + 1;
		$dataend	=	$datastart + $dataperpage - 1;
		$query      =   $this->query("SELECT IFNULL(COUNT(".$keyfield."), 0) AS TOTAL FROM (".$basequery.") AS A");
		
		$row		=	$query->getRow();
		$datatotal	=	$row->TOTAL;
		$pagetotal	=	ceil($datatotal/$dataperpage);
		$datastart	=	$pagetotal == 0 ? 0 : $startid + 1;
		$startnumber=	$pagetotal == 0 ? 0 : ($page-1) * $dataperpage + 1;
		$dataend	=	$dataend > $datatotal ? $datatotal : $dataend;

		return ["data"=>$result ,"dataStart"=>$datastart, "dataEnd"=>$dataend, "dataTotal"=>$datatotal, "pageTotal"=>$pagetotal, "startNumber"=>$startnumber];
    }

    public function generatePageProperty($page, $dataPerPage, $dataNumberTotal) : array
    {
        if($dataNumberTotal == 0) return $this->generateEmptyPageProperty();
        
        $dataNumberStart=	($page * 1 - 1) * $dataPerPage + 1;
		$dataNumberEnd	=	$dataNumberStart + $dataPerPage - 1;

		$pageTotal      =	ceil($dataNumberTotal/$dataPerPage);
		$dataNumberStart=	$pageTotal == 0 ? 0 : $dataNumberStart;
		$dataNumberEnd	=	$dataNumberEnd > $dataNumberTotal ? $dataNumberTotal : $dataNumberEnd;
        return ["dataNumberStart"=>$dataNumberStart, "dataNumberEnd"=>$dataNumberEnd, "dataNumberTotal"=>$dataNumberTotal, "pageTotal"=>$pageTotal];
    }

    public function generateEmptyPageProperty() : array
    {
        return ["dataNumberStart"=>0, "dataNumberEnd"=>0, "dataNumberTotal"=>0, "pageTotal"=>0];
    }

	public function generateEmptyResult()
    {
		return ["data"=>[], "datastart"=>0, "dataend"=>0, "datatotal"=>0, "pagetotal"=>0];
	}

    public function getColumnData($tableName, $columnName, $where = [])
    {
        $db     =   \Config\Database::connect();
        $table  =   $db->table($tableName);
        $table->select($columnName);
        
        if(count($where) > 0){
            foreach($where as $field => $value){
                if(is_array($value)){
                    $table->whereIn($field, $value);
                } else {
                    $table->where($field, $value);
                }
            }
        }

        $query  =   $table->get();
        return $query->getResultObject();
    }

    public function isDataExist($tableName, $arrField)
    {
        $db   =   \Config\Database::connect();
        $table=   $db->table($tableName);
        foreach($arrField as $field => $value){
            if(is_array($value)){
                $table->whereIn($field, $value);
            } else {
                $table->where($field, $value);
            }
        }
        
        $query  =   $table->get();
        return $query->getNumRows() > 0 ? $query->getRowArray() : false;
    }

    public function insertDataTable($tableName, $arrInsert)
    {
        $db     =   \Config\Database::connect();
        try {
            $table  =   $db->table($tableName);
            foreach($arrInsert as $field => $value){
                $table->set($field, $value);
            }
            $table->insert();

            $insertID       =   $db->insertID();
            $affectedRows   =   $db->affectedRows();

            if($insertID > 0 || $affectedRows > 0) return ["status"=>true, "errCode"=>false, "insertID"=>$insertID];
            return ["status"=>false, "errCode"=>1329];
        } catch (\Throwable $th) {
            $error		    =	$db->error();
            $errorCode	    =	$error['code'] == 0 ? 1329 : $error['code'];
            return ["status"=>false, "errCode"=>$errorCode, "errorMessages"=>$th];
        }
    }

    public function insertDataBatchTable($tableName, $arrInsert)
    {
        $db     =   \Config\Database::connect();
        try {
            $table          =   $db->table($tableName);
            $table->insertBatch($arrInsert);
            $affectedRows   =   $db->affectedRows();

            if($affectedRows > 0) return ["status"=>true, "errCode"=>false];
            return ["status"=>false, "errCode"=>1329];
        } catch (\Throwable $th) {
            $error		    =	$db->error();
            $errorCode	    =	$error['code'] == 0 ? 1329 : $error['code'];
            return ["status"=>false, "errCode"=>$errorCode, "errorMessages"=>$th->getMessage()];
        }
    }

    public function updateDataTable($tableName, $arrUpdate, $arrWhere)
    {
        $db     =   \Config\Database::connect();
        try {
            $table  =   $db->table($tableName);
            foreach($arrUpdate as $field => $value){
                $table->set($field, $value);
            }

            foreach($arrWhere as $field => $value){
                if(is_array($value)){
                    $table->whereIn($field, $value);
                } else {
                    $table->where($field, $value);
                }
            }
            $table->update();

            $affectedRows   =   $db->affectedRows();
            if($affectedRows > 0) return ["status"=>true, "errCode"=>false];
            return ["status"=>false, "errCode"=>1329, "queryString"=>$db->getLastQuery()];
        } catch (\Throwable $th) {
            $error		    =	$db->error();
            $errorCode	    =	$error['code'] == 0 ? 1329 : $error['code'];
            return ["status"=>false, "error"=>$error, "errCode"=>$errorCode, "errorMessages"=>$th, "queryString"=>$db->getLastQuery()];
        }
        return ["status"=>false, "errCode"=>false];
    }

    public function deleteDataTable($tableName, $arrWhere)
    {
        $db     =   \Config\Database::connect();
        try {
            $table  =   $db->table($tableName);

            foreach($arrWhere as $field => $value){
                if(is_array($value)){
                    $table->whereIn($field, $value);
                } else {
                    $table->where($field, $value);
                }
            }
            $table->delete();

            $affectedRows   =   $db->affectedRows();
            if($affectedRows > 0) return ["status"=>true, "affectedRows"=>$affectedRows];
            return ["status"=>false, "errCode"=>1329];
        } catch (\Throwable $th) {
            $error		    =	$db->error();
            $errorCode	    =	$error['code'] == 0 ? 1329 : $error['code'];
            return ["status"=>false, "errCode"=>$errorCode, "errorMessages"=>$th, "error"=>$error];
        }
    }

    public function getDataSystemSetting($idSystemSetting)
    {	
        $this->select("DATASETTING");
        $this->from('a_systemsettings', true);
        $this->where('IDSYSTEMSETTINGS', $idSystemSetting);
        $this->limit(1);

        $result =   $this->first();

        if(is_null($result)) return '[]';
        return $result['DATASETTING'];
    }

    public function getIdGudangParentToko($idToko)
    {	
        $this->select("IDGUDANG");
        $this->from('m_toko', true);
        $this->where('IDTOKO', $idToko);
        $this->limit(1);

        $result =   $this->first();

        if(is_null($result)) return 0;
        return $result['IDGUDANG'];
    }

    public function getDetailProdusenDistributor($idProdusenDistributor)
    {	
        $this->select("ARRIDBARANGMERK, TIPEPRODUSENDISTRIBUTOR, KODEPRODUSENDISTRIBUTOR, NAMA, TELPON, ALAMAT, CATATAN");
        $this->from('m_produsendistributor', true);
        $this->where('IDPRODUSENDISTRIBUTOR', $idProdusenDistributor);
        $this->limit(1);

        $result =   $this->first();

        if(is_null($result)) return [
            'ARRIDBARANGMERK'           =>  '[]',
            'TIPEPRODUSENDISTRIBUTOR'   => 1,
            'KODEPRODUSENDISTRIBUTOR'   => '-',
            'NAMA'                      =>  '-',
            'TELPON'                    =>  '-',
            'ALAMAT'                    =>  '-',
            'CATATAN'                   =>  '-',
        ];
        return $result;
    }

    public function getDetailGudang($idGudang)
    {	
        $this->select("A.KODE, A.NAMAPERUSAHAAN, A.NAMA, A.ALAMAT, A.KOTA, A.PROVINSI, B.NAME AS NAMAKEPALAGUDANG,
                    IF(A.LOGO = '' OR A.LOGO IS NULL, 'default-logo.png', A.LOGO) AS LOGO");
        $this->from('m_gudang A', true);
        $this->join('m_useradmin AS B', 'A.IDUSERADMINKEPALAGUDANG = B.IDUSERADMIN', 'LEFT');
        $this->where('A.IDGUDANG', $idGudang);
        $this->limit(1);

        $result =   $this->first();

        if(is_null($result)) return [
            'KODE'              =>  '-',
            'NAMAPERUSAHAAN'    =>  '-',
            'NAMA'              =>  '-',
            'ALAMAT'            =>  '-',
            'NAMAKEPALAGUDANG'  =>  '-',
            'LOGO'              =>  'default-logo.png'
        ];
        return $result;
    }

    public function getDetailToko($idToko)
    {	
        $this->select("A.IDGUDANG, A.IDKELOMPOKHARGAGROSIR, B.NAMA AS NAMAGUDANG, C.NAME AS NAMAKEPALATOKO, A.KODE, A.NAMA, A.ALAMAT");
        $this->from('m_toko A', true);
        $this->join('m_gudang AS B', 'A.IDGUDANG = B.IDGUDANG', 'LEFT');
        $this->join('m_useradmin AS C', 'A.IDUSERADMINKEPALATOKO = C.IDUSERADMIN', 'LEFT');
        $this->where('A.IDTOKO', $idToko);
        $this->limit(1);

        $result =   $this->first();

        if(is_null($result)) return [
            'IDGUDANG'              =>  0,
            'IDKELOMPOKHARGAGROSIR' =>  0,
            'NAMAGUDANG'            =>  '-',
            'NAMAKEPALATOKO'        =>  '-',
            'KODE'                  =>  '-',
            'NAMA'                  =>  '-',
            'ALAMAT'                =>  '-'
        ];
        return $result;
    }

    public function getDetailMetodeBayar($idMetodeBayar)
    {	
        $this->select("METODEBAYAR, KETERANGAN, URUTAN");
        $this->from('a_metodebayar', true);
        $this->where('IDMETODEBAYAR', $idMetodeBayar);
        $this->limit(1);

        $result =   $this->first();

        if(is_null($result)) return [
            'METODEBAYAR'    =>  '-',
            'KETERANGAN'     =>  '-',
            'URUTAN'         =>  '-'
        ];
        return $result;
    }

    public function getDetailBarangKategori($idBarangKategori)
    {	
        $this->select("NAMAKATEGORI, DESKRIPSI");
        $this->from('m_barangkategori', true);
        $this->where('IDBARANGKATEGORI', $idBarangKategori);
        $this->limit(1);

        $result =   $this->first();

        if(is_null($result)) return [
            'NAMAKATEGORI'  =>  '-',
            'DESKRIPSI'     =>  '-'
        ];
        return $result;
    }

    public function getDetailBarangMerk($idBarangMerk)
    {	
        $this->select("NAMAMERK, DESKRIPSI");
        $this->from('m_barangmerk', true);
        $this->where('IDBARANGMERK', $idBarangMerk);
        $this->limit(1);

        $result =   $this->first();

        if(is_null($result)) return [
            'NAMAMERK'  =>  '-',
            'DESKRIPSI' =>  '-'
        ];
        return $result;
    }

    public function getDetailBarangSKU($idBarangSKU)
    {	
        $this->select("A.KODESKU, D.NAMAKATEGORI, C.NAMAMERK, B.NAMABARANG, CONCAT(C.NAMAMERK, ' ', B.NAMABARANG, ' - ', A.KODESKU) AS NAMABARANGFULL, A.DESKRIPSI");
        $this->from('m_barangsku A', true);
        $this->join('m_barang B', 'A.IDBARANG = B.IDBARANG', 'LEFT');
        $this->join('m_barangmerk C', 'B.IDBARANGMERK = C.IDBARANGMERK', 'LEFT');
        $this->join('m_barangkategori D', 'B.IDBARANGKATEGORI = D.IDBARANGKATEGORI', 'LEFT');
        $this->where('A.IDBARANGSKU', $idBarangSKU);
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return [
            'KODESKU'       => '-',
            'NAMAKATEGORI'  => '-',
            'NAMAMERK'      => '-',
            'NAMABARANG'    => '-',
            'NAMABARANGFULL'=> '-',
            'DESKRIPSI'     => '-'

        ];
        return $result;
    }

    public function getArrIdKategoriBarangToko($idToko)
    {	
        $this->select("ARRIDBARANGKATEGORI");
        $this->from('m_toko', true);
        $this->where('IDTOKO', $idToko);
        $this->limit(1);

        $result =   $this->first();

        if(is_null($result)) return [0];
        return json_decode($result['ARRIDBARANGKATEGORI']);
    }

    public function getKodeSatuanById($idBarangSatuan)
    {	
        $this->select("KODESATUAN");
        $this->from('m_barangsatuan', true);
        $this->where('IDBARANGSATUAN', $idBarangSatuan);
        $this->limit(1);

        $result =   $this->first();

        if(is_null($result)) return 'PCS';
        return $result['KODESATUAN'];
    }
}