<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(TemplateSeeder::class);
        $this->call(CompanyEmailSettingsSeeder::class);
        $this->call(AppGroupsSeeder::class);
        $this->call(PermissionsTableSeeder::class);
        $this->call(AttributeTableSeeder::class);
//        $this->call(AttributeDataSeeder::class);
        $this->call(AppUsersSeeder::class);
        $this->call(LookUpTableSeeder::class);
        $this->call(LanguagesSeeder::class);
        $this->call(CompanyAppsSeeder::class);
        $this->call(SegmentsSeeder::class);
        $this->call(CampaignTableSeeder::class);
        //  $this->call(CampaignTypesTableSeeder::class);
        $this->call(LinkTrackingTableSeeder::class);
        $this->call(CampaignTrackingTableSeeder::class);
        //$this->call(CompanyUserTableSeeder::class);
    }
}
