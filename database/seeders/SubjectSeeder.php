<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Subject::create([
            'name' => 'اللغة الإنجليزية',
            'comment' => 'لغة أجنبية',
            'full_mark' => 400.00,
            'class_id' => 2,
        ]);

        // مواد الصف الثاني (class_id = 2)
        Subject::create([
            'name' => 'اللغة العربية',
            'comment' => 'المادة الأساسية',
            'full_mark' => 600.00,
            'class_id' => 2,
        ]);
    }
}
