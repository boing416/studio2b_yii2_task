<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'modules' => [
//        'user' => [
//            'class' => 'dektrium\user\Module',
//            'enableUnconfirmedLogin' => true,
//            'confirmWithin' => 21600,
//            'cost' => 12,
//            'admins' => ['admin']
//        ],
    ],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-frontend',
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-frontend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'advanced-frontend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'authClientCollection' => [
            'class' => 'yii\authclient\Collection',
            'clients' => [
                'facebook' => [
                    'class' => 'yii\authclient\clients\Facebook',
                    'clientId' => '1849923741937329',
                    'clientSecret' => '27d46925639fece777a3cc019937d9d1',
                ],
//                'google' => [
//                    'class' => 'yii\authclient\clients\GoogleOAuth',
//                    'clientId' => 'google_client_id',
//                    'clientSecret' => 'google_client_secret',
//                ],
//                'twitter' => [
//                    'class' => 'yii\authclient\clients\Twitter',
//                    'consumerKey' => 'twitter_consumer_key',
//                    'consumerSecret' => 'twitter_consumer_secret',
//                ],
            ],
        ],

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
        
    ],
    'params' => $params,
];
