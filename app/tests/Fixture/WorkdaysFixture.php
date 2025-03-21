<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * WorkdaysFixture
 */
class WorkdaysFixture extends TestFixture
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
                'visits' => 1,
                'completed' => 1,
                'duration' => 1,
                'created_at' => 1742522751,
                'updated_at' => 1742522751,
            ],
        ];
        parent::init();
    }
}
