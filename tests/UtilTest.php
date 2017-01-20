<?php

/**
 * Proj. dml.php
 *
 * @author Yarco Wang <yarco.wang@gmail.com>
 * @since 17/1/15 上午11:57
 */
class UtilTest extends PHPUnit_Framework_TestCase
{
    public function testNameConvert()
    {
        $s = 'AbcDefGhi';
        $this->assertEquals(
            'abc_def_ghi',
            \Yarco\Dml\Util::NameConvert($s)
        );

        $s = 'AbcAbcabcAcb';
        $this->assertEquals(
            'abc_abcabc_acb',
            \Yarco\Dml\Util::NameConvert($s)
        );
    }
}
