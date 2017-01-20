<?php

/**
 * Proj. dml.php
 *
 * @author Yarco Wang <yarco.wang@gmail.com>
 * @since 17/1/14 下午7:27
 */
class DmlManagerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $dml = new Yarco\Dml\Dml('mysql:host=127.0.0.1;dbname=employees', 'root', '');
        $this->_mgr = new \Yarco\Dml\DmlManager($dml);
    }

    public function testContructor()
    {
        $this->assertEquals(
            'default',
            $this->_mgr->current()
        );
    }

    public function testSetAndGet()
    {
        $dml = new Yarco\Dml\Dml('mysql:host=127.0.0.1;dbname=test', 'root', '');
        $this->_mgr->add('test', $dml);

        $this->assertEquals(
            'test',
            $this->_mgr->current()
        );
        $this->assertInstanceOf(\Yarco\Dml\Dml::class, $this->_mgr->conn());
        $this->assertInstanceOf(\Yarco\Dml\Dml::class, $this->_mgr->conn('go_to_default'));
    }


}
