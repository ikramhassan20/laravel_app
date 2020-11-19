<?php

use Illuminate\Database\Seeder;

class AttributeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $disk = \Storage::disk('seeders');
        $items = array_filter(\GuzzleHttp\json_decode(
            $disk->get('attributes.json'),
            true
        ));

        $type = ['user', 'action', 'conversion'];

        $i = 0;
        foreach ($items as $item) {
            unset($item['deleted_at']);

            \Schema::disableForeignKeyConstraints();
            //$item['app_group_id'] = 1;
            //$item['attribute_type'] = $i < 3 ? $type[$i] : $type[rand() % 3];
            //$item['attribute_type'] = $type[rand() % 3];
            //$item['attribute_type'] = $type[rand() % 1];
            \App\Attribute::create($item);
            \Schema::enableForeignKeyConstraints();

            $i++;
        }
    }
}
