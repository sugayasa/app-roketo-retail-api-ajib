<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMenuHargajualPaket extends Migration
{
    public function up()
    {
        $this->db->table('m_menuadmin')
        ->where('IDAPPLICATIONTYPE', 1)
        ->where('GROUPNAME', 'Stok')
        ->where('ORDERMENU >', 2)
        ->orderBy('ORDERMENU', 'ASC')
        ->increment('ORDERMENU', 1);

        $data   =   [
            'IDAPPLICATIONTYPE' => '1',
            'GROUPNAME'         => 'Stok',
            'MENUNAME'          => 'Pengaturan Harga Jual Paket',
            'DESCRIPTION'       => 'Pengaturan harga jual paket',
            'URL'               => 's-pengaturanHargaJualPaket',
            'ICON'              => 'lucide:money',
            'ORDERGROUP'        => '2',
            'ORDERMENU'         => '3',
            'SUPERADMIN'        => '0',
        ];

        $this->db->table('m_menuadmin')->upsert($data);
    }

    public function down()
    {
        $this->db->table('m_menuadmin')->where('MENUNAME', 'Pengaturan Harga Jual Paket')->delete();
        $this->db->table('m_menuadmin')
        ->where('IDAPPLICATIONTYPE', 1)
        ->where('GROUPNAME', 'Stok')
        ->where('ORDERMENU >', 3)
        ->orderBy('ORDERMENU', 'DESC')
        ->decrement('ORDERMENU', 1);
    }
}
