<?php

/**
 * (c) sauber digital <info@sauberdigital.de>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sd\DynamicFormsBundle\Builder;

use Closure;
use IteratorAggregate;
use Sd\DynamicFormsBundle\Builder\Subscriber\RootFormSubscriber;
use Sd\DynamicFormsBundle\FieldDependency\Manager\FieldDependencyManager;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Dynamic form builder that enables various types of dynamic behavior.
 * 
 * Responsibilities:
 * - Provide clean API for dynamic form features
 * - Coordinate between different dependency managers (field, button, etc.)
 * - Proxy FormBuilderInterface methods to the inner builder
 * - Set up root form event subscription
 */
final class DynamicFormBuilder implements FormBuilderInterface, IteratorAggregate
{
    use FormBuilderProxyTrait;

    private ?FieldDependencyManager $fieldDependencyManager = null;
    private ?RootFormSubscriber $rootFormSubscriber = null;

    /**
     * @param FormBuilderInterface $builder The inner form builder to wrap
     */
    public function __construct(
        private readonly FormBuilderInterface $builder,
    ) {
    }

    /**
     * Add a field that depends on other fields.
     *
     * @param string $fieldName The name of the dependent field
     * @param string|array<string> $dependencies The field(s) this field depends on
     * @param Closure $callback The callback to configure the dependent field
     *
     * @return self Returns this instance for method chaining
     */
    public function addDependentField(string $fieldName, string|array $dependencies, Closure $callback): self
    {
        // Create field dependency manager if not exists
        if (!$this->fieldDependencyManager) {
            $this->fieldDependencyManager = new FieldDependencyManager(formBuilder: $this->builder);
            $this->ensureRootFormSubscriber();
        }
        
        // Delegate to field dependency manager
        $this->fieldDependencyManager->addDependentField(fieldName: $fieldName, dependencies: $dependencies, callback: $callback);
        
        return $this;
    }

    /**
     * Get the inner form builder instance.
     * 
     * @return FormBuilderInterface The wrapped form builder
     */
    public function getInnerBuilder(): FormBuilderInterface
    {
        return $this->builder;
    }

    /**
     * Ensure root form subscriber is set up with current managers.
     */
    private function ensureRootFormSubscriber(): void
    {
        if ($this->rootFormSubscriber) {
            // Subscriber already exists, no need to recreate
            return;
        }
        
        // Create new subscriber with current managers
        $this->rootFormSubscriber = new RootFormSubscriber(fieldDependencyManager: $this->fieldDependencyManager);
        
        // Add to form builder
        $this->builder->addEventSubscriber(subscriber: $this->rootFormSubscriber);
    }
}
