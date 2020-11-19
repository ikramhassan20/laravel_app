<?php

use Illuminate\Database\Seeder;

class CompanyEmailSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \Schema::disableForeignKeyConstraints();
        (new \App\UserEmailSettings())->create([
            "company_id"    => 2,
            "host"          => "email-smtp.us-east-1.amazonaws.com",
            "port"          => 587,
            "username"      => "AKIAJRZAEJN6W7DTSZVQ",
            "password"      => "AhG63LHZXZV84DFoOmjcaJLXeuf7pmucFIPdMd6uIvx4",
            "from_name"     => "developer@entertainerebizservices.com",
            "from_email"    => "Engagement Platform Support"
        ]);
        \Schema::enableForeignKeyConstraints();
    }
}
