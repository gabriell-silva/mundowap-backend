<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateVisits extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('visits');

        $table->addPrimaryKey('id');

        $table->addColumn('date', 'date', [
            'null' => false
        ]);

        $table->addColumn('completed', 'integer', [
            'null' => false,
            'default' => '0'
        ]);

        $table->addColumn('forms', 'integer', [
            'null' => false
        ]);

        $table->addColumn('products', 'integer', [
            'null' => false
        ]);

        $table->addColumn('duration', 'integer', [
            'null' => false,
            'default' => '0'
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
