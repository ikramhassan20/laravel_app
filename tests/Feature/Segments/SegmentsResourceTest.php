<?php

namespace Tests\Feature\Segments;

use App\Components\AppStatusCodes;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\TestResourcesHelper;

class SegmentsResourceTest extends TestCase
{
    use DatabaseTransactions, TestResourcesHelper;

    protected function setUp()
    {
        parent::setUp();

        self::loginCompany();
        self::createResource(\App\Segment::class, [
            "app_group_id"  => self::$user->defaultAppGroup()->id,
            "name"          => "Test Segment",
            "criteria"      => "(app_id='com.dev.engagement')",
            "tags"          => "Test,TestSegment",
            "created_by"    => self::$user->id,
            "updated_by"    => self::$user->id,
        ]);
    }

    /** @test */
    public function it_can_access_segments_index_page()
    {
        $this->postJson(route('segments.index', ['v1']), [], [
            'Authorization' => 'Bearer ' . self::$authToken,
        ])->assertStatus(AppStatusCodes::HTTP_OK);
    }

    /** @test */
    public function it_can_create_segment()
    {
        $this->postJson(route('segments.store', ['v1']), [
            "name"      => "Test Segment",
            "criteria"  => "(app_id='com.dev.engagement')",
            "tags"      => "Test,TestSegment"
        ], [
            'Authorization' => 'Bearer ' . self::$authToken,
        ])->assertStatus(AppStatusCodes::HTTP_OK);
    }

    /** @test */
    public function it_can_update_segment()
    {
        $this->put(route('segments.update', ['v1', self::$item]), [
            "name"      => "Test Segment",
            "criteria"  => "(app_id='com.dev.engagement')",
            "tags"      => "Test,TestSegment"
        ], [
            'Authorization' => 'Bearer ' . self::$authToken,
        ])->assertStatus(AppStatusCodes::HTTP_OK);
    }

    /** @test */
    public function it_can_show_segment()
    {
        $this->get(route('segments.show', ['v1', self::$item]), [], [
            'Authorization' => 'Bearer ' . self::$authToken,
        ])->assertStatus(AppStatusCodes::HTTP_OK);
    }

    /** @test */
    public function it_can_delete_segment()
    {
        $this->delete(route('segments.destroy', ['v1', self::$item]), [], [
            'Authorization' => 'Bearer ' . self::$authToken,
        ])->assertStatus(AppStatusCodes::HTTP_OK);
    }
}
