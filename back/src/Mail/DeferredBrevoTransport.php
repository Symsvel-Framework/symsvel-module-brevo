<?php

declare(strict_types=1);

namespace SymSvel\Module\Brevo\Mail;

use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use SymSvel\Module\Settings\Service\SettingManager;

/**
 * Lazy named transport: an unconfigured Brevo account must NEVER prevent
 * other named transports (Mailpit, SMTP, etc.) from initializing or sending.
 */
final readonly class DeferredBrevoTransport implements TransportInterface
{
    public function __construct(
        private SettingManager $settings,
        private HttpClientInterface $client,
    ) {
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $apiKey = trim((string) $this->settings->get('brevo.api_key'));
        if ($apiKey === '') {
            throw new \LogicException('Brevo API key is not configured.');
        }

        // Factory and HTTP request are created ONLY on a Brevo delivery.
        // The official bridge remains responsible for API/MIME compatibility.
        return (new BrevoTransportFactory(client: $this->client))
            ->create(new Dsn('brevo+api', 'default', $apiKey))
            ->send($message, $envelope);
    }

    public function __toString(): string
    {
        return 'symsvel-brevo://default';
    }
}
