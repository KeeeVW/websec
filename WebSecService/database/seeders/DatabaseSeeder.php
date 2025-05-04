<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            GradeSeeder::class,
            QuestionSeeder::class,
<<<<<<< HEAD
            ManualSeeder::class,
            RolesAndPermissionsSeeder::class,
            CleanupRolesSeeder::class,
            UsersSeeder::class,
            ProductsSeeder::class,
=======
>>>>>>> 6c4297d3fdfd66398b2d51a8dc8705571982f414
        ]);
    }
}
