<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default assessment totals (UCC-style)
    |--------------------------------------------------------------------------
    */
    'class_mark_total' => 30,
    'exam_mark_total' => 70,
    'final_mark_total' => 100,

    /*
    |--------------------------------------------------------------------------
    | Default components suggested when creating a new course
    |--------------------------------------------------------------------------
    | contribution values must sum to class_mark_total / exam_mark_total.
    */
    'default_components' => [
        [
            'code' => 'ASSIGN1',
            'name' => 'Assignment 1',
            'category' => 'class',
            'max_mark' => 20,
            'contribution' => 5,
            'sequence' => 1,
        ],
        [
            'code' => 'ASSIGN2',
            'name' => 'Assignment 2',
            'category' => 'class',
            'max_mark' => 20,
            'contribution' => 5,
            'sequence' => 2,
        ],
        [
            'code' => 'QUIZ1',
            'name' => 'Quiz 1',
            'category' => 'class',
            'max_mark' => 10,
            'contribution' => 5,
            'sequence' => 3,
        ],
        [
            'code' => 'MIDSEM',
            'name' => 'Mid-Semester Examination',
            'category' => 'class',
            'max_mark' => 50,
            'contribution' => 15,
            'sequence' => 4,
        ],
        [
            'code' => 'ENDSEM',
            'name' => 'End-of-Semester Examination',
            'category' => 'exam',
            'max_mark' => 100,
            'contribution' => 70,
            'sequence' => 5,
        ],
    ],
];
