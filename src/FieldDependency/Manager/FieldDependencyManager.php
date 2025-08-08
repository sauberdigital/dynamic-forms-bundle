<?php

/**
 * (c) sauber digital <info@sauberdigital.de>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sd\DynamicFormsBundle\FieldDependency\Manager;

use Closure;
use Sd\DynamicFormsBundle\FieldDependency\DependentField\DependentFieldConfig;
use Sd\DynamicFormsBundle\FieldDependency\EventHandler\DependencyEventHandler;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Manages field dependencies for dynamic forms.
 * 
 * Responsibilities:
 * - Coordinate field dependency registration
 * - Initialize event handlers for field dependencies
 * - Provide clean API for field dependency management
 */
final class FieldDependencyManager
{
    private DependencyEventHandler $eventHandler;

    /**
     * @param FormBuilderInterface $formBuilder The form builder to manage dependencies for
     */
    public function __construct(FormBuilderInterface $formBuilder)
    {
        $this->eventHandler = new DependencyEventHandler($formBuilder);
    }

    /**
     * Add a field that depends on other fields.
     *
     * @param string $fieldName The name of the dependent field
     * @param string|array<string> $dependencies Single dependency or array of field names this field depends on
     * @param Closure $callback Callback that configures the field based on dependency data
     */
    public function addDependentField(string $fieldName, string|array $dependencies, Closure $callback): self
    {
        // make sure, $dependencies is an array
        $dependencies = (array) $dependencies;
        
        // Register the configuration
        $config = new DependentFieldConfig(name: $fieldName, dependencies: $dependencies, callback: $callback);

        // add the config
        $this->eventHandler->addDependentFieldConfig($config);
        
        // Initialize listeners for dependency fields
        $this->eventHandler->initializeListeners(fieldsToConsider: $dependencies);

        return $this;
    }

    /**
     * Get the event handler for integration with form subscribers.
     * 
     * @return DependencyEventHandler The event handler instance
     */
    public function getEventHandler(): DependencyEventHandler
    {
        return $this->eventHandler;
    }
}