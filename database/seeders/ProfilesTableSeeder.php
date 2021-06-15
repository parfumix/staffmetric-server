<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProfilesTableSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        foreach ($this->getProfiles() as $profile) {
            \App\Models\Profile::create(['title' => $profile]);
        }
    }

    public function getProfiles() {
        return [
            'Application Developer',
            'Application Support Analyst',
            'Applications Engineer',
            'Computer Programmer',
            'Computer Systems Analyst',
            'Computer Systems Manager',
            'Customer Support Administrator',
            'Database Administrator',
            'Desktop Support Manager',
            'Developer',
            'IT Analyst',
            'IT Manager',
            'IT Support Manager',
            'Java Developer',
            '.NET Developer',
            'Web Developer',
            'Other',
        ];
    }
}
