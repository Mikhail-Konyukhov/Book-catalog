<?php

declare(strict_types=1);

/**
 * One phone is subscribed to both authors of book 4 - notifyNewBook() must count it once.
 */
return [
    'both' => ['id' => 1, 'author_id' => 1, 'phone' => '+79991110000', 'created_at' => 1600000100, 'notified_at' => null],
    'bothSecond' => ['id' => 2, 'author_id' => 2, 'phone' => '+79991110000', 'created_at' => 1600000101, 'notified_at' => null],
    'other' => ['id' => 3, 'author_id' => 3, 'phone' => '+79993330000', 'created_at' => 1600000102, 'notified_at' => null],
];
