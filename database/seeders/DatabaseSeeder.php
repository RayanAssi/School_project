<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

    /*    User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]); */

        $this->call([
            UserSeeder::class,
            /* Section::class,
            Subject::class,
            StudentSubject::class, */
        ]);
    }
}
