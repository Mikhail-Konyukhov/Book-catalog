<?php

/**
 * Password equals the username; the hashes are the ones shipped with yii2-app-basic,
 * so the template login tests keep working against the user table.
 */

declare(strict_types=1);

return [
    'admin' => [
        'id' => 100,
        'username' => 'admin',
        'password_hash' => '$2y$13$gYAywKSkhfZDq9FLNdm7buKnvlRxDexf5xipSMAxQPDUxpaptmZJu',
        'auth_key' => 'test100key',
    ],
    'demo' => [
        'id' => 101,
        'username' => 'demo',
        'password_hash' => '$2y$13$alRLq1PGVMlGYwS/Y3iy3ewQns1Z8ol8Iq6Zb5k7ZwEhblA1aL29y',
        'auth_key' => 'test101key',
    ],
];
