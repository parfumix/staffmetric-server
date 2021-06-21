<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CategoriesTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $categories = [
            'General' => 'productive',
            'Business' => 'productive',
            'Entertainment' => 'productive',
            'Accounting' => 'productive',
            'Administration' => 'productive',
            'Sales' => 'productive',
            'Marketing' => 'productive',
            'Customer Relations' => 'productive',
            'Intelligence' => 'productive',
            'Project Management' => 'productive',
            'Social' => 'non-productive',
            'Email' => 'productive',
        ];

        foreach ($categories as $category => $productivity) {
            \App\Models\Category::create(['user_id' => null, 'title' => $category, 'productivity' => $productivity,]);
        }
    }
}
