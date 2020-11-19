<?php

use Illuminate\Database\Seeder;

class AttributeDataSeeder extends Seeder
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
            $disk->get('attribute_data.json'),
            true
        );

        $company_id = 2;
        $row = \App\AttributeData::selectRaw('MAX(row_id) AS row_id')
            ->where('company_id', $company_id)->first();
        $row_id = !empty($row) ? ($row->row_id+1) : 1;

        foreach ($items as $item) {
            \Schema::disableForeignKeyConstraints();

            $item['company_id'] = $company_id;
            $item['row_id'] = $row_id;
            $item['created_by'] = 1;
            $item['updated_by'] = 1;
            \App\AttributeData::create($item);

            \Schema::enableForeignKeyConstraints();
        }
    }
}
