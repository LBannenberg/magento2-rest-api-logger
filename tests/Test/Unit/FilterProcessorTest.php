<?php

namespace Corrivate\RestApiLogger\Tests\Unit;

use Corrivate\RestApiLogger\Helpers\FilterProcessor;
use PHPUnit\Framework\TestCase;

class FilterProcessorTest extends TestCase
{
    private \Magento\Framework\TestFramework\Unit\Helper\ObjectManager $_objectManager;

    public function setUp(): void
    {
        $this->_objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
    }


    public function testThatUnitTestsActuallyRun()
    {
        echo "Ensuring unit tests are actually running...\n";
        $this->assertEquals(1, 1); // should pass
    }
}
