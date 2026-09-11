<?php

/**
 * One phone is subscribed to both authors of book 4 - notifyNewBook() must count it once.
 * Subscription 4 hangs on the bookless author 13 and already has notified_at set:
 * it gives the "already notified" state without touching any notifyNewBook() count.
 */

declare(strict_types=1);

return [
    'both' => ['id' => 1, 'author_id' => 1, 'phone' => '+79991110000', 'created_at' => 1600000100, 'notified_at' => null],
    'bothSecond' => ['id' => 2, 'author_id' => 2, 'phone' => '+79991110000', 'created_at' => 1600000101, 'notified_at' => null],
    'other' => ['id' => 3, 'author_id' => 3, 'phone' => '+79993330000', 'created_at' => 1600000102, 'notified_at' => null],
    'notified' => ['id' => 4, 'author_id' => 13, 'phone' => '+79994440000', 'created_at' => 1600000103, 'notified_at' => 1600000200],
];
