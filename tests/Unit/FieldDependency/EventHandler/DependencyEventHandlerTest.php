<?php

/**
 * (c) sauber digital <info@sauberdigital.de>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sd\DynamicFormsBundle\Tests\Unit\FieldDependency\EventHandler;

use Sd\DynamicFormsBundle\FieldDependency\DependentField\DependentField;
use Sd\DynamicFormsBundle\FieldDependency\DependentField\DependentFieldConfig;
use Sd\DynamicFormsBundle\FieldDependency\EventHandler\DependencyEventHandler;
use Sd\DynamicFormsBundle\Tests\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class DependencyEventHandlerTest extends TestCase
{
    private DependencyEventHandler $eventHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventHandler = new DependencyEventHandler($this->formBuilder);
    }

    public function testCanCreateEventHandler(): void
    {
        $this->assertInstanceOf(DependencyEventHandler::class, $this->eventHandler);
    }

    public function testCanAddDependentFieldConfig(): void
    {
        $config = new DependentFieldConfig(
            name: 'test_field',
            dependencies: ['parent'],
            callback: function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );

        // Should not throw any exceptions
        $this->eventHandler->addDependentFieldConfig($config);
        
        $this->assertTrue(true);
    }

    public function testInitializeListeners(): void
    {
        // Add a parent field first
        $this->formBuilder->add('parent', TextType::class);
        
        $config = new DependentFieldConfig(
            name: 'test_field',
            dependencies: ['parent'],
            callback: function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );

        $this->eventHandler->addDependentFieldConfig($config);
        
        // Should not throw any exceptions
        $this->eventHandler->initializeListeners(['parent']);
        
        $this->assertTrue(true);
    }

    public function testAddDependencyFieldListeners(): void
    {
        $fieldBuilder = $this->formBuilder->create('test_field', TextType::class);
        
        // Should not throw any exceptions
        $this->eventHandler->addDependencyFieldListeners($fieldBuilder);
        
        $this->assertTrue(true);
    }

    public function testOnPreSetData(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('has')->willReturn(false);
        $form->method('add');
        
        $event = new FormEvent($form, []);
        
        // Should not throw any exceptions
        $this->eventHandler->onPreSetData($event);
        
        $this->assertTrue(true);
    }

    public function testStorePreSetDataDependencyData(): void
    {
        $subForm = $this->createMock(FormInterface::class);
        $subForm->method('getName')->willReturn('field_name');
        
        $event = new FormEvent($subForm, 'test_data');
        
        // Should not throw any exceptions
        $this->eventHandler->storePreSetDataDependencyData($event);
        
        $this->assertTrue(true);
    }

    public function testStorePostSubmitDependencyData(): void
    {
        $subForm = $this->createMock(FormInterface::class);
        $subForm->method('getName')->willReturn('field_name');
        $subForm->method('getData')->willReturn('test_data');
        
        $event = new FormEvent($subForm, null);
        
        // Should not throw any exceptions
        $this->eventHandler->storePostSubmitDependencyData($event);
        
        $this->assertTrue(true);
    }

    public function testClearTransformationErrors(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('has')->willReturn(false);
        
        $event = new FormEvent($form, []);
        
        // Should not throw any exceptions
        $this->eventHandler->clearTransformationErrors($event);
        
        $this->assertTrue(true);
    }
}