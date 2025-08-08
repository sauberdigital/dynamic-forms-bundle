<?php

/**
 * (c) sauber digital <info@sauberdigital.de>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sd\DynamicFormsBundle\FieldDependency\DependentField;

use Closure;
use InvalidArgumentException;
use Symfony\Component\Form\FormEvents;

/**
 * Configuration for a dependent field.
 */
final class DependentFieldConfig
{
    /** @var array<string, bool> */
    public array $callbackExecuted = [
        FormEvents::PRE_SET_DATA => false,
        FormEvents::POST_SUBMIT => false,
    ];

    /**
     * @param string $name Field name
     * @param string[] $dependencies Array of field names this depends on
     * @param Closure $callback Callback to execute
     */
    public function __construct(
        public readonly string $name,
        public readonly array $dependencies,
        public readonly Closure $callback
    ) {
        if (empty($this->dependencies)) {
            throw new InvalidArgumentException(sprintf(
                'Field "%s" must have at least one dependency.',
                $this->name
            ));
        }
    }

    /**
     * Check if this config is ready to execute for the given event.
     * 
     * @param array<string, mixed> $availableDependencyData Available dependency field data
     * @param string $eventName The name of the form event (FormEvents constant)
     * 
     * @return bool True if all dependencies have data and callback hasn't been executed for this event
     */
    public function isReady(array $availableDependencyData, string $eventName): bool
    {
        // check if exists
        if (!\array_key_exists($eventName, $this->callbackExecuted)) {
            throw new \InvalidArgumentException(\sprintf('Invalid event name "%s"', $eventName));
        }

        // Check if callback already executed for this event
        if ($this->callbackExecuted[$eventName]) {
            return false;
        }

        // Check if all dependencies have data
        foreach ($this->dependencies as $dependency) {
            if (!array_key_exists(key: $dependency, array: $availableDependencyData)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mark the callback as executed for the given event.
     * 
     * @param string $eventName The name of the form event (FormEvents constant)
     */
    public function markCallbackExecuted(string $eventName): void
    {
        if (!\array_key_exists($eventName, $this->callbackExecuted)) {
            throw new \InvalidArgumentException(\sprintf('Invalid event name "%s"', $eventName));
        }
        
        $this->callbackExecuted[$eventName] = true;
    }
}