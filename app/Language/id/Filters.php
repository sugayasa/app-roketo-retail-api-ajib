<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

// Filters language settings
return [
    'noFilter'           => 'Filter "{0}" harus memiliki kecocokan dengan alias yang ditetapkan.',
    'incorrectInterface' => '"{0}" harus menerapkan CodeIgniter\Filters\FilterInterface.',
    'Auth' => [
        'before' => [
            'tokenRequired' => 'Token diperlukan',
            'sessionEndPleaseLogin' => 'Sesi berakhir, silakan login untuk melanjutkan',
            'notAllowedPerformActionLoginSessionActive' => 'Anda tidak diizinkan untuk melakukan tindakan ini karena sesi login Anda sudah aktif',
            'loginToPerformAction' => 'Silakan login untuk melakukan tindakan ini',
            'hardwareIdChangeLoginToContinue' => 'Id hardware Anda telah berubah, silakan login untuk melanjutkan',
            'invalidToken' => 'Token tidak valid',
        ],
        'after' => [
            'internalServerError' => 'Kesalahan server internal - Auth',
        ]
    ]
];
