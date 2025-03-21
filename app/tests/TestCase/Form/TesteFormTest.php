<?php
declare(strict_types=1);

namespace App\Test\TestCase\Form;

use App\Form\TesteForm;
use Cake\TestSuite\TestCase;

/**
 * App\Form\TesteForm Test Case
 */
class TesteFormTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Form\TesteForm
     */
    protected $Teste;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->Teste = new TesteForm();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Teste);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Form\TesteForm::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
