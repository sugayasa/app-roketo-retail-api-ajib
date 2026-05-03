<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterTablePenjualanAddColumnPaket extends Migration
{
    public function up()
    {
        $this->forge->addColumn('t_penjualanbarang', [
            'IDHARGARETAILPAKETSKU' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
                'default'    => 0,
                'after'      => 'IDBARANGSATUAN',
            ],
        ]);

        $this->forge->dropKey('t_penjualanbarang', 'IDPENJUALANREKAP');
        
        $this->forge->addUniqueKey(['IDPENJUALANREKAP', 'IDBARANGSKU', 'IDBARANGSATUAN', 'IDDISKONRETAIL', 'IDDISKONRETAILPAKETNOMINAL', 'IDHARGARETAILPAKETSKU'], 'IDPENJUALANREKAP');
        $this->forge->processIndexes('t_penjualanbarang');
    }

    public function down()
    {
        $this->forge->dropKey('t_penjualanbarang', 'IDPENJUALANREKAP');
        $this->forge->addUniqueKey(['IDPENJUALANREKAP', 'IDBARANGSKU', 'IDBARANGSATUAN', 'IDDISKONRETAIL', 'IDDISKONRETAILPAKETNOMINAL'], 'IDPENJUALANREKAP');
        $this->forge->processIndexes('t_penjualanbarang');
        
        $this->forge->dropColumn('t_penjualanbarang', 'IDHARGARETAILPAKETSKU');
    }
}
