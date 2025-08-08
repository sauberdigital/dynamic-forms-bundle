<?php

/**
 * (c) sauber digital <info@sauberdigital.de>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sd\DynamicFormsBundle\Builder;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\RequestHandlerInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

trait FormBuilderProxyTrait
{
    abstract protected function getInnerBuilder(): FormBuilderInterface;

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->getInnerBuilder()->all());
    }

    public function count(): int
    {
        return $this->getInnerBuilder()->count();
    }

    public function add(string|FormBuilderInterface $child, ?string $type = null, array $options = []): static
    {
        $this->getInnerBuilder()->add($child, $type, $options);

        return $this;
    }

    public function create(string $name, ?string $type = null, array $options = []): FormBuilderInterface
    {
        return $this->getInnerBuilder()->create($name, $type, $options);
    }

    public function get(string $name): FormBuilderInterface
    {
        return $this->getInnerBuilder()->get($name);
    }

    public function has(string $name): bool
    {
        return $this->getInnerBuilder()->has($name);
    }

    public function remove(string $name): static
    {
        $this->getInnerBuilder()->remove($name);

        return $this;
    }

    public function getForm(): FormInterface
    {
        return $this->getInnerBuilder()->getForm();
    }

    public function getFormConfig(): FormConfigInterface
    {
        return $this->getInnerBuilder()->getFormConfig();
    }

    public function setAttribute(string $name, $value): static
    {
        $this->getInnerBuilder()->setAttribute($name, $value);

        return $this;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->getInnerBuilder()->getAttribute($name, $default);
    }

    public function hasAttribute(string $name): bool
    {
        return $this->getInnerBuilder()->hasAttribute($name);
    }

    public function setAttributes(array $attributes): static
    {
        $this->getInnerBuilder()->setAttributes($attributes);

        return $this;
    }

    public function getAttributes(): array
    {
        return $this->getInnerBuilder()->getAttributes();
    }

    public function hasOption(string $name): bool
    {
        return $this->getInnerBuilder()->hasOption($name);
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->getInnerBuilder()->getOption($name, $default);
    }

    public function setData(mixed $modelData): static
    {
        $this->getInnerBuilder()->setData($modelData);

        return $this;
    }

    public function getData(): mixed
    {
        return $this->getInnerBuilder()->getData();
    }

    public function setEmptyData(mixed $emptyData): static
    {
        $this->getInnerBuilder()->setEmptyData($emptyData);

        return $this;
    }

    public function getEmptyData(): mixed
    {
        return $this->getInnerBuilder()->getEmptyData();
    }

    public function setRequired(bool $required): static
    {
        $this->getInnerBuilder()->setRequired($required);

        return $this;
    }

    public function getRequired(): bool
    {
        return $this->getInnerBuilder()->getRequired();
    }

    public function setMapped(bool $mapped): static
    {
        $this->getInnerBuilder()->setMapped($mapped);

        return $this;
    }

    public function getMapped(): bool
    {
        return $this->getInnerBuilder()->getMapped();
    }

    public function setByReference(bool $byReference): static
    {
        $this->getInnerBuilder()->setByReference($byReference);

        return $this;
    }

    public function getByReference(): bool
    {
        return $this->getInnerBuilder()->getByReference();
    }

    public function setPropertyPath($propertyPath): static
    {
        $this->getInnerBuilder()->setPropertyPath($propertyPath);

        return $this;
    }

    public function getPropertyPath(): ?PropertyPathInterface
    {
        return $this->getInnerBuilder()->getPropertyPath();
    }

    public function setDisabled(bool $disabled): static
    {
        $this->getInnerBuilder()->setDisabled($disabled);

        return $this;
    }

    public function getDisabled(): bool
    {
        return $this->getInnerBuilder()->getDisabled();
    }

    public function setErrorBubbling(bool $errorBubbling): static
    {
        $this->getInnerBuilder()->setErrorBubbling($errorBubbling);

        return $this;
    }

    public function getErrorBubbling(): bool
    {
        return $this->getInnerBuilder()->getErrorBubbling();
    }

    public function setInheritData(bool $inheritData): static
    {
        $this->getInnerBuilder()->setInheritData($inheritData);

        return $this;
    }

    public function getInheritData(): bool
    {
        return $this->getInnerBuilder()->getInheritData();
    }

    public function setAutoInitialize(bool $autoInitialize): static
    {
        $this->getInnerBuilder()->setAutoInitialize($autoInitialize);

        return $this;
    }

    public function getAutoInitialize(): bool
    {
        return $this->getInnerBuilder()->getAutoInitialize();
    }

    public function setCompound(bool $compound): static
    {
        $this->getInnerBuilder()->setCompound($compound);

        return $this;
    }

    public function getCompound(): bool
    {
        return $this->getInnerBuilder()->getCompound();
    }

    public function setType(ResolvedFormTypeInterface $type): static
    {
        $this->getInnerBuilder()->setType($type);

        return $this;
    }

    public function getType(): ResolvedFormTypeInterface
    {
        return $this->getInnerBuilder()->getType();
    }

    public function setRequestHandler(RequestHandlerInterface $requestHandler): static
    {
        $this->getInnerBuilder()->setRequestHandler($requestHandler);

        return $this;
    }

    public function getRequestHandler(): RequestHandlerInterface
    {
        return $this->getInnerBuilder()->getRequestHandler();
    }

    public function setFormFactory(FormFactoryInterface $formFactory): static
    {
        $this->getInnerBuilder()->setFormFactory($formFactory);

        return $this;
    }

    public function getFormFactory(): FormFactoryInterface
    {
        return $this->getInnerBuilder()->getFormFactory();
    }

    public function setDataMapper(?DataMapperInterface $dataMapper): static
    {
        $this->getInnerBuilder()->setDataMapper($dataMapper);

        return $this;
    }

    public function getDataMapper(): ?DataMapperInterface
    {
        return $this->getInnerBuilder()->getDataMapper();
    }

    public function addEventListener(string $eventName, callable $listener, int $priority = 0): static
    {
        $this->getInnerBuilder()->addEventListener($eventName, $listener, $priority);

        return $this;
    }

    public function addEventSubscriber(EventSubscriberInterface $subscriber): static
    {
        $this->getInnerBuilder()->addEventSubscriber($subscriber);

        return $this;
    }

    public function addViewTransformer(DataTransformerInterface $viewTransformer, bool $forcePrepend = false): static
    {
        $this->getInnerBuilder()->addViewTransformer($viewTransformer, $forcePrepend);

        return $this;
    }

    public function resetViewTransformers(): static
    {
        $this->getInnerBuilder()->resetViewTransformers();

        return $this;
    }

    public function addModelTransformer(DataTransformerInterface $modelTransformer, bool $forceAppend = false): static
    {
        $this->getInnerBuilder()->addModelTransformer($modelTransformer, $forceAppend);

        return $this;
    }

    public function resetModelTransformers(): static
    {
        $this->getInnerBuilder()->resetModelTransformers();

        return $this;
    }

    public function setAction(?string $action): static
    {
        $this->getInnerBuilder()->setAction($action);

        return $this;
    }

    public function getAction(): string
    {
        return $this->getInnerBuilder()->getAction();
    }

    public function setMethod(string $method): static
    {
        $this->getInnerBuilder()->setMethod($method);

        return $this;
    }

    public function getMethod(): string
    {
        return $this->getInnerBuilder()->getMethod();
    }

    public function setDataLocked(bool $locked): static
    {
        $this->getInnerBuilder()->setDataLocked($locked);

        return $this;
    }

    public function getDataLocked(): bool
    {
        return $this->getInnerBuilder()->getDataLocked();
    }

    public function getDataClass(): ?string
    {
        return $this->getInnerBuilder()->getDataClass();
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->getInnerBuilder()->getEventDispatcher();
    }

    public function getName(): string
    {
        return $this->getInnerBuilder()->getName();
    }

    public function all(): array
    {
        return $this->getInnerBuilder()->all();
    }

    public function getViewTransformers(): array
    {
        return $this->getInnerBuilder()->getViewTransformers();
    }

    public function getModelTransformers(): array
    {
        return $this->getInnerBuilder()->getModelTransformers();
    }

    public function getOptions(): array
    {
        return $this->getInnerBuilder()->getOptions();
    }

    public function setIsEmptyCallback(?callable $callback): static
    {
        $this->getInnerBuilder()->setIsEmptyCallback($callback);

        return $this;
    }

    public function getIsEmptyCallback(): ?callable
    {
        return $this->getInnerBuilder()->getIsEmptyCallback();
    }
}