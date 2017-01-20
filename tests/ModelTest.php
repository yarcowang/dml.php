<?php

/**
 * Proj. dml.php
 *
 * @author Yarco Wang <yarco.wang@gmail.com>
 * @since 17/1/20 下午10:43
 */

class Employees extends \Yarco\Dml\Model {};

class ModelTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->_test_db = new Yarco\Dml\Dml('mysql:host=127.0.0.1;dbname=employees', 'root', '');
        $this->_test_db->init();
    }

    public function testCreate()
    {
        $model = $this->_test_db->fill(Employees::class, 463286);

        $this->assertInstanceOf(Employees::class, $model);
        $this->assertEquals(
            'Kwangho',
            $model->first_name
        );
        $this->assertEquals(
            '1952-05-26',
            $model->birth_date
        );
    }

    public function testUpdate()
    {
        $model = $this->_test_db->fill(Employees::class, 463286);
        $model->first_name = 'Yarco';
        $model->save();

        $model = $this->_test_db->fill(Employees::class, 463286);
        $this->assertEquals(
            'Yarco',
            $model->first_name
        );

        // cleanup
        $model->first_name = 'Kwangho';
        $model->save();
    }

    public function testBatchUpdate()
    {
        $model = $this->_test_db->fill(Employees::class, 463286);
        $model->save(
            ['first_name' => 'Yarco', 'last_name' => 'Wang']
        );

        $model = $this->_test_db->fill(Employees::class, 463286);
        $this->assertEquals(
            'Yarco',
            $model->first_name
        );
        $this->assertEquals(
            'Wang',
            $model->last_name
        );

        // cleanup
        $model->save(
            ['first_name' => 'Kwangho', 'last_name' => 'Avouris']
        );
    }
}
