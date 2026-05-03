<?php

namespace App\Models\ERP\UserSettings;
use CodeIgniter\Model;

class UserAdminModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'm_useradmin';
    protected $primaryKey       = 'IDUSERADMIN';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['IDUSERADMINLEVEL', 'NAME', 'EMAIL', 'USERNAME', 'PASSWORD', 'STATUS'];

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
    
    public function getListUserAdmin($searchKeyword)
    {	
        $this->select("IDUSERADMIN, NAME, EMAIL, USERNAME, STATUS, '[]' AS USERADMINAPPLICATIONLEVEL,
                    IF(DATETIMELOGIN IS NULL OR DATETIMELOGIN = '0000-00-00 00:00:00', 'Not Available', DATE_FORMAT(DATETIMELOGIN, '%d %b %Y %H:%i')) AS DATETIMELOGIN,
                    IF(DATETIMEACTIVITY IS NULL OR DATETIMEACTIVITY = '0000-00-00 00:00:00', 'Not Available', DATE_FORMAT(DATETIMEACTIVITY, '%d %b %Y %H:%i')) AS DATETIMEACTIVITY");
        $this->from('m_useradmin', true);
        $this->where('ISPERMANENTUSER', 0);
        if(isset($searchKeyword) && !is_null($searchKeyword)){
            $this->groupStart();
            $this->like('NAME', $searchKeyword, 'both')
            ->orLike('EMAIL', $searchKeyword, 'both')
            ->orLike('USERNAME', $searchKeyword, 'both');
            $this->groupEnd();
        }
        $this->orderBy('NAME');

        $result =   $this->get()->getResultObject();

        if(is_null($result)) return false;
        return $result;
	}

    public function getUserAdminApplicationLevel($idUserAdmin)
    {	
        $this->select("A.IDAPPLICATIONTYPE, A.APPLICATIONTYPE, A.APPLICATIONTYPESHORT, IF(B.IDUSERADMINLEVEL IS NULL, 0, 1) AS ISALLOWAPPLICATION,
                        IFNULL(B.IDUSERADMINLEVEL, 'NULL') AS IDUSERADMINLEVEL, IFNULL(C.LEVELNAME, '') AS LEVELNAME,
                        IFNULL(B.IDGUDANG, 0) AS IDGUDANG, IFNULL(B.IDTOKO, 0) AS IDTOKO");
        $this->from('a_applicationtype AS A', true);
        $this->join('m_useradminapplicationlevel AS B', 'A.IDAPPLICATIONTYPE = B.IDAPPLICATIONTYPE AND B.IDUSERADMIN = '.$idUserAdmin, 'LEFT');
        $this->join('m_useradminlevel AS C', 'B.IDUSERADMINLEVEL = C.IDUSERADMINLEVEL', 'LEFT');
        $this->orderBy('A.IDAPPLICATIONTYPE');

        $result =   $this->get()->getResultObject();

        if(is_null($result)) return [];
        return $result;
	}

    public function isApplicationLevelExist($idUserAdmin, $idApplicationType): mixed
    {	
        $this->select("IDUSERADMINAPPLICATIONLEVEL");
        $this->from('m_useradminapplicationlevel', true);
        $this->where('IDUSERADMIN', $idUserAdmin);
        $this->where('IDAPPLICATIONTYPE', $idApplicationType);
        $this->limit(1);

        $result =   $this->get()->getRowArray();

        if(is_null($result)) return false;
        return $result;
	}
}