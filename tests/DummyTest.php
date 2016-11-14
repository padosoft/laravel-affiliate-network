<?php

namespace Padosoft\AffiliateNetwork\Test;

class DummyTest extends \PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    /**
     * @param $expected
     * @return bool
     */
    protected function expectedIsAnException($expected)
    {
        if (is_array($expected)) {
            return false;
        }

        return strpos($expected, 'Exception') !== false
        || strpos($expected, 'PHPUnit_Framework_') !== false
        || strpos($expected, 'TypeError') !== false;
    }

    /**
     * Dummy Test.
     * @test
     */
    public function dummy()
    {
        $this->assertEquals(
            '1',
            '1'
        );
    }
}
