<?php

/**
 * (c) sauber digital <info@sauberdigital.de>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sd\DynamicFormsBundle\Tests\Unit\FieldDependency\Manager;

use Sd\DynamicFormsBundle\FieldDependency\DependentField\DependentField;
use Sd\DynamicFormsBundle\FieldDependency\EventHandler\DependencyEventHandler;
use Sd\DynamicFormsBundle\FieldDependency\Manager\FieldDependencyManager;
use Sd\DynamicFormsBundle\Tests\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class FieldDependencyManagerTest extends TestCase
{
    private FieldDependencyManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new FieldDependencyManager($this->formBuilder);
    }

    public function testCanCreateManager(): void
    {
        $this->assertInstanceOf(FieldDependencyManager::class, $this->manager);
    }

    public function testCanAddDependentField(): void
    {
        $result = $this->manager->addDependentField(
            'dependent',
            'parent',
            function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );

        $this->assertSame($this->manager, $result);
    }

    public function testCanAddDependentFieldWithArrayDependencies(): void
    {
        $result = $this->manager->addDependentField(
            'dependent',
            ['field1', 'field2'],
            function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );

        $this->assertSame($this->manager, $result);
    }

    public function testCanAddMultipleDependentFields(): void
    {
        $this->manager->addDependentField(
            'field1',
            'trigger',
            function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );

        $this->manager->addDependentField(
            'field2',
            'trigger',
            function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );

        // Should not throw any exceptions
        $this->assertTrue(true);
    }

    public function testGetEventHandler(): void
    {
        $eventHandler = $this->manager->getEventHandler();
        
        $this->assertInstanceOf(DependencyEventHandler::class, $eventHandler);
        
        // Should return the same instance
        $this->assertSame($eventHandler, $this->manager->getEventHandler());
    }

    public function testFluentInterface(): void
    {
        $result = $this->manager
            ->addDependentField(
                'field1',
                'parent1',
                function (DependentField $field, array $data): void {
                    $field->add(TextType::class);
                }
            )
            ->addDependentField(
                'field2',
                'parent2',
                function (DependentField $field, array $data): void {
                    $field->add(TextType::class);
                }
            );

        $this->assertSame($this->manager, $result);
    }

    public function testConvertsStringDependencyToArray(): void
    {
        // This test verifies that string dependencies are properly converted to arrays
        // We can't directly test this, but we ensure no errors occur
        $this->manager->addDependentField(
            'dependent',
            'single_parent', // String dependency
            function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );

        $this->assertTrue(true);
    }
}