<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateAddresses extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('addresses');

        $table->addPrimaryKey('id');

        $table->addColumn('foreign_table', 'string', [
            'limit' => 100,
            'null' => false
        ]);

        $table->addColumn('foreign_id', 'integer', [
            'null' => false
        ]);

        $table->addColumn('postal_code', 'string', [
            'limit' => 9,
            'null' => false
        ]);

        $table->addColumn('state', 'string', [
            'limit' => 2,
            'null' => false
        ]);

        $table->addColumn('city', 'string', [
            'limit' => 200,
            'null' => false]
        );

        $table->addColumn('sublocality', 'string', [
            'limit' => 200,
            'null' => false
        ]);

        $table->addColumn('street', 'string', [
            'limit' => 200,
            'null' => false
        ]);

        $table->addColumn('street_number', 'string', [
            'limit' => 200,
            'null' => false
        ]);

        $table->addColumn('complement', 'string', [
            'limit' => 200,
            'null' => false,
            'default' => ''
        ]);

        $table->addIndex(['foreign_table', 'foreign_id'], [
            'name' => 'foreign_table__foreign_id'
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
