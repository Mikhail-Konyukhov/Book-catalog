<?php

return [
    // Пусто = смс не отправляем, а пишем в лог. Ключ в репозиторий не кладётся.
    'smspilotKey' => getenv('SMSPILOT_KEY') ?: '',
];
