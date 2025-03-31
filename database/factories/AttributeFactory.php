<?php

namespace Database\Factories;

use App\Models\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttributeFactory extends Factory
{
    protected $model = Attribute::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'type' => $this->faker->randomElement(['text', 'number', 'boolean', 'date', 'select']),
            'options' => json_encode($this->faker->optional()->randomElements(['Option 1', 'Option 2', 'Option 3'])),
        ];
    }
}