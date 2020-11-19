<?php

use Illuminate\Database\Seeder;
use App\Template;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $disk = \Storage::disk('seeders');
        $items = \GuzzleHttp\json_decode(
            $disk->get('template.json'),
            true
        );


        \Schema::disableForeignKeyConstraints();

        foreach ($items as $item) {
            $obj = new Template();
            $obj->code = $item['title'];
            $obj->name = $item['title'];
            $obj->thumbNail = $item['thumbNail'];
            $obj->content_url = $item['content'];
            $obj->type = $item['type'];
            $obj->save();
        }

        \Schema::enableForeignKeyConstraints();
    }
}
