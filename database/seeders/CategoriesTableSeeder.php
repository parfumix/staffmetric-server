<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Symfony\Component\Yaml\Yaml;

class CategoriesTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $categories = Yaml::parse(file_get_contents(storage_path('categories.yaml')));

        foreach ($categories as $item) {
            $category = \App\Models\Category::create(['user_id' => null, 'title' => $item['title'], 'productivity' => $item['productivity'],]);
            if( isset($item['apps']) ) {
                foreach($item['apps'] as $app) {
                    \App\Models\App::create(['user_id' => null, 'profile_id' => null, 'name' => $app['name'], 'category_id' => $category->id,]);
                }
            }
        }
    }
}
