<?php

namespace iDB;

use iDB\QueryBuilder\QueryBuilderHandler;

/**
 * @internal
 * @coversNothing
 */
final class QueryBuilderBehaviorTest extends TestCase {
    private $builder;

    protected function setUp(): void {
        parent::setUp();

        $this->builder = new QueryBuilder\QueryBuilderHandler($this->mockConnection);
    }

    public function testSelectFlexibility(): void {
        $query = $this->builder
            ->select('foo')
            ->select(['bar', 'baz'])
            ->select('qux', 'lol', 'wut')
            ->from('t')
        ;
        static::assertSame(
            'SELECT `foo`, `bar`, `baz`, `qux`, `lol`, `wut` FROM `cb_t`',
            $query->getQuery()->getRawSql(),
            'SELECT is pretty flexible!'
        );
    }

    public function testSelectQuery(): void {
        $subQuery = $this->builder->table('person_details')->select('details')->where('person_id', '=', 3);

        $query = $this->builder->table('my_table')
            ->select('my_table.*')
            ->select([$this->builder->raw('count(cb_my_table.id) as tot'), $this->builder->subQuery($subQuery, 'pop')])
            ->where('value', '=', 'Ifrah')
            ->whereNot('my_table.id', -1)
            ->orWhereNot('my_table.id', -2)
            ->orWhereIn('my_table.id', [1, 2])
            ->groupBy(['value', 'my_table.id', 'person_details.id'])
            ->orderBy('my_table.id', 'DESC')
            ->orderBy('value')
            ->having('tot', '<', 2)
            ->limit(1)
            ->offset(0)
            ->join(
                'person_details',
                'person_details.person_id',
                '=',
                'my_table.id'
            )
        ;

        $nestedQuery = $this->builder->table($this->builder->subQuery($query, 'bb'))->select('*');
        static::assertSame("SELECT * FROM (SELECT `cb_my_table`.*, count(cb_my_table.id) as tot, (SELECT `details` FROM `cb_person_details` WHERE `person_id` = 3) as pop FROM `cb_my_table` INNER JOIN `cb_person_details` ON `cb_person_details`.`person_id` = `cb_my_table`.`id` WHERE `value` = 'Ifrah' AND NOT `cb_my_table`.`id` = -1 OR NOT `cb_my_table`.`id` = -2 OR `cb_my_table`.`id` IN (1, 2) GROUP BY `value`, `cb_my_table`.`id`, `cb_person_details`.`id` HAVING `tot` < 2 ORDER BY `cb_my_table`.`id` DESC, `value` ASC LIMIT 1 OFFSET 0) as bb", $nestedQuery->getQuery()->getRawSql());
    }

    public function testSelectAliases(): void {
        $query = $this->builder->from('my_table')->select('foo')->select(['bar' => 'baz', 'qux']);

        static::assertSame(
            'SELECT `foo`, `bar` AS `baz`, `qux` FROM `cb_my_table`',
            $query->getQuery()->getRawSql()
        );
    }

    public function testRawStatementsWithinCriteria(): void {
        $query = $this->builder->from('my_table')
            ->where('simple', 'criteria')
            ->where($this->builder->raw('RAW'))
            ->where($this->builder->raw('PARAMETERIZED_ONE(?)', 'foo'))
            ->where($this->builder->raw('PARAMETERIZED_SEVERAL(?, ?, ?)', [1, '2', 'foo']))
        ;

        static::assertSame(
            "SELECT * FROM `cb_my_table` WHERE `simple` = 'criteria' AND RAW AND PARAMETERIZED_ONE('foo') AND PARAMETERIZED_SEVERAL(1, '2', 'foo')",
            $query->getQuery()->getRawSql()
        );
    }

    public function testStandaloneWhereNot(): void {
        $query = $this->builder->table('my_table')->whereNot('foo', 1);
        static::assertSame('SELECT * FROM `cb_my_table` WHERE NOT `foo` = 1', $query->getQuery()->getRawSql());
    }

    public function testSelectDistinct(): void {
        $query = $this->builder->selectDistinct(['name', 'surname'])->from('my_table');
        static::assertSame('SELECT DISTINCT `name`, `surname` FROM `cb_my_table`', $query->getQuery()->getRawSql());
    }

    public function testSelectDistinctWithSingleColumn(): void {
        $query = $this->builder->selectDistinct('name')->from('my_table');
        static::assertSame('SELECT DISTINCT `name` FROM `cb_my_table`', $query->getQuery()->getRawSql());
    }

    public function testSelectDistinctAndSelectCalls(): void {
        $query = $this->builder->select('name')->selectDistinct('surname')->select(['birthday', 'address'])->from('my_table');
        static::assertSame('SELECT DISTINCT `name`, `surname`, `birthday`, `address` FROM `cb_my_table`', $query->getQuery()->getRawSql());
    }

    public function testSelectQueryWithNestedCriteriaAndJoins(): void {
        $builder = $this->builder;

        $query = $builder->table('my_table')
            ->where('my_table.id', '>', 1)
            ->orWhere('my_table.id', 1)
            ->where(function($q): void {
                $q->where('value', 'LIKE', '%sana%');
                $q->orWhere(function($q2): void {
                    $q2->where('key', 'LIKE', '%sana%');
                    $q2->orWhere('value', 'LIKE', '%sana%');
                });
            })
            ->join(['person_details', 'a'], 'a.person_id', '=', 'my_table.id')

            ->leftJoin(['person_details', 'b'], function($table) use ($builder): void {
                $table->on('b.person_id', '=', 'my_table.id');
                $table->on('b.deleted', '=', $builder->raw(0));
                $table->orOn('b.age', '>', $builder->raw(1));
            })
        ;

        static::assertSame("SELECT * FROM `cb_my_table` INNER JOIN `cb_person_details` AS `cb_a` ON `cb_a`.`person_id` = `cb_my_table`.`id` LEFT JOIN `cb_person_details` AS `cb_b` ON `cb_b`.`person_id` = `cb_my_table`.`id` AND `cb_b`.`deleted` = 0 OR `cb_b`.`age` > 1 WHERE `cb_my_table`.`id` > 1 OR `cb_my_table`.`id` = 1 AND (`value` LIKE '%sana%' OR (`key` LIKE '%sana%' OR `value` LIKE '%sana%'))", $query->getQuery()->getRawSql());
    }

    public function testSelectWithQueryEvents(): void {
        $builder = $this->builder;

        $builder->registerEvent('before-select', ':any', function($qb): void {
            $qb->whereIn('status', [1, 2]);
        });

        $query = $builder->table('some_table')->where('name', 'Some');
        $query->get();
        $actual = $query->getQuery()->getRawSql();

        static::assertSame("SELECT * FROM `cb_some_table` WHERE `name` = 'Some' AND `status` IN (1, 2)", $actual);
    }

    public function testEventPropagation(): void {
        $builder = $this->builder;
        $counter = 0;

        foreach (['before', 'after'] as $prefix) {
            foreach (['insert', 'select', 'update', 'delete'] as $action) {
                $builder->registerEvent("{$prefix}-{$action}", ':any', function($qb) use (&$counter) {
                    return $counter++;
                });
            }
        }

        $insert = $builder->table('foo')->insert(['bar' => 'baz']);
        static::assertSame(0, $insert);
        static::assertSame(1, $counter, 'after-insert was not called');

        $select = $builder->from('foo')->select('bar')->get();
        static::assertSame(1, $select);
        static::assertSame(2, $counter, 'after-select was not called');

        $update = $builder->table('foo')->update(['bar' => 'baz']);
        static::assertSame(2, $update);
        static::assertSame(3, $counter, 'after-update was not called');

        $delete = $builder->from('foo')->delete();
        static::assertSame(3, $delete);
        static::assertSame(4, $counter, 'after-delete was not called');
    }

    public function testInsertQuery(): void {
        $builder = $this->builder->from('my_table');
        $data = ['key' => 'Name',
            'value' => 'Sana', ];

        static::assertSame("INSERT INTO `cb_my_table` (`key`,`value`) VALUES ('Name','Sana')", $builder->getQuery('insert', $data)->getRawSql());
    }

    public function testInsertIgnoreQuery(): void {
        $builder = $this->builder->from('my_table');
        $data = ['key' => 'Name',
            'value' => 'Sana', ];

        static::assertSame("INSERT IGNORE INTO `cb_my_table` (`key`,`value`) VALUES ('Name','Sana')", $builder->getQuery('insertignore', $data)->getRawSql());
    }

    public function testReplaceQuery(): void {
        $builder = $this->builder->from('my_table');
        $data = ['key' => 'Name',
            'value' => 'Sana', ];

        static::assertSame("REPLACE INTO `cb_my_table` (`key`,`value`) VALUES ('Name','Sana')", $builder->getQuery('replace', $data)->getRawSql());
    }

    public function testInsertOnDuplicateKeyUpdateQuery(): void {
        $builder = $this->builder;
        $data = [
            'name' => 'Sana',
            'counter' => 1,
        ];
        $dataUpdate = [
            'name' => 'Sana',
            'counter' => 2,
        ];
        $builder->from('my_table')->onDuplicateKeyUpdate($dataUpdate);
        static::assertSame("INSERT INTO `cb_my_table` (`name`,`counter`) VALUES ('Sana',1) ON DUPLICATE KEY UPDATE `name`='Sana',`counter`=2", $builder->getQuery('insert', $data)->getRawSql());
    }

    public function testUpdateQuery(): void {
        $builder = $this->builder->table('my_table')->where('value', 'Sana');

        $data = [
            'key' => 'Sana',
            'value' => 'Amrin',
        ];

        static::assertSame("UPDATE `cb_my_table` SET `key`='Sana',`value`='Amrin' WHERE `value` = 'Sana'", $builder->getQuery('update', $data)->getRawSql());
    }

    public function testDeleteQuery(): void {
        $this->builder = new QueryBuilder\QueryBuilderHandler($this->mockConnection);

        $builder = $this->builder->table('my_table')->where('value', '=', 'Amrin');

        static::assertSame("DELETE FROM `cb_my_table` WHERE `value` = 'Amrin'", $builder->getQuery('delete')->getRawSql());
    }

    public function testOrderByFlexibility(): void {
        $query = $this->builder
            ->from('t')
            ->orderBy('foo', 'DESC')
            ->orderBy(['bar', 'baz' => 'ASC', $this->builder->raw('raw1')], 'DESC')
            ->orderBy($this->builder->raw('raw2'), 'DESC')
        ;

        static::assertSame(
            'SELECT * FROM `cb_t` ORDER BY `foo` DESC, `bar` DESC, `baz` ASC, raw1 DESC, raw2 DESC',
            $query->getQuery()->getRawSql(),
            'ORDER BY is flexible enough!'
        );
    }

    public function testSelectQueryWithNull(): void {
        $query = $this->builder->from('my_table')
            ->whereNull('key1')
            ->orWhereNull('key2')
            ->whereNotNull('key3')
            ->orWhereNotNull('key4')
        ;

        static::assertSame(
            'SELECT * FROM `cb_my_table` WHERE `key1` IS  NULL OR `key2` IS  NULL AND `key3` IS NOT NULL OR `key4` IS NOT NULL',
            $query->getQuery()->getRawSql()
        );
    }

    public function testIsPossibleToUseSubqueryInWhereClause(): void {
        $sub = clone $this->builder;
        $query = $this->builder->from('my_table')->whereIn('foo', $this->builder->subQuery(
            $sub->from('some_table')->select('foo')->where('id', 1)
        ));
        static::assertSame(
            'SELECT * FROM `cb_my_table` WHERE `foo` IN (SELECT `foo` FROM `cb_some_table` WHERE `id` = 1)',
            $query->getQuery()->getRawSql()
        );
    }

    public function testIsPossibleToUseSubqueryInWhereNotClause(): void {
        $sub = clone $this->builder;
        $query = $this->builder->from('my_table')->whereNotIn('foo', $this->builder->subQuery(
            $sub->from('some_table')->select('foo')->where('id', 1)
        ));
        static::assertSame(
            'SELECT * FROM `cb_my_table` WHERE `foo` NOT IN (SELECT `foo` FROM `cb_some_table` WHERE `id` = 1)',
            $query->getQuery()->getRawSql()
        );
    }

    public function testYouCanSetFetchModeFromConstructorAsOptionalParameter(): void {
        $selectedFetchMode = \PDO::FETCH_ASSOC;
        $builder = new QueryBuilderHandler($this->mockConnection, $selectedFetchMode);
        static::assertSame($selectedFetchMode, $builder->getFetchMode());
    }

    public function testFetchModeSelectedWillBeMaintainedBetweenInstances(): void {
        $selectedFetchMode = \PDO::FETCH_ASSOC;
        $builder = new QueryBuilderHandler($this->mockConnection, $selectedFetchMode);
        $newBuilder = $builder->table('stuff');

        static::assertSame($selectedFetchMode, $newBuilder->getFetchMode());
    }
}
