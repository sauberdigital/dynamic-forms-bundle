<?php

/**
 * (c) sauber digital <info@sauberdigital.de>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sd\DynamicFormsBundle\Tests\Unit\Builder;

use Sd\DynamicFormsBundle\Builder\DynamicFormBuilder;
use Sd\DynamicFormsBundle\FieldDependency\DependentField\DependentField;
use Sd\DynamicFormsBundle\Tests\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class DynamicFormBuilderTest extends TestCase
{
    private DynamicFormBuilder $dynamicBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dynamicBuilder = new DynamicFormBuilder($this->formBuilder);
    }

    public function testCanCreateDynamicFormBuilder(): void
    {
        $this->assertInstanceOf(DynamicFormBuilder::class, $this->dynamicBuilder);
    }

    public function testCanAddStaticField(): void
    {
        $this->dynamicBuilder->add('static_field', TextType::class, [
            'label' => 'Static Field',
        ]);

        $form = $this->dynamicBuilder->getForm();
        
        $this->assertTrue($form->has('static_field'));
        $this->assertInstanceOf(TextType::class, $form->get('static_field')->getConfig()->getType()->getInnerType());
    }

    public function testCanAddDependentField(): void
    {
        // Add parent field
        $this->dynamicBuilder->add('parent', ChoiceType::class, [
            'choices' => ['Option 1' => '1', 'Option 2' => '2'],
        ]);

        // Add dependent field
        $this->dynamicBuilder->addDependentField(
            'dependent',
            'parent',
            function (DependentField $field, array $data): void {
                if ($data['parent'] === '1') {
                    $field->add(TextType::class, ['label' => 'Dependent Field']);
                }
            }
        );

        $form = $this->dynamicBuilder->getForm();
        
        // Parent field should exist
        $this->assertTrue($form->has('parent'));
        
        // Form should also have hidden error field
        $this->assertTrue($form->has('__dynamic_error'));
    }

    public function testCanAddMultipleDependentFields(): void
    {
        $this->dynamicBuilder->add('trigger', ChoiceType::class, [
            'choices' => ['A' => 'a', 'B' => 'b'],
        ]);

        $this->dynamicBuilder->addDependentField(
            'field1',
            'trigger',
            function (DependentField $field, array $data): void {
                if ($data['trigger'] === 'a') {
                    $field->add(TextType::class);
                }
            }
        );

        $this->dynamicBuilder->addDependentField(
            'field2',
            'trigger',
            function (DependentField $field, array $data): void {
                if ($data['trigger'] === 'b') {
                    $field->add(TextType::class);
                }
            }
        );

        $form = $this->dynamicBuilder->getForm();
        
        $this->assertTrue($form->has('trigger'));
        $this->assertTrue($form->has('__dynamic_error'));
    }

    public function testDependentFieldWithMultipleDependencies(): void
    {
        $this->dynamicBuilder->add('field1', TextType::class);
        $this->dynamicBuilder->add('field2', TextType::class);

        $this->dynamicBuilder->addDependentField(
            'dependent',
            ['field1', 'field2'],
            function (DependentField $field, array $data): void {
                if (!empty($data['field1']) && !empty($data['field2'])) {
                    $field->add(TextType::class);
                }
            }
        );

        $form = $this->dynamicBuilder->getForm();
        
        $this->assertTrue($form->has('field1'));
        $this->assertTrue($form->has('field2'));
    }

    public function testFormBuilderProxyMethods(): void
    {
        // Test that proxy methods work correctly
        $this->dynamicBuilder->add('test', TextType::class);
        
        $this->assertTrue($this->dynamicBuilder->has('test'));
        $this->assertCount(1, $this->dynamicBuilder->all());
        
        $this->dynamicBuilder->remove('test');
        $this->assertFalse($this->dynamicBuilder->has('test'));
        
        // Test getName
        $this->assertSame('form', $this->dynamicBuilder->getName());
        
        // Test count
        $this->dynamicBuilder->add('field1', TextType::class);
        $this->dynamicBuilder->add('field2', TextType::class);
        $this->assertCount(2, $this->dynamicBuilder);
    }

    public function testGetInnerBuilder(): void
    {
        $innerBuilder = $this->dynamicBuilder->getInnerBuilder();
        
        $this->assertSame($this->formBuilder, $innerBuilder);
    }

    public function testIteratorAggregate(): void
    {
        $this->dynamicBuilder->add('field1', TextType::class);
        $this->dynamicBuilder->add('field2', TextType::class);
        
        $fields = [];
        foreach ($this->dynamicBuilder as $name => $field) {
            $fields[$name] = $field;
        }
        
        $this->assertCount(2, $fields);
        $this->assertArrayHasKey('field1', $fields);
        $this->assertArrayHasKey('field2', $fields);
    }

    public function testCascadingDependencies(): void
    {
        // Test A -> B -> C cascading
        $this->dynamicBuilder->add('field_a', ChoiceType::class, [
            'choices' => ['Yes' => 'yes', 'No' => 'no'],
        ]);

        $this->dynamicBuilder->addDependentField(
            'field_b',
            'field_a',
            function (DependentField $field, array $data): void {
                if ($data['field_a'] === 'yes') {
                    $field->add(ChoiceType::class, [
                        'choices' => ['Option 1' => '1', 'Option 2' => '2'],
                    ]);
                }
            }
        );

        $this->dynamicBuilder->addDependentField(
            'field_c',
            'field_b',
            function (DependentField $field, array $data): void {
                if ($data['field_b'] === '1') {
                    $field->add(TextType::class);
                }
            }
        );

        $form = $this->dynamicBuilder->getForm();
        
        $this->assertTrue($form->has('field_a'));
        // Hidden error field should be created
        $this->assertTrue($form->has('__dynamic_error'));
    }
}