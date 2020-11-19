<?php

use Illuminate\Database\Seeder;
use App\Components\DatabaseFactory;

class SegmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DatabaseFactory::create(\App\Segment::class, [
            "app_group_id"  => 1,
            "name"          => "Test Segment",
            "criteria"      => "(app_id='com.devengagement.v2')",
            "tags"          => "Test,TestSegment",
            "attribute_fields"   =>"app_id",
            "created_by"    => 2,
            "updated_by"    => 2,
        ]);
    }
}
