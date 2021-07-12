<?php

namespace iDB\QueryBuilder;

class Transaction extends QueryBuilderHandler {
    /**
     * Commit the database changes.
     */
    public function commit(): void {
        $this->pdo->commit();

        throw new TransactionHaltException();
    }

    /**
     * Rollback the database changes.
     */
    public function rollback(): void {
        $this->pdo->rollBack();

        throw new TransactionHaltException();
    }
}
