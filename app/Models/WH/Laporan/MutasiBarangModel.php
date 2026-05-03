<?php

namespace App\Models\WH\Laporan;
use CodeIgniter\Model;

class MutasiBarangModel extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 't_gudangstok';
    protected $primaryKey       = 'IDGUDANGSTOK';
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
    
    public function getDetailMutasiBarang($idGudang, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian)
    {	
        $this->select("A.IDBARANGSKU, E.NAMAKATEGORI, D.NAMAMERK, C.NAMABARANG, B.KODESKU, '[]' AS ATRIBUTSKUSTR, B.DESKRIPSI AS DESKRIPSISKU, F.NAMASATUAN,
                    A.JUMLAHMASUK, A.JUMLAHKELUAR, A.MUTASIKETERANGAN, A.INPUTUSER, DATE_FORMAT(A.INPUTTANGGALWAKTU, '%d %b %Y %H:%i') AS TANGGALWAKTUMUTASI");
        $this->from('t_gudangstok A', true);
        $this->join('m_barangsku B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->join('m_barang C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangmerk D', 'C.IDBARANGMERK = D.IDBARANGMERK', 'LEFT');
        $this->join('m_barangkategori E', 'C.IDBARANGKATEGORI = E.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangsatuan F', 'A.IDBARANGSATUAN = F.IDBARANGSATUAN', 'LEFT');
        $this->where('A.IDGUDANG', $idGudang);
        $this->where('DATE(A.INPUTTANGGALWAKTU) >= ', $tanggalAwal);
        $this->where('DATE(A.INPUTTANGGALWAKTU) <=', $tanggalAkhir);
        $this->orderBy('A.INPUTTANGGALWAKTU ASC');

        if(isset($kataKunciPencarian) && !is_null($kataKunciPencarian) && $kataKunciPencarian != ''){
            $this->groupStart();
            $this->like('E.NAMAKATEGORI', $kataKunciPencarian, 'both')
            ->orLike('D.NAMAMERK', $kataKunciPencarian, 'both')
            ->orLike('C.NAMABARANG', $kataKunciPencarian, 'both')
            ->orLike('B.KODESKU', $kataKunciPencarian, 'both')
            ->orLike('B.DESKRIPSI', $kataKunciPencarian, 'both')
            ->orLike('F.NAMASATUAN', $kataKunciPencarian, 'both')
            ->orLike('A.INPUTUSER', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }

        return $this;
	}

    public function getRekapMutasiBarangPerTanggal($idGudang, $tanggalAwal, $tanggalAkhir, $kataKunciPencarian)
    {	
        $this->select("DATE_FORMAT(A.INPUTTANGGALWAKTU, '%d %b') AS TANGGAL, COUNT(DISTINCT(A.IDGUDANGSTOK)) AS JUMLAHMUTASI,
                    IFNULL(SUM(A.JUMLAHMASUK), 0) AS JUMLAHMASUK, IFNULL(SUM(A.JUMLAHKELUAR), 0) AS JUMLAHKELUAR");
        $this->from('t_gudangstok AS A', true);
        $this->join('m_barangsku B', 'A.IDBARANGSKU = B.IDBARANGSKU', 'LEFT');
        $this->join('m_barang C', 'B.IDBARANG = C.IDBARANG', 'LEFT');
        $this->join('m_barangkategori E', 'C.IDBARANGKATEGORI = E.IDBARANGKATEGORI', 'LEFT');
        $this->join('m_barangmerk F', 'C.IDBARANGMERK = F.IDBARANGMERK', 'LEFT');
        $this->join('m_barangsatuan G', 'A.IDBARANGSATUAN = G.IDBARANGSATUAN', 'LEFT');
        $this->where('DATE(A.INPUTTANGGALWAKTU) >= ', $tanggalAwal);
        $this->where('DATE(A.INPUTTANGGALWAKTU) <=', $tanggalAkhir);
        $this->where('A.IDGUDANG', $idGudang);
        $this->groupBy('DATE(A.INPUTTANGGALWAKTU)');
        $this->orderBy('TANGGAL');

        if(isset($kataKunciPencarian) && !is_null($kataKunciPencarian) && $kataKunciPencarian != ''){
            $this->groupStart();
            $this->like('E.NAMAKATEGORI', $kataKunciPencarian, 'both')
            ->orLike('F.NAMAMERK', $kataKunciPencarian, 'both')
            ->orLike('C.NAMABARANG', $kataKunciPencarian, 'both')
            ->orLike('B.KODESKU', $kataKunciPencarian, 'both')
            ->orLike('B.DESKRIPSI', $kataKunciPencarian, 'both')
            ->orLike('G.NAMASATUAN', $kataKunciPencarian, 'both')
            ->orLike('A.INPUTUSER', $kataKunciPencarian, 'both');
            $this->groupEnd();
        }

        $result     =   $this->get()->getResultObject();
        if(is_null($result)) return [];
        return $result;
	}
}