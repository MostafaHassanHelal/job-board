<?php

namespace Database\Factories;

use App\Models\AttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttributeValueFactory extends Factory
{
    protected $model = AttributeValue::class;

    public function definition(): array
    {
        return [
            'job_id' => \App\Models\Job::factory(),
            'attribute_id' => \App\Models\Attribute::factory(),
            'value' => $this->faker->word,
        ];
    }
}