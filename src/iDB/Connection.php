<?php

namespace iDB;

use iDB\xDB\Container;

class Connection {
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var string
     */
    protected $adapter;

    /**
     * @var array
     */
    protected $adapterConfig;

    /**
     * @var \PDO
     */
    protected $pdoInstance;

    /**
     * @var Connection
     */
    protected static $storedConnection;

    /**
     * @var EventHandler
     */
    protected $eventHandler;

    /**
     * @param $adapter
     * @param null|string $alias
     * @param Container $container
     */
    public function __construct($adapter, array $adapterConfig, $alias = null, Container $container = null) {
        $container = $container ?: new Container();

        $this->container = $container;

        $this->setAdapter($adapter)->setAdapterConfig($adapterConfig)->connect();

        // Create event dependency
        $this->eventHandler = $this->container->build('\\iDB\\EventHandler');

        if ($alias) {
            $this->createAlias($alias);
        }
    }

    /**
     * Create an easily accessible query builder alias.
     *
     * @param $alias
     */
    public function createAlias($alias): void {
        class_alias('iDB\\AliasFacade', $alias);
        $builder = $this->container->build('\\iDB\\QueryBuilder\\QueryBuilderHandler', [$this]);
        AliasFacade::setQueryBuilderInstance($builder);
    }

    /**
     * Returns an instance of Query Builder.
     */
    public function getQueryBuilder() {
        return $this->container->build('\\iDB\\QueryBuilder\\QueryBuilderHandler', [$this]);
    }

    /**
     * @return $this
     */
    public function setPdoInstance(\PDO $pdo) {
        $this->pdoInstance = $pdo;

        return $this;
    }

    /**
     * @return \PDO
     */
    public function getPdoInstance() {
        return $this->pdoInstance;
    }

    /**
     * @param $adapter
     *
     * @return $this
     */
    public function setAdapter($adapter) {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * @return string
     */
    public function getAdapter() {
        return $this->adapter;
    }

    /**
     * @return $this
     */
    public function setAdapterConfig(array $adapterConfig) {
        $this->adapterConfig = $adapterConfig;

        return $this;
    }

    /**
     * @return array
     */
    public function getAdapterConfig() {
        return $this->adapterConfig;
    }

    /**
     * @return Container
     */
    public function getContainer() {
        return $this->container;
    }

    /**
     * @return EventHandler
     */
    public function getEventHandler() {
        return $this->eventHandler;
    }

    /**
     * @return Connection
     */
    public static function getStoredConnection() {
        return static::$storedConnection;
    }

    /**
     * Create the connection adapter.
     */
    protected function connect(): void {
        // Build a database connection if we don't have one connected

        $adapter = '\\iDB\\ConnectionAdapters\\'.ucfirst(strtolower($this->adapter));

        $adapterInstance = $this->container->build($adapter, [$this->container]);

        $pdo = $adapterInstance->connect($this->adapterConfig);
        $this->setPdoInstance($pdo);

        // Preserve the first database connection with a static property
        if (!static::$storedConnection) {
            static::$storedConnection = $this;
        }
    }
}
