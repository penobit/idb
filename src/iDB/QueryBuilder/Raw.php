<?php

namespace iDB\QueryBuilder;

class Raw {
    /**
     * @var string
     */
    protected $value;

    /**
     * @var array
     */
    protected $bindings;

    public function __construct($value, $bindings = []) {
        $this->value = (string) $value;
        $this->bindings = (array) $bindings;
    }

    /**
     * @return string
     */
    public function __toString() {
        return (string) $this->value;
    }

    public function getBindings() {
        return $this->bindings;
    }
}
