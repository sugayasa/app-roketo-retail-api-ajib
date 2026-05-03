<?php

namespace App\Models;

use CodeIgniter\Model;
use PHPUnit\Framework\Constraint\IsNull;

class AccessModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'm_useradmin';
    protected $primaryKey       = 'IDUSERADMIN';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['NAME', 'EMAIL', 'USERNAME', 'PASSWORD', 'HARDWAREID', 'REDIRECTTOKEN', 'DATETIMELOGIN', 'DATETIMEACTIVITY', 'DATETIMEEXPIRED', 'STATUS'];

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

    public function checkHardwareIDUserAdmin($idUserAdmin, $hardwareID)
    {
        $this->select('IDUSERADMIN')->from('m_useradmin', true);
        $this->where('IDUSERADMIN', $idUserAdmin);
        $this->where('HARDWAREID', $hardwareID);

        if(is_null($this->get()->getRowArray())) return false;
        return true;
    }

    public function getUserAdminDetail($idUserAdmin, $idApplicationType)
    {
        $this->select('A.HARDWAREID, B.IDUSERADMINLEVEL, A.NAME, A.USERNAME, A.EMAIL, C.LEVELNAME');
        $this->from('m_useradmin AS A', true);
        $this->join('m_useradminapplicationlevel AS B', 'A.IDUSERADMIN = B.IDUSERADMIN AND B.IDAPPLICATIONTYPE = ' . $idApplicationType, 'LEFT');
        $this->join('m_useradminlevel AS C', 'B.IDUSERADMINLEVEL = C.IDUSERADMINLEVEL', 'LEFT');
        $this->where('A.IDUSERADMIN', $idUserAdmin);

        return $this->get()->getRowArray();
    }

    public function getApplicationList($idUserAdmin)
    {
        $this->select('A.IDAPPLICATIONTYPE AS ID, B.APPLICATIONTYPESHORT AS VALUE, B.APPLICATIONURL AS URL');
        $this->from('m_useradminapplicationlevel AS A', true);
        $this->join('a_applicationtype AS B', 'A.IDAPPLICATIONTYPE = B.IDAPPLICATIONTYPE', 'LEFT');
        $this->where("A.IDUSERADMIN", $idUserAdmin);

        return $this->get()->getResultObject();
    }

    public function getUserAdminMenu($idUserAdminLevel)
    {
        $this->select("A.IDMENUADMIN, B.GROUPNAME, B.MENUNAME, B.DESCRIPTION, B.URL, B.ICON, '[]' AS PERMISSIONS");
        $this->from('m_menuleveladmin AS A', true);
        $this->join('m_menuadmin AS B', 'A.IDMENUADMIN = B.IDMENUADMIN', 'LEFT');
        $this->where('A.IDUSERADMINLEVEL', $idUserAdminLevel);
        $this->orderBy('B.ORDERGROUP, B.ORDERMENU');

        return $this->get()->getResultObject();
    }

    public function getUserAdminMenuPermissions($idUserAdminLevel, $idMenuAdmin)
    {
        $this->select('A.PERMISSIONCODE, B.ALLOW');
        $this->from('m_menuadminpermission AS A', true);
        $this->join('m_menuleveladminpermission AS B', 'A.IDMENUADMINPERMISSION = B.IDMENUADMINPERMISSION', 'LEFT');
        $this->where('A.IDMENUADMIN', $idMenuAdmin);
        $this->where('B.IDUSERADMINLEVEL', $idUserAdminLevel);
        $this->orderBy('A.ORDERNUMBER');

        return $this->get()->getResultObject();
    }

    public function getDataApplicationType()
    {
        $this->select('IDAPPLICATIONTYPE AS ID, APPLICATIONTYPE AS VALUE, APPLICATIONTYPESHORT AS VALUESHORT');
        $this->from('a_applicationtype', true);
        $this->orderBy('IDAPPLICATIONTYPE');

        return $this->get()->getResultObject();
    }

    public function getDataMetodeBayar()
    {
        $this->select('IDMETODEBAYAR AS ID, METODEBAYAR AS VALUE, KETERANGAN');
        $this->from('a_metodebayar', true);
        $this->orderBy('URUTAN');

        return $this->get()->getResultObject();
    }

    public function getDataCaraPelunasan()
    {
        $this->select('IDCARAPELUNASAN AS ID, CARAPELUNASAN AS VALUE, KETERANGAN');
        $this->from('a_carapelunasan', true);
        $this->orderBy('URUTAN');

        return $this->get()->getResultObject();
    }

    public function getDataUserAdminLevel()
    {
        $this->select('IDAPPLICATIONTYPE, IDUSERADMINLEVEL AS ID, LEVELNAME AS VALUE');
        $this->from('m_useradminlevel', true);
        $this->orderBy('LEVELNAME');

        return $this->get()->getResultObject();
    }

    public function getDataUserAdminGudang()
    {
        $this->select('A.IDUSERADMIN AS ID, A.NAME AS VALUE');
        $this->from('m_useradmin A', true);
        $this->join('m_useradminapplicationlevel AS B', 'A.IDUSERADMIN = B.IDUSERADMIN', 'LEFT');
        $this->where('B.IDAPPLICATIONTYPE', 2); // Assuming 2 is the ID for Gudang application type
        $this->where('A.STATUS', 1); // Only active users
        $this->groupBy('A.IDUSERADMIN');
        $this->orderBy('A.NAME');

        return $this->get()->getResultObject();
    }

    public function getDataUserAdminToko()
    {
        $this->select('A.IDUSERADMIN AS ID, A.NAME AS VALUE');
        $this->from('m_useradmin A', true);
        $this->join('m_useradminapplicationlevel AS B', 'A.IDUSERADMIN = B.IDUSERADMIN', 'LEFT');
        $this->where('B.IDAPPLICATIONTYPE', 3); // Assuming 3 is the ID for Toko application type
        $this->where('A.STATUS', 1); // Only active users
        $this->groupBy('A.IDUSERADMIN');
        $this->orderBy('A.NAME');

        return $this->get()->getResultObject();
    }

    public function getDataBarangSatuan()
    {
        $this->select('IDBARANGSATUAN AS ID, CONCAT("[", KODESATUAN, "] ", NAMASATUAN) AS VALUE');
        $this->from('m_barangsatuan', true);
        $this->orderBy('KODESATUAN, NAMASATUAN');

        return $this->get()->getResultObject();
    }

    public function getDataBarangMerk()
    {
        $this->select('IDBARANGMERK AS ID, NAMAMERK AS VALUE');
        $this->from('m_barangmerk', true);
        $this->orderBy('NAMAMERK');

        return $this->get()->getResultObject();
    }

    public function getDataBarangKategori($arrIdKategoriBarang)
    {
        $this->select('IDBARANGKATEGORI AS ID, NAMAKATEGORI AS VALUE');
        $this->from('m_barangkategori', true);
        if(!empty($arrIdKategoriBarang)) $this->whereIn('IDBARANGKATEGORI', $arrIdKategoriBarang);
        $this->orderBy('NAMAKATEGORI');

        return $this->get()->getResultObject();
    }

    public function getDataBarangAtribut()
    {
        $this->select("IDBARANGATRIBUT AS ID, CONCAT('[', KODEATRIBUT, '] ', NAMAATRIBUT) AS VALUE");
        $this->from('m_barangatribut', true);
        $this->orderBy('KODEATRIBUT');

        return $this->get()->getResultObject();
    }

    public function getDataBarangSKU($arrIdKategoriBarang = [])
    {
        $this->select("IDBARANGSKU AS ID, CONCAT('[', C.NAMAKATEGORI, '] [', D.NAMAMERK, '] ', A.KODESKU, ' - ', A.DESKRIPSI) AS VALUE");
        $this->from('m_barangsku A', true);
        $this->join('m_barang AS B', 'A.IDBARANG = B.IDBARANG', 'LEFT');
        $this->join('m_barangkategori AS C', 'B.IDBARANGKATEGORI = C.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk AS D', 'B.IDBARANGMERK = D.IDBARANGMERK', 'LEFT');
        if(!empty($arrIdKategoriBarang)) $this->whereIn('B.IDBARANGKATEGORI', $arrIdKategoriBarang);
        $this->orderBy('C.NAMAKATEGORI, D.NAMAMERK, A.KODESKU');

        return $this->get()->getResultObject();
    }

    public function getDataProdusenDistributor()
    {
        $this->select("IDPRODUSENDISTRIBUTOR AS ID, CONCAT('[', IF(TIPEPRODUSENDISTRIBUTOR = 1, 'Produsen', 'Distributor'), '] ', NAMA) AS VALUE");
        $this->from('m_produsendistributor', true);
        $this->orderBy('TIPEPRODUSENDISTRIBUTOR, NAMA');

        return $this->get()->getResultObject();
    }

    public function getDataGudang()
    {
        $this->select("IDGUDANG AS ID, CONCAT('[', KODE, '] ', NAMA) AS VALUE");
        $this->from('m_gudang', true);
        $this->orderBy('KODE');

        return $this->get()->getResultObject();
    }

    public function getDataToko()
    {
        $this->select("A.IDTOKO AS ID, CONCAT('[', C.KODE, '] [', IF(A.STATUSEKSTERNAL = 1, 'Eksternal', 'Internal'), '] [', A.KODE, '] ', A.NAMA) AS VALUE, A.ALAMAT,
                    IFNULL(B.NAME, '-') AS NAMAKEPALATOKO, C.NAMA AS NAMAGUDANG, D.KELOMPOKHARGAGROSIR");
        $this->from('m_toko AS A', true);
        $this->join('m_useradmin AS B', 'A.IDUSERADMINKEPALATOKO = B.IDUSERADMIN', 'LEFT');
        $this->join('m_gudang AS C', 'A.IDGUDANG = C.IDGUDANG', 'LEFT');
        $this->join('m_kelompokhargagrosir AS D', 'A.IDKELOMPOKHARGAGROSIR = D.IDKELOMPOKHARGAGROSIR', 'LEFT');
        $this->orderBy('C.NAMA, A.STATUSEKSTERNAL, A.KODE');

        return $this->get()->getResultObject();
    }

    public function getDataKelompokHargaGrosir()
    {
        $this->select("IDKELOMPOKHARGAGROSIR AS ID, KELOMPOKHARGAGROSIR AS VALUE");
        $this->from('m_kelompokhargagrosir', true);
        $this->orderBy('KELOMPOKHARGAGROSIR');

        return $this->get()->getResultObject();
    }

    public function setHardwareIdNull($hardwareID) : bool
    {
        $baseQuery  =   "UPDATE m_useradmin SET HARDWAREID = NULL WHERE HARDWAREID = ?";
        $this->query($baseQuery, [$hardwareID]);
        return $this->affectedRows() > 0;
    }

    public function setLastActivityUserAdmin($idUserAdmin, $datetimeActivity)
    {
        $this->set('DATETIMEACTIVITY', $datetimeActivity);
        $this->where('IDUSERADMIN', $idUserAdmin);
        $this->update();
    }
}
