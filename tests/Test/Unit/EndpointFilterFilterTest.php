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
        $filters = new EndpointFilter(
            $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock()
        );
    }


    /**
     * @dataProvider scenarioProvider
     */
    public function testScenarios(EndpointFilterScenario $scenario)
    {
        // ARRANGE
        $unitUnderTest = new EndpointFilter(
            $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()->getMock()
        );

        // ACT
        $match = $unitUnderTest->matchPathToService($scenario->path, $scenario->filter);


        // ASSERT
        $this->assertSame($scenario->shouldMatch, $match);
    }


    /**
     * @return array<EndpointFilterScenario[]>
     */
    public function scenarioProvider(): array
    {
        $scenarios = [
            'Endpoints filters without variables are matched' =>
                new EndpointFilterScenario(
                    'GET http://mag2.test/rest/V1/orders',
                    new Filter('endpoint', '=', 'GET orders', 'censor_both'),
                    true
                ),

            'Endpoints with variables in them are matched in their generic form' =>
                new EndpointFilterScenario(
                    'get http://mag2.test/rest/V1/orders/1/comments',
                    new Filter('endpoint', '=', 'GET orders/:id/comments', 'censor_both'),
                    true
                ),

            'Endpoints with variables are distinguished from endpoints with fixed fragments' =>
                new EndpointFilterScenario(
                    'get http://mag2.test/rest/default/V1/cmsPage/search?searchCriteria=',
                    new Filter('endpoint', '=', 'GET cmsPage/:id', 'censor_response'),
                    false
                ),

            'Endpoint filters distinguish different methods on the same endpoint' =>
                new EndpointFilterScenario(
                    'put http://mag2.test/rest/V1/customerGroups/1',
                    new Filter('endpoint', '=', 'GET customerGroups/:id', 'censor_both'),
                    false
                ),

            'Query parameters are ignored when matching a request with a service' =>
                new EndpointFilterScenario(
                    'get http://mag2.test/rest/V1/orders/1/comments?query=true',
                    new Filter('endpoint', '=', 'GET orders/:id/comments', 'censor_both'),
                    true
                )
        ];

        // PHPUnit requires each case to be an array of (1) input arguments
        return array_map(fn($s) => [$s], $scenarios);
    }
}


class EndpointFilterScenario //phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
{
    public string $path;
    public Filter $filter;
    public bool $shouldMatch;

    public function __construct(string $path, Filter $filter, bool $shouldMatch)
    {
        $this->path = $path;
        $this->filter = $filter;
        $this->shouldMatch = $shouldMatch;
    }
}
