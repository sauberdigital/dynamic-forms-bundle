# Dynamic Forms Bundle for Symfony

A powerful Symfony bundle that simplifies the creation of dynamic form fields with dependencies. This bundle leverages Symfony's form lifecycle events to provide seamless field dependency management without requiring JavaScript.

## Features

- **Dynamic Field Dependencies**: Create fields that depend on other form fields
- **Multiple Dependencies**: Fields can depend on multiple parent fields
- **Nested Dependencies**: Support for complex dependency chains (A → B → C → D)
- **Circular Dependency Detection**: Prevents infinite loops in field dependencies
- **Form Lifecycle Integration**: Uses Symfony's POST_SUBMIT events for optimal performance
- **No JavaScript Required**: Works entirely through Symfony form events
- **Type Safe**: Full PHP 8.2+ type hints and modern language features

## Installation

```bash
composer require sd/dynamic-forms-bundle
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
            'placeholder' => 'Select continent...',
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

class ComplexLocationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        // Continent field
        $builder->add('continent', ChoiceType::class, [
            'choices' => ['North America' => 'NA', 'Europe' => 'EU'],
        ]);

        // Country depends on continent
        $builder->addDependentField(
            'country',
            'continent',
            function (DependentField $field, array $data): void {
                switch ($data['continent'] ?? null) {
                    case 'NA':
                        $field->add(ChoiceType::class, [
                            'choices' => ['USA' => 'US', 'Canada' => 'CA'],
                        ]);
                        break;
                    case 'EU':
                        $field->add(ChoiceType::class, [
                            'choices' => ['Germany' => 'DE', 'France' => 'FR'],
                        ]);
                        break;
                }
            }
        );

        // State depends on country
        $builder->addDependentField(
            'state',
            'country',
            function (DependentField $field, array $data): void {
                switch ($data['country'] ?? null) {
                    case 'US':
                        $field->add(ChoiceType::class, [
                            'choices' => ['California' => 'CA', 'New York' => 'NY'],
                        ]);
                        break;
                    case 'CA':
                        $field->add(ChoiceType::class, [
                            'choices' => ['Ontario' => 'ON', 'Quebec' => 'QC'],
                        ]);
                        break;
                    case 'DE':
                        $field->add(ChoiceType::class, [
                            'choices' => ['Bavaria' => 'BY', 'Berlin' => 'BE'],
                        ]);
                        break;
                }
            }
        );

        // City depends on state
        $builder->addDependentField(
            'city',
            'state',
            function (DependentField $field, array $data): void {
                switch ($data['state'] ?? null) {
                    case 'CA':
                        $field->add(ChoiceType::class, [
                            'choices' => ['Los Angeles' => 'LA', 'San Francisco' => 'SF'],
                        ]);
                        break;
                    // ... more states
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
    'shipping_options',
    ['country', 'product_type'],
    function (DependentField $field, array $data): void {
        $country = $data['country'] ?? null;
        $productType = $data['product_type'] ?? null;

        if ($country === 'US' && $productType === 'electronics') {
            $field->add(ChoiceType::class, [
                'choices' => [
                    'Standard (5-7 days)' => 'standard',
                    'Express (2-3 days)' => 'express',
                    'Overnight' => 'overnight',
                ],
            ]);
        } elseif ($country === 'US' && $productType === 'books') {
            $field->add(ChoiceType::class, [
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

$builder->addDependentField(
    'tax_id',
    'customer_type',
    function (DependentField $field, array $data): void {
        if (($data['customer_type'] ?? null) === 'business') {
            $field->add(TextType::class, [
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
    'custom_field',
    'trigger_field',
    function (DependentField $field, array $data): void {
        $field->add(CustomFieldType::class, [
            'custom_option' => $data['trigger_field'] ?? 'default',
            'mapped' => false,
        ]);
    }
);
```

## API Reference

### DynamicFormBuilder

The main class for creating dynamic forms.

#### Methods

- `addDependentField(string $fieldName, string|array $dependencies, callable $callback): self`
  - Add a field that depends on other fields
  - `$fieldName`: Name of the dependent field
  - `$dependencies`: Single dependency or array of dependencies
  - `$callback`: Function that configures the field based on form data

### DependentField

Represents a field that can be dynamically configured.

#### Methods

- `add(string $type, array $options = []): void`
  - Add the field with specified type and options
- `remove(): void`
  - Remove the field from the form
- `getName(): string`
  - Get the field name

## How It Works

1. **Field Registration**: When you call `addDependentField()`, the bundle registers a dependency relationship
2. **Event Subscription**: POST_SUBMIT event listeners are attached to dependency fields
3. **Dependency Processing**: When a dependency field changes, all dependent fields are recalculated
4. **Field Updates**: Dependent fields are removed and re-added based on the callback logic
5. **Circular Detection**: The dependency graph prevents circular dependencies

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

The bundle includes comprehensive unit and functional tests covering:
- Field dependency logic
- Circular dependency detection
- Event handling
- Form integration

## Requirements

- PHP 8.2+
- Symfony 7.2+

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This bundle is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Support

For support and questions, please use the [GitHub Issues](https://github.com/sauberdigital/dynamic-forms-bundle/issues) page.