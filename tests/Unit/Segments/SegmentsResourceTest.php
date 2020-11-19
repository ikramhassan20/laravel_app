<?php

namespace Tests\Unit\Segments;

use App\Components\AppStatusCodes;
use App\Components\Testing\MockHttpRequest;
use App\Segment;
use Tests\TestCase;

class SegmentsResourceTest extends TestCase
{
    use MockHttpRequest;

    protected $segment;

    protected function setUp()
    {
        parent::setUp();

        $this->segment = Segment::find(1);
    }

    /** @test */
    public function it_can_access_segments_index_page()
    {
        $expectedResponse = [
            'meta' => [
                'code'      => AppStatusCodes::HTTP_OK,
                'status'    => 'success'
            ],
            'data' => [
                [
                    'id' => 1,
                    "name" => "Test Segment",
                    "tags" => "Test,TestSegment",
                    'app_group_id' => 1
                ]
            ]
        ];

        $expectedEndPoint = route('segments.index', 'v1');

        $expectedParams = [
            'headers' => [
                'Authorization' => 'Bearer: some-test-token'
            ],
            'json' => [
                'resource' => 'segments',
                'action' => 'get'
            ]
        ];

        $mockHttpClient = $this->mockHttpRequest(
            'get',
            $expectedEndPoint,
            $expectedResponse,
            $expectedParams
        );

        $this->assertEquals($expectedResponse, $mockHttpClient->get($expectedEndPoint));
    }

    /** @test */
    public function it_can_create_segment()
    {
        $expectedResponse = [
            'meta' => [
                'code'      => AppStatusCodes::HTTP_OK,
                'status'    => 'success'
            ],
            'data' => [
                [
                    'id' => 1,
                    "name" => "Test Segment",
                    "tags" => "Test,TestSegment",
                    'app_group_id' => 1
                ]
            ]
        ];

        $expectedEndPoint = route('segments.store', 'v1');

        $expectedParams = [
            'headers' => [
                'Authorization' => 'Bearer: some-test-token'
            ],
            'json' => [
                "name"      => "Test Segment",
                "criteria"  => "(app_id='com.dev.engagement')",
                "tags"      => "Test,TestSegment"
            ]
        ];

        $mockHttpClient = $this->mockHttpRequest(
            'post',
            $expectedEndPoint,
            $expectedResponse,
            $expectedParams
        );

        $this->assertEquals($expectedResponse, $mockHttpClient->post($expectedEndPoint));
    }

    /** @test */
    public function it_can_update_segment()
    {
        $expectedResponse = [
            'meta' => [
                'code'      => AppStatusCodes::HTTP_OK,
                'status'    => 'success'
            ],
            'data' => [
                [
                    'id' => 1,
                    "name" => "Test Segment",
                    "tags" => "Test,TestSegment",
                    'app_group_id' => 1
                ]
            ]
        ];

        $expectedEndPoint = route('segments.update', ['v1', $this->segment]);

        $expectedParams = [
            'headers' => [
                'Authorization' => 'Bearer: some-test-token'
            ],
            'json' => [
                'name'      => 'Test Segment',
                "criteria"  => "(app_id='com.dev.engagement')",
                "tags"      => "Test,TestSegment"
            ]
        ];

        $mockHttpClient = $this->mockHttpRequest(
            'put',
            $expectedEndPoint,
            $expectedResponse,
            $expectedParams
        );

        $this->assertEquals($expectedResponse, $mockHttpClient->put($expectedEndPoint));
    }

    /** @test */
    public function it_can_show_segment()
    {
        $expectedResponse = [
            'meta' => [
                'code'      => AppStatusCodes::HTTP_OK,
                'status'    => 'success'
            ],
            'data' => [
                [
                    'id' => 1,
                    "name" => "Test Segment",
                    "tags" => "Test,TestSegment",
                    'app_group_id' => 1
                ]
            ]
        ];

        $expectedEndPoint = route('segments.show', ['v1', $this->segment]);

        $expectedParams = [
            'headers' => [
                'Authorization' => 'Bearer: some-test-token'
            ]
        ];

        $mockHttpClient = $this->mockHttpRequest(
            'get',
            $expectedEndPoint,
            $expectedResponse,
            $expectedParams
        );

        $this->assertEquals($expectedResponse, $mockHttpClient->get($expectedEndPoint));
    }

    /** @test */
    public function it_can_delete_segment()
    {
        $expectedResponse = [
            'meta' => [
                'code'      => AppStatusCodes::HTTP_OK,
                'status'    => 'success'
            ]
        ];

        $expectedEndPoint = route('segments.show', ['v1', $this->segment]);

        $expectedParams = [
            'headers' => [
                'Authorization' => 'Bearer: some-test-token'
            ]
        ];

        $mockHttpClient = $this->mockHttpRequest(
            'get',
            $expectedEndPoint,
            $expectedResponse,
            $expectedParams
        );

        $this->assertEquals($expectedResponse, $mockHttpClient->get($expectedEndPoint));
    }
}
