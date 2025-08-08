<?php

/**
 * (c) sauber digital <info@sauberdigital.de>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return function (ContainerConfigurator $container): void {
    ########## SERVICES ##########
    $services = $container->services();

    ########## DEFAULTS ##########
    $services->defaults()
        ->autowire(true)
        ->autoconfigure(true)
        ->private()
    ;
};
