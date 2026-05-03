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
    'Auth' => [
        'before' => [
            'tokenRequired' => 'Token is required',
            'sessionEndPleaseLogin' => 'Session ends, please log in to continue',
            'notAllowedPerformActionLoginSessionActive' => 'You are not allowed to perform this action because your login session is already active',
            'loginToPerformAction' => 'Please log in to perform this action',
            'hardwareIdChangeLoginToContinue' => 'Your hardware ID has changed, please log in to continue',
            'invalidToken' => 'Invalid Token',
        ],
        'after' => [
            'internalServerError' => 'Internal server error - Auth',
        ]
    ]
];
