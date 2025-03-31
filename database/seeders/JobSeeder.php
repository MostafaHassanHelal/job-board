<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Job;
use App\Models\Category;
use App\Models\Language;
use App\Models\Location;
use App\Models\Attribute;
use App\Models\AttributeValue;

class JobSeeder extends Seeder
{
    public function run()
    {
        // Create sample categories
        $categories = Category::factory()->count(5)->create();

        // Create sample languages
        $languages = Language::factory()->count(5)->create();

        // Create sample locations
        $locations = Location::factory()->count(5)->create();

        // Create sample attributes and their values
        $attributes = Attribute::factory()->count(3)->create();
        foreach ($attributes as $attribute) {
            AttributeValue::factory()->count(3)->create(['attribute_id' => $attribute->id]);
        }

        // Create sample jobs
        Job::factory()->count(20)->create()->each(function ($job) use ($categories, $languages, $locations, $attributes) {
            // Attach random categories, languages, and locations
            $job->categories()->attach($categories->random(rand(1, 3))->pluck('id')->toArray());
            $job->languages()->attach($languages->random(rand(1, 3))->pluck('id')->toArray());
            $job->locations()->attach($locations->random(rand(1, 3))->pluck('id')->toArray());

            // Attach random attributes
            foreach ($attributes as $attribute) {
                $job->attributes()->attach($attribute->id, [
                    'value' => AttributeValue::inRandomOrder()->first()->value
                ]);
            }
        });
    }
}