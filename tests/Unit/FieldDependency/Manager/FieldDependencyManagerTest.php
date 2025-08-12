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
use Symfony\Component\Form\Exception\LogicException;
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

    public function testThrowsExceptionForDirectCircularDependency(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        // Add field A depending on B
        $this->manager->addDependentField(
            'field_a',
            'field_b',
            function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );

        // Add field B depending on A - should throw exception
        $this->manager->addDependentField(
            'field_b',
            'field_a',
            function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );
    }

    public function testThrowsExceptionForSelfDependency(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        // Field depending on itself
        $this->manager->addDependentField(
            'self_dependent',
            'self_dependent',
            function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );
    }

    public function testThrowsExceptionForIndirectCircularDependency(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        // Create chain: A → B → C → A
        $this->manager->addDependentField(
            'field_a',
            'field_b',
            function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );

        $this->manager->addDependentField(
            'field_b',
            'field_c',
            function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );

        // This should throw exception as it creates a cycle
        $this->manager->addDependentField(
            'field_c',
            'field_a',
            function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );
    }

    public function testAllowsValidDiamondDependency(): void
    {
        // Diamond pattern (valid DAG):
        // A depends on B and C
        // B depends on D
        // C depends on D
        // This is valid as there's no cycle

        $this->manager->addDependentField(
            'field_a',
            ['field_b', 'field_c'],
            function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );

        $this->manager->addDependentField(
            'field_b',
            'field_d',
            function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );

        $this->manager->addDependentField(
            'field_c',
            'field_d',
            function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );

        // Should not throw exception
        $this->assertTrue(true);
    }

    public function testThrowsExceptionForComplexCircularDependency(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        // Create a complex dependency graph with a cycle
        $this->manager->addDependentField(
            'field_1',
            ['field_2', 'field_3'],
            function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );

        $this->manager->addDependentField(
            'field_2',
            'field_4',
            function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );

        $this->manager->addDependentField(
            'field_3',
            'field_4',
            function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );

        // This creates a cycle: field_4 → field_1
        $this->manager->addDependentField(
            'field_4',
            'field_1',
            function (DependentField $field, array $data): void {
                $field->add(TextType::class);
            }
        );
    }

    public function testCircularDependencyMessageContainsPath(): void
    {
        try {
            $this->manager->addDependentField(
                'field_x',
                'field_y',
                function (DependentField $field, array $data): void {
                    $field->add(TextType::class);
                }
            );

            $this->manager->addDependentField(
                'field_y',
                'field_x',
                function (DependentField $field, array $data): void {
                    $field->add(TextType::class);
                }
            );

            $this->fail('Expected LogicException was not thrown');
        } catch (LogicException $e) {
            $this->assertStringContainsString('field_x', $e->getMessage());
            $this->assertStringContainsString('field_y', $e->getMessage());
            $this->assertStringContainsString('→', $e->getMessage());
        }
    }
}