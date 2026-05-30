<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

final class ContactRoleClassifier
{
    public const CATEGORY_RECRUITMENT_HR = 'recruitment_hr';
    public const CATEGORY_DEVELOPER_TECHNICAL = 'developer_technical';
    public const CATEGORY_MANAGER_DECISION_MAKER = 'manager_decision_maker';
    public const CATEGORY_BUSINESS_SALES_ACCOUNT = 'business_sales_account';
    public const CATEGORY_PRODUCT_PROJECT_ANALYSIS = 'product_project_analysis';
    public const CATEGORY_DESIGN_MARKETING_COMMUNICATION = 'design_marketing_communication';
    public const CATEGORY_IT_OPS_SECURITY_INFRA = 'it_ops_security_infra';
    public const CATEGORY_OTHER = 'other';
    public const CATEGORY_TO_REVIEW = 'to_review';

    /**
     * @var array<string, string>
     */
    private const CATEGORY_LABELS = [
        self::CATEGORY_RECRUITMENT_HR => 'Recrutement / RH',
        self::CATEGORY_DEVELOPER_TECHNICAL => 'Développement / technique',
        self::CATEGORY_MANAGER_DECISION_MAKER => 'Direction / décideur',
        self::CATEGORY_BUSINESS_SALES_ACCOUNT => 'Business / sales / account',
        self::CATEGORY_PRODUCT_PROJECT_ANALYSIS => 'Produit / projet / analyse',
        self::CATEGORY_DESIGN_MARKETING_COMMUNICATION => 'Design / marketing / communication',
        self::CATEGORY_IT_OPS_SECURITY_INFRA => 'Ops / sécurité / infra',
        self::CATEGORY_OTHER => 'Autre',
        self::CATEGORY_TO_REVIEW => 'À revoir',
    ];

    /**
     * @var list<array{
     *     category: string,
     *     rule: string,
     *     confidence: int,
     *     terms: list<string>
     * }>
     */
    private const RULES = [
        [
            'category' => self::CATEGORY_RECRUITMENT_HR,
            'rule' => 'recruitment_keywords',
            'confidence' => 96,
            'terms' => [
                'talent acquisition',
                'recruiter',
                'recruitment',
                'recruiting',
                'headhunter',
                'sourcing',
                'sourcer',
                'human resources',
                'people and culture',
                'people culture',
                'hr',
                'rh',
            ],
        ],
        [
            'category' => self::CATEGORY_BUSINESS_SALES_ACCOUNT,
            'rule' => 'commercial_keywords',
            'confidence' => 90,
            'terms' => [
                'business development',
                'business developer',
                'business manager',
                'account manager',
                'account executive',
                'sales',
                'commercial',
                'client relations',
                'client relation',
                'client strategy',
                'partnerships',
                'partnership',
                'bd',
            ],
        ],
        [
            'category' => self::CATEGORY_PRODUCT_PROJECT_ANALYSIS,
            'rule' => 'product_project_keywords',
            'confidence' => 90,
            'terms' => [
                'product owner',
                'product manager',
                'product analyst',
                'project manager',
                'project lead',
                'project coordinator',
                'scrum master',
                'business analyst',
                'delivery manager',
                'program manager',
                'programme manager',
            ],
        ],
        [
            'category' => self::CATEGORY_DESIGN_MARKETING_COMMUNICATION,
            'rule' => 'creative_keywords',
            'confidence' => 88,
            'terms' => [
                'designer',
                'design',
                'ux',
                'ui',
                'marketing',
                'communications',
                'communication',
                'brand',
                'content',
                'creative',
                'media',
                'graphic',
            ],
        ],
        [
            'category' => self::CATEGORY_IT_OPS_SECURITY_INFRA,
            'rule' => 'infra_keywords',
            'confidence' => 92,
            'terms' => [
                'system administrator',
                'systems administrator',
                'system and network administrator',
                'administrator',
                'infrastructure',
                'infra',
                'security',
                'cybersecurity',
                'network',
                'cloud',
                'devops',
                'sre',
                'helpdesk',
                'support',
                'operations',
            ],
        ],
        [
            'category' => self::CATEGORY_DEVELOPER_TECHNICAL,
            'rule' => 'technical_keywords',
            'confidence' => 95,
            'terms' => [
                'developer',
                'developpeur',
                'software engineer',
                'engineer',
                'architecte logiciel',
                'architecte applicatif',
                'architecte technique',
                'software architect',
                'solution architect',
                'technical architect',
                'tech lead',
                'technical lead',
                'full stack',
                'backend',
                'back end',
                'frontend',
                'front end',
                'php',
                'symfony',
                'javascript',
                'typescript',
                'react',
                'vue',
                'angular',
                'java',
                'net',
                'dotnet',
                'python',
                'mobile developer',
                'ios',
                'android',
                'software development',
                'development',
            ],
        ],
        [
            'category' => self::CATEGORY_MANAGER_DECISION_MAKER,
            'rule' => 'decision_maker_keywords',
            'confidence' => 97,
            'terms' => [
                'chief executive officer',
                'ceo',
                'chief technology officer',
                'cto',
                'chief product officer',
                'cpo',
                'chief operating officer',
                'coo',
                'co founder',
                'cofounder',
                'founder',
                'owner',
                'managing director',
                'managing partner',
                'president',
                'vice president',
                'vp',
                'head of',
                'general manager',
            ],
        ],
    ];

    /**
     * @var list<string>
     */
    private const TO_REVIEW_TOKENS = [
        'consultant',
        'consultante',
        'manager',
        'director',
        'lead',
        'specialist',
        'advisor',
        'adviser',
        'expert',
        'officer',
        'coordinator',
        'assistant',
        'associate',
        'member',
        'responsable',
        'chef',
        'gérant',
        'gerant',
        'directeur',
        'directrice',
        'student',
        'etudiant',
        'étudiant',
        'intern',
        'freelance',
        'independent',
    ];

    public function __construct(
        private readonly ContactMergeRulesService $mergeRules,
    ) {
    }

    /**
     * @return array{
     *     category: string,
     *     label: string,
     *     confidence: int,
     *     matched_rule: string|null,
     *     normalized_role: string
     * }
     */
    public function classify(mixed $role): array
    {
        $normalizedRole = $this->normalizeRole($role);
        if ($normalizedRole === '') {
            return $this->buildResult(self::CATEGORY_TO_REVIEW, null, 0, $normalizedRole);
        }

        foreach (self::RULES as $categoryRules) {
            foreach ($categoryRules['terms'] as $term) {
                if ($this->matchesTerm($normalizedRole, $term)) {
                    return $this->buildResult(
                        $categoryRules['category'],
                        $categoryRules['rule'] . ':' . $term,
                        $categoryRules['confidence'],
                        $normalizedRole,
                    );
                }
            }
        }

        if ($this->shouldReview($normalizedRole)) {
            return $this->buildResult(self::CATEGORY_TO_REVIEW, 'generic_role', 35, $normalizedRole);
        }

        return $this->buildResult(self::CATEGORY_OTHER, 'fallback_other', 15, $normalizedRole);
    }

    /**
     * @return array<string, string>
     */
    public function getCategoryOptions(): array
    {
        return ['' => 'Toutes les catégories'] + self::CATEGORY_LABELS;
    }

    public function getCategoryLabel(string $category): string
    {
        return self::CATEGORY_LABELS[$category] ?? self::CATEGORY_LABELS[self::CATEGORY_OTHER];
    }

    public function isKnownCategory(string $category): bool
    {
        return isset(self::CATEGORY_LABELS[$category]);
    }

    private function normalizeRole(mixed $role): string
    {
        return $this->mergeRules->normalizeComparableText($role);
    }

    private function shouldReview(string $normalizedRole): bool
    {
        foreach (self::TO_REVIEW_TOKENS as $token) {
            if (str_contains($normalizedRole, $token)) {
                return true;
            }
        }

        return false;
    }

    private function matchesTerm(string $normalizedRole, string $term): bool
    {
        $pattern = '/\b' . preg_quote($term, '/') . '\b/';

        return preg_match($pattern, $normalizedRole) === 1;
    }

    /**
     * @return array{
     *     category: string,
     *     label: string,
     *     confidence: int,
     *     matched_rule: string|null,
     *     normalized_role: string
     * }
     */
    private function buildResult(string $category, ?string $matchedRule, int $confidence, string $normalizedRole): array
    {
        return [
            'category' => $category,
            'label' => $this->getCategoryLabel($category),
            'confidence' => max(0, min(100, $confidence)),
            'matched_rule' => $matchedRule,
            'normalized_role' => $normalizedRole,
        ];
    }
}
