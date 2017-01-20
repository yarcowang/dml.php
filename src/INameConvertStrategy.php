<?php
/**
 * Proj. dml.php
 *
 * @author Yarco Wang <yarco.wang@gmail.com>
 * @since 17/1/15 下午12:06
 */

namespace Yarco\Dml;


interface INameConvertStrategy
{
    function convert(string $className);
}