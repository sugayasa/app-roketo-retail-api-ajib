<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTablesHargaRetailPaket extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'IDHARGARETAILPAKET' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => false,
                'auto_increment' => true,
            ],
            'IDTOKO' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'NAMAHARGARETAILPAKET' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'DESKRIPSI' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'JUMLAHBARANG' => [
                'type'       => 'SMALLINT',
                'constraint' => 6,
            ],
            'STATUS' => [
                'type'       => 'TINYINT',
                'constraint' => 4,
                'default'    => 1,
                'comment'    => '1:AKTIF, -1:NON AKTIF',
            ]
        ]);

        $this->forge->addKey('IDHARGARETAILPAKET', true);
        $this->forge->addUniqueKey(['IDTOKO', 'NAMAHARGARETAILPAKET']);

        $this->forge->createTable('t_hargaretailpaket', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_0900_ai_ci',
        ]);

        $this->forge->addField([
            'IDHARGARETAILPAKETSKU' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => false,
                'auto_increment' => true,
            ],
            'IDHARGARETAILPAKET' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'IDBARANGSKU' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'IDBARANGSATUAN' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'JUMLAH' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'HARGA' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
        ]);

        $this->forge->addKey('IDHARGARETAILPAKETSKU', true);
        $this->forge->addUniqueKey([
            'IDHARGARETAILPAKET',
            'IDBARANGSKU',
            'IDBARANGSATUAN'
        ]);

        $this->forge->createTable('t_hargaretailpaketsku', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_0900_ai_ci',
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('t_hargaretailpaketsku');
        $this->forge->dropTable('t_hargaretailpaket');
    }
}
