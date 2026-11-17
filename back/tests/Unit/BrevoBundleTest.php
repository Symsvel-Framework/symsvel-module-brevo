<?php

declare(strict_types=1);

namespace SymSvel\Module\Brevo\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use SymSvel\Module\Brevo\BrevoBundle;
use SymSvel\Module\Brevo\Mail\BrevoSettingsTransportFactory;
use SymSvel\Module\Brevo\Mail\BrevoTransportProvider;
use SymSvel\Module\Brevo\Service\BrevoSettingDefinitionProvider;
use SymSvel\Module\Mails\Mail\MailTransportCatalog;
use SymSvel\Module\Settings\Service\SettingKeyQualifier;
use SymSvel\Module\Settings\Service\SettingRegistry;

final class BrevoBundleTest extends TestCase
{
    public function testRegistersBrevoAndSettingsProvidersWithExistingContracts(): void
    {
        $builder = new ContainerBuilder();
        $configurator = (new \ReflectionClass(ContainerConfigurator::class))->newInstanceWithoutConstructor();
        (new BrevoBundle())->loadExtension([], $configurator, $builder);

        self::assertTrue($builder->getDefinition(BrevoTransportProvider::class)
            ->hasTag('symsvel.mails.transport_provider'));
        self::assertTrue($builder->getDefinition(BrevoSettingDefinitionProvider::class)
            ->hasTag('symsvel.settings.definition_provider'));
        self::assertTrue($builder->getDefinition(BrevoSettingsTransportFactory::class)
            ->hasTag('mailer.transport_factory'));
        self::assertTrue($builder->getDefinition(BrevoSettingsTransportFactory::class)->isAutowired());
    }

    public function testSettingsSecretAndOptionalMailChoice(): void
    {
        $registry = new SettingRegistry([]);
        $registry->registerOwned(new BrevoSettingDefinitionProvider(), 'brevo', new SettingKeyQualifier());

        $definition = $registry->get('brevo.api_key');
        self::assertNotNull($definition);
        self::assertTrue($definition->secret);
        self::assertFalse($definition->public);
        self::assertFalse($definition->required);
        self::assertSame('brevo.api_key', $definition->key);

        $catalog = new MailTransportCatalog([new BrevoTransportProvider()]);
        self::assertTrue($catalog->has('brevo'));
        self::assertSame('Brevo (API transactionnelle)', $catalog->choices()['brevo']);
        self::assertTrue($catalog->has('mailpit'));
    }

    public function testBundleHasNoTablesOrExternalProviderWorkAtConstruction(): void
    {
        self::assertInstanceOf(BrevoBundle::class, new BrevoBundle());
    }
}
