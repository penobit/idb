<?php

namespace iDB\xDB;

/**
 * This class gives the ability to access non-static methods statically.
 *
 * Class AliasFacade
 */
class AliasFacade {
    /**
     * @var Container
     */
    protected static $xDBInstance;

    /**
     * @param $method
     * @param $args
     */
    public static function __callStatic($method, $args) {
        if (!static::$xDBInstance) {
            static::$xDBInstance = new iDB\xDB\Container();
        }

        return \call_user_func_array([static::$xDBInstance, $method], $args);
    }

    /**
     * @param Container $instance
     */
    public static function setxDBInstance(iDB\xDB\Container $instance): void {
        static::$xDBInstance = $instance;
    }

    /**
     * @return \xDB\Container $instance
     */
    public static function getxDBInstance() {
        return static::$xDBInstance;
    }
}