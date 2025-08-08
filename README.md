
# Dynamic Forms Bundle for Symfony

[![CI](https://github.com/sauberdigital/dynamic-forms-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/sauberdigital/dynamic-forms-bundle/actions/workflows/ci.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A powerful Symfony bundle that simplifies the creation of dynamic form fields with dependencies. This bundle leverages Symfony's form lifecycle events to provide seamless field dependency management **without JavaScript**.

## Features

- **Dynamic Field Dependencies**: Create fields that depend on other form fields
- **Multiple Dependencies**: Fields can depend on multiple parent fields
- **Nested Dependencies**: Support for complex dependency chains (A → B → C → D)
- **Circular Dependency Detection**: Prevents infinite loops in field dependencies
- **Form Lifecycle Integration**: Uses Symfony's `FormEvents::POST_SUBMIT` for optimal performance
- **No JavaScript Required**: Works entirely through Symfony form events
- **Type Safe**: Full PHP 8.2+ type hints and modern language features

## Requirements

- PHP **8.2+**
- Symfony **7.2+**

## Installation

```bash
composer require sauberdigital/dynamic-forms-bundle
```

If you're using Symfony Flex, the bundle will be automatically enabled. Otherwise, add the bundle to your `config/bundles.php`:

```php
<?php

return [
    // ... other bundles
    Sd\DynamicFormsBundle\SdDynamicFormsBundle::class => ['all' => true],
];
```

## Basic Usage

### Complete Example

Here's a complete example showing how to create a dynamic form with cascading location fields (continent → country → state → city):

```php
<?php

use Sd\DynamicFormsBundle\Builder\DynamicFormBuilder;
use Sd\DynamicFormsBundle\FieldDependency\DependentField\DependentField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        // Add the continent field
        $builder->add(name: 'continent', type: ChoiceType::class, options: [
            'choices' => [
                'North America' => 'NA',
                'Europe' => 'EU',
            ],
            'data' => 'EU', // Set default value
            'placeholder' => 'Select continent…',
        ]);

        // Country field depends on continent
        $builder->addDependentField(
            fieldName: 'country',
            dependencies: 'continent',
            callback: function (DependentField $field, array $data): void {
                switch ($data['continent'] ?? null) {
                    case 'NA':
                        $field->add(type: ChoiceType::class, options: [
                            'placeholder' => 'Select country…',
                            'choices' => [
                                'USA' => 'US',
                                'Canada' => 'CA',
                                'Mexico' => 'MX',
                            ],
                        ]);
                        break;
                    case 'EU':
                        $field->add(type: ChoiceType::class, options: [
                            'placeholder' => 'Select country…',
                            'choices' => [
                                'Germany' => 'DE',
                                'France' => 'FR',
                                'Spain' => 'ES',
                            ],
                        ]);
                        break;
                }
            }
        );

        // State/Province field depends on country
        $builder->addDependentField(
            fieldName: 'state',
            dependencies: 'country',
            callback: function (DependentField $field, array $data): void {
                switch ($data['country'] ?? null) {
                    case 'US':
                        $field->add(type: ChoiceType::class, options: [
                            'placeholder' => 'Select state…',
                            'choices' => [
                                'California' => 'CA',
                                'New York' => 'NY',
                                'Texas' => 'TX',
                            ],
                        ]);
                        break;
                    case 'CA':
                        $field->add(type: ChoiceType::class, options: [
                            'placeholder' => 'Select province…',
                            'choices' => [
                                'Ontario' => 'ON',
                                'Quebec' => 'QC',
                            ],
                        ]);
                        break;
                    case 'DE':
                        $field->add(type: ChoiceType::class, options: [
                            'placeholder' => 'Select state…',
                            'choices' => [
                                'Bavaria' => 'BY',
                                'Berlin' => 'BE',
                            ],
                        ]);
                        break;
                }
            }
        );

        // City field depends on state
        $builder->addDependentField(
            fieldName: 'city',
            dependencies: 'state',
            callback: function (DependentField $field, array $data): void {
                switch ($data['state'] ?? null) {
                    case 'CA': // California
                        $field->add(type: ChoiceType::class, options: [
                            'placeholder' => 'Select city…',
                            'choices' => [
                                'Los Angeles' => 'LA',
                                'San Francisco' => 'SF',
                            ],
                        ]);
                        break;
                    case 'BY': // Bavaria
                        $field->add(type: ChoiceType::class, options: [
                            'placeholder' => 'Select city…',
                            'choices' => [
                                'Munich' => 'MUC',
                                'Nuremberg' => 'NUE',
                            ],
                        ]);
                        break;
                    case 'BE': // Berlin
                        $field->add(type: ChoiceType::class, options: [
                            'placeholder' => 'Select city…',
                            'choices' => [
                                'Berlin Mitte' => 'BM',
                                'Berlin Charlottenburg' => 'BC',
                            ],
                        ]);
                        break;
                }
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
```

### Nested Dependencies

```php
<?php

use Sd\DynamicFormsBundle\Builder\DynamicFormBuilder;
use Sd\DynamicFormsBundle\FieldDependency\DependentField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class ComplexLocationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        // Continent field
        $builder->add(name: 'continent', type: ChoiceType::class, options: [
            'choices' => ['North America' => 'NA', 'Europe' => 'EU'],
        ]);

        // Country depends on continent
        $builder->addDependentField(
            fieldName: 'country',
            dependencies: 'continent',
            callback: function (DependentField $field, array $data): void {
                switch ($data['continent'] ?? null) {
                    case 'NA':
                        $field->add(type: ChoiceType::class, options: [
                            'choices' => ['USA' => 'US', 'Canada' => 'CA'],
                        ]);
                        break;
                    case 'EU':
                        $field->add(type: ChoiceType::class, options: [
                            'choices' => ['Germany' => 'DE', 'France' => 'FR'],
                        ]);
                        break;
                }
            }
        );

        // State depends on country
        $builder->addDependentField(
            fieldName: 'state',
            dependencies: 'country',
            callback: function (DependentField $field, array $data): void {
                switch ($data['country'] ?? null) {
                    case 'US':
                        $field->add(type: ChoiceType::class, options: [
                            'choices' => ['California' => 'CA', 'New York' => 'NY'],
                        ]);
                        break;
                    case 'CA':
                        $field->add(type: ChoiceType::class, options: [
                            'choices' => ['Ontario' => 'ON', 'Quebec' => 'QC'],
                        ]);
                        break;
                    case 'DE':
                        $field->add(type: ChoiceType::class, options: [
                            'choices' => ['Bavaria' => 'BY', 'Berlin' => 'BE'],
                        ]);
                        break;
                }
            }
        );

        // City depends on state
        $builder->addDependentField(
            fieldName: 'city',
            dependencies: 'state',
            callback: function (DependentField $field, array $data): void {
                if (($data['state'] ?? null) === 'CA') {
                    $field->add(type: ChoiceType::class, options: [
                        'choices' => ['Los Angeles' => 'LA', 'San Francisco' => 'SF'],
                    ]);
                }
            }
        );
    }
}
```

### Multiple Dependencies

```php
<?php

// A field that depends on multiple parent fields
$builder->addDependentField(
    fieldName: 'shipping_options',
    dependencies: ['country', 'product_type'],
    callback: function (DependentField $field, array $data): void {
        $country = $data['country'] ?? null;
        $productType = $data['product_type'] ?? null;

        if ($country === 'US' && $productType === 'electronics') {
            $field->add(type: ChoiceType::class, options: [
                'choices' => [
                    'Standard (5-7 days)' => 'standard',
                    'Express (2-3 days)' => 'express',
                    'Overnight' => 'overnight',
                ],
            ]);
        } elseif ($country === 'US' && $productType === 'books') {
            $field->add(type: ChoiceType::class, options: [
                'choices' => [
                    'Standard (3-5 days)' => 'standard',
                    'Express (1-2 days)' => 'express',
                ],
            ]);
        }
        // ... handle other combinations
    }
);
```

### Conditional Field Removal

```php
<?php

use Symfony\Component\Form\Extension\Core\Type\TextType;

$builder->addDependentField(
    fieldName: 'tax_id',
    dependencies: 'customer_type',
    callback: function (DependentField $field, array $data): void {
        if (($data['customer_type'] ?? null) === 'business') {
            $field->add(type: TextType::class, options: [
                'label' => 'Tax ID',
                'required' => true,
            ]);
        } else {
            // Remove the field for non-business customers
            $field->remove();
        }
    }
);
```

## Advanced Usage

### Working with Symfony UX Live Components

This bundle works seamlessly with Symfony UX Live Components:

```php
<?php

use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;

#[AsLiveComponent]
class LocationFormComponent
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?array $initialFormData = null;

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(LocationFormType::class, $this->initialFormData);
    }
}
```

### Custom Field Types

The bundle works with any Symfony form field type:

```php
<?php

$builder->addDependentField(
    fieldName: 'custom_field',
    dependencies: 'trigger_field',
    callback: function (DependentField $field, array $data): void {
        $field->add(type: CustomFieldType::class, options: [
            'custom_option' => $data['trigger_field'] ?? 'default',
            'mapped' => false,
        ]);
    }
);
```

## No JS by default — but UX-friendly

This bundle computes dependencies on the server using Symfony Form events. That means:

- **Works without JavaScript**: changes are applied on submit with a full-page or partial reload.
- **Progressive enhancement**: pair it with **Symfony UX Live Components** to re-render the form instantly on field changes — no custom JS needed.
- **Bring your own frontend**: Stimulus, Turbo, htmx, Alpine, or classic AJAX all work — just re-submit or re-render the form when a dependency changes.

**Recommended**: With Live Components, bind your form to a component (see example above). When a dependency field changes, the server recalculates dependent fields and updates the DOM. Users get instant feedback, and the form still works without JS.

## API Reference

### `DynamicFormBuilder`

The main class for creating dynamic forms.

#### Methods

- `addDependentField(string $fieldName, string|array $dependencies, callable $callback): self`
  - Add a field that depends on other fields
  - `$fieldName`: Name of the dependent field
  - `$dependencies`: Single dependency or array of dependencies
  - `$callback`: Function that configures the field based on form data

### `DependentField`

Represents a field that can be dynamically configured.

#### Methods

- `add(string $type, array $options = []): void` – Add the field with specified type and options
- `remove(): void` – Remove the field from the form
- `getName(): string` – Get the field name

## How It Works

1. **Field Registration**: When you call `addDependentField()`, the bundle registers a dependency relationship.
2. **Event Subscription**: `POST_SUBMIT` event listeners are attached to dependency fields.
3. **Dependency Processing**: When a dependency field changes, all dependent fields are recalculated.
4. **Field Updates**: Dependent fields are removed and re-added based on the callback logic.
5. **Circular Detection**: The dependency graph prevents circular dependencies.

## Error Handling

### Circular Dependencies

```php
<?php

// This will throw a CircularDependencyException
$builder->addDependentField('field_a', 'field_b', $callback);
$builder->addDependentField('field_b', 'field_a', $callback); // Exception!
```

### Missing Dependencies

If a dependency field doesn't exist when `addDependentField()` is called, the bundle will skip adding the event listener but won't throw an exception. Make sure to add dependency fields before dependent fields.

## Testing

Run the test suite:

```bash
vendor/bin/phpunit
```

The bundle includes unit and functional tests covering:
- Field dependency logic
- Circular dependency detection
- Event handling
- Form integration

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for details on branching, commit style, and the PR process.

## License

This bundle is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Support

For support and questions, please use the [GitHub Issues](https://github.com/sauberdigital/dynamic-forms-bundle/issues) page.