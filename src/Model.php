<?php
/**
 * Proj. dml.php
 *
 * @author Yarco Wang <yarco.wang@gmail.com>
 * @since 17/1/15 上午10:30
 */

namespace Yarco\Dml;


abstract class Model
{
    protected $_table;
    protected $_pk;
    protected $_context;

    protected $_modified = false;

    // auto triggers
    protected $_fields = [];
    protected $_triggers = [];

    // data
    protected $_originalData = [];
    protected $_data = [];
    protected $_changed = [];

    /**
     * Model constructor.
     * @param string $table
     * @param array $context
     */
    final public function __construct(string $table, array $context = [])
    {
        $this->_table = $table;
        $this->_pk = $context['IDml']->getPkName($table);
        $this->_context = $context;

        $this->installTriggers();
        $this->doTriggers();

        $this->_data = $this->_originalData;
    }

    /**
     * @param $k
     * @param $v
     * @return mixed
     */
    public function __set($k, $v)
    {
        if (empty($this->_data)) { // initializing
            $this->_originalData[$k] = $v;
        } else {
            $this->_data[$k] = $v;
            $this->_changed[$k] = $v;
            $this->_modified = true;
        }
        return $v;
    }

    /**
     * @param $k
     * @return mixed|null
     */
    public function __get($k)
    {
        return $this->_data[$k] ?? null;
    }

    /**
     * @param $k
     * @return bool
     */
    public function __isset($k)
    {
        return isset($this->_originalData[$k]);
    }

    /**
     * default triggers you would like to use in latest MySQL json type
     */
    public function installTriggers()
    {
        $this->_triggers['json'] = [];
        $this->_triggers['json']['from_string'] = function(string $v) {
            return json_decode($v, true);
        };
        $this->_triggers['json']['to_string'] = function($v) {
            return json_encode($v, JSON_FORCE_OBJECT);
        };
    }

    /**
     * @param string $method
     */
    protected function doTriggers(string $method = 'from_string')
    {
        $key = $method === 'from_string' ? '_originalData' : '_changed';
        foreach($this->_fields as $k => $t) {
            if (!isset($this->$key[$k]) || !isset($this->_triggers[$t]) || !isset($this->_triggers[$t][$method])) {
                continue;
            }
            $this->$key[$k] = $this->_triggers[$t][$method]($this->$key[$k]);
        }
    }

    /**
     * get connection which should implement IDml
     * (actually, it means you should do `extend \PDO implements IDml`)
     *
     * @return mixed
     */
    public function conn()
    {
        return $this->_context['IDml'];
    }

    /**
     * batch mode set
     *
     * @param array $mixed
     * @return $this
     */
    public function set(array $mixed)
    {
        foreach($mixed as $k => $v) {
            if (!isset($this->$k)) continue;
            $this->$k = $v;
        }
        return $this;
    }

    /**
     * save changed data or direct save the data
     *
     * @param array $data
     * @return mixed
     */
    public function save(array $data = [])
    {
        if (!empty($data)) {
            $this->set($data);
        }

        $this->doTriggers('to_string');
        $ret = $this->_context['IDml']->update($this->_table, $this->_changed, $this->{$this->_pk});

        // cleanup
        if ($ret === true) {
            $this->_changed = [];
            $this->_modified = false;
        }

        return $ret;
    }

}