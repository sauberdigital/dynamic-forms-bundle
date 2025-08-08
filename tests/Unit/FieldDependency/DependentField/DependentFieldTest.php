<?php

/**
 * (c) sauber digital <info@sauberdigital.de>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sd\DynamicFormsBundle\Tests\Unit\FieldDependency\DependentField;

use PHPUnit\Framework\TestCase;
use Sd\DynamicFormsBundle\FieldDependency\DependentField\DependentField;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class DependentFieldTest extends TestCase
{
    private DependentField $dependentField;

    protected function setUp(): void
    {
        $this->dependentField = new DependentField();
    }

    public function testInitialState(): void
    {
        $this->assertFalse($this->dependentField->shouldBeAdded());
        $this->assertNull($this->dependentField->getType());
        $this->assertSame([], $this->dependentField->getOptions());
    }

    public function testAddField(): void
    {
        $this->dependentField->add(TextType::class, [
            'label' => 'Test Field',
            'required' => true,
        ]);

        $this->assertTrue($this->dependentField->shouldBeAdded());
        $this->assertSame(TextType::class, $this->dependentField->getType());
        $this->assertSame([
            'label' => 'Test Field',
            'required' => true,
        ], $this->dependentField->getOptions());
    }

    public function testAddFieldWithoutOptions(): void
    {
        $this->dependentField->add(TextType::class);

        $this->assertTrue($this->dependentField->shouldBeAdded());
        $this->assertSame(TextType::class, $this->dependentField->getType());
        $this->assertSame([], $this->dependentField->getOptions());
    }

    public function testRemoveField(): void
    {
        // First add a field
        $this->dependentField->add(TextType::class, ['label' => 'Test']);
        $this->assertTrue($this->dependentField->shouldBeAdded());

        // Then remove it
        $this->dependentField->remove();
        $this->assertFalse($this->dependentField->shouldBeAdded());
        
        // Type and options should remain unchanged (for debugging purposes)
        $this->assertSame(TextType::class, $this->dependentField->getType());
        $this->assertSame(['label' => 'Test'], $this->dependentField->getOptions());
    }

    public function testCanOverwriteField(): void
    {
        // Add first configuration
        $this->dependentField->add(TextType::class, ['label' => 'First']);
        
        // Overwrite with new configuration
        $this->dependentField->add(TextType::class, ['label' => 'Second', 'required' => false]);

        $this->assertTrue($this->dependentField->shouldBeAdded());
        $this->assertSame(TextType::class, $this->dependentField->getType());
        $this->assertSame(['label' => 'Second', 'required' => false], $this->dependentField->getOptions());
    }

    public function testCanChangeBetweenAddAndRemove(): void
    {
        // Add field
        $this->dependentField->add(TextType::class);
        $this->assertTrue($this->dependentField->shouldBeAdded());

        // Remove field
        $this->dependentField->remove();
        $this->assertFalse($this->dependentField->shouldBeAdded());

        // Add again
        $this->dependentField->add(TextType::class, ['label' => 'New']);
        $this->assertTrue($this->dependentField->shouldBeAdded());
        $this->assertSame(['label' => 'New'], $this->dependentField->getOptions());
    }
}