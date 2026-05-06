<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTableAddIsDiskonPerItem extends Migration
{
    public function up()
    {
        $this->forge->addColumn('t_diskonevent', [
            'ISDISKONPERITEM' => [
                'type'       => 'BOOLEAN',
                'null'       => false,
                'default'    => false,
                'after'      => 'TANGGALBERLAKUAKHIR',
            ],
        ]);

        $this->forge->addColumn('t_penjualandiskonevent', [
            'ISDISKONPERITEM' => [
                'type'       => 'BOOLEAN',
                'null'       => false,
                'default'    => false,
                'after'      => 'IDDISKONEVENT',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('t_diskonevent', 'ISDISKONPERITEM');
        $this->forge->dropColumn('t_penjualandiskonevent', 'ISDISKONPERITEM');
    }
}
