<?php
// OAuth 三方登入憑證（讀取環境變數，避免 secret 進 git）
return [
    'google' => [
        'client_id'     => getenv('GOOGLE_CLIENT_ID') ?: '',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
        'redirect_uri'  => getenv('OAUTH_REDIRECT_URI') ?: 'http://localhost:5173/auth/callback',
    ],
    'line' => [
        'channel_id'     => getenv('LINE_CHANNEL_ID') ?: '',
        'channel_secret' => getenv('LINE_CHANNEL_SECRET') ?: '',
        'redirect_uri'   => getenv('OAUTH_REDIRECT_URI') ?: 'http://localhost:5173/auth/callback',
    ],
];
