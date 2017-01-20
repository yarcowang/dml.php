<?php

/**
 * Proj. dml.php
 *
 * @author Yarco Wang <yarco.wang@gmail.com>
 * @since 17/1/15 下午12:14
 */
class NameConvertStrategyTest extends PHPUnit_Framework_TestCase
{
    public function testDefaultConvert()
    {
        $s = 'App\AbcAbcabcAcb';
        $this->assertEquals(
            'abc_abcabc_acb',
            \Yarco\Dml\NameConvertStrategy::GetDefault()->convert($s)
        );
    }
}
