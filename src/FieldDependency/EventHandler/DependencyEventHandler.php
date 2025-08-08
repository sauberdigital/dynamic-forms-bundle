<?php

/**
 * (c) sauber digital <info@sauberdigital.de>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sd\DynamicFormsBundle\FieldDependency\EventHandler;

use Exception;
use Sd\DynamicFormsBundle\FieldDependency\DependentField\DependentField;
use Sd\DynamicFormsBundle\FieldDependency\DependentField\DependentFieldConfig;
use Symfony\Component\Form\ClearableErrorsInterface;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Handles dependency events for dynamic form fields.
 * Manages the complete lifecycle of dependent fields including data collection and field processing.
 */
final class DependencyEventHandler
{
    /** @var DependentFieldConfig[] */
    private array $dependentFieldConfigs = [];

    private ?FormInterface $form = null;
    private FormBuilderInterface $builder;
    
    /** @var array<string, mixed> Data from PRE_SET_DATA events */
    private array $preSetData = [];
    
    /** @var array<string, mixed> Data from POST_SUBMIT events */
    private array $postSubmitData = [];

    /**
     * @param FormBuilderInterface $builder The form builder to attach event listeners to
     */
    public function __construct(FormBuilderInterface $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Register a dependent field configuration.
     * 
     * @param DependentFieldConfig $config The configuration for the dependent field
     */
    public function addDependentFieldConfig(DependentFieldConfig $config): void
    {
        $this->dependentFieldConfigs[] = $config;
    }

    /**
     * Initialize event listeners for dependency fields.
     *
     * @param array<string> $fieldsToConsider Array of field names to set up listeners for
     */
    public function initializeListeners(array $fieldsToConsider): void
    {
        $fieldsToConsider = array_flip(array: $fieldsToConsider);
        $registeredFields = [];

        foreach ($this->dependentFieldConfigs as $config) {
            foreach ($config->dependencies as $dependency) {
                if (!isset($fieldsToConsider[$dependency])) {
                    continue;
                }

                if (!$this->builder->has(name: $dependency)) {
                    continue;
                }

                if (isset($registeredFields[$dependency])) {
                    continue;
                }

                $registeredFields[$dependency] = true;

                // Add listeners to the dependency field
                $this->addDependencyFieldListeners($this->builder->get(name: $dependency));
            }
        }
    }

    /**
     * Add dependency event listeners for a form field builder.
     * 
     * @param FormBuilderInterface $fieldBuilder The field builder to add listeners to
     */
    public function addDependencyFieldListeners(FormBuilderInterface $fieldBuilder): void
    {
        $fieldBuilder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'storePreSetDataDependencyData']);
        $fieldBuilder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'storePostSubmitDependencyData']);
    }

    /**
     * Handle PRE_SET_DATA on the root form.
     * 
     * @param FormEvent $event The form event containing the form and data
     */
    public function onPreSetData(FormEvent $event): void
    {
        $this->form = $event->getForm();
        $this->clearData();

        // Ensure error field exists
        $this->ensureErrorField($this->form);
    }

    /**
     * Store PRE_SET_DATA dependency data from individual fields.
     * 
     * @param FormEvent $event The form event from the dependency field
     */
    public function storePreSetDataDependencyData(FormEvent $event): void
    {
        $fieldName = $event->getForm()->getName();
        $this->preSetData[$fieldName] = $event->getData();

        $this->executeReadyCallbacks(eventName: FormEvents::PRE_SET_DATA);
    }

    /**
     * Store POST_SUBMIT dependency data from individual fields.
     * 
     * @param FormEvent $event The form event from the dependency field
     */
    public function storePostSubmitDependencyData(FormEvent $event): void
    {
        $fieldName = $event->getForm()->getName();
        $this->postSubmitData[$fieldName] = $event->getForm()->getData();

        $this->executeReadyCallbacks(eventName: FormEvents::POST_SUBMIT);
    }

    /**
     * Clear transformation errors on dependent fields.
     * This prevents confusing validation errors when parent fields change.
     * 
     * @param FormEvent $event The POST_SUBMIT event from the root form
     */
    public function clearTransformationErrors(FormEvent $event): void
    {
        $form = $event->getForm();
        $transformationErrorsCleared = false;

        foreach ($this->dependentFieldConfigs as $config) {
            if (!$form->has($config->name)) {
                continue;
            }

            $subForm = $form->get($config->name);
            
            // Check if the field has a transformation failure and can clear errors
            if ($subForm->getTransformationFailure() && $subForm instanceof ClearableErrorsInterface) {
                $subForm->clearErrors();
                $transformationErrorsCleared = true;
            }
        }

        // If we cleared errors, add a hidden error to prevent form submission
        if ($transformationErrorsCleared && $form->has('__dynamic_error')) {
            $errorField = $form->get('__dynamic_error');
            if ($errorField->isValid()) {
                $errorField->addError(new FormError('Some dynamic fields have errors.'));
            }
        }
    }

    /**
     * Process a dependent field configuration and apply changes to the form.
     * 
     * @param DependentFieldConfig $config The configuration for the dependent field
     * @param array<string, mixed> $dependencyData Data from dependency fields
     * @param string $eventName The name of the form event (FormEvents constant)
     * @param FormInterface $form The form to modify
     */
    private function processField(DependentFieldConfig $config, array $dependencyData, string $eventName, FormInterface $form): void
    {
        // Execute the callback to configure the field
        $dependentField = new DependentField();
        ($config->callback)($dependentField, $dependencyData);
        $config->markCallbackExecuted(eventName: $eventName);

        if (!$dependentField->shouldBeAdded()) {
            $form->remove(name: $config->name);
            return;
        }

        $this->createAndAddField(config: $config, dependentField: $dependentField, form: $form);
    }

    /**
     * Create and add a dependent field to the form.
     * 
     * @param DependentFieldConfig $config The configuration for the dependent field
     * @param DependentField $dependentField The configured dependent field instance
     * @param FormInterface $form The form to add the field to
     * 
     * @throws LogicException If the field type is not specified
     */
    private function createAndAddField(DependentFieldConfig $config, DependentField $dependentField, FormInterface $form): void
    {
        // Validate field type
        $type = $dependentField->getType();
        if ($type === null) {
            throw new LogicException(message: sprintf('Field "%s" must specify a type', $config->name));
        }

        // Remove existing field if present
        $form->remove(name: $config->name);

        // Add the field to the builder (following SymfonyCasts pattern)
        $this->builder->add(child: $config->name, type: $type, options: array_merge($dependentField->getOptions(), ['data' => null]));

        // Add listeners if other fields depend on this one
        $hasFieldDependents = false;
        foreach ($this->dependentFieldConfigs as $checkConfig) {
            if (in_array(needle: $config->name, haystack: $checkConfig->dependencies, strict: true)) {
                $hasFieldDependents = true;
                break;
            }
        }
        
        if ($hasFieldDependents) {
            $this->addDependencyFieldListeners(fieldBuilder: $this->builder->get($config->name));
        }

        // Get the field from builder and add it to the form
        $field = $this->builder->get($config->name)->setAutoInitialize(false)->getForm();
        $form->add(child: $field);
    }

    /**
     * Execute callbacks that are ready based on available dependency data.
     * 
     * @param string $eventName The name of the form event (FormEvents constant)
     */
    private function executeReadyCallbacks(string $eventName): void
    {
        if (!$this->form) {
            return;
        }

        $availableDependencyData = $this->getDataForEvent(eventName: $eventName);

        foreach ($this->dependentFieldConfigs as $config) {
            if (!$config->isReady(availableDependencyData: $availableDependencyData, eventName: $eventName)) {
                continue;
            }

            $dependencyData = $this->prepareDependencyData(dependencies: $config->dependencies, eventName: $eventName);

            try {
                $this->processField(config: $config, dependencyData: $dependencyData, eventName: $eventName, form: $this->form);
            } catch (Exception $e) {
                $this->addErrorToForm($e->getMessage());
            }
        }
    }

    /**
     * Get all available data for a specific event.
     * 
     * @param string $eventName The name of the form event (FormEvents constant)
     * 
     * @return array<string, mixed> Array of field names to their values for the event
     */
    private function getDataForEvent(string $eventName): array
    {
        return match ($eventName) {
            FormEvents::PRE_SET_DATA => $this->preSetData,
            FormEvents::POST_SUBMIT => $this->postSubmitData,
            default => [],
        };
    }

    /**
     * Prepare dependency data for a specific configuration.
     * 
     * @param array<string> $dependencies Array of dependency field names
     * @param string $eventName The name of the form event (FormEvents constant)
     * 
     * @return array<string, mixed> Array of dependency field names to their values (null if missing)
     */
    private function prepareDependencyData(array $dependencies, string $eventName): array
    {
        $availableData = $this->getDataForEvent($eventName);
        
        // Create the dependency data array with values for this field's dependencies
        $dependencyKeys = array_flip($dependencies);
        $dependencyData = array_intersect_key($availableData, $dependencyKeys);
        
        // Add null values for missing dependencies
        foreach ($dependencies as $dependency) {
            if (!array_key_exists($dependency, $dependencyData)) {
                $dependencyData[$dependency] = null;
            }
        }
        
        return $dependencyData;
    }

    /**
     * Clear all stored dependency data.
     * Resets both PRE_SET_DATA and POST_SUBMIT data collections.
     */
    private function clearData(): void
    {
        $this->preSetData = [];
        $this->postSubmitData = [];
    }

    /**
     * Ensure error field exists in form.
     * 
     * @param FormInterface $form The form to add the error field to
     */
    private function ensureErrorField(FormInterface $form): void
    {
        if (!$form->has('__dynamic_error')) {
            $form->add(child: '__dynamic_error', type: HiddenType::class, options: [
                'mapped' => false,
                'error_bubbling' => false,
            ]);
        }
    }

    /**
     * Add error to the form's error field.
     * 
     * @param string $message The error message to add
     */
    private function addErrorToForm(string $message): void
    {
        if ($this->form && $this->form->has('__dynamic_error')) {
            $errorField = $this->form->get('__dynamic_error');
            $errorField->addError(new FormError($message));
        }
    }
}