<?php

declare(strict_types=1);

namespace App\Public\Service;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ExperienceProvider
{
    /**
     * @var list<string>
     */
    private const array EXPERIENCE_ORDER = [
        'contraste-digital',
        'blubird',
        'isobar',
        'adneom',
        'f2c',
        'sogesa',
        'cbmn',
    ];

    /**
     * @var array<string, array{
     *     slug: string,
     *     translation_key: string,
     *     period: string,
     *     start_year: int,
     *     start_month: int,
     *     end_year: int|null,
     *     end_month: int|null,
     *     technologies: list<string>
     * }>
     */
    private const array EXPERIENCES = [
        'contraste-digital' => [
            'slug' => 'contraste-digital',
            'translation_key' => 'contraste_digital',
            'period' => '2019 — 2025',
            'start_year' => 2019,
            'start_month' => 11,
            'end_year' => 2025,
            'end_month' => 10,
            'technologies' => ['PHP', 'Drupal', 'Go', 'Docker', 'Azure DevOps'],
        ],
        'blubird' => [
            'slug' => 'blubird',
            'translation_key' => 'blubird',
            'period' => '2018 — 2019',
            'start_year' => 2018,
            'start_month' => 4,
            'end_year' => 2019,
            'end_month' => 7,
            'technologies' => ['PHP', 'Symfony', 'Docker', 'GitLab', 'Jenkins'],
        ],
        'isobar' => [
            'slug' => 'isobar',
            'translation_key' => 'isobar',
            'period' => '2017 — 2018',
            'start_year' => 2017,
            'start_month' => 7,
            'end_year' => 2018,
            'end_month' => 2,
            'technologies' => ['PHP', 'Laravel', 'Drupal', 'WordPress', 'JavaScript'],
        ],
        'adneom' => [
            'slug' => 'adneom',
            'translation_key' => 'adneom',
            'period' => '2016 — 2017',
            'start_year' => 2016,
            'start_month' => 7,
            'end_year' => 2017,
            'end_month' => 7,
            'technologies' => ['PHP', 'Symfony', 'MariaDB', 'Solr', 'Docker'],
        ],
        'f2c' => [
            'slug' => 'f2c',
            'translation_key' => 'f2c',
            'period' => '2015',
            'start_year' => 2015,
            'start_month' => 1,
            'end_year' => 2015,
            'end_month' => 9,
            'technologies' => ['PHP', 'MySQL', 'MongoDB', 'PDFLib'],
        ],
        'sogesa' => [
            'slug' => 'sogesa',
            'translation_key' => 'sogesa',
            'period' => '2011 — 2014',
            'start_year' => 2011,
            'start_month' => 11,
            'end_year' => 2014,
            'end_month' => 12,
            'technologies' => ['PHP', 'Symfony 2', 'MySQL', 'jQuery', 'Bootstrap'],
        ],
        'cbmn' => [
            'slug' => 'cbmn',
            'translation_key' => 'cbmn',
            'period' => '2010',
            'start_year' => 2010,
            'start_month' => 2,
            'end_year' => 2010,
            'end_month' => 6,
            'technologies' => ['Perl', 'MySQL', 'Linux'],
        ],
    ];

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @return list<array{
     *     slug: string,
     *     translation_key: string,
     *     period: string,
     *     start_year: int,
     *     start_month: int,
     *     end_year: int|null,
     *     end_month: int|null,
     *     duration: string,
     *     technologies: list<string>
     * }>
     */
    public function getExperiences(?string $locale = null): array
    {
        $locale ??= $this->translator->getLocale();

        return array_values(array_map(
            fn (array $experienceConfig): array => $this->buildExperienceListItem($experienceConfig, $locale),
            self::EXPERIENCES,
        ));
    }

    /**
     * @return array{
     *     slug: string,
     *     translation_key: string,
     *     period: string,
     *     start_year: int,
     *     start_month: int,
     *     end_year: int|null,
     *     end_month: int|null,
     *     duration: string,
     *     technologies: list<string>,
     *     company: string,
     *     role: string,
     *     metaTitle: string,
     *     summary: string,
     *     context: list<string>,
     *     responsibilities: list<string>,
     *     takeaways: list<string>
     * }
     */
    public function getExperienceData(string $experience, string $locale): array
    {
        if (!isset(self::EXPERIENCES[$experience])) {
            throw new NotFoundHttpException(sprintf('Experience "%s" was not found.', $experience));
        }

        $experienceConfig = self::EXPERIENCES[$experience];
        $translationPrefix = sprintf('experiences.items.%s', $experienceConfig['translation_key']);

        return [
            ...$this->buildExperienceListItem($experienceConfig, $locale),
            'period' => $this->trans($translationPrefix . '.period', $locale),
            'company' => $this->trans($translationPrefix . '.company', $locale),
            'role' => $this->trans($translationPrefix . '.role', $locale),
            'metaTitle' => $this->trans($translationPrefix . '.meta_title', $locale),
            'summary' => $this->trans($translationPrefix . '.summary', $locale),
            'context' => $this->transList($translationPrefix . '.context', $locale),
            'responsibilities' => $this->transList($translationPrefix . '.responsibilities', $locale),
            'takeaways' => $this->transList($translationPrefix . '.takeaways', $locale),
        ];
    }

    /**
     * @return array{
     *     slug: string,
     *     period: string,
     *     company: string,
     *     role: string,
     *     duration: string
     * }
     */
    public function getExperienceSummary(string $experience, string $locale): array
    {
        if (!isset(self::EXPERIENCES[$experience])) {
            throw new NotFoundHttpException(sprintf('Experience "%s" was not found.', $experience));
        }

        $experienceConfig = self::EXPERIENCES[$experience];
        $translationPrefix = sprintf('experiences.items.%s', $experienceConfig['translation_key']);

        return [
            'slug' => $experienceConfig['slug'],
            'period' => $experienceConfig['period'],
            'company' => $this->trans($translationPrefix . '.company', $locale),
            'role' => $this->trans($translationPrefix . '.role', $locale),
            'duration' => $this->formatDuration(
                $experienceConfig['start_year'],
                $experienceConfig['start_month'],
                $experienceConfig['end_year'],
                $experienceConfig['end_month'],
                $locale,
            ),
        ];
    }

    private function trans(string $id, string $locale): string
    {
        return $this->translator->trans($id, domain: 'experiences', locale: $locale);
    }

    /**
     * @param array{
     *     slug: string,
     *     translation_key: string,
     *     period: string,
     *     start_year: int,
     *     start_month: int,
     *     end_year: int|null,
     *     end_month: int|null,
     *     technologies: list<string>
     * } $experienceConfig
     *
     * @return array{
     *     slug: string,
     *     translation_key: string,
     *     period: string,
     *     start_year: int,
     *     start_month: int,
     *     end_year: int|null,
     *     end_month: int|null,
     *     technologies: list<string>,
     *     duration: string
     * }
     */
    private function buildExperienceListItem(array $experienceConfig, string $locale): array
    {
        return [
            ...$experienceConfig,
            'duration' => $this->formatDuration(
                $experienceConfig['start_year'],
                $experienceConfig['start_month'],
                $experienceConfig['end_year'],
                $experienceConfig['end_month'],
                $locale,
            ),
        ];
    }

    private function formatDuration(
        int $startYear,
        int $startMonth,
        ?int $endYear,
        ?int $endMonth,
        string $locale,
    ): string {
        $endYear ??= $startYear;
        $endMonth ??= $startMonth;

        $totalMonths = (($endYear - $startYear) * 12) + ($endMonth - $startMonth) + 1;

        if ($totalMonths < 1) {
            $totalMonths = 1;
        }

        $years = intdiv($totalMonths, 12);
        $months = $totalMonths % 12;
        $parts = [];

        if ($years > 0) {
            $parts[] = $years . ' ' . $this->translateDurationUnit('year', $years, $locale);
        }

        if ($months > 0) {
            $parts[] = $months . ' ' . $this->translateDurationUnit('month', $months, $locale);
        }

        return implode(' ', $parts);
    }

    private function translateDurationUnit(string $unit, int $count, string $locale): string
    {
        $translationKey = sprintf(
            'experiences.index.duration.%s_%s',
            $unit,
            $count === 1 ? 'singular' : 'plural',
        );

        return $this->translator->trans($translationKey, domain: 'experiences', locale: $locale);
    }

    public function getPreviousExperience(string $experience): ?string
    {
        $currentIndex = array_search($experience, self::EXPERIENCE_ORDER, true);

        if ($currentIndex === false || $currentIndex === 0) {
            return null;
        }

        return self::EXPERIENCE_ORDER[$currentIndex - 1];
    }

    public function getNextExperience(string $experience): ?string
    {
        $currentIndex = array_search($experience, self::EXPERIENCE_ORDER, true);

        if ($currentIndex === false || $currentIndex === count(self::EXPERIENCE_ORDER) - 1) {
            return null;
        }

        return self::EXPERIENCE_ORDER[$currentIndex + 1];
    }

    /**
     * @return list<string>
     */
    private function transList(string $prefix, string $locale): array
    {
        $items = [];
        $index = 1;

        while (true) {
            $key = sprintf('%s.%d', $prefix, $index);
            $translated = $this->translator->trans($key, domain: 'experiences', locale: $locale);

            if ($translated === $key) {
                break;
            }

            $items[] = $translated;
            $index++;
        }

        return $items;
    }
}
