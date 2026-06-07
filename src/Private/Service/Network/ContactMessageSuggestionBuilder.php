<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

final class ContactMessageSuggestionBuilder
{
    private const string RECOMMENDED_CHANNEL_LINKEDIN = 'linkedin';
    private const string RECOMMENDED_CHANNEL_EMAIL = 'email';
    private const string RECOMMENDED_CHANNEL_PHONE = 'phone';
    private const string RECOMMENDED_CHANNEL_NONE = 'none';

    /**
     * @var array<string, string>
     */
    private const SUBJECT_TEMPLATES = [
        ContactRoleClassifier::CATEGORY_RECRUITMENT_HR => 'Disponibilité freelance - développement web backend',
    ];

    private const DEFAULT_SUBJECT = 'Prise de contact freelance';

    public function __construct(
        private readonly ContactMergeRulesService $mergeRules,
        private readonly ContactRoleClassifier $roleClassifier,
    ) {
    }

    /**
     * @param array<string, mixed> $contact
     *
     * @return array{
     *     template: string,
     *     subject: string,
     *     prompt: string,
     *     recommended_channel: string,
     *     recommended_channel_label: string
     * }
     */
    public function build(array $contact): array
    {
        return [
            'template' => $this->templateKeyFor($contact),
            'subject' => $this->subjectFor($contact),
            'prompt' => $this->buildPrompt($contact),
            'recommended_channel' => $this->recommendedChannelFor($contact),
            'recommended_channel_label' => $this->recommendedChannelLabelFor($contact),
        ];
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function templateKeyFor(array $contact): string
    {
        $category = $this->normalizedCategory($contact);
        if ($category !== '' && isset(self::SUBJECT_TEMPLATES[$category])) {
            return $category;
        }

        return 'default';
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function subjectFor(array $contact): string
    {
        $category = $this->normalizedCategory($contact);

        return self::SUBJECT_TEMPLATES[$category] ?? self::DEFAULT_SUBJECT;
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function buildPrompt(array $contact): string
    {
        $contactLines = [
            'Contact :',
            $this->promptLine('Nom', $contact['display_name'] ?? '', 'Contact non renseigné'),
            $this->promptLine('Entreprise', $contact['organization'] ?? '', 'non renseignée'),
            $this->promptLine('Rôle / fonction', $contact['role'] ?? '', 'non renseigné'),
            $this->promptLine('Catégorie métier', $contact['role_category_label'] ?? '', 'Autre'),
            $this->promptLine('Canal prévu', $this->plannedChannelValue($contact), 'inconnu'),
            '- Langue supposée du contact : inconnue',
            $this->promptLine('Contexte connu', $this->knownContextValue($contact), 'aucune note disponible'),
            $this->promptLine('Dernière interaction connue', $this->lastInteractionValue($contact), 'aucune interaction enregistrée'),
            $this->promptLine('Statut de la relation', $contact['relationship_status_label'] ?? '', 'non renseigné'),
            $this->promptLine('Dernier contact enregistré', $contact['last_contact_at_label'] ?? '', 'jamais'),
            $this->promptLine('Prochaine action éventuelle', $this->nextActionValue($contact), 'aucune'),
            $this->promptLine('Priorité', $contact['priority_label'] ?? '', 'non renseignée'),
            $this->promptLine('Profil', $contact['profile_url'] ?? '', 'non renseigné'),
            $this->promptLine('Source', $contact['source'] ?? '', 'non renseignée'),
        ];

        $aboutMeLines = [
            'À propos de moi :',
            '- Je m’appelle Benjamin Lemin.',
            '- Je suis développeur web senior freelance.',
            '- J’ai plus de 15 ans d’expérience.',
            '- Je suis spécialisé en PHP, Symfony, Drupal et applications web métier.',
            '- J’interviens surtout sur de la reprise d’existant, de la maintenance évolutive, de l’amélioration de systèmes existants et des projets nécessitant une bonne compréhension du contexte.',
            '- Je recherche des missions freelance à temps partiel.',
            '- Je suis disponible environ 20h/semaine.',
            '- Je privilégie les missions backend ou full-stack orientées web, idéalement avec une part de PHP/Symfony/Drupal.',
            '- Je peux travailler en français ou en anglais professionnel.',
            '- Je souhaite garder un ton professionnel, simple, direct et humain.',
        ];

        $goalLines = [
            'Ce que je veux obtenir :',
            'Rédige un message prêt à envoyer à cette personne.',
        ];

        $constraintLines = [
            'Contraintes :',
            '- Choisis la langue la plus adaptée selon les informations du contact.',
            '- Si la langue du contact est inconnue, rédige en français sauf si le contexte indique plutôt l’anglais.',
            '- Le message doit être court et naturel.',
            '- Le ton doit être professionnel sans être commercial agressif.',
            '- Évite les formulations trop génériques.',
            '- Ne survends pas mon profil.',
            '- Mentionne clairement que je suis disponible pour des missions freelance.',
            '- Mentionne mes compétences principales seulement si c’est pertinent.',
            '- Termine par une ouverture simple à l’échange.',
            '- Ne mets pas d’objet d’email sauf si le canal prévu est l’email.',
            '- Si le canal prévu est l’email, propose aussi un objet court et pertinent.',
            '- N’invente aucune information absente du contexte.',
        ];

        return implode("\n", array_merge(
            [
                'Tu es un assistant spécialisé dans la rédaction de messages professionnels courts, humains et efficaces.',
                '',
                'Je souhaite contacter la personne suivante :',
                '',
            ],
            $contactLines,
            [
                '',
            ],
            $aboutMeLines,
            [
                '',
            ],
            $goalLines,
            [
                '',
            ],
            $constraintLines,
        ));
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function promptLine(string $label, mixed $value, string $fallback): string
    {
        return sprintf('- %s : %s', $label, $this->normalizePromptValue($value, $fallback));
    }

    private function normalizePromptValue(mixed $value, string $fallback): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $fallback;
        }

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function plannedChannelValue(array $contact): string
    {
        $mainChannel = trim((string) ($contact['main_channel'] ?? ''));
        if ($mainChannel !== '') {
            return $mainChannel;
        }

        $recommendedChannel = $this->recommendedChannelLabelFor($contact);

        return $recommendedChannel !== 'Aucun canal exploitable' ? $recommendedChannel : 'inconnu';
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function knownContextValue(array $contact): string
    {
        $notes = $this->normalizePromptValue($contact['notes'] ?? '', '');
        $source = $this->normalizePromptValue($contact['source'] ?? '', '');

        $parts = [];
        if ($notes !== '') {
            $parts[] = $notes;
        }

        if ($source !== '') {
            $parts[] = sprintf('source : %s', $source);
        }

        return $parts !== [] ? implode(' | ', $parts) : 'aucune note disponible';
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function lastInteractionValue(array $contact): string
    {
        $date = trim((string) ($contact['last_interaction_at_label'] ?? ''));
        $summary = $this->normalizePromptValue($contact['last_interaction_summary'] ?? '', '');
        $channel = $this->normalizePromptValue($contact['last_interaction_channel'] ?? '', '');

        if ($date === '' && $summary === '' && $channel === '') {
            return 'aucune interaction enregistrée';
        }

        $parts = [];
        if ($date !== '') {
            $parts[] = $date;
        }

        if ($summary !== '') {
            $parts[] = $summary;
        }

        if ($channel !== '') {
            $parts[] = sprintf('canal : %s', $channel);
        }

        return implode(' — ', $parts);
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function nextActionValue(array $contact): string
    {
        $nextAction = $this->normalizePromptValue($contact['next_action'] ?? '', '');
        if ($nextAction === '') {
            return 'aucune';
        }

        $nextActionAt = trim((string) ($contact['next_action_at_label'] ?? ''));
        if ($nextActionAt === '') {
            return $nextAction;
        }

        return sprintf('%s (%s)', $nextAction, $nextActionAt);
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function normalizedCategory(array $contact): string
    {
        $category = trim((string) ($contact['role_category'] ?? ''));

        return $this->roleClassifier->isKnownCategory($category) ? $category : '';
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function recommendedChannelFor(array $contact): string
    {
        if ($this->hasLinkedInProfile($contact)) {
            return self::RECOMMENDED_CHANNEL_LINKEDIN;
        }

        if ($this->hasEmail($contact)) {
            return self::RECOMMENDED_CHANNEL_EMAIL;
        }

        if ($this->hasPhone($contact)) {
            return self::RECOMMENDED_CHANNEL_PHONE;
        }

        return self::RECOMMENDED_CHANNEL_NONE;
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function recommendedChannelLabelFor(array $contact): string
    {
        return match ($this->recommendedChannelFor($contact)) {
            self::RECOMMENDED_CHANNEL_LINKEDIN => 'LinkedIn',
            self::RECOMMENDED_CHANNEL_EMAIL => 'Email',
            self::RECOMMENDED_CHANNEL_PHONE => 'Téléphone',
            default => 'Aucun canal exploitable',
        };
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function hasLinkedInProfile(array $contact): bool
    {
        return $this->mergeRules->isLinkedInProfileUrl($contact['profile_url'] ?? '')
            || mb_strtolower(trim((string) ($contact['main_channel'] ?? ''))) === 'linkedin';
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function hasEmail(array $contact): bool
    {
        $emails = $contact['emails'] ?? [];
        if (!is_array($emails)) {
            $emails = [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $email): string => trim((string) $email),
            $emails,
        ))) !== [];
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function hasPhone(array $contact): bool
    {
        $phones = $contact['phones'] ?? [];
        if (!is_array($phones)) {
            $phones = [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $phone): string => trim((string) $phone),
            $phones,
        ))) !== [];
    }
}
