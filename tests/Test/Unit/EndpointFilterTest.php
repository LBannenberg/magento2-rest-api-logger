<?php

declare(strict_types=1);

namespace Corrivate\RestApiLogger\Tests\Unit;

use Corrivate\RestApiLogger\Filter\EndpointFilter;
use Corrivate\RestApiLogger\Model\Config\Filter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EndpointFilterTest extends TestCase
{
    public function testThatFiltersCanBeInstantiated()
    {
        $this->expectNotToPerformAssertions();

        $filters = new EndpointFilter(
            $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock()
        );
    }


    /**
     * @dataProvider scenarioProvider
     */
    public function testScenarios(array $scenario)
    {
        // ARRANGE
        $unitUnderTest = new EndpointFilter(
            $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock()
        );

        // ACT
        $match = $unitUnderTest->matchRequestToFilter($scenario['path'], $scenario['filter']);

        // ASSERT
        $this->assertSame($scenario['shouldMatch'], $match);
    }


    /**
     * @return array{Description: array{path: string, filter: Filter, shouldMatch: bool}}
     */
    public function scenarioProvider(): array
    {
        return [
            'Endpoints filters without variables are matched' => [
                'Description' => [
                    'path' => 'GET http://mag2.test/rest/V1/orders',
                    'filter' => new Filter('endpoint', '=', 'GET orders', 'censor_both'),
                    'shouldMatch' => true
                ]
            ],

            'Endpoints with variables in them are matched in their generic form' => [
                'Description' => [
                    'path' => 'get http://mag2.test/rest/V1/orders/1/comments',
                    'filter' => new Filter('endpoint', '=', 'GET orders/:id/comments', 'censor_both'),
                    'shouldMatch' => true
                ]
            ],

            'Endpoints with variables are distinguished from endpoints with fixed fragments' => [
                'Description' => [
                    'path' => 'get http://mag2.test/rest/default/V1/cmsPage/search?searchCriteria=',
                    'filter' => new Filter('endpoint', '=', 'GET cmsPage/:id', 'censor_response'),
                    'shouldMatch' => false
                ]
            ],

            'Endpoint filters distinguish different methods on the same endpoint' => [
                'Description' => [
                    'path' => 'put http://mag2.test/rest/V1/customerGroups/1',
                    'filter' => new Filter('endpoint', '=', 'GET customerGroups/:id', 'censor_both'),
                    'shouldMatch' => false
                ]
            ],

            'Query parameters are ignored when matching a request with an endpoint' => [
                'Description' => [
                    'path' => 'get http://mag2.test/rest/V1/orders/1/comments?query=true',
                    'filter' => new Filter('endpoint', '=', 'GET orders/:id/comments', 'censor_both'),
                    'shouldMatch' => true
                ]
            ]

        ];
    }
}
