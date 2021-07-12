<?php

namespace iDB;

use iDB\QueryBuilder\QueryBuilderHandler;
use PDO;

/**
 * @internal
 * @coversNothing
 */
final class QueryBuilderTest extends TestCase {
    /**
     * @var QueryBuilderHandler
     */
    protected $builder;

    protected function setUp(): void {
        parent::setUp();

        $this->builder = new QueryBuilderHandler($this->mockConnection);
    }

    public function testRawQuery(): void {
        $query = 'select * from cb_my_table where id = ? and name = ?';
        $bindings = [5, 'penobit'];
        $queryArr = $this->builder->query($query, $bindings)->get();
        static::assertSame(
            [
                $query,
                [[5, PDO::PARAM_INT], ['penobit', PDO::PARAM_STR]],
            ],
            $queryArr
        );
    }

    public function testInsertQueryReturnsIdForInsert(): void {
        $this->mockPdoStatement
            ->expects(static::once())
            ->method('rowCount')
            ->willReturn(1)
        ;

        $this->mockPdo
            ->expects(static::once())
            ->method('lastInsertId')
            ->willReturn(11)
        ;

        $id = $this->builder->table('test')->insert([
            'id' => 5,
            'name' => 'penobit',
        ]);

        static::assertSame(11, $id);
    }

    public function testInsertQueryReturnsIdForInsertIgnore(): void {
        $this->mockPdoStatement
            ->expects(static::once())
            ->method('rowCount')
            ->willReturn(1)
        ;

        $this->mockPdo
            ->expects(static::once())
            ->method('lastInsertId')
            ->willReturn(11)
        ;

        $id = $this->builder->table('test')->insertIgnore([
            'id' => 5,
            'name' => 'penobit',
        ]);

        static::assertSame(11, $id);
    }

    public function testInsertQueryReturnsNullForIgnoredInsert(): void {
        $this->mockPdoStatement
            ->expects(static::once())
            ->method('rowCount')
            ->willReturn(0)
        ;

        $id = $this->builder->table('test')->insertIgnore([
            'id' => 5,
            'name' => 'penobit',
        ]);

        static::assertNull($id);
    }

    public function testTableNotSpecifiedException(): void {
        $this->expectException(\iDB\Exception::class);
        $this->expectExceptionCode(3);

        $this->builder->where('a', 'b')->get();
    }
}