<?php

namespace Database\Seeders;

use App\Models\GradingScheme;
use App\Models\GradingSchemeClassification;
use App\Models\GradingSchemeLine;
use Illuminate\Database\Seeder;

class UccGradingSchemeSeeder extends Seeder
{
    public function run(): void
    {
        $scheme = GradingScheme::updateOrCreate(
            ['code' => 'UCC'],
            [
                'name' => 'UCC Affiliated Grading Scheme',
                'affiliation' => 'UCC',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        // Ensure only this default
        GradingScheme::where('id', '!=', $scheme->id)->update(['is_default' => false]);

        $lines = [
            ['grade' => 'A',  'min_mark' => 80, 'max_mark' => 100, 'grade_point' => 4.00, 'sequence' => 1],
            ['grade' => 'B+', 'min_mark' => 75, 'max_mark' => 79,  'grade_point' => 3.50, 'sequence' => 2],
            ['grade' => 'B',  'min_mark' => 70, 'max_mark' => 74,  'grade_point' => 3.00, 'sequence' => 3],
            ['grade' => 'C+', 'min_mark' => 65, 'max_mark' => 69,  'grade_point' => 2.50, 'sequence' => 4],
            ['grade' => 'C',  'min_mark' => 60, 'max_mark' => 64,  'grade_point' => 2.00, 'sequence' => 5],
            ['grade' => 'D+', 'min_mark' => 55, 'max_mark' => 59,  'grade_point' => 1.50, 'sequence' => 6],
            ['grade' => 'D',  'min_mark' => 50, 'max_mark' => 54,  'grade_point' => 1.00, 'sequence' => 7],
            ['grade' => 'E',  'min_mark' => 0,  'max_mark' => 49,  'grade_point' => 0.00, 'sequence' => 8],
        ];

        foreach ($lines as $line) {
            GradingSchemeLine::updateOrCreate(
                [
                    'grading_scheme_id' => $scheme->id,
                    'grade' => $line['grade'],
                ],
                $line
            );
        }

        $classes = [
            ['name' => 'FIRST CLASS', 'min_cgpa' => 3.60, 'max_cgpa' => 4.00, 'sequence' => 1],
            ['name' => 'SECOND CLASS (UPPER)', 'min_cgpa' => 3.00, 'max_cgpa' => 3.59, 'sequence' => 2],
            ['name' => 'SECOND CLASS (LOWER)', 'min_cgpa' => 2.50, 'max_cgpa' => 2.99, 'sequence' => 3],
            ['name' => 'THIRD CLASS', 'min_cgpa' => 2.00, 'max_cgpa' => 2.49, 'sequence' => 4],
            ['name' => 'PASS', 'min_cgpa' => 1.00, 'max_cgpa' => 1.99, 'sequence' => 5],
            ['name' => 'FAIL', 'min_cgpa' => 0.00, 'max_cgpa' => 0.99, 'sequence' => 6],
        ];

        foreach ($classes as $row) {
            GradingSchemeClassification::updateOrCreate(
                [
                    'grading_scheme_id' => $scheme->id,
                    'name' => $row['name'],
                ],
                $row
            );
        }
    }
}
