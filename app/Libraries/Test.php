<?php
/**
 * Created by PhpStorm.
 * User: jaume
 * Date: 01/11/2019
 * Time: 07:44
 */

namespace App\Libraries;


class Test implements TestInterface
{
    private $var;
    private $var2;


    function __construct($var) {
        $this->var = $var;
    }

    public function set($var) {

        $this->var = $var;
        $this->var2 = $var;
    }

    public function get() {

        return $this->var;
    }

    public function get2() {

        return $this->var2;
    }



}