<?php

declare(strict_types=1);

namespace SymSvel\Module\Brevo\Mail;

use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use SymSvel\Module\Settings\Service\SettingManager;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Registers a lazy transport that reads encrypted Settings credentials only
 * when Brevo is selected for an actual delivery.
 * No Brevo HTTP payload is reimplemented by this module.
 */
final class BrevoSettingsTransportFactory extends AbstractTransportFactory
{
    public function __construct(private readonly SettingManager $settings, HttpClientInterface $client)
    {
        parent::__construct(client: $client);
    }

    public function create(Dsn $dsn): TransportInterface
    {
        if (!$this->supports($dsn) || $dsn->getHost() !== 'default') {
            throw new \InvalidArgumentException('Unsupported Brevo transport DSN.');
        }

        return new DeferredBrevoTransport($this->settings, $this->client);
    }

    protected function getSupportedSchemes(): array
    {
        return ['symsvel-brevo'];
    }
}
