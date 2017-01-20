<?php
/**
 * Proj. dml.php
 *
 * @author Yarco Wang <yarco.wang@gmail.com>
 * @since 17/1/15 上午11:51
 */

namespace Yarco\Dml;


class Util
{
    /**
     * basic used in convert class name to mysql table name
     *
     * @param string $className
     * @return string
     */
    public static function NameConvert(string $className)
    {
        static $from = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
        static $to = ['_a','_b','_c','_d','_e','_f','_g','_h','_i','_j','_k','_l','_m','_n','_o','_p','_q','_r','_s','_t','_u','_v','_w','_x','_y','_z'];
        return substr(str_replace($from, $to, $className), 1);
    }
}