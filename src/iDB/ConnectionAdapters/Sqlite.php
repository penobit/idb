<?php

namespace iDB\ConnectionAdapters;

class Sqlite extends BaseAdapter {
    /**
     * @param $config
     */
    public function doConnect($config) {
        $connectionString = 'sqlite:'.$config['database'];

        return $this->container->build(
            '\PDO',
            [$connectionString, null, null, $config['options']]
        );
    }
}
