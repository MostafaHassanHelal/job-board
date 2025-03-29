<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attribute;

class AttributeSeeder extends Seeder
{
    public function run()
    {
        Attribute::create([
            'name' => 'years_experience',
            'type' => 'number',
            'options' => null
        ]);

        Attribute::create([
            'name' => 'education_level',
            'type' => 'select',
            'options' => json_encode(['Bachelor', 'Master', 'PhD'])
        ]);
    }
}
