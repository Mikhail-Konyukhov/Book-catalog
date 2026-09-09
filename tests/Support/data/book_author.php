<?php

declare(strict_types=1);

/**
 * Book counts per author for 2020, on which AuthorTest relies:
 * author 1 - 4 books, author 2 - 3, authors 3 and 4 - 2 each, authors 5-12 - one each.
 * So the top ten are 1, 2, 3, 4 and then 5-10 by ascending id; 11 and 12 are cut off.
 */
return [
    ['book_id' => 1, 'author_id' => 1],
    ['book_id' => 2, 'author_id' => 1],
    ['book_id' => 3, 'author_id' => 1],
    ['book_id' => 4, 'author_id' => 1],
    ['book_id' => 4, 'author_id' => 2],
    ['book_id' => 5, 'author_id' => 2],
    ['book_id' => 6, 'author_id' => 2],
    ['book_id' => 6, 'author_id' => 3],
    ['book_id' => 7, 'author_id' => 3],
    ['book_id' => 7, 'author_id' => 4],
    ['book_id' => 8, 'author_id' => 4],
    ['book_id' => 8, 'author_id' => 5],
    ['book_id' => 9, 'author_id' => 6],
    ['book_id' => 9, 'author_id' => 7],
    ['book_id' => 10, 'author_id' => 8],
    ['book_id' => 10, 'author_id' => 9],
    ['book_id' => 11, 'author_id' => 10],
    ['book_id' => 11, 'author_id' => 11],
    ['book_id' => 12, 'author_id' => 12],
    ['book_id' => 13, 'author_id' => 12],
];
