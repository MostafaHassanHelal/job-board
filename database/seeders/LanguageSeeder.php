<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Language;

class LanguageSeeder extends Seeder
{
    public function run()
    {
        $languages = ['PHP', 'JavaScript', 'Python', 'Java', 'C#'];
        
        foreach ($languages as $lang) {
            Language::create(['name' => $lang]);
        }
    }
}
