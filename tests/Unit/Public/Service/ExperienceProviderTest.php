<?php

declare(strict_types=1);

namespace App\Tests\Unit\Public\Service;

use App\Public\Service\ExperienceProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ExperienceProviderTest extends TestCase
{
    #[DataProvider('provideDurationExpectations')]
    public function testItBuildsExperienceDurations(string $locale, array $expectedDurations): void
    {
        $provider = new ExperienceProvider($this->createTranslator($locale));

        $experiences = $provider->getExperiences($locale);

        self::assertSame($expectedDurations, array_column($experiences, 'duration', 'slug'));
    }

    public function testItExposesDurationInExperienceSummary(): void
    {
        $provider = new ExperienceProvider($this->createTranslator('en'));

        $summary = $provider->getExperienceSummary('blubird', 'en');

        self::assertSame('1 year 4 months', $summary['duration']);
    }

    public static function provideDurationExpectations(): iterable
    {
        yield 'fr' => [
            'fr',
            [
                'contraste-digital' => '6 ans',
                'blubird' => '1 an 4 mois',
                'isobar' => '8 mois',
                'adneom' => '1 an 1 mois',
                'f2c' => '9 mois',
                'sogesa' => '3 ans 2 mois',
                'cbmn' => '5 mois',
            ],
        ];

        yield 'en' => [
            'en',
            [
                'contraste-digital' => '6 years',
                'blubird' => '1 year 4 months',
                'isobar' => '8 months',
                'adneom' => '1 year 1 month',
                'f2c' => '9 months',
                'sogesa' => '3 years 2 months',
                'cbmn' => '5 months',
            ],
        ];
    }

    private function createTranslator(string $locale): TranslatorInterface
    {
        return new class ($locale) implements TranslatorInterface {
            public function __construct(private readonly string $locale)
            {
            }

            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                $locale ??= $this->locale;

                return match ([$locale, $id]) {
                    ['fr', 'experiences.index.duration.year_singular'] => 'an',
                    ['fr', 'experiences.index.duration.year_plural'] => 'ans',
                    ['fr', 'experiences.index.duration.month_singular'] => 'mois',
                    ['fr', 'experiences.index.duration.month_plural'] => 'mois',
                    ['en', 'experiences.index.duration.year_singular'] => 'year',
                    ['en', 'experiences.index.duration.year_plural'] => 'years',
                    ['en', 'experiences.index.duration.month_singular'] => 'month',
                    ['en', 'experiences.index.duration.month_plural'] => 'months',
                    default => $id,
                };
            }

            public function getLocale(): string
            {
                return $this->locale;
            }
        };
    }
}
