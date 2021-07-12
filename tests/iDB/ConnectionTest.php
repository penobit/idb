<?php

namespace iDB;

use Mockery as m;

/**
 * @internal
 * @coversNothing
 */
final class ConnectionTest extends TestCase {
    private $mysqlConnectionMock;
    private $connection;

    protected function setUp(): void {
        parent::setUp();

        $this->mysqlConnectionMock = m::mock('\\iDB\\ConnectionAdapters\\Mysql');
        $this->mysqlConnectionMock->shouldReceive('connect')->andReturn($this->mockPdo);

        $this->container->setInstance('\\iDB\\ConnectionAdapters\\Mysqlmock', $this->mysqlConnectionMock);
        $this->connection = new Connection('mysqlmock', ['prefix' => 'cb_'], null, $this->container);
    }

    public function testConnection(): void {
        static::assertSame($this->mockPdo, $this->connection->getPdoInstance());
        static::assertInstanceOf('\\PDO', $this->connection->getPdoInstance());
        static::assertSame('mysqlmock', $this->connection->getAdapter());
        static::assertSame(['prefix' => 'cb_'], $this->connection->getAdapterConfig());
    }

    public function testQueryBuilderAliasCreatedByConnection(): void {
        $mockQBAdapter = m::mock('\\iDB\\QueryBuilder\\Adapters\\Mysql');

        $this->container->setInstance('\\iDB\\QueryBuilder\\Adapters\\Mysqlmock', $mockQBAdapter);
        $connection = new Connection('mysqlmock', ['prefix' => 'cb_'], 'DBAlias', $this->container);
        static::assertSame($this->mockPdo, $connection->getPdoInstance());
        static::assertInstanceOf('\\iDB\\QueryBuilder\\QueryBuilderHandler', \DBAlias::newQuery());
    }
}