<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTableTDiskonEvent extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'IDDISKONEVENT' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => false,
                'auto_increment' => true,
            ],
            'ARRIDTOKO' => [
                'type' => 'JSON',
                'null' => false,
            ],
            'NAMAEVENT' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
            ],
            'DESKRIPSI' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => false,
            ],
            'TIPEDISKON' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'comment'    => '1:PERSENTASE, 2:NOMINAL',
            ],
            'JUMLAHDISKON' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'TANGGALBERLAKUAWAL' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'TANGGALBERLAKUAKHIR' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'INPUTUSER' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => false,
            ],
            'INPUTTANGGALWAKTU' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('IDDISKONEVENT', true);
        $this->forge->addUniqueKey('NAMAEVENT');

        $this->forge->createTable('t_diskonevent', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_0900_ai_ci',
            'AUTO_INCREMENT' => '3000',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('t_diskonevent');
    }
}
