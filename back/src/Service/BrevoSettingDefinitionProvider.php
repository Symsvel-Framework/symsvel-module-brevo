<?php

declare(strict_types=1);

namespace SymSvel\Module\Brevo\Service;

use SymSvel\Module\Settings\Service\SettingDefinition;
use SymSvel\Module\Settings\Service\SettingDefinitionProviderInterface;

/** Brevo owns its secret; Mails remains responsible for sender identity. */
final class BrevoSettingDefinitionProvider implements SettingDefinitionProviderInterface
{
    public function definitions(): iterable
    {
        yield new SettingDefinition(
            key: 'api_key',
            label: 'Clé API transactionnelle Brevo',
            group: 'brevo',
            required: false,
            secret: true,
            description: 'Clé API v3 pour l’envoi Brevo. Nécessaire uniquement si le transport Brevo est sélectionné.',
        );
    }
}
