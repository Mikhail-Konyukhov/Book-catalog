<?php

return [
    // Пусто = смс не отправляем, а пишем в лог. Ключ в репозиторий не кладётся.
    'smspilotKey' => getenv('SMSPILOT_KEY') ?: '',
    // Значение по умолчанию совпадает с docker-compose и годится только для разработки:
    // в бою ключ задаётся переменной COOKIE_VALIDATION_KEY.
    'cookieValidationKey' => getenv('COOKIE_VALIDATION_KEY') ?: 'dev-only-LYVJyY7Ytd5uDjx3ZAnJPJpB',
];
