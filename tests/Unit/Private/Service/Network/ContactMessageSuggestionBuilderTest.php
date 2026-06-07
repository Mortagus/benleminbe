<?php

declare(strict_types=1);

namespace App\Tests\Unit\Private\Service\Network;

use App\Private\Service\Network\ContactMessageSuggestionBuilder;
use App\Private\Service\Network\ContactMergeRulesService;
use App\Private\Service\Network\ContactRoleClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ContactMessageSuggestionBuilderTest extends TestCase
{
    private ContactMessageSuggestionBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new ContactMessageSuggestionBuilder(
            new ContactMergeRulesService(),
            new ContactRoleClassifier(new ContactMergeRulesService()),
        );
    }

    public static function provideMessageSuggestions(): iterable
    {
        yield 'recruitment linkedin' => [
            [
                'display_name' => 'Anne Example',
                'first_name' => 'Anne',
                'organization' => 'Acme',
                'role' => 'Talent Acquisition Specialist',
                'role_category' => ContactRoleClassifier::CATEGORY_RECRUITMENT_HR,
                'role_category_label' => 'Recrutement / RH',
                'main_channel' => 'LinkedIn',
                'profile_url' => 'https://www.linkedin.com/in/anne-example',
                'priority_label' => 'Haute',
                'relationship_status_label' => 'À relancer',
                'last_contact_at_label' => '01/06/2026',
                'notes' => 'Entretien informel lors d’un salon.',
                'source' => 'LinkedIn',
                'last_interaction_at_label' => '01/06/2026',
                'last_interaction_summary' => 'Message envoyé via LinkedIn',
                'last_interaction_channel' => 'LinkedIn',
                'next_action' => 'Relancer dans une semaine',
                'next_action_at_label' => '08/06/2026',
                'emails' => ['anne@example.com'],
                'phones' => ['0475 25 89 41'],
            ],
            'recruitment_hr',
            'linkedin',
            'LinkedIn',
            'Disponibilité freelance - développement web backend',
            [
                'Tu es un assistant spécialisé dans la rédaction de messages professionnels courts, humains et efficaces.',
                'Nom : Anne Example',
                'Entreprise : Acme',
                'Rôle / fonction : Talent Acquisition Specialist',
                'Catégorie métier : Recrutement / RH',
                'Canal prévu : LinkedIn',
                'Langue supposée du contact : inconnue',
                'Contexte connu : Entretien informel lors d’un salon. | source : LinkedIn',
                'Dernière interaction connue : 01/06/2026 — Message envoyé via LinkedIn — canal : LinkedIn',
                'Statut de la relation : À relancer',
                'Dernier contact enregistré : 01/06/2026',
                'Prochaine action éventuelle : Relancer dans une semaine (08/06/2026)',
                'Priorité : Haute',
                'Profil : https://www.linkedin.com/in/anne-example',
                'Source : LinkedIn',
                'Je m’appelle Benjamin Lemin.',
                'Je suis disponible environ 20h/semaine.',
                'Rédige un message prêt à envoyer à cette personne.',
                'Si le canal prévu est l’email, propose aussi un objet court et pertinent.',
            ],
        ];

        yield 'email fallback' => [
            [
                'first_name' => 'Marc',
                'role_category' => ContactRoleClassifier::CATEGORY_DEVELOPER_TECHNICAL,
                'role_category_label' => 'Développement / technique',
                'profile_url' => '',
                'emails' => ['marc@example.com'],
                'phones' => [],
                'relationship_status_label' => 'À relancer',
            ],
            'default',
            'email',
            'Email',
            'Prise de contact freelance',
            [
                'Nom : Contact non renseigné',
                'Entreprise : non renseignée',
                'Canal prévu : Email',
                'Dernière interaction connue : aucune interaction enregistrée',
                'Dernier contact enregistré : jamais',
                'Prochaine action éventuelle : aucune',
                'Profil : non renseigné',
                'Source : non renseignée',
            ],
        ];

        yield 'phone fallback' => [
            [
                'first_name' => '',
                'role_category' => ContactRoleClassifier::CATEGORY_DESIGN_MARKETING_COMMUNICATION,
                'role_category_label' => 'Design / marketing / communication',
                'profile_url' => '',
                'emails' => [],
                'phones' => ['+32 475 25 89 41'],
            ],
            'default',
            'phone',
            'Téléphone',
            'Prise de contact freelance',
            [
                'Canal prévu : Téléphone',
                'Langue supposée du contact : inconnue',
                'Dernière interaction connue : aucune interaction enregistrée',
                'Contexte connu : aucune note disponible',
            ],
        ];

        yield 'no channel fallback' => [
            [
                'first_name' => '',
                'role_category' => ContactRoleClassifier::CATEGORY_OTHER,
                'role_category_label' => 'Autre',
                'profile_url' => '',
                'emails' => [],
                'phones' => [],
            ],
            'default',
            'none',
            'Aucun canal exploitable',
            'Prise de contact freelance',
            [
                'Canal prévu : inconnu',
                'Dernière interaction connue : aucune interaction enregistrée',
            ],
        ];
    }

    #[DataProvider('provideMessageSuggestions')]
    public function testItBuildsSuggestions(array $contact, string $expectedTemplate, string $expectedChannel, string $expectedChannelLabel, string $expectedSubject, array $expectedPromptFragments): void
    {
        $suggestion = $this->builder->build($contact);

        self::assertSame($expectedTemplate, $suggestion['template']);
        self::assertSame($expectedChannel, $suggestion['recommended_channel']);
        self::assertSame($expectedChannelLabel, $suggestion['recommended_channel_label']);
        self::assertSame($expectedSubject, $suggestion['subject']);

        foreach ($expectedPromptFragments as $fragment) {
            self::assertStringContainsString($fragment, $suggestion['prompt']);
        }
    }
}
