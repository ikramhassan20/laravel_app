<?php

use Illuminate\Database\Seeder;

class CompanyUserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $disk = \Storage::disk('seeders');
        $items = \GuzzleHttp\json_decode(
            $disk->get('companyUser.json'),
            true
        );

        $disk = \Storage::disk('seeders');
        $content = \GuzzleHttp\json_decode(
            $disk->get('init.json'),
            true
        );

        foreach ($content['users'] as $user) {
            \App\User::create($user);
        }

        $model = config('laravel-permission.models.role');
        foreach ($content['roles'] as $role) {
            $model::create(['name' => $role]);
        }

        foreach ($content['user_roles'] as $role) {
            \App\User::find($role[0])->assignRole(
                $model::find($role[1])
            );
        }

        foreach (\App\User::all() as $user) {
            \App\Components\CreateOAuthClients::createClients($user);
        }
    }

}
