<?php

namespace Tests\Unit\AppGroup;

use App\AppGroup;
use App\Components\AppStatusCodes;
use App\Components\Testing\MockHttpRequest;
use Tests\TestCase;

class AppGroupResourceTest extends TestCase
{
    use MockHttpRequest;

    protected $group;

    protected function setUp()
    {
        parent::setUp();

        $this->group = AppGroup::find(2);
    }

    /** @test */
    public function it_can_access_app_groups_index_page()
    {
        $expectedResponse = [
            'meta' => [
                'code'      => AppStatusCodes::HTTP_OK,
                'status'    => 'success'
            ],
            'data' => [
                [
                    'id' => 1,
                    'code' => 'some-code',
                    'name' => 'some-name',
                    'company_id' => 2
                ]
            ]
        ];

        $expectedEndPoint = route('groups.index', 'v1');

        $expectedParams = [
            'headers' => [
                'Authorization' => 'Bearer: some-test-token'
            ],
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
    public function it_can_create_app_group()
    {
        $expectedResponse = [
            'meta' => [
                'code'      => AppStatusCodes::HTTP_OK,
                'status'    => 'success'
            ],
            'data' => [
                [
                    'id' => 1,
                    'code' => 'some-code',
                    'name' => 'some-name',
                    'company_id' => 2
                ]
            ]
        ];

        $expectedEndPoint = route('groups.store', 'v1');

        $expectedParams = [
            'headers' => [
                'Authorization' => 'Bearer: some-test-token'
            ],
            'json' => [
                'name'      => 'some name',
                'default'   => false,
                'current'   => false,
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
    public function it_can_update_app_group()
    {
        $expectedResponse = [
            'meta' => [
                'code'      => AppStatusCodes::HTTP_OK,
                'status'    => 'success'
            ],
            'data' => [
                [
                    'id' => 1,
                    'code' => 'some-code',
                    'name' => 'some-name',
                    'company_id' => 2
                ]
            ]
        ];

        $expectedEndPoint = route('groups.update', ['v1', $this->group]);

        $expectedParams = [
            'headers' => [
                'Authorization' => 'Bearer: some-test-token'
            ],
            'json' => [
                'name'      => 'some name',
                'default'   => false,
                'current'   => false,
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
    public function it_can_show_app_group()
    {
        $expectedResponse = [
            'meta' => [
                'code'      => AppStatusCodes::HTTP_OK,
                'status'    => 'success'
            ],
            'data' => [
                [
                    'id' => 1,
                    'code' => 'some-code',
                    'name' => 'some-name',
                    'company_id' => 2
                ]
            ]
        ];

        $expectedEndPoint = route('groups.show', ['v1', $this->group]);

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
    public function it_can_delete_app_group()
    {
        $expectedResponse = [
            'meta' => [
                'code'      => AppStatusCodes::HTTP_OK,
                'status'    => 'success'
            ],
        ];

        $expectedEndPoint = route('groups.destroy', ['v1', $this->group]);

        $expectedParams = [
            'headers' => [
                'Authorization' => 'Bearer: some-test-token'
            ]
        ];

        $mockHttpClient = $this->mockHttpRequest(
            'delete',
            $expectedEndPoint,
            $expectedResponse,
            $expectedParams
        );

        $this->assertEquals($expectedResponse, $mockHttpClient->delete($expectedEndPoint));
    }

    /** @test */
    public function it_can_set_current_app_group()
    {
        $expectedResponse = [
            'meta' => [
                'code'      => AppStatusCodes::HTTP_OK,
                'status'    => 'success'
            ],
        ];

        $expectedEndPoint = route('groups.current', ['v1', $this->group]);

        $expectedParams = [
            'headers' => [
                'Authorization' => 'Bearer: some-test-token'
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
}
