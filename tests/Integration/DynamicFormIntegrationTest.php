<?php

/**
 * (c) sauber digital <info@sauberdigital.de>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sd\DynamicFormsBundle\Tests\Integration;

use Sd\DynamicFormsBundle\Builder\DynamicFormBuilder;
use Sd\DynamicFormsBundle\FieldDependency\DependentField\DependentField;
use Sd\DynamicFormsBundle\Tests\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class DynamicFormIntegrationTest extends TestCase
{
    private DynamicFormBuilder $dynamicBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dynamicBuilder = new DynamicFormBuilder($this->formBuilder);
    }

    public function testSimpleDependentFieldScenario(): void
    {
        // Create a form with country -> state dependency
        $this->dynamicBuilder->add('country', ChoiceType::class, [
            'choices' => [
                'USA' => 'usa',
                'Canada' => 'canada',
                'Mexico' => 'mexico',
            ],
        ]);

        $this->dynamicBuilder->addDependentField(
            'state',
            'country',
            function (DependentField $field, array $data): void {
                switch ($data['country'] ?? null) {
                    case 'usa':
                        $field->add(ChoiceType::class, [
                            'choices' => [
                                'California' => 'CA',
                                'New York' => 'NY',
                                'Texas' => 'TX',
                            ],
                        ]);
                        break;
                    case 'canada':
                        $field->add(ChoiceType::class, [
                            'choices' => [
                                'Ontario' => 'ON',
                                'Quebec' => 'QC',
                                'British Columbia' => 'BC',
                            ],
                        ]);
                        break;
                    case 'mexico':
                        $field->add(ChoiceType::class, [
                            'choices' => [
                                'Jalisco' => 'JAL',
                                'Nuevo León' => 'NL',
                                'Yucatán' => 'YUC',
                            ],
                        ]);
                        break;
                    default:
                        $field->remove();
                }
            }
        );

        $form = $this->dynamicBuilder->getForm();
        
        // Set initial data
        $form->setData(['country' => 'usa']);
        
        $this->assertTrue($form->has('country'));
        $this->assertTrue($form->has('__dynamic_error'));
    }

    public function testCascadingDependenciesScenario(): void
    {
        // Create form with continent -> country -> state -> city cascading dependencies
        $this->dynamicBuilder->add('continent', ChoiceType::class, [
            'choices' => [
                'North America' => 'NA',
                'Europe' => 'EU',
                'Asia' => 'AS',
            ],
        ]);

        $this->dynamicBuilder->addDependentField(
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
                    default:
                        $field->remove();
                }
            }
        );

        $this->dynamicBuilder->addDependentField(
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
                    default:
                        $field->remove();
                }
            }
        );

        $this->dynamicBuilder->addDependentField(
            'city',
            'state',
            function (DependentField $field, array $data): void {
                switch ($data['state'] ?? null) {
                    case 'CA':
                        $field->add(ChoiceType::class, [
                            'choices' => ['Los Angeles' => 'LA', 'San Francisco' => 'SF'],
                        ]);
                        break;
                    case 'NY':
                        $field->add(ChoiceType::class, [
                            'choices' => ['New York City' => 'NYC', 'Buffalo' => 'BUF'],
                        ]);
                        break;
                    default:
                        $field->remove();
                }
            }
        );

        $form = $this->dynamicBuilder->getForm();
        
        $this->assertTrue($form->has('continent'));
        $this->assertTrue($form->has('__dynamic_error'));
    }

    public function testMultipleDependenciesScenario(): void
    {
        // Field that depends on multiple other fields
        $this->dynamicBuilder->add('customer_type', ChoiceType::class, [
            'choices' => [
                'Individual' => 'individual',
                'Business' => 'business',
            ],
        ]);

        $this->dynamicBuilder->add('country', ChoiceType::class, [
            'choices' => [
                'USA' => 'US',
                'Germany' => 'DE',
                'Japan' => 'JP',
            ],
        ]);

        $this->dynamicBuilder->addDependentField(
            'tax_id',
            ['customer_type', 'country'],
            function (DependentField $field, array $data): void {
                $customerType = $data['customer_type'] ?? null;
                $country = $data['country'] ?? null;

                if ($customerType === 'business') {
                    switch ($country) {
                        case 'US':
                            $field->add(TextType::class, [
                                'label' => 'EIN (Employer Identification Number)',
                                'attr' => ['pattern' => '\d{2}-\d{7}'],
                            ]);
                            break;
                        case 'DE':
                            $field->add(TextType::class, [
                                'label' => 'USt-IdNr (VAT Number)',
                                'attr' => ['pattern' => 'DE\d{9}'],
                            ]);
                            break;
                        case 'JP':
                            $field->add(TextType::class, [
                                'label' => 'Corporate Number',
                                'attr' => ['pattern' => '\d{13}'],
                            ]);
                            break;
                        default:
                            $field->remove();
                    }
                } else {
                    $field->remove();
                }
            }
        );

        $form = $this->dynamicBuilder->getForm();
        
        $this->assertTrue($form->has('customer_type'));
        $this->assertTrue($form->has('country'));
    }

    public function testConditionalFieldVisibilityScenario(): void
    {
        // Newsletter subscription with conditional email field
        $this->dynamicBuilder->add('subscribe_newsletter', ChoiceType::class, [
            'choices' => [
                'Yes' => true,
                'No' => false,
            ],
            'expanded' => true,
        ]);

        $this->dynamicBuilder->addDependentField(
            'email',
            'subscribe_newsletter',
            function (DependentField $field, array $data): void {
                if ($data['subscribe_newsletter'] === true) {
                    $field->add(EmailType::class, [
                        'label' => 'Email Address',
                        'required' => true,
                    ]);
                } else {
                    $field->remove();
                }
            }
        );

        $this->dynamicBuilder->addDependentField(
            'frequency',
            'subscribe_newsletter',
            function (DependentField $field, array $data): void {
                if ($data['subscribe_newsletter'] === true) {
                    $field->add(ChoiceType::class, [
                        'label' => 'Email Frequency',
                        'choices' => [
                            'Daily' => 'daily',
                            'Weekly' => 'weekly',
                            'Monthly' => 'monthly',
                        ],
                    ]);
                } else {
                    $field->remove();
                }
            }
        );

        $form = $this->dynamicBuilder->getForm();
        
        $this->assertTrue($form->has('subscribe_newsletter'));
    }

    public function testDynamicValidationScenario(): void
    {
        // Age field with dynamic validation based on country
        $this->dynamicBuilder->add('country', ChoiceType::class, [
            'choices' => [
                'USA' => 'US',
                'Japan' => 'JP',
                'Germany' => 'DE',
            ],
        ]);

        $this->dynamicBuilder->addDependentField(
            'age_verification',
            'country',
            function (DependentField $field, array $data): void {
                switch ($data['country'] ?? null) {
                    case 'US':
                        $field->add(TextType::class, [
                            'label' => 'Age (must be 21+)',
                            'attr' => ['min' => 21],
                        ]);
                        break;
                    case 'JP':
                        $field->add(TextType::class, [
                            'label' => 'Age (must be 20+)',
                            'attr' => ['min' => 20],
                        ]);
                        break;
                    case 'DE':
                        $field->add(TextType::class, [
                            'label' => 'Age (must be 18+)',
                            'attr' => ['min' => 18],
                        ]);
                        break;
                    default:
                        $field->remove();
                }
            }
        );

        $form = $this->dynamicBuilder->getForm();
        
        $this->assertTrue($form->has('country'));
    }

    public function testComplexBusinessFormScenario(): void
    {
        // Complex business form with multiple interdependencies
        $this->dynamicBuilder->add('business_type', ChoiceType::class, [
            'choices' => [
                'Sole Proprietorship' => 'sole',
                'Partnership' => 'partnership',
                'Corporation' => 'corporation',
                'LLC' => 'llc',
            ],
        ]);

        $this->dynamicBuilder->add('industry', ChoiceType::class, [
            'choices' => [
                'Technology' => 'tech',
                'Finance' => 'finance',
                'Healthcare' => 'health',
                'Retail' => 'retail',
            ],
        ]);

        // Registration number depends on business type
        $this->dynamicBuilder->addDependentField(
            'registration_number',
            'business_type',
            function (DependentField $field, array $data): void {
                switch ($data['business_type'] ?? null) {
                    case 'corporation':
                        $field->add(TextType::class, [
                            'label' => 'Corporate Registration Number',
                        ]);
                        break;
                    case 'llc':
                        $field->add(TextType::class, [
                            'label' => 'LLC Registration Number',
                        ]);
                        break;
                    default:
                        $field->remove();
                }
            }
        );

        // License type depends on both business type and industry
        $this->dynamicBuilder->addDependentField(
            'license_type',
            ['business_type', 'industry'],
            function (DependentField $field, array $data): void {
                $businessType = $data['business_type'] ?? null;
                $industry = $data['industry'] ?? null;

                if ($industry === 'finance' && in_array($businessType, ['corporation', 'llc'])) {
                    $field->add(ChoiceType::class, [
                        'label' => 'Financial License Type',
                        'choices' => [
                            'Banking License' => 'banking',
                            'Investment Advisory' => 'investment',
                            'Insurance License' => 'insurance',
                        ],
                    ]);
                } elseif ($industry === 'health') {
                    $field->add(ChoiceType::class, [
                        'label' => 'Healthcare License Type',
                        'choices' => [
                            'Medical Practice' => 'medical',
                            'Pharmacy' => 'pharmacy',
                            'Laboratory' => 'lab',
                        ],
                    ]);
                } else {
                    $field->remove();
                }
            }
        );

        $form = $this->dynamicBuilder->getForm();
        
        $this->assertTrue($form->has('business_type'));
        $this->assertTrue($form->has('industry'));
    }

    public function testExampleTypeScenario(): void
    {
        // Test the example from documentation with named arguments
        $this->dynamicBuilder->add(child: 'continent', type: ChoiceType::class, options: [
            'choices' => [
                'North America' => 'NA',
                'Europe' => 'EU',
            ],
            'data' => 'EU'
        ]);

        $this->dynamicBuilder->addDependentField(
            fieldName: 'country',
            dependencies: 'continent',
            callback: function (DependentField $field, array $data): void {
                switch ($data['continent'] ?? null) {
                    case 'NA':
                        $field->add(type: ChoiceType::class, options: [
                            'placeholder' => 'Select country…',
                            'choices' => ['USA' => 'US', 'Canada' => 'CA', 'Mexico' => 'MX'],
                        ]);
                        break;
                    case 'EU':
                        $field->add(type: ChoiceType::class, options: [
                            'placeholder' => 'Select country…',
                            'choices' => ['Germany' => 'DE', 'France' => 'FR', 'Spain' => 'ES'],
                        ]);
                        break;
                }
            }
        );

        $this->dynamicBuilder->addDependentField(
            fieldName: 'state',
            dependencies: 'country',
            callback: function (DependentField $field, array $data): void {
                switch ($data['country'] ?? null) {
                    case 'US':
                        $field->add(type: ChoiceType::class, options: [
                            'placeholder' => 'Select state…',
                            'choices' => ['California' => 'CA', 'New York' => 'NY', 'Texas' => 'TX'],
                        ]);
                        break;
                    case 'CA':
                        $field->add(type: ChoiceType::class, options: [
                            'placeholder' => 'Select province…',
                            'choices' => ['Ontario' => 'ON', 'Quebec' => 'QC'],
                        ]);
                        break;
                    case 'DE':
                        $field->add(type: ChoiceType::class, options: [
                            'placeholder' => 'Select state…',
                            'choices' => ['Bavaria' => 'BY', 'Berlin' => 'BE'],
                        ]);
                        break;
                }
            }
        );

        $this->dynamicBuilder->addDependentField(
            fieldName: 'city',
            dependencies: 'state',
            callback: function (DependentField $field, array $data): void {
                switch ($data['state'] ?? null) {
                    case 'CA':
                        $field->add(type: ChoiceType::class, options: [
                            'placeholder' => 'Select city…',
                            'choices' => ['Los Angeles' => 'LA', 'San Francisco' => 'SF'],
                        ]);
                        break;
                    case 'BY':
                        $field->add(type: ChoiceType::class, options: [
                            'placeholder' => 'Select city…',
                            'choices' => ['Munich' => 'MUC', 'Nuremberg' => 'NUE'],
                        ]);
                        break;
                    case 'BE':
                        $field->add(type: ChoiceType::class, options: [
                            'placeholder' => 'Select city…',
                            'choices' => ['Berlin Mitte' => 'BM', 'Berlin Charlottenburg' => 'BC'],
                        ]);
                        break;
                }
            }
        );

        $form = $this->dynamicBuilder->getForm();
        
        $this->assertTrue($form->has('continent'));
        $this->assertTrue($form->has('__dynamic_error'));
        
        // Test with initial data
        $form->setData(['continent' => 'EU']);
        $this->assertEquals('EU', $form->get('continent')->getData());
    }

    public function testFormSubmissionScenario(): void
    {
        // Test actual form submission
        $this->dynamicBuilder->add('has_address', ChoiceType::class, [
            'choices' => ['Yes' => 'yes', 'No' => 'no'],
        ]);

        $this->dynamicBuilder->addDependentField(
            'street',
            'has_address',
            function (DependentField $field, array $data): void {
                if ($data['has_address'] === 'yes') {
                    $field->add(TextType::class);
                }
            }
        );

        $form = $this->dynamicBuilder->getForm();
        
        // Simulate form submission
        $form->submit([
            'has_address' => 'yes',
            'street' => '123 Main St',
        ]);

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());
        
        $data = $form->getData();
        $this->assertArrayHasKey('has_address', $data);
        $this->assertEquals('yes', $data['has_address']);
    }
}