<?php

namespace iDB;

use iDB\QueryBuilder\QueryBuilderHandler;

/**
 * This class gives the ability to access non-static methods statically.
 *
 * Class AliasFacade
 */
class AliasFacade {
    /**
     * @var QueryBuilderHandler
     */
    protected static $queryBuilderInstance;

    /**
     * @param $method
     * @param $args
     */
    public static function __callStatic($method, $args) {
        if (!static::$queryBuilderInstance) {
            static::$queryBuilderInstance = new QueryBuilderHandler();
        }

        // Call the non-static method from the class instance
        return \call_user_func_array([static::$queryBuilderInstance, $method], $args);
    }

    /**
     * @param QueryBuilderHandler $queryBuilderInstance
     */
    public static function setQueryBuilderInstance($queryBuilderInstance): void {
        static::$queryBuilderInstance = $queryBuilderInstance;
    }
}
