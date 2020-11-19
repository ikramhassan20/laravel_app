<?php

namespace Tests\Feature\AppGroup;

use App\Components\AppStatusCodes;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\TestResourcesHelper;

class AppGroupResourceTest extends TestCase
{
    use DatabaseTransactions, TestResourcesHelper;

    protected function setUp()
    {
        parent::setUp();

        self::loginCompany();
        self::createResource(\App\AppGroup::class, [
            'name'          => 'some name',
            'company_id'    => self::$user->id,
            'default'       => false,
            'current'       => true,
        ]);
    }

    /** @test */
    public function it_can_access_app_groups_index_page()
    {
        $this->postJson(route('groups.index', ['v1']), [], [
            'Authorization' => 'Bearer ' . self::$authToken,
        ])->assertStatus(AppStatusCodes::HTTP_OK);
    }

    /** @test */
    public function it_can_create_app_group()
    {
        $this->postJson(route('groups.store', ['v1']), [
            'name'      => 'some name',
            'default'   => false,
            'current'   => false,
        ], [
            'Authorization' => 'Bearer ' . self::$authToken,
        ])->assertStatus(AppStatusCodes::HTTP_OK);
    }

    /** @test */
    public function it_can_update_app_group()
    {
        $this->put(route('groups.update', ['v1', self::$item]), [
            'name'      => 'some name',
            'default'   => true,
            'current'   => false,
        ], [
            'Authorization' => 'Bearer ' . self::$authToken,
        ])->assertStatus(AppStatusCodes::HTTP_OK);
    }

    /** @test */
    public function it_can_show_app_group()
    {
        $this->get(route('groups.show', ['v1', self::$item]), [], [
            'Authorization' => 'Bearer ' . self::$authToken,
        ])->assertStatus(AppStatusCodes::HTTP_OK);
    }

    /** @test */
    public function it_can_delete_app_group()
    {
        $this->delete(route('groups.destroy', ['v1', self::$item]), [], [
            'Authorization' => 'Bearer ' . self::$authToken,
        ])->assertStatus(AppStatusCodes::HTTP_OK);
    }

    /** @test */
    public function it_can_set_current_app_group()
    {
        $this->put(route('groups.current', ['v1', self::$item]), [], [
            'Authorization' => 'Bearer ' . self::$authToken,
        ])->assertStatus(AppStatusCodes::HTTP_OK);
    }
}
