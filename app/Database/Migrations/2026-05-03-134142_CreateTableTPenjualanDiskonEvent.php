<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTPenjualanDiskonEvent extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'IDPENJUALANDISKONEVENT' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => false,
                'auto_increment' => true,
            ],
            'IDPENJUALANREKAP' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'IDDISKONEVENT' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'NOMINAL' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'KETERANGAN' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => false,
            ],
        ]);

        $this->forge->addKey('IDPENJUALANDISKONEVENT', true);
        $this->forge->addUniqueKey(['IDPENJUALANREKAP', 'IDDISKONEVENT']);

        $this->forge->createTable('t_penjualandiskonevent', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_0900_ai_ci',
            'AUTO_INCREMENT' => '300',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('t_penjualandiskonevent');
    }
}
