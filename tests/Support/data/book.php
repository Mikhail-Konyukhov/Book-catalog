<?php

/**
 * Books 1-12 are from 2020 and feed the report; book 13 is from 2019 and must not
 * leak into it. Only book 1 has a cover - the rest check that a null cover is fine.
 */

declare(strict_types=1);

return [
    'book1' => ['id' => 1, 'title' => 'Река времени', 'year' => 2020, 'description' => 'Роман о времени.', 'isbn' => '978-5-0001-0001-1', 'cover_path' => 'uploads/cover1.jpg', 'created_at' => 1600000001],
    'book2' => ['id' => 2, 'title' => 'Тень над городом', 'year' => 2020, 'description' => null, 'isbn' => '978-5-0001-0002-8', 'cover_path' => null, 'created_at' => 1600000002],
    'book3' => ['id' => 3, 'title' => 'Стеклянный дом', 'year' => 2020, 'description' => null, 'isbn' => '978-5-0001-0003-5', 'cover_path' => null, 'created_at' => 1600000003],
    'book4' => ['id' => 4, 'title' => 'Двойная звезда', 'year' => 2020, 'description' => 'Книга двух авторов.', 'isbn' => '978-5-0001-0004-2', 'cover_path' => null, 'created_at' => 1600000004],
    'book5' => ['id' => 5, 'title' => 'Птицы улетают', 'year' => 2020, 'description' => null, 'isbn' => '978-5-0001-0005-9', 'cover_path' => null, 'created_at' => 1600000005],
    'book6' => ['id' => 6, 'title' => 'Соавторы', 'year' => 2020, 'description' => null, 'isbn' => '978-5-0001-0006-6', 'cover_path' => null, 'created_at' => 1600000006],
    'book7' => ['id' => 7, 'title' => 'Мост', 'year' => 2020, 'description' => null, 'isbn' => '978-5-0001-0007-3', 'cover_path' => null, 'created_at' => 1600000007],
    'book8' => ['id' => 8, 'title' => 'Северный ветер', 'year' => 2020, 'description' => null, 'isbn' => '978-5-0001-0008-0', 'cover_path' => null, 'created_at' => 1600000008],
    'book9' => ['id' => 9, 'title' => 'Два берега', 'year' => 2020, 'description' => null, 'isbn' => '978-5-0001-0009-7', 'cover_path' => null, 'created_at' => 1600000009],
    'book10' => ['id' => 10, 'title' => 'Полдень', 'year' => 2020, 'description' => null, 'isbn' => '978-5-0001-0010-3', 'cover_path' => null, 'created_at' => 1600000010],
    'book11' => ['id' => 11, 'title' => 'Тихий сад', 'year' => 2020, 'description' => null, 'isbn' => '978-5-0001-0011-0', 'cover_path' => null, 'created_at' => 1600000011],
    'book12' => ['id' => 12, 'title' => 'Последняя глава', 'year' => 2020, 'description' => null, 'isbn' => '978-5-0001-0012-7', 'cover_path' => null, 'created_at' => 1600000012],
    'book13' => ['id' => 13, 'title' => 'Прошлогодний снег', 'year' => 2019, 'description' => null, 'isbn' => '978-5-0001-0013-4', 'cover_path' => null, 'created_at' => 1600000013],
];
