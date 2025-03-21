<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * VisitsFixture
 */
class VisitsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'date' => '2025-03-21',
                'completed' => '02:05:29',
                'forms' => 1,
                'products' => 1,
                'duration' => 1,
                'created_at' => 1742522729,
                'updated_at' => 1742522729,
            ],
        ];
        parent::init();
    }
}
