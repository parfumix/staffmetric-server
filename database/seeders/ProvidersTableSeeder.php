<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProvidersTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $email_providers = ["gmail.com", "mail.ru", "mail.com", "mail.google.com", "e.mail.ru"];
        $social_providers = ["facebook.com" , "instagram.com" , "vk.com" , "twitter.com" , "youtube.com" , "pinterest.com" , "tumblr.com" ,];

        $email_category = \App\Models\Category::where('title', 'Email')->firstOrFail();
        $social_category = \App\Models\Category::where('title', 'Social')->firstOrFail();

        foreach ($email_providers as $provider) {
            \App\Models\App::create(['name' => $provider, 'category_id' => $email_category->id]);
        }

        foreach ($social_providers as $provider) {
            \App\Models\App::create(['name' => $provider,  'category_id' => $social_category->id]);
        }
    }

    
}
