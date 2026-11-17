<?php

declare(strict_types=1);

namespace SymSvel\Module\Brevo;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use SymSvel\Module\Brevo\Mail\BrevoSettingsTransportFactory;
use SymSvel\Module\Brevo\Mail\BrevoTransportProvider;
use SymSvel\Module\Brevo\Service\BrevoSettingDefinitionProvider;

/**
 * Optional bridge between Mails, Settings and Symfony Brevo Mailer.
 * No application mail business logic belongs here.
 */
final class BrevoBundle extends AbstractBundle
{
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // The provider is absent from Symfony's named transports unless the
        // Brevo module is installed. Selecting it still requires valid Settings.
        $container->extension('framework', [
            'mailer' => ['transports' => ['brevo' => 'symsvel-brevo://default']],
        ]);
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->register(BrevoSettingDefinitionProvider::class, BrevoSettingDefinitionProvider::class)
            ->addTag('symsvel.settings.definition_provider');

        $builder->register(BrevoTransportProvider::class, BrevoTransportProvider::class)
            ->addTag('symsvel.mails.transport_provider');

        $builder->register(BrevoSettingsTransportFactory::class, BrevoSettingsTransportFactory::class)
            ->setAutowired(true)
            ->addTag('mailer.transport_factory');
    }
}
