<?php

declare(strict_types=1);

namespace SymSvel\Module\Brevo\Mail;

use SymSvel\Module\Mails\Mail\MailTransportProviderInterface;

/** Declares the choice only; never verifies the user's Brevo account. */
final class BrevoTransportProvider implements MailTransportProviderInterface
{
    public function transports(): iterable
    {
        yield 'brevo' => 'Brevo (API transactionnelle)';
    }
}
