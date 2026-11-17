<?php

declare(strict_types=1);

namespace SymSvel\Module\Brevo\Tests\Integration;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mime\Email;
use SymSvel\Module\Brevo\Mail\BrevoSettingsTransportFactory;
use SymSvel\Module\Brevo\Service\BrevoSettingDefinitionProvider;
use SymSvel\Module\Settings\Entity\Setting;
use SymSvel\Module\Settings\Repository\SettingRepository;
use SymSvel\Module\Settings\Service\SettingKeyQualifier;
use SymSvel\Module\Settings\Service\SettingManager;
use SymSvel\Module\Settings\Service\SettingRegistry;
use SymSvel\Module\Settings\Service\SettingSecretCipher;

/** Uses the actual Symfony Brevo bridge, but never makes a network call. */
final class BrevoSettingsTransportFactoryTest extends TestCase
{
    /** @return array{EntityManager, SettingManager, SettingRepository} */
    private function settings(): array
    {
        if (!extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('pdo_sqlite is required for Brevo integration tests.');
        }
        $config = ORMSetup::createAttributeMetadataConfiguration(
            [dirname((new \ReflectionClass(Setting::class))->getFileName())], true,
        );
        $config->enableNativeLazyObjects(true);
        $entityManager = new EntityManager(
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]), $config,
        );
        (new SchemaTool($entityManager))->createSchema([$entityManager->getClassMetadata(Setting::class)]);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->with(Setting::class)->willReturn($entityManager);
        $registry = new SettingRegistry([]);
        $registry->registerOwned(new BrevoSettingDefinitionProvider(), 'brevo', new SettingKeyQualifier());
        $repository = new SettingRepository($managerRegistry);
        return [$entityManager, new SettingManager($registry, $repository, $entityManager,
            new SettingSecretCipher('brevo-test-secret')), $repository];
    }

    public function testNativeBridgeSendsWithStoredEncryptedKey(): void
    {
        [$entityManager, $settings, $repository] = $this->settings();
        try {
            $settings->set('brevo.api_key', 'test-api-secret');
            $settings->flush();
            $entityManager->clear();
            self::assertNotSame('test-api-secret', $repository->findOneBy(['key' => 'brevo.api_key'])?->getValue());

            $called = false;
            $client = new MockHttpClient(static function (string $method, string $url, array $options) use (&$called): MockResponse {
                $called = true;
                self::assertSame('POST', $method);
                self::assertSame('https://api.brevo.com/v3/smtp/email', $url);
                self::assertContains('api-key: test-api-secret', $options['normalized_headers']['api-key'] ?? []);
                $data = json_decode((string) ($options['body'] ?? ''), true, 512, JSON_THROW_ON_ERROR);
                self::assertSame([['email' => 'to@example.test']], $data['to'] ?? null);
                self::assertSame(['email' => 'sender@example.test', 'name' => 'SymSvel'], $data['sender'] ?? null);
                self::assertSame('Test', $data['subject'] ?? null);
                self::assertSame('<p>Test</p>', $data['htmlContent'] ?? null);
                return new MockResponse('{"messageId":"<message@example.test>"}', ['http_code' => 201]);
            });
            $factory = new BrevoSettingsTransportFactory($settings, $client);
            self::assertTrue($factory->supports(new Dsn('symsvel-brevo', 'default')));
            $transport = $factory->create(new Dsn('symsvel-brevo', 'default'));
            $transport->send((new Email())->from('SymSvel <sender@example.test>')
                ->to('to@example.test')->subject('Test')->html('<p>Test</p>')->text('Test'));
            self::assertTrue($called);
        } finally {
            $entityManager->getConnection()->close();
        }
    }

    public function testMissingKeyFailsBeforeAnyExternalRequest(): void
    {
        [$entityManager, $settings] = $this->settings();
        try {
            $client = new MockHttpClient(static function (): never {
                self::fail('No Brevo request is allowed without a configured key.');
            });
            $factory = new BrevoSettingsTransportFactory($settings, $client);
            // Mailpit/SMTP may already be configured: creating the Brevo
            // transport must not read Settings or reject an absent key.
            $transport = $factory->create(new Dsn('symsvel-brevo', 'default'));
            self::assertSame('symsvel-brevo://default', (string) $transport);
            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage('Brevo API key is not configured.');
            $transport->send((new Email())->from('sender@example.test')
                ->to('to@example.test')->subject('Test')->text('Test'));
        } finally {
            $entityManager->getConnection()->close();
        }
    }

    public function testWrongSchemeIsRejectedWithoutUsingKey(): void
    {
        [$entityManager, $settings] = $this->settings();
        try {
            $factory = new BrevoSettingsTransportFactory($settings, new MockHttpClient());
            self::assertFalse($factory->supports(new Dsn('smtp', 'localhost')));
            $this->expectException(\InvalidArgumentException::class);
            $factory->create(new Dsn('symsvel-brevo', 'other-host'));
        } finally {
            $entityManager->getConnection()->close();
        }
    }
}
