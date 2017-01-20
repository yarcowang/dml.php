<?php

/**
 * Proj. dml.php
 *
 * @author Yarco Wang <yarco.wang@gmail.com>
 * @since 17/1/14 下午12:20
 */
class DmlTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->_test_db = new Yarco\Dml\Dml('mysql:host=127.0.0.1;dbname=employees', 'root', '');
    }

    public function testTables()
    {
        $this->assertContains('employees', $this->_test_db->tables());
        $this->assertContains('salaries', $this->_test_db->tables());
    }

    public function testDescribe()
    {
        $t = $this->_test_db->describe('employees');
        $this->assertEquals(
            ['pk' => 'emp_no', 'ts' => [
                'emp_no' => 'int(11)',
                'birth_date' => 'date',
                'first_name' => 'varchar(14)',
                'last_name' => 'varchar(16)',
                'gender' => "enum('M','F')",
                'hire_date' => 'date'
            ]],
            $t
        );
    }

//    public function testInit() {}

//    public function testGetValidData()
//    {
//        $this->_test_db->init();
//
//        $valid_data = $this->_test_db->getValidData('employees', ['birth_date' => '1980-05-17', 'first_name' => 'Yarco', 'last_name' => 'Wang', 'not_exist' => '__void__'], $placeholders);
//
//        // cause no `not_exist` field
//        $this->assertEquals(
//            ['birth_date' => '1980-05-17', 'first_name' => 'Yarco', 'last_name' => 'Wang'],
//            $valid_data
//        );
//        $this->assertEquals(
//            [':birth_date', ':first_name', ':last_name'],
//            $placeholders
//        );
//    }

    public function testGet()
    {
        $this->_test_db->init();

        $stmt = $this->_test_db->get('employees', 463286);
        $this->assertInstanceOf(
            \PDOStatement::class,
            $stmt
        );
        $this->assertEquals(
            ['emp_no' => 463286, 'birth_date' => '1952-05-26', 'first_name' => 'Kwangho', 'last_name' => 'Avouris', 'gender' => 'F', 'hire_date' => '1986-08-19'],
            $stmt->fetch(\PDO::FETCH_ASSOC)
        );
    }

    public function testUpdate()
    {
        $this->_test_db->init();
        $this->_test_db->update('employees', ['first_name' => 'Yarco'], 463286);

        $data = $this->_test_db->get('employees', 463286)->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(
            'Yarco',
            $data['first_name']
        );

        // clean
        $this->_test_db->update('employees', ['first_name' => 'Kwangho'], 463286);
    }

    public function testFind()
    {
        $this->_test_db->init();

        // exists
        $stmt = $this->_test_db->find('employees', 'first_name=:first_name AND last_name=:last_name', [
            'first_name' => 'Kwangho',
            'last_name' => 'Avouris'
        ]);
        $this->assertInstanceOf(
            \PDOStatement::class,
            $stmt
        );
        $this->assertEquals(
            ['emp_no' => 463286, 'birth_date' => '1952-05-26', 'first_name' => 'Kwangho', 'last_name' => 'Avouris', 'gender' => 'F', 'hire_date' => '1986-08-19'],
            $stmt->fetch(\PDO::FETCH_ASSOC)
        );

        // not exists
        $stmt = $this->_test_db->find('employees', 'first_name=:first_name AND last_name=:last_name', [
            'first_name' => 'Yarco',
            'last_name' => 'Wang'
        ]);
        $this->assertInstanceOf(
            \PDOStatement::class,
            $stmt
        );
        $this->assertEmpty($stmt->fetch(\PDO::FETCH_ASSOC));
    }

    public function testDelete()
    {
        $this->_test_db->init();

        $ret = $this->_test_db->insert(
            'employees',
            [
                ['birth_date' => '1980-05-17', 'first_name' => 'Yarco', 'last_name' => 'Wang'],
            ],
            true
        );
        $ret2 = $this->_test_db->delete('employees', 'first_name=:first_name', ['first_name' => 'Yarco']);
        $this->assertNotFalse($ret2);

        $this->assertEmpty(
            $this->_test_db->get('employees', $ret)->fetch(\PDO::FETCH_ASSOC)
        );
    }

    public function testInsertArray()
    {
        $this->_test_db->init();

        $ret = $this->_test_db->insert(
            'employees',
            [
                ['birth_date' => '1980-05-17', 'first_name' => 'Yarco', 'last_name' => 'Wang'],
            ],
            true
        );
        $me = $this->_test_db->get('employees', $ret)->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals('Yarco', $me['first_name']);

        // cleanup
        $this->assertEquals(
            1,
            $this->_test_db->rm('employees', $ret)
        );
    }

    public function testInsertCallback()
    {
        $this->_test_db->init();

        $callback = function() {
            yield ['birth_date' => '1980-05-17', 'first_name' => 'Yarco', 'last_name' => 'Wang'];
        };

        $ret = $this->_test_db->insert(
            'employees',
            $callback,
            true
        );
        $me = $this->_test_db->get('employees', $ret)->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals('Yarco', $me['first_name']);

        // cleanup
        $this->assertEquals(
            1,
            $this->_test_db->rm('employees', $ret)
        );
    }

}
