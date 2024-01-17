<?php

namespace Corrivate\RestApiLogger\Tests\Unit;

use Corrivate\RestApiLogger\Helpers\Config;
use Corrivate\RestApiLogger\Helpers\FilterProcessor;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FilterProcessorTest extends TestCase
{
    private $loggerMock;


    public function setUp(): void
    {
        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)->getMock();
    }


    public function testThatUnitTestsActuallyRun()
    {
        echo "Ensuring unit tests are actually running...\n";
        $this->assertEquals(1, 1); // should pass
    }


    public function testThatFiltersCanBeInstantiated()
    {
        $filters = new FilterProcessor(
            $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock(),
            $this->loggerMock
        );
    }


    /**
     * @dataProvider scenarioProvider
     */
    public function testScenarios(
        array $configScenario,
        array $requestScenario,
        bool  $preventLogRequestEnvelopeScenario,
        bool  $censorRequestBodyScenario,
        array $responseScenario,
        bool  $preventLogResponseBodyScenario,
        bool  $censorResponseBodyScenario
    )
    {
        // ARRANGE
        $configMock = $this->getMockConfig($configScenario);
        $requestMock = $this->getMockRequest($requestScenario);


        // ACT
        $filters = new FilterProcessor($configMock, $this->loggerMock);
        [$preventLogRequestEnvelope, $censorRequestBody] = $filters->processRequest($requestMock);


        // ASSERT
        $this->assertEquals($preventLogRequestEnvelope, $preventLogRequestEnvelopeScenario);
        $this->assertEquals($censorRequestBody, $censorRequestBodyScenario);


        if (empty($responseScenario)) {
            return;
        }


        // ARRANGE
        $responseMock = $this->getMockResponse($responseScenario);


        // ACT
        $filters = new FilterProcessor($configMock, $this->loggerMock);
        [$preventLogResponseBody, $censorResponseBody] = $filters->processResponse($responseMock);


        // ASSERT
        $this->assertEquals($preventLogResponseBody, $preventLogResponseBodyScenario);
        $this->assertEquals($censorResponseBody, $censorResponseBodyScenario);
    }


    private function getMockConfig(array $filterSettings): Config
    {
        $mock = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();
        $mock->method('getFilterSettings')->willReturn($filterSettings);
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
     * @return array[][]
     */
    public function scenarioProvider(): array
    {
        $baseScenario = [
            'config' => [],
            'request' => [],
            'preventLogRequestEnvelope' => false,
            'censorRequestBody' => false,
            'response' => [],
            'preventLogResponseEnvelope' => false,
            'censorResponseBody' => false,
        ];

        return [
            'If no filters are configured, the request and response can be logged' => $baseScenario,

            'There are "allow" filters and at least one passes.' => array_replace([], $baseScenario, [
                'config' => [
                    [
                        'aspect' => 'route',
                        'condition' => 'contains',
                        'value' => 'store',
                        'filter' => 'allow_request'
                    ],
                    [
                        'aspect' => 'user_agent',
                        'condition' => 'contains',
                        'value' => 'corrivate',
                        'filter' => 'allow_request'
                    ],
                ],
                'request' => ['user_agent' => 'Corrivate HQ', 'route' => 'somewhere else entirely']
            ]),

            'There are "allow" filters, and no "allow" filter passes' => array_replace([], $baseScenario, [
                'config' => [
                    [
                        'aspect' => 'route',
                        'condition' => 'contains',
                        'value' => 'store',
                        'filter' => 'allow_request'
                    ],
                    [
                        'aspect' => 'user_agent',
                        'condition' => 'contains',
                        'value' => 'corrivate',
                        'filter' => 'allow_request'
                    ],
                ],
                'request' => ['user_agent' => 'Rival Corp HQ', 'route' => 'somewhere else entirely'],
                'preventLogRequestEnvelope' => true,
            ]),


            'All "require" filters pass' => array_replace([], $baseScenario, [
                'config' => [
                    [
                        'aspect' => 'route',
                        'condition' => 'contains',
                        'value' => 'store',
                        'filter' => 'require_request'
                    ],
                    [
                        'aspect' => 'user_agent',
                        'condition' => 'contains',
                        'value' => 'corrivate',
                        'filter' => 'require_request'
                    ],
                ],
                'request' => ['user_agent' => 'Corrivate HQ', 'route' => 'http://mag2.test/rest/V1/store/websites'],
            ]),

            'Not all "require" filters pass' => array_replace([], $baseScenario, [
                'config' => [
                    [
                        'aspect' => 'route',
                        'condition' => 'contains',
                        'value' => 'store',
                        'filter' => 'require_request'
                    ],
                    [
                        'aspect' => 'user_agent',
                        'condition' => 'contains',
                        'value' => 'corrivate',
                        'filter' => 'require_request'
                    ],
                ],
                'request' => ['user_agent' => 'Corrivate HQ', 'route' => 'somewhere else entirely'],
                'preventLogRequestEnvelope' => true,
            ]),

            'Status code filter matches response' => array_replace([], $baseScenario, [
                'config' => [
                    [
                        'aspect' => 'status_code',
                        'condition' => '>=',
                        'value' => '400',
                        'filter' => 'forbid_response'
                    ]
                ],
                'response' => [
                    'status_code' => '500'
                ],
                'preventLogResponseEnvelope' => true
            ]),


            'Status code filter does not match response' => array_replace([], $baseScenario, [
                'config' => [
                    [
                        'aspect' => 'status_code',
                        'condition' => '<',
                        'value' => '400',
                        'filter' => 'require_response'
                    ]
                ],
                'response' => [
                    'status_code' => '500'
                ],
                'preventLogResponseEnvelope' => true
            ]),


            'Comparison is case insensitive' => array_replace([], $baseScenario, [
                'config' => [
                    [
                        'aspect' => 'user_agent',
                        'condition' => '!=',
                        'value' => 'Corrivate',
                        'filter' => 'allow_request'
                    ]
                ],
                'request' => [
                    'user_agent' => 'corrivate'
                ],
                'preventLogRequestEnvelope' => true
            ]),


            'Filter state is retained from request to response' => array_replace([], $baseScenario, [
                'config' => [
                    [
                        'aspect' => 'user_agent',
                        'condition' => '=',
                        'value' => 'Corrivate',
                        'filter' => 'forbid_response'
                    ]
                ],
                'request' => [
                    'user_agent' => 'corrivate'
                ],
                'preventLogResponseEnvelope' => true
            ])

        ];
    }
}
