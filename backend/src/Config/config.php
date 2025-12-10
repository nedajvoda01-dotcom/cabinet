<?php
// backend/src/Config/config.php

declare(strict_types=1);

return [
    'env' => getenv('APP_ENV') ?: 'dev',
    'debug' => (getenv('APP_DEBUG') ?: '0') === '1',
    'integrations_mode' => getenv('INTEGRATIONS_MODE') ?: 'real',

    'db' => [
        'dsn'  => getenv('DB_DSN')  ?: 'pgsql:host=localhost;port=5432;dbname=autocontent',
        'user' => getenv('DB_USER') ?: 'postgres',
        'pass' => getenv('DB_PASS') ?: 'postgres',
    ],

    'auth' => [
        'jwt_secret' => getenv('JWT_SECRET') ?: 'change_me',
        'access_ttl_sec'  => (int)(getenv('ACCESS_TTL_SEC') ?: 3600),
        'refresh_ttl_sec' => (int)(getenv('REFRESH_TTL_SEC') ?: 2592000),
    ],

    'integrations' => [
        'parser' => [
            'base_url' => getenv('PARSER_BASE_URL') ?: 'http://parser:8080',
            'api_key'  => getenv('PARSER_API_KEY') ?: '',
        ],
        'photo_api' => [
            'base_url' => getenv('PHOTO_API_BASE_URL') ?: 'http://photo-api:8081',
            'api_key'  => getenv('PHOTO_API_KEY') ?: '',
        ],
        'storage' => [
            'bucket'     => getenv('S3_BUCKET') ?: 'autocontent',
            'endpoint'   => getenv('S3_ENDPOINT') ?: 'http://minio:9000',
            'access_key' => getenv('S3_ACCESS_KEY') ?: 'minio',
            'secret_key' => getenv('S3_SECRET_KEY') ?: 'minio123',
            'region'     => getenv('S3_REGION') ?: 'us-east-1',
            'fs_root'    => getenv('S3_FS_ROOT') ?: null, // если SDK нет — путь к on-prem FS
            'path_style' => (getenv('S3_PATH_STYLE') ?: '1') === '1',
        ],
        'dolphin' => [
            'base_url' => getenv('DOLPHIN_BASE_URL') ?: 'http://dolphin:3000',
            'api_key'  => getenv('DOLPHIN_API_KEY') ?: '',
        ],
        'robot' => [
            'base_url' => getenv('ROBOT_BASE_URL') ?: 'http://robot:8090',
            'api_key'  => getenv('ROBOT_API_KEY') ?: '',
        ],
    ],

    'ws' => [
        'enabled' => (getenv('WS_ENABLED') ?: '1') === '1',
    ],

    'workers' => [
        'sleep_ms' => (int)(getenv('WORKER_SLEEP_MS') ?: 300),
        'id' => getenv('WORKER_ID') ?: gethostname(),
    ],
];
