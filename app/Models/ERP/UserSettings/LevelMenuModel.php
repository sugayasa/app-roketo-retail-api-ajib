<?php

namespace App\Models\ERP\UserSettings;
use CodeIgniter\Model;

class LevelMenuModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'm_useradminlevel';
    protected $primaryKey       = 'IDUSERADMINLEVEL';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDUSERADMINLEVEL', 'LEVELNAME', 'DESCRIPTION'];

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
    
    public function isUserLevelAdminExist($userLevelName, $idUserLevel = null) : bool
    {
        $this->select("IDUSERADMINLEVEL");
        $this->from('m_useradminlevel', true);
        $this->where('LEVELNAME', $userLevelName);
        if(!is_null($idUserLevel)) $this->where('IDUSERADMINLEVEL !=', $idUserLevel);
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return false;
        return true;
    }
    
    public function getList($searchKeyword, $idApplicationType = '') : mixed
    {	
        $this->select("A.IDUSERADMINLEVEL, A.IDAPPLICATIONTYPE, B.APPLICATIONTYPE, B.APPLICATIONTYPESHORT, A.LEVELNAME, A.DESCRIPTION");
        $this->from('m_useradminlevel AS A', true);
        $this->join('a_applicationtype AS B', 'A.IDAPPLICATIONTYPE = B.IDAPPLICATIONTYPE', 'LEFT');
        $this->where('A.ISSUPERADMIN', 0);
        if(isset($searchKeyword) && !is_null($searchKeyword)){
            $this->groupStart();
            $this->like('A.LEVELNAME', $searchKeyword, 'both')
            ->orLike('A.DESCRIPTION', $searchKeyword, 'both');
            $this->groupEnd();
        }
        if(isset($idApplicationType) && $idApplicationType != ''){
            $this->where('A.IDAPPLICATIONTYPE', $idApplicationType);
        }
        $this->orderBy('B.APPLICATIONTYPE, A.LEVELNAME');

        $result =   $this->get()->getResultObject();

        if(is_null($result)) return false;
        return $result;
	}

    public function getDetail($idUserAdminLevel, $idApplicationType) : mixed
    {	
        $subQuery   =   $this->db->table('m_menuleveladmin B')
                        ->select('IDMENULEVELADMIN, IDMENUADMIN')
                        ->where('IDUSERADMINLEVEL', $idUserAdminLevel)
                        ->getCompiledSelect();
                        
         $builder   =   $this->db->table('m_menuadmin A')
                        ->select("A.IDMENUADMIN, IFNULL(B.IDMENULEVELADMIN, 0) AS IDMENULEVELADMIN, A.GROUPNAME, A.MENUNAME, A.DESCRIPTION,
                                IF(B.IDMENULEVELADMIN IS NULL, 0, 1) AS ISMENUOPEN, '[]' AS PERMISSIONS")
                        ->join("($subQuery) B", 'A.IDMENUADMIN = B.IDMENUADMIN', 'LEFT')
                        ->where('A.IDAPPLICATIONTYPE', $idApplicationType)
                        ->orderBy("A.ORDERGROUP, A.ORDERMENU, A.MENUNAME");
        $query      =   $builder->get();
        $result     =   $query->getResultObject();

        if(is_null($result)) return false;
        return $result;
	}

    public function getUserAdminMenuPermissions($idUserAdminLevel, $idMenuAdmin)
    {
        $this->select('A.IDMENUADMINPERMISSION, A.PERMISSIONNAME, A.PERMISSIONDESCRIPTION, IFNULL(B.ALLOW, 0) AS ALLOW');
        $this->from('m_menuadminpermission AS A', true);
        $this->join('m_menuleveladminpermission AS B', 'A.IDMENUADMINPERMISSION = B.IDMENUADMINPERMISSION AND B.IDUSERADMINLEVEL = '.$idUserAdminLevel, 'LEFT');
        $this->where('A.IDMENUADMIN', $idMenuAdmin);
        $this->orderBy('A.ORDERNUMBER');

        return $this->get()->getResultObject();
    }

    public function isMenuLevelPermissionExist($idMenuAdminPermission, $idUserAdminLevel) : mixed
    {
        $this->select("IDMENULEVELADMINPERMISSION");
        $this->from('m_menuleveladminpermission', true);
        $this->where('IDMENUADMINPERMISSION', $idMenuAdminPermission);
        $this->where('IDUSERADMINLEVEL', $idUserAdminLevel);
        $this->limit(1);

        $result =   $this->get()->getRowArray();
        if(is_null($result)) return false;
        return $result ?? false;
    }
}