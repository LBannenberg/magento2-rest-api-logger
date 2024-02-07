<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Tests\Unit;

use Corrivate\RestApiLogger\Model\Config;
use Corrivate\RestApiLogger\Model\Config\Filter;
use Corrivate\RestApiLogger\Filter\MainFilter;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;
use PHPUnit\Framework\TestCase;

class MainFilterTest extends TestCase
{
    public function testThatUnitTestsActuallyRun()
    {
        // phpcs:ignore Magento2.Security.LanguageConstruct.DirectOutput
        echo "Ensuring unit tests are actually running...\n";
        $this->assertEquals(1, 1); // should pass
    }


    public function testThatFiltersCanBeInstantiated()
    {
        $filters = new MainFilter(
            $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock()
        );
    }


    /**
     * @dataProvider scenarioProvider
     */
    public function testScenarios(Scenario $scenario)
    {
        // ARRANGE
        $configMock = $this->getMockConfig($scenario->config);
        $requestMock = $this->getMockRequest($scenario->request);
        $responseMock = $this->getMockResponse($scenario->response);
        $filters = new MainFilter($configMock);

        // ACT
        [$shouldLogRequest, $shouldCensorRequestBody] = $filters->processRequest($requestMock);
        [$shouldLogResponse, $shouldCensorResponseBody] = $filters->processResponse($responseMock);

        // ASSERT
        if ($scenario->logRequest !== null) {
            $this->assertEquals($scenario->logRequest, $shouldLogRequest);
        }

        if ($scenario->censorRequestBody !== null) {
            $this->assertEquals($scenario->censorRequestBody, $shouldCensorRequestBody);
        }

        if ($scenario->logResponse !== null) {
            $this->assertEquals($scenario->logResponse, $shouldLogResponse);
        }

        if ($scenario->censorResponseBody !== null) {
            $this->assertEquals($scenario->censorResponseBody, $shouldCensorResponseBody,);
        }
    }


    private function getMockConfig(array $filterSettings): Config
    {
        $mock = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();

        $mock->method('getRequestFilters')->willReturn(
            array_filter(
                $filterSettings,
                fn($f) => in_array($f->aspect, Config::REQUEST_ASPECTS)
            )
        );

        $mock->method('getResponseFilters')->willReturn(
            array_filter(
                $filterSettings,
                fn($f) => in_array($f->aspect, Config::RESPONSE_ASPECTS)
            )
        );

        return $mock;
    }


    private function getMockRequest(array $aspects): Request
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


    private function getMockResponse(array $aspects): Response
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
     * @return array<Scenario[]>
     */
    public function scenarioProvider(): array
    {
        $scenarios = [
            'If no filters are configured, all signs point to Yes' =>
                (new Scenario())
                    ->config([])
                    ->logRequest(true)
                    ->censorRequestBody(false)
                    ->logResponse(true)
                    ->censorResponseBody(false),

            'There are "allow" filters and at least one passes.' =>
                (new Scenario())
                    ->config([
                        new Filter('user_agent', 'contains', 'corrivate', 'allow_request'),
                        new Filter('route', 'contains', 'store', 'allow_request')
                    ])
                    ->request(['user_agent' => 'Rival Corp HQ', 'route' => 'http://mag2.test/rest/V1/store/websites'])
                    ->logRequest(true),

            'There are "allow" filters, and no "allow" filter passes' =>
                (new Scenario())
                    ->config([
                        new Filter('route', 'contains', 'store', 'allow_request'),
                        new Filter('user_agent', 'contains', 'corrivate', 'allow_request')
                    ])
                    ->request(['user_agent' => 'Rival Corp HQ', 'route' => 'somewhere else entirely'])
                    ->logRequest(false),

            'All "require" filters pass' =>
                (new Scenario())
                    ->config([
                        new Filter('route', 'contains', 'store', 'require_request'),
                        new Filter('user_agent', 'contains', 'corrivate', 'require_request')
                    ])
                    ->request(['user_agent' => 'Corrivate HQ', 'route' => 'http://mag2.test/rest/V1/store/websites'])
                    ->logRequest(true),

            'Not all "require" filters pass' =>
                (new Scenario())
                    ->config([
                        new Filter('route', 'contains', 'store', 'require_request'),
                        new Filter('user_agent', 'contains', 'corrivate', 'require_request')
                    ])
                    ->request(['user_agent' => 'Corrivate HQ', 'route' => 'somewhere else entirely'])
                    ->logRequest(false),

            'Status code filter matches response' =>
                (new Scenario())
                    ->config([new Filter('status_code', '>=', '400', 'forbid_response')])
                    ->response(['status_code' => '500'])
                    ->logResponse(false),

            'Status code filter does not match response' =>
                (new Scenario())
                    ->config([new Filter('status_code', '<', '400', 'require_response')])
                    ->response(['status_code' => '500'])
                    ->logResponse(false),

            'Comparison is case insensitive' =>
                (new Scenario())
                    ->config([new Filter('user_agent', '=', 'Corrivate', 'allow_request')])
                    ->request(['user_agent' => 'corrivate'])
                    ->logRequest(true),

            'Filter state is retained from request to response' =>
                (new Scenario())
                    ->config([new Filter('user_agent', '=', 'Corrivate', 'forbid_response')])
                    ->request(['user_agent' => 'corrivate'])
                    ->logResponse(false),

        ];

        // PHPUnit requires each case to be an array of (1) input arguments
        return array_map(fn($s) => [$s], $scenarios);
    }
}


class Scenario //phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
{
    public array $config = [];
    public array $request = [];
    public ?bool $logRequest = null;
    public ?bool $censorRequestBody = null;
    public array $response = [];
    public ?bool $logResponse = null;
    public ?bool $censorResponseBody = null;

    public function config(array $config): Scenario
    {
        $this->config = $config;
        return $this;
    }

    public function request(array $request): Scenario
    {
        $this->request = $request;
        return $this;
    }

    public function logRequest(bool $policy): Scenario
    {
        $this->logRequest = $policy;
        return $this;
    }

    public function censorRequestBody(bool $policy): Scenario
    {
        $this->censorRequestBody = $policy;
        return $this;
    }

    public function response(array $response): Scenario
    {
        $this->response = $response;
        return $this;
    }

    public function logResponse(bool $policy): Scenario
    {
        $this->logResponse = $policy;
        return $this;
    }

    public function censorResponseBody(bool $policy): Scenario
    {
        $this->censorResponseBody = $policy;
        return $this;
    }
}
