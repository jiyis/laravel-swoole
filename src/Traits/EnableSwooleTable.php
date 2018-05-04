<?php

namespace Jiyis\Traits;

use Jiyis\Illuminate\Table\SwooleTable;
use Swoole\Table;

trait EnableSwooleTable
{
    /**
     * @var \Jiyis\Illuminate\Table
     */
    protected $table;

    /**
     * Register customized swoole talbes.
     */
    protected function createTables()
    {
        $this->table = new SwooleTable();
        $this->registerTables();
    }

    /**
     * Register user-defined swoole tables.
     */
    protected function registerTables()
    {
        $tables = $this->container['config']->get('swoole_http.tables', []);

        foreach ($tables as $key => $value) {
            $table = new Table($value['size']);
            $columns = $value['columns'] ?? [];
            foreach ($columns as $column) {
                if (isset($column['size'])) {
                    $table->column($column['name'], $column['type'], $column['size']);
                } else {
                    $table->column($column['name'], $column['type']);
                }
            }
            $table->create();

            $this->table->add($key, $table);
        }
    }

    /**
     * Bind swoole table to Laravel app container.
     */
    protected function bindSwooleTable()
    {
        $this->app->singleton('swoole.table', function () {
            return $this->table;
        });
    }
}
