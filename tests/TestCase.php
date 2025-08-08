<?php

/**
 * (c) sauber digital <info@sauberdigital.de>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sd\DynamicFormsBundle\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

abstract class TestCase extends BaseTestCase
{
    protected FormFactoryInterface $formFactory;
    protected FormBuilderInterface $formBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->formFactory = Forms::createFormFactory();
        $this->formBuilder = $this->formFactory->createBuilder();
    }

    protected function tearDown(): void
    {
        unset($this->formFactory, $this->formBuilder);
        
        parent::tearDown();
    }
}