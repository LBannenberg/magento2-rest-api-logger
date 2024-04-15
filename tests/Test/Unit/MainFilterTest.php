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
use Psr\Log\LoggerInterface;

class MainFilterTest extends TestCase
{
    public function testThatFiltersCanBeInstantiated()
    {
        $filters = new MainFilter(
            $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(EndpointFilter::class)->disableOriginalConstructor()->getMock(),
        );
    }


    /**
     * @dataProvider scenarioProvider
     */
    public function testScenarios(MainFilterScenario $scenario)
    {
        // ARRANGE
        $configMock = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $configMock->method('getRequestFilters')->willReturn($scenario->requestFilters);
        $configMock->method('getResponseFilters')->willReturn($scenario->responseFilters);

        $endpointFilterMock = $this->getMockBuilder(EndpointFilter::class)->disableOriginalConstructor()->getMock();

        $requestMock = $this->buildMockRequest($scenario->request);

        $responseMock = $this->buildMockResponse($scenario->response);

        $filters = new MainFilter($configMock, $endpointFilterMock);

        // ACT
        [$shouldLogRequest, $shouldCensorRequestBody] = $filters->processRequest($requestMock);
        [$shouldLogResponse, $shouldCensorResponseBody] = $filters->processResponse($responseMock);

        // ASSERT
        if ($scenario->shouldLogRequest !== null) {
            $this->assertSame($scenario->shouldLogRequest, $shouldLogRequest);
        }

        if ($scenario->shouldCensorRequestBody !== null) {
            $this->assertSame($scenario->shouldCensorRequestBody, $shouldCensorRequestBody);
        }

        if ($scenario->shouldLogResponse !== null) {
            $this->assertSame($scenario->shouldLogResponse, $shouldLogResponse);
        }

        if ($scenario->shouldCensorResponseBody !== null) {
            $this->assertSame($scenario->shouldCensorResponseBody, $shouldCensorResponseBody,);
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


    /**
     * @return array<MainFilterScenario[]>
     */
    public function scenarioProvider(): array
    {
        $scenarios = [
            'If no filters are configured, all signs point to Yes' =>
                (new MainFilterScenario())
                    ->requestFiltersConfig([])
                    ->responseFiltersConfig([])
                    ->shouldLogRequest(true)
                    ->shouldCensorRequestBody(false)
                    ->shouldLogResponse(true)
                    ->shouldCensorResponseBody(false),

            'There are "allow" filters and at least one passes.' =>
                (new MainFilterScenario())
                    ->requestFiltersConfig([
                        new Filter('user_agent', 'contains', 'corrivate', 'allow_request'),
                        new Filter('route', 'contains', 'store', 'allow_request')
                    ])
                    ->request(['user_agent' => 'Rival Corp HQ', 'route' => 'http://mag2.test/rest/V1/store/websites'])
                    ->shouldLogRequest(true),

            'There are "allow" filters, and no "allow" filter passes' =>
                (new MainFilterScenario())
                    ->requestFiltersConfig([
                        new Filter('route', 'contains', 'store', 'allow_request'),
                        new Filter('user_agent', 'contains', 'corrivate', 'allow_request')
                    ])
                    ->request(['user_agent' => 'Rival Corp HQ', 'route' => 'somewhere else entirely'])
                    ->shouldLogRequest(false),

            'All "require" filters pass' =>
                (new MainFilterScenario())
                    ->requestFiltersConfig([
                        new Filter('route', 'contains', 'store', 'require_request'),
                        new Filter('user_agent', 'contains', 'corrivate', 'require_request')
                    ])
                    ->request(['user_agent' => 'Corrivate HQ', 'route' => 'http://mag2.test/rest/V1/store/websites'])
                    ->shouldLogRequest(true),

            'Not all "require" filters pass' =>
                (new MainFilterScenario())
                    ->requestFiltersConfig([
                        new Filter('route', 'contains', 'store', 'require_request'),
                        new Filter('user_agent', 'contains', 'corrivate', 'require_request')
                    ])
                    ->request(['user_agent' => 'Corrivate HQ', 'route' => 'somewhere else entirely'])
                    ->shouldLogRequest(false),

            'Status code filter matches response' =>
                (new MainFilterScenario())
                    ->responseFiltersConfig([new Filter('status_code', '>=', '400', 'forbid_response')])
                    ->response(['status_code' => '500'])
                    ->shouldLogResponse(false),

            'Status code filter does not match response' =>
                (new MainFilterScenario())
                    ->responseFiltersConfig([new Filter('status_code', '<', '400', 'require_response')])
                    ->response(['status_code' => '500'])
                    ->shouldLogResponse(false),

            'Comparison is case insensitive' =>
                (new MainFilterScenario())
                    ->requestFiltersConfig([new Filter('user_agent', '=', 'Corrivate', 'allow_request')])
                    ->request(['user_agent' => 'corrivate'])
                    ->shouldLogRequest(true),

            'Filter state is retained from request to response' =>
                (new MainFilterScenario())
                    ->requestFiltersConfig([new Filter('user_agent', '=', 'Corrivate', 'forbid_response')])
                    ->request(['user_agent' => 'corrivate'])
                    ->shouldLogResponse(false),
        ];

        // PHPUnit requires each case to be an array of (1) input arguments
        return array_map(fn($s) => [$s], $scenarios);
    }
}


class MainFilterScenario //phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
{
    public array $requestFilters = [];
    public array $responseFilters = [];
    public array $request = [];
    public ?bool $shouldLogRequest = null;
    public ?bool $shouldCensorRequestBody = null;
    public array $response = [];
    public ?bool $shouldLogResponse = null;
    public ?bool $shouldCensorResponseBody = null;


    public function requestFiltersConfig(array $config): MainFilterScenario
    {
        $this->requestFilters = $config;
        return $this;
    }

    public function responseFiltersConfig(array $config): MainFilterScenario
    {
        $this->responseFilters = $config;
        return $this;
    }

    public function request(array $request): MainFilterScenario
    {
        $this->request = $request;
        return $this;
    }

    public function shouldLogRequest(bool $policy): MainFilterScenario
    {
        $this->shouldLogRequest = $policy;
        return $this;
    }

    public function shouldCensorRequestBody(bool $policy): MainFilterScenario
    {
        $this->shouldCensorRequestBody = $policy;
        return $this;
    }

    public function response(array $response): MainFilterScenario
    {
        $this->response = $response;
        return $this;
    }

    public function shouldLogResponse(bool $policy): MainFilterScenario
    {
        $this->shouldLogResponse = $policy;
        return $this;
    }

    public function shouldCensorResponseBody(bool $policy): MainFilterScenario
    {
        $this->shouldCensorResponseBody = $policy;
        return $this;
    }
}
