<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\AddressController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\AddressController Test Case
 *
 * @uses \App\Controller\AddressController
 */
class AddressControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected $fixtures = [
        'app.Address',
    ];
}
