<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTablesDiskonRetailPaket extends Migration
{
    public function up()
    {
        // Create table t_diskonretailpaket
        $this->forge->addField([
            'IDDISKONRETAILPAKET' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => false,
                'auto_increment' => true,
            ],
            'NAMAPAKETDISKON' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'DESKRIPSIPAKETDISKON' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'TANGGALBATAS' => [
                'type'    => 'DATE',
                'default' => '0000-00-00',
            ],
            'INPUTUSER' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
            ],
            'INPUTTANGGALWAKTU' => [
                'type' => 'DATETIME',
            ],
            'STATUS' => [
                'type'       => 'TINYINT',
                'constraint' => 4,
                'default'    => 1,
                'comment'    => '1:AKTIF, -1:KADALUARSA',
            ]
        ]);

        $this->forge->addKey('IDDISKONRETAILPAKET', true);
        $this->forge->addUniqueKey('NAMAPAKETDISKON');

        $this->forge->createTable('t_diskonretailpaket', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_0900_ai_ci',
            'AUTO_INCREMENT' => '900'
        ]);

        // Create table t_diskonretailpaketkondisi
        $this->forge->addField([
            'IDDISKONRETAILPAKETKONDISI' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => false,
                'auto_increment' => true,
            ],
            'IDDISKONRETAILPAKET' => [
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
            'MINIMALJUMLAH' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
        ]);

        $this->forge->addKey('IDDISKONRETAILPAKETKONDISI', true);
        $this->forge->addUniqueKey([
            'IDDISKONRETAILPAKET',
            'IDBARANGSKU',
            'IDBARANGSATUAN'
        ]);

        $this->forge->createTable('t_diskonretailpaketkondisi', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_0900_ai_ci',
            'AUTO_INCREMENT' => '9000',
        ]);

        // Create table t_diskonretailpaketnominal
        $this->forge->addField([
            'IDDISKONRETAILPAKETNOMINAL' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => false,
                'auto_increment' => true,
            ],
            'IDDISKONRETAILPAKET' => [
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
            'TIPEDISKON' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 2,
                'comment'    => '1:PERSENTASE, 2:NOMINAL',
            ],
            'JUMLAHDISKON' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
        ]);

        $this->forge->addKey('IDDISKONRETAILPAKETNOMINAL', true);
        $this->forge->addUniqueKey([
            'IDDISKONRETAILPAKET',
            'IDBARANGSKU',
            'IDBARANGSATUAN'
        ]);

        $this->forge->createTable('t_diskonretailpaketnominal', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_0900_ai_ci',
            'AUTO_INCREMENT' => '9000',
        ]);

        // Add column IDDISKONRETAILPAKETNOMINAL to t_penjualanbarang
        $this->forge->addColumn('t_penjualanbarang', [
            'IDDISKONRETAILPAKETNOMINAL' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
                'default'    => 0,
                'after'      => 'IDDISKONRETAIL',
            ],
        ]);

        // Modify index on t_penjualanbarang - drop old index
        $this->forge->dropKey('t_penjualanbarang', 'IDPENJUALANREKAP');
        
        // Add new composite unique key
        $this->forge->addUniqueKey(['IDPENJUALANREKAP', 'IDBARANGSKU', 'IDBARANGSATUAN', 'IDDISKONRETAIL', 'IDDISKONRETAILPAKETNOMINAL'], 'IDPENJUALANREKAP');
        $this->forge->processIndexes('t_penjualanbarang');
    }

    public function down()
    {
        // Restore original index on t_penjualanbarang
        $this->forge->dropKey('t_penjualanbarang', 'IDPENJUALANREKAP');
        $this->forge->addUniqueKey(['IDPENJUALANREKAP', 'IDBARANGSKU', 'IDBARANGSATUAN', 'IDDISKONRETAIL'], 'IDPENJUALANREKAP');
        $this->forge->processIndexes('t_penjualanbarang');
        
        // Remove column IDDISKONRETAILPAKETNOMINAL from t_penjualanbarang
        $this->forge->dropColumn('t_penjualanbarang', 'IDDISKONRETAILPAKETNOMINAL');
        
        $this->forge->dropTable('t_diskonretailpaketnominal');
        $this->forge->dropTable('t_diskonretailpaketkondisi');
        $this->forge->dropTable('t_diskonretailpaket');
    }
}
