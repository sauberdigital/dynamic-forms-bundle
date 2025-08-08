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
use Sd\DynamicFormsBundle\FieldDependency\DependentField\DependentFieldConfig;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvents;

class DependentFieldConfigTest extends TestCase
{
    private \Closure $callback;
    private DependentFieldConfig $config;

    protected function setUp(): void
    {
        $this->callback = function (DependentField $field, array $data): void {
            if ($data['trigger'] === 'yes') {
                $field->add(TextType::class);
            }
        };

        $this->config = new DependentFieldConfig(
            name: 'test_field',
            dependencies: ['trigger'],
            callback: $this->callback
        );
    }

    public function testConstructor(): void
    {
        $this->assertSame('test_field', $this->config->name);
        $this->assertSame(['trigger'], $this->config->dependencies);
        $this->assertSame($this->callback, $this->config->callback);
    }

    public function testMultipleDependencies(): void
    {
        $config = new DependentFieldConfig(
            name: 'multi_dep',
            dependencies: ['field1', 'field2', 'field3'],
            callback: $this->callback
        );

        $this->assertSame(['field1', 'field2', 'field3'], $config->dependencies);
    }

    public function testIsReadyWithAllDependencies(): void
    {
        $availableData = ['trigger' => 'yes'];
        
        $this->assertTrue($this->config->isReady($availableData, FormEvents::PRE_SET_DATA));
    }

    public function testIsReadyWithMissingDependencies(): void
    {
        $availableData = ['other_field' => 'value'];
        
        $this->assertFalse($this->config->isReady($availableData, FormEvents::PRE_SET_DATA));
    }

    public function testIsReadyWithPartialDependencies(): void
    {
        $config = new DependentFieldConfig(
            name: 'multi',
            dependencies: ['field1', 'field2'],
            callback: $this->callback
        );

        $availableData = ['field1' => 'value1'];
        
        $this->assertFalse($config->isReady($availableData, FormEvents::PRE_SET_DATA));
        
        $availableData = ['field1' => 'value1', 'field2' => 'value2'];
        
        $this->assertTrue($config->isReady($availableData, FormEvents::PRE_SET_DATA));
    }

    public function testIsReadyAfterCallbackExecuted(): void
    {
        $availableData = ['trigger' => 'yes'];
        
        // First call should be ready
        $this->assertTrue($this->config->isReady($availableData, FormEvents::PRE_SET_DATA));
        
        // Mark as executed
        $this->config->markCallbackExecuted(FormEvents::PRE_SET_DATA);
        
        // Second call should not be ready (already executed)
        $this->assertFalse($this->config->isReady($availableData, FormEvents::PRE_SET_DATA));
        
        // But POST_SUBMIT should still be ready
        $this->assertTrue($this->config->isReady($availableData, FormEvents::POST_SUBMIT));
    }

    public function testMarkCallbackExecuted(): void
    {
        $this->assertFalse($this->config->callbackExecuted[FormEvents::PRE_SET_DATA]);
        $this->assertFalse($this->config->callbackExecuted[FormEvents::POST_SUBMIT]);
        
        $this->config->markCallbackExecuted(FormEvents::PRE_SET_DATA);
        
        $this->assertTrue($this->config->callbackExecuted[FormEvents::PRE_SET_DATA]);
        $this->assertFalse($this->config->callbackExecuted[FormEvents::POST_SUBMIT]);
        
        $this->config->markCallbackExecuted(FormEvents::POST_SUBMIT);
        
        $this->assertTrue($this->config->callbackExecuted[FormEvents::PRE_SET_DATA]);
        $this->assertTrue($this->config->callbackExecuted[FormEvents::POST_SUBMIT]);
    }

    public function testIsReadyWithInvalidEventName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid event name "invalid.event"');
        
        $this->config->isReady(['trigger' => 'yes'], 'invalid.event');
    }

    public function testMarkCallbackExecutedWithInvalidEventName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid event name "invalid.event"');
        
        $this->config->markCallbackExecuted('invalid.event');
    }

    public function testCallbackExecutedTracking(): void
    {
        $availableData = ['trigger' => 'yes'];
        
        // PRE_SET_DATA
        $this->assertTrue($this->config->isReady($availableData, FormEvents::PRE_SET_DATA));
        $this->config->markCallbackExecuted(FormEvents::PRE_SET_DATA);
        $this->assertFalse($this->config->isReady($availableData, FormEvents::PRE_SET_DATA));
        
        // POST_SUBMIT
        $this->assertTrue($this->config->isReady($availableData, FormEvents::POST_SUBMIT));
        $this->config->markCallbackExecuted(FormEvents::POST_SUBMIT);
        $this->assertFalse($this->config->isReady($availableData, FormEvents::POST_SUBMIT));
    }
}