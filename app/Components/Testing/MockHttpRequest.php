<?php

namespace App\Components\Testing;

use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\ResponseInterface;

trait MockHttpRequest
{
    /**
     * Mock http request.
     *
     * @param string $expectedMethod
     * @param string $expectedEndpoint
     * @param array  $expectedResponse
     * @param array  $expectedParams
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function mockHttpRequest($expectedMethod, $expectedEndpoint, $expectedResponse, $expectedParams = [])
    {
        $mockResponse = $this->getMockBuilder(ResponseInterface::class)
            ->getMock();
        $mockResponse->expects($this->once())
            ->method('getBody')
            ->willReturn($expectedResponse);

        $mockHttpClient = $this->getMockBuilder(HttpClient::class)
            ->setMethods(['get', 'post', 'put', 'delete'])
            ->getMock();

        switch ($expectedMethod) {
            case 'get':
                $mockHttpClient->expects($this->once())
                    ->method('get')
                    ->with($expectedEndpoint)
                    ->willReturn($mockResponse->getBody());
                break;

            case 'post':
                $mockHttpClient->expects($this->once())
                    ->method('post')
                    ->withAnyParameters($expectedEndpoint, $expectedParams)
                    ->will(
                        $this->returnValue(
                            $mockResponse->getBody()
                        )
                    );
                break;

            case 'put':
                $mockHttpClient->expects($this->once())
                    ->method('put')
                    ->withAnyParameters($expectedEndpoint, $expectedParams)
                    ->will(
                        $this->returnValue(
                            $mockResponse->getBody()
                        )
                    );
                break;

            case 'delete':
                $mockHttpClient->expects($this->once())
                    ->method('delete')
                    ->withAnyParameters($expectedEndpoint, $expectedParams)
                    ->will(
                        $this->returnValue(
                            $mockResponse->getBody()
                        )
                    );
                break;
        }

        return $mockHttpClient;
    }
}
