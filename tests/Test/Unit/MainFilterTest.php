<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Tests\Unit;

use Corrivate\RestApiLogger\Filter\EndpointFilter;
use Corrivate\RestApiLogger\Model\Config;
use Corrivate\RestApiLogger\Model\Config\Filter;
use Corrivate\RestApiLogger\Filter\MainFilter;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;
use PHPUnit\Framework\TestCase;

class MainFilterTest extends TestCase
{
    public function testThatFiltersCanBeInstantiated()
    {
        $this->expectNotToPerformAssertions();

        $filters = new MainFilter(
            $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(EndpointFilter::class)->disableOriginalConstructor()->getMock(),
        );
    }


    /**
     * @dataProvider scenarioProvider
     */
    public function testScenarios(array $scenario)
    {
        // ARRANGE
        $configMock = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $configMock->method('getRequestFilters')->willReturn($scenario['requestFilters']);
        $configMock->method('getResponseFilters')->willReturn($scenario['responseFilters']);

        $endpointFilterMock = $this->getMockBuilder(EndpointFilter::class)->disableOriginalConstructor()->getMock();

        $requestMock = $this->buildMockRequest($scenario['request']);

        $responseMock = $this->buildMockResponse($scenario['response']);

        $filters = new MainFilter($configMock, $endpointFilterMock);

        // ACT
        [$shouldLogRequest, $shouldCensorRequestBody] = $filters->processRequest($requestMock);
        [$shouldLogResponse, $shouldCensorResponseBody] = $filters->processResponse($responseMock);

        // ASSERT
        if ($scenario['shouldLogRequest'] !== null) {
            $this->assertSame($scenario['shouldLogRequest'], $shouldLogRequest);
        }

        if ($scenario['shouldCensorRequestBody'] !== null) {
            $this->assertSame($scenario['shouldCensorRequestBody'], $shouldCensorRequestBody);
        }

        if ($scenario['shouldLogResponse'] !== null) {
            $this->assertSame($scenario['shouldLogResponse'], $shouldLogResponse);
        }

        if ($scenario['shouldCensorResponseBody'] !== null) {
            $this->assertSame($scenario['shouldCensorResponseBody'], $shouldCensorResponseBody);
        }
    }


    private function buildMockRequest(array $aspects): Request
    {
        $mock = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        if (isset($aspects['method'])) {
            $mock->method('getMethod')->willReturn($aspects['method']);
        }

        if (isset($aspects['route'])) {
            $mock->method('getRequestUri')->willReturn($aspects['route']);
        }

        if (isset($aspects['ip'])) {
            $mock->method('getClientIp')->willReturn($aspects['ip']);
        }

        if (isset($aspects['user_agent'])) {
            $mock->method('getHeader')->with('User-Agent')->willReturn($aspects['user_agent']);
        }

        if (isset($aspects['request_body'])) {
            $mock->method('getContent')->willReturn($aspects['request_body']);
        }

        return $mock;
    }


    private function buildMockResponse(array $aspects): Response
    {
        $mock = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();

        if (isset($aspects['status_code'])) {
            $mock->method('getStatusCode')->willReturn($aspects['status_code']);
        }

        if (isset($aspects['response_body'])) {
            $mock->method('getBody')->willReturn($aspects['response_body']);
        }

        return $mock;
    }


    public function scenarioProvider(): array
    {
        $scenarios = [
            'If no filters are configured, all signs point to Yes' => [
                'requestFilters' => [],
                'responseFilters' => [],
                'request' => [],
                'shouldLogRequest' => true,
                'shouldCensorRequestBody' => false,
                'response' => [],
                'shouldLogResponse' => true,
                'shouldCensorResponseBody' => false,
            ],

            'There are "allow" filters and at least one passes.' => [
                'requestFilters' => [
                    new Filter('user_agent', 'contains', 'corrivate', 'allow_request'),
                    new Filter('route', 'contains', 'store', 'allow_request')
                ],
                'responseFilters' => [],
                'request' => ['user_agent' => 'Rival Corp HQ', 'route' => 'http://mag2.test/rest/V1/store/websites'],
                'shouldLogRequest' => true,
                'shouldCensorRequestBody' => null,
                'response' => [],
                'shouldLogResponse' => null,
                'shouldCensorResponseBody' => null,
            ],

            'There are "allow" filters, and no "allow" filter passes' => [
                'requestFilters' => [
                    new Filter('route', 'contains', 'store', 'allow_request'),
                    new Filter('user_agent', 'contains', 'corrivate', 'allow_request')
                ],
                'responseFilters' => [],
                'request' => ['user_agent' => 'Rival Corp HQ', 'route' => 'somewhere else entirely'],
                'shouldLogRequest' => false,
                'shouldCensorRequestBody' => null,
                'response' => [],
                'shouldLogResponse' => null,
                'shouldCensorResponseBody' => null,
            ],

            'All "require" filters pass' => [
                'requestFilters' => [
                    new Filter('route', 'contains', 'store', 'require_request'),
                    new Filter('user_agent', 'contains', 'corrivate', 'require_request')
                ],
                'responseFilters' => [],
                'request' => ['user_agent' => 'Corrivate HQ', 'route' => 'http://mag2.test/rest/V1/store/websites'],
                'shouldLogRequest' => true,
                'shouldCensorRequestBody' => null,
                'response' => [],
                'shouldLogResponse' => null,
                'shouldCensorResponseBody' => null,
            ],

            'Not all "require" filters pass' => [
                'requestFilters' => [
                    new Filter('route', 'contains', 'store', 'require_request'),
                    new Filter('user_agent', 'contains', 'corrivate', 'require_request')
                ],
                'responseFilters' => [],
                'request' => ['user_agent' => 'Corrivate HQ', 'route' => 'somewhere else entirely'],
                'shouldLogRequest' => false,
                'shouldCensorRequestBody' => null,
                'response' => [],
                'shouldLogResponse' => null,
                'shouldCensorResponseBody' => null,
            ],

            'Status code filter matches response' => [
                'requestFilters' => [],
                'responseFilters' => [new Filter('status_code', '>=', '400', 'forbid_response')],
                'request' => [],
                'shouldLogRequest' => null,
                'shouldCensorRequestBody' => null,
                'response' => ['status_code' => '500'],
                'shouldLogResponse' => false,
                'shouldCensorResponseBody' => null,
            ],

            'Status code filter does not match response' => [
                'requestFilters' => [],
                'responseFilters' => [new Filter('status_code', '<', '400', 'require_response')],
                'request' => [],
                'shouldLogRequest' => null,
                'shouldCensorRequestBody' => null,
                'response' => ['status_code' => '500'],
                'shouldLogResponse' => false,
                'shouldCensorResponseBody' => null,
            ],

            'Comparison is case insensitive' => [
                'requestFilters' => [new Filter('user_agent', '=', 'Corrivate', 'allow_request')],
                'responseFilters' => [],
                'request' => ['user_agent' => 'corrivate'],
                'shouldLogRequest' => true,
                'shouldCensorRequestBody' => null,
                'response' => [],
                'shouldLogResponse' => null,
                'shouldCensorResponseBody' => null,
            ],

            'Filter state is retained from request to response' => [
                'requestFilters' => [new Filter('user_agent', '=', 'Corrivate', 'forbid_response')],
                'responseFilters' => [],
                'request' => ['user_agent' => 'corrivate'],
                'shouldLogRequest' => null,
                'shouldCensorRequestBody' => null,
                'response' => [],
                'shouldLogResponse' => false,
                'shouldCensorResponseBody' => null,
            ],
        ];

        // PHPUnit requires each case to be an array of (1) input arguments
        return array_map(fn($s) => [$s], $scenarios);
    }
}
