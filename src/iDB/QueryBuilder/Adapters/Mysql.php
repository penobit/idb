<?php

namespace iDB\QueryBuilder\Adapters;

class Mysql extends BaseAdapter {
    /**
     * @var string
     */
    protected $sanitizer = '`';
}
