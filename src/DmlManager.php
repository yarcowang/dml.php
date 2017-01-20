<?php
/**
 * Proj. dml.php
 *
 * @author Yarco Wang <yarco.wang@gmail.com>
 * @since 17/1/14 下午7:00
 */

namespace Yarco\Dml;


class DmlManager implements IDml
{
    protected $_curr = '';
    protected $_connections = [];

    /**
     * singleton, if supplied an pdo instance, it means you want to re-initialize the singleton
     *
     * @param Dml|null $pdo
     * @return null|DmlManager
     */
    public static function instance(Dml $pdo = null)
    {
        static $inst = null;

        if (!$pdo) {
            $inst = new self($pdo);
        }

        return $inst;
    }

    /**
     * DmlManager constructor.
     * @param Dml $pdo
     */
    public function __construct(Dml $pdo)
    {
        $pdo->init();
        $this->_curr = 'default';
        $this->_connections['default'] = $pdo;
    }

    /**
     * add a new or overwrite an old pdo connection
     *
     * Notice: you can not change the default connection
     *
     * @param string $key
     * @param Dml $pdo
     * @return $this
     */
    public function add(string $key, Dml $pdo)
    {
        if ($key === 'default') return $this; // You can not change default connection
        $pdo->init();
        $this->_curr = $key;
        $this->_connections[$key] = $pdo;
        return $this;
    }

    /**
     * alias method, same as add
     *
     * @param string $key
     * @param Dml $pdo
     * @return DmlManager
     */
    public function set(string $key, Dml $pdo)
    {
        return $this->add($key, $pdo);
    }

    /**
     * get pdo connection by key, if empty key supplied, it will choose the first matched as the following:
     *  $this->_curr => 'default' => null
     *
     * @param string|null $key
     * @return mixed|null
     */
    public function conn(string $key = null)
    {
        if (!empty($key) && isset($this->_connections[$key])) {
            return $this->_connections[$key];
        }

        return $this->_connections[$this->_curr ?: 'default'] ?? ($this->_connections['default'] ?? null);
    }

    /**
     * get/set current pdo connection
     *
     * @param string|null $current
     * @return string
     */
    public function current(string $current = null)
    {
        if (!empty($current)) {
            $this->_curr = $current;
        }
        return $this->_curr;
    }

    /**
     * make DmlManager works like Dml
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->conn(), $name], $arguments);
    }
}