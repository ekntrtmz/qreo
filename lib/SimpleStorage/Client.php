<?php
/**
 * This file is part of the Cockpit project.
 *
 * (c) Artur Heinze - 🅰🅶🅴🅽🆃🅴🅹🅾, http://agentejo.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SimpleStorage;

class Client {

    protected $driver;

    public function __construct($server, $options=[]) {

        $this->driver = new \RedisLite(str_replace('redislite://', '', $server), $options);

    }

    public function get($key, $default = false) {
        
        $val = $this->driver->get($key);

        if ($val === false) {
            return $default;
        }

        return $val;
    }


    public function __call($method, $args) {

        return call_user_func_array([$this->driver, $method], $args);
    }
}
