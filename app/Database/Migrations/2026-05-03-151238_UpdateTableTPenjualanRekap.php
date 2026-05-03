<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTableTPenjualanRekap extends Migration
{
    public function up()
    {
        // Add TOTALHARGADISKONEVENT column to t_penjualanrekap
        $this->forge->addColumn('t_penjualanrekap', [
            'TOTALHARGADISKONEVENT' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
                'default'    => 0,
                'after'      => 'TOTALHARGADISKON',
            ],
        ]);
    }

    public function down()
    {
        // Remove TOTALHARGADISKONEVENT column from t_penjualanrekap
        $this->forge->dropColumn('t_penjualanrekap', 'TOTALHARGADISKONEVENT');
    }
}
