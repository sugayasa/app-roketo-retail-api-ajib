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
        'forbiddenAccess' => 'Forbidden Access',
    ],
    'Access' => [
        'validationLabel' => [
            'userTimeZoneOffset' => 'User Time Zone Offset',
            'password' => 'Password',
            'captcha' => 'Captcha',
            'name' => 'Name',
            'email' => 'Email',
        ],
        'check' => [
            'enterUsernamePassword' => 'Please login, enter your username and password',
            'invalidToken' => 'Invalid Token',
            'hardwareIdChangedLogin' => 'Your hardware Id changed, please login to continue',
            'loginSuccessfullContinue' => 'Login successfully, continue',
            'invalidUserCredentials' => 'Invalid user, unmatched user credentials',
            'sessionEndLoginFirst' => 'Session ends, please log in first',
            'userNotRegisteredLogin' => 'Your credential is not registered. Please log in to continue',
        ],
        'login' => [
            'captchaDoesNotMatch' => 'The captcha code you entered does not match',
            'noMatchingUsername' => 'There are no matching username, enter another username',
            'incorrectPassword' => 'The password you entered is incorrect',
            'loginSuccessfullContinue' => 'Login successfully, continue',
        ],
        'logout' => [
            'invalidToken' => 'Invalid token',
            'logoutSuccessfull' => 'Logout successfully',
        ],
        'detailProfile' => [
            'userDetailsNotFound' => 'Your user details not found'
        ],
        'saveDetailProfile' => [
            'pleaseEnterOldPassword' => 'Please enter your old password (your current password)',
            'pleaseEnterNewPassword' => 'Please enter a new password',
            'pleaseEnterNewPasswordRepetition' => 'Please enter a new password repetition',
            'passwordRepetitionNotMatch' => 'The repetition of the password you entered is not match',
            'userDataNotFoundTryAgain' => 'Your user data was not found, please try again later',
            'oldPasswordIncorrect' => 'The old password you entered is incorrect',
            'userDataUpdated' => 'Your user data has been updated',
        ]
    ]
];
