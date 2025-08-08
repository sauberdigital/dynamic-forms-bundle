<?php

/**
 * (c) sauber digital <info@sauberdigital.de>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sd\DynamicFormsBundle\FieldDependency\DependentField;

/**
 * Represents a field that can be dynamically added to a form based on dependencies.
 */
final class DependentField
{
    private ?string $type = null;
    private array $options = [];
    private bool $shouldBeAdded = false;

    /**
     * Add the field to the form with the specified type and options.
     * 
     * @param string $type The form field type class name
     * @param array<string, mixed> $options Form field options
     */
    public function add(string $type, array $options = []): void
    {
        $this->type = $type;
        $this->options = $options;
        $this->shouldBeAdded = true;
    }

    /**
     * Mark this field to be removed from the form.
     * After calling this method, the field will be removed from the parent form.
     */
    public function remove(): void
    {
        $this->shouldBeAdded = false;
    }

    /**
     * Check if this field should be added to the form.
     * 
     * @return bool True if the field should be added, false if it should be removed
     */
    public function shouldBeAdded(): bool
    {
        return $this->shouldBeAdded;
    }

    /**
     * Get the field type.
     * 
     * @return string|null The form field type class name or null if not set
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Get the field options.
     * 
     * @return array<string, mixed> The form field options
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}