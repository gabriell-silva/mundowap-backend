<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\WorkdaysController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\WorkdaysController Test Case
 *
 * @uses \App\Controller\WorkdaysController
 */
class WorkdaysControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Workdays',
    ];
}
