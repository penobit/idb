<?php

namespace iDB;

use iDB\QueryBuilder\QueryBuilderHandler;
use iDB\QueryBuilder\Raw;

class EventHandler {
    /**
     * @var array
     */
    protected $events = [];

    /**
     * @var array
     */
    protected $firedEvents = [];

    /**
     * @return array
     */
    public function getEvents() {
        return $this->events;
    }

    /**
     * @param $event
     * @param $table
     *
     * @return null|callable
     */
    public function getEvent($event, $table = ':any') {
        if ($table instanceof Raw) {
            return null;
        }

        return isset($this->events[$table][$event]) ? $this->events[$table][$event] : null;
    }

    /**
     * @param $event
     * @param string $table
     * @param callable $action
     */
    public function registerEvent($event, $table, \Closure $action): void {
        $table = $table ?: ':any';

        $this->events[$table][$event] = $action;
    }

    /**
     * @param $event
     * @param string $table
     */
    public function removeEvent($event, $table = ':any'): void {
        unset($this->events[$table][$event]);
    }

    /**
     * @param QueryBuilderHandler $queryBuilder
     * @param $event
     */
    public function fireEvents($queryBuilder, $event) {
        $statements = $queryBuilder->getStatements();
        $tables = isset($statements['tables']) ? $statements['tables'] : [];

        // Events added with :any will be fired in case of any table,
        // we are adding :any as a fake table at the beginning.
        array_unshift($tables, ':any');

        // Fire all events
        foreach ($tables as $table) {
            // Fire before events for :any table
            if ($action = $this->getEvent($event, $table)) {
                // Make an event id, with event type and table
                $eventId = $event.$table;

                // Fire event
                $handlerParams = \func_get_args();
                unset($handlerParams[1]); // we do not need $event
                // Add to fired list
                $this->firedEvents[] = $eventId;
                $result = \call_user_func_array($action, $handlerParams);
                if (null !== $result) {
                    return $result;
                }
            }
        }
    }
}
