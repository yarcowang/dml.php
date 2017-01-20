<?php
/**
 * Proj. dml.php
 *
 * @author Yarco Wang <yarco.wang@gmail.com>
 * @since 17/1/15 下午12:06
 */

namespace Yarco\Dml;


class NameConvertStrategy implements INameConvertStrategy
{
    public $strategies = [];

    /**
     * the default implement
     *
     * @return NameConvertStrategy
     */
    public static function GetDefault()
    {
        $me = new Self;
        $me->strategies[] = function(string $s) {   // remove namespace
            return substr($s, ($i = strrpos($s, '\\')) !== false ? $i + 1 : 0);
        };
        $me->strategies[] = [Util::class, 'NameConvert'];
        return $me;
    }

    /**
     * convert class name to table name
     *
     * @param string $className
     * @return mixed|string
     */
    public function convert(string $className)
    {
        $s = $className;
        foreach($this->strategies as $strategy) {
            if (!is_callable($strategy)) continue;
            $s = call_user_func($strategy, $s);
        }
        return $s;
    }
}