<?php

declare(strict_types=1);

namespace SymSvel\Module\Brevo\Mail;

use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use SymSvel\Module\Settings\Service\SettingManager;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Adapts encrypted Settings credentials to Symfony's official Brevo bridge.
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

        $apiKey = trim((string) $this->settings->get('brevo.api_key'));
        if ($apiKey === '') {
            throw new \LogicException('Brevo API key is not configured.');
        }

        // The Symfony bridge owns Brevo's API request, MIME attachments,
        // transport errors and HTTPS. Never expose the key in an exception.
        return (new BrevoTransportFactory($this->dispatcher, $this->client, $this->logger))
            ->create(new Dsn('brevo+api', 'default', $apiKey));
    }

    protected function getSupportedSchemes(): array
    {
        return ['symsvel-brevo'];
    }
}
