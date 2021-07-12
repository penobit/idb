<?php

namespace iDB\ConnectionAdapters;

abstract class BaseAdapter {
    /**
     * @var \iDB\xDB\Container
     */
    protected $container;

    public function __construct(\iDB\xDB\Container $container) {
        $this->container = $container;
    }

    /**
     * @param $config
     *
     * @return \PDO
     */
    public function connect($config) {
        if (!isset($config['options'])) {
            $config['options'] = [];
        }

        return $this->doConnect($config);
    }

    /**
     * @param $config
     */
    abstract protected function doConnect($config);
}
