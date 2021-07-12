<?php

namespace iDB;

use iDB\xDB\Container;
use Mockery as m;

/**
 * @internal
 * @coversNothing
 */
final class TestCase extends \PHPUnit\Framework\TestCase {
    /**
     * @var Container
     */
    protected $container;
    protected $mockConnection;
    protected $mockPdo;
    protected $mockPdoStatement;

    protected function setUp(): void {
        $this->container = new Container();

        $this->mockPdoStatement = $this->createMock('\\PDOStatement');

        $mockPdoStatement = &$this->mockPdoStatement;

        $mockPdoStatement->bindings = [];

        $this->mockPdoStatement
            ->expects(static::any())
            ->method('bindValue')
            ->willReturnCallback(function($parameter, $value, $dataType) use ($mockPdoStatement): void {
                $mockPdoStatement->bindings[] = [$value, $dataType];
            })
        ;

        $this->mockPdoStatement
            ->expects(static::any())
            ->method('execute')
            ->willReturnCallback(function($bindings = null) use ($mockPdoStatement): void {
                if ($bindings) {
                    $mockPdoStatement->bindings = $bindings;
                }
            })
        ;

        $this->mockPdoStatement
            ->expects(static::any())
            ->method('fetchAll')
            ->willReturnCallback(function() use ($mockPdoStatement) {
                return [$mockPdoStatement->sql, $mockPdoStatement->bindings];
            })
        ;

        $this->mockPdo = $this->createPartialMock('\\iDB\\MockPdo', ['prepare', 'setAttribute', 'quote', 'lastInsertId']);

        $this->mockPdo
            ->expects(static::any())
            ->method('prepare')
            ->willReturnCallback(function($sql) use ($mockPdoStatement) {
                $mockPdoStatement->sql = $sql;

                return $mockPdoStatement;
            })
        ;

        $this->mockPdo
            ->expects(static::any())
            ->method('quote')
            ->willReturnCallback(function($value) {
                return "'{$value}'";
            })
        ;

        $eventHandler = new EventHandler();

        $this->mockConnection = m::mock('\\iDB\\Connection');
        $this->mockConnection->shouldReceive('getPdoInstance')->andReturn($this->mockPdo);
        $this->mockConnection->shouldReceive('getAdapter')->andReturn('mysql');
        $this->mockConnection->shouldReceive('getAdapterConfig')->andReturn(['prefix' => 'cb_']);
        $this->mockConnection->shouldReceive('getContainer')->andReturn($this->container);
        $this->mockConnection->shouldReceive('getEventHandler')->andReturn($eventHandler);
    }

    protected function tearDown(): void {
        m::close();
    }

    public function callbackMock() {
        $args = \func_get_args();

        return \count($args) === 1 ? $args[0] : $args;
    }
}

class MockPdo extends \PDO {
    public function __construct() {
    }
}