<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Job;
use App\Models\Language;
use App\Models\Location;
use App\Models\Category;

class JobSeeder extends Seeder
{
    public function run()
    {
        $job1 = Job::create([
            'title' => 'Senior Laravel Developer',
            'description' => 'We are looking for an experienced Laravel developer.',
            'company_name' => 'ASTUDIO',
            'salary_min' => 60000,
            'salary_max' => 90000,
            'is_remote' => true,
            'job_type' => 'full-time',
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Attach relationships
        $job1->languages()->attach(Language::where('name', 'PHP')->first()->id);
        $job1->locations()->attach(Location::where('city', 'New York')->first()->id);
        $job1->categories()->attach(Category::where('name', 'Software Engineering')->first()->id);
    }
}
