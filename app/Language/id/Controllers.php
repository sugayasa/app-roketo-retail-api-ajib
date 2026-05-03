<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

// Controller language settings
return [
    'Global' => [
        'forbiddenAccess' => 'Akses Terlarang',
    ],
    'Access' => [
        'validationLabel' => [
            'userTimeZoneOffset' => 'Zona Waktu Pengguna',
            'password' => 'Kata Sandi',
            'captcha' => 'Kode Captcha',
            'name' => 'Nama',
            'email' => 'Alamat Email'
        ],
        'check' => [
            'enterUsernamePassword' => 'Harap login dengan masukkan username dan kata sandi',
            'invalidToken' => 'Token tidak valid',
            'hardwareIdChangedLogin' => 'Hardware Id anda berubah, harap login untuk melanjutkan',
            'loginSuccessfullContinue' => 'Proses login berhasil, lanjutkan',
            'invalidUserCredentials' => 'Pengguna tidak valid, kredensial pengguna tidak cocok',
            'sessionEndLoginFirst' => 'Sesi berakhir, silakan login terlebih dahulu',
            'userNotRegisteredLogin' => 'Kredensial Anda belum terdaftar. Silakan masuk untuk melanjutkan',
        ],
        'login' => [
            'captchaDoesNotMatch' => 'Kode captcha yang anda masukkan tidak cocok',
            'noMatchingUsername' => 'Tidak ada username yang cocok, masukkan username lainnya',
            'incorrectPassword' => 'Password yang anda masukkan salah',
            'loginSuccessfullContinue' => 'Login berhasil, lanjutkan',
        ],
        'logout' => [
            'invalidToken' => 'Token tidak valid',
            'logoutSuccessfull' => 'Logout berhasil',
        ],
        'detailProfile' => [
            'userDetailsNotFound' => 'Detail pengguna tidak ditemukan'
        ],
        'saveDetailProfile' => [
            'pleaseEnterOldPassword' => 'Harap masukkan kata sandi lama anda (kata sandi anda sekarang)',
            'pleaseEnterNewPassword' => 'Harap masukkan kata sandi baru',
            'pleaseEnterNewPasswordRepetition' => 'Harap masukkan pengulangan kata sandi baru',
            'passwordRepetitionNotMatch' => 'Pengulangan kata sandi yang anda masukkan tidak cocok',
            'userDataNotFoundTryAgain' => 'Your user data was not found, please try again later',
            'oldPasswordIncorrect' => 'The old password you entered is incorrect',
            'userDataUpdated' => 'Your user data has been updated',
        ]
    ]
];
