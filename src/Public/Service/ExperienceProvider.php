<?php

declare(strict_types=1);

namespace App\Public\Service;

final class ExperienceProvider {
    /**
     * @return array<int, array{
     *     slug: string,
     *     translation_key: string,
     *     period: string,
     *     start_year: int,
     *     end_year: int|null,
     *     technologies: list<string>
     * }>
     */
    public function getExperiences(): array {
        return [
            [
                'slug' => 'contraste-digital',
                'translation_key' => 'contraste_digital',
                'period' => '2019 — 2025',
                'start_year' => 2019,
                'end_year' => 2025,
                'technologies' => ['PHP', 'Drupal', 'Go', 'Docker', 'Azure DevOps'],
            ],
            [
                'slug' => 'blubird',
                'translation_key' => 'blubird',
                'period' => '2018 — 2019',
                'start_year' => 2018,
                'end_year' => 2019,
                'technologies' => ['PHP', 'Symfony', 'Docker', 'GitLab', 'Jenkins'],
            ],
            [
                'slug' => 'isobar',
                'translation_key' => 'isobar',
                'period' => '2017 — 2018',
                'start_year' => 2017,
                'end_year' => 2018,
                'technologies' => ['PHP', 'Laravel', 'Drupal', 'WordPress', 'JavaScript'],
            ],
            [
                'slug' => 'adneom',
                'translation_key' => 'adneom',
                'period' => '2016 — 2017',
                'start_year' => 2016,
                'end_year' => 2017,
                'technologies' => ['PHP', 'Symfony', 'MariaDB', 'Solr', 'Docker'],
            ],
            [
                'slug' => 'f2c',
                'translation_key' => 'f2c',
                'period' => '2015',
                'start_year' => 2015,
                'end_year' => 2015,
                'technologies' => ['PHP', 'MySQL', 'MongoDB', 'PDFLib'],
            ],
            [
                'slug' => 'sogesa',
                'translation_key' => 'sogesa',
                'period' => '2011 — 2014',
                'start_year' => 2011,
                'end_year' => 2014,
                'technologies' => ['PHP', 'Symfony 2', 'MySQL', 'jQuery', 'Bootstrap'],
            ],
            [
                'slug' => 'cbmn',
                'translation_key' => 'cbmn',
                'period' => '2010',
                'start_year' => 2010,
                'end_year' => 2010,
                'technologies' => ['Perl', 'MySQL', 'Linux'],
            ],
        ];
    }
}
