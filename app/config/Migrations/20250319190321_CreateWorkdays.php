<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateWorkdays extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('workdays');

        $table->addPrimaryKey('id');

        $table->addColumn('date', 'date', [
            'null' => false
        ]);

        $table->addColumn('visits', 'integer', [
            'null' => false,
            'default' => 0
        ]);

        $table->addColumn('completed', 'integer', [
            'null' => false,
            'default' => 0
        ]);

        $table->addColumn('duration', 'integer', [
            'null' => false,
            'default' => 0
        ]);

        $table->addIndex(['date'], [
            'name' => 'date'
        ]);

        $table->addColumn('created_at', 'timestamp', [
            'default' => 'CURRENT_TIMESTAMP'
        ]);

        $table->addColumn('updated_at', 'timestamp', [
            'default' => 'CURRENT_TIMESTAMP'
        ]);

        $table->create();
    }
}
