<?php

use Illuminate\Database\Seeder;

class LanguagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $languages = [
            ['name' => 'English', 'code' => 'en', 'dir' => 'ltr', 'image' => "https://upload.wikimedia.org/wikipedia/en/thumb/a/ae/Flag_of_the_United_Kingdom.svg/255px-Flag_of_the_United_Kingdom.svg.png"],
            ['name' => 'Arabic', 'code' => 'ar', 'dir' => 'rtl', 'image' => "https://upload.wikimedia.org/wikipedia/commons/thumb/c/cb/Flag_of_the_United_Arab_Emirates.svg/1200px-Flag_of_the_United_Arab_Emirates.svg.png"],
            ['name' => 'Russian', 'code' => 'ru', 'dir' => 'ltr', 'image' => "https://www.advantour.com/russia/images/symbolics/russia-flag.jpg"],
        ];

        foreach ($languages as $language) {
            \App\Language::create($language);
        }
    }
}
