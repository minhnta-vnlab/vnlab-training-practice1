<?php
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;
use backend\utils\twofa\TwoFAVerifier;
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-backend',
        ],
        'user' => [
            'identityClass' => 'backend\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'advanced-backend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                [
                    'class' => 'yii\rest\UrlRule', 
                    'controller' => ['user', 'login-history'], 
                    'prefix' => 'api'
                ],
                'POST api/auth/login' => 'auth/login', // Route for login
                'POST api/auth/register' => 'auth/register', // Route for registration,
                'POST api/auth/verify' => 'auth/verify', // Route for verification
                'POST api/auth/unlock' => 'auth/unlock',
                'GET api/user/two-factor-qr' => 'user/two-factor-qr',
                'PUT api/user/update-two-fa' => 'user/update-two-fa',
            ],
        ],
        'tfa' => new TwoFactorAuth(new EndroidQrCodeProvider()),
        'twoFAVerifier' => new TwoFAVerifier()
    ],
    'params' => $params,
];
