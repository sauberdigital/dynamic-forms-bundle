<?php

/**
 * (c) sauber digital <info@sauberdigital.de>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sd\DynamicFormsBundle\Builder\Subscriber;

use Sd\DynamicFormsBundle\FieldDependency\Manager\FieldDependencyManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Handles root form events and coordinates with dependency managers.
 * 
 * Responsibilities:
 * - Subscribe to root form events (PRE_SET_DATA, POST_SUBMIT)
 * - Delegate to appropriate dependency managers
 * - Coordinate between different types of dynamic behavior (field dependencies, button dependencies, etc.)
 */
final readonly class RootFormSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FieldDependencyManager $fieldDependencyManager,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => ['onPreSetData'],
            FormEvents::POST_SUBMIT => ['onPostSubmit', -1], // Low priority to run after validation
        ];
    }

    /**
     * Handle PRE_SET_DATA event on the root form.
     */
    public function onPreSetData(FormEvent $event): void
    {
        $this->fieldDependencyManager->getEventHandler()->onPreSetData(event: $event);
    }

    /**
     * Handle POST_SUBMIT event on the root form.
     * Clears transformation errors on dependent fields.
     */
    public function onPostSubmit(FormEvent $event): void
    {
        $this->fieldDependencyManager->getEventHandler()->clearTransformationErrors(event: $event);
    }
}
