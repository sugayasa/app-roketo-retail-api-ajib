<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTableDateTimeNullable extends Migration
{
    public function up()
    {
        // Modify DATETIME columns in m_useradmin to be nullable
        $this->forge->modifyColumn('m_useradmin', [
            'DATETIMELOGIN' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null
            ],
            'DATETIMEACTIVITY' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'DATETIMEEXPIRED' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null
            ]
        ]);

        // Modify TANGGALBATAS column in t_diskonretailpaket to be nullable
        $this->forge->modifyColumn('t_diskonretailpaket', [
            'TANGGALBATAS' => [
                'type'    => 'DATE',
                'null'    => true,
                'default' => null
            ]
        ]);
    }
    public function down()
    {
        $this->forge->modifyColumn('m_useradmin', [
            'DATETIMELOGIN' => [
                'type' => 'DATETIME',
                'null' => false,
                'default' => '0000-00-00 00:00:00'
            ],
            'DATETIMEACTIVITY' => [
                'type' => 'DATETIME',
                'null' => false,
                'default' => '0000-00-00 00:00:00'
            ],
            'DATETIMEEXPIRED' => [
                'type' => 'DATETIME',
                'null' => false,
                'default' => '0000-00-00 00:00:00'
            ]
        ]);

        // Revert TANGGALBATAS column to NOT NULL with default value
        $this->forge->modifyColumn('t_diskonretailpaket', [
            'TANGGALBATAS' => [
                'type'    => 'DATE',
                'null'    => false,
                'default' => '0000-00-00'
            ]
        ]);
    }
}
