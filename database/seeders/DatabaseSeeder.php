<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run() {
        $this->call(RolesTableSeeder::class);
        $this->call(ProfilesTableSeeder::class);
        $this->call(CategoriesTableSeeder::class);
        $this->call(ProvidersTableSeeder::class);
        $this->call(UsersTableSeeder::class);
    }
}
