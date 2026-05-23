<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TextHighlightExtension extends AbstractExtension
{
    /**
     * @return list<TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('highlight_keywords', $this->highlightKeywords(...), ['is_safe' => ['html']]),
            new TwigFilter('strip_keyword_highlights', $this->stripKeywordHighlights(...)),
        ];
    }

    public function highlightKeywords(string $text): string
    {
        $parts = preg_split('/(\*\*[^*]+\*\*)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        if ($parts === false) {
            return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $html = '';

        foreach ($parts as $part) {
            if (str_starts_with($part, '**') && str_ends_with($part, '**')) {
                $html .= sprintf(
                    '<strong class="text-highlight">%s</strong>',
                    htmlspecialchars(substr($part, 2, -2), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                );

                continue;
            }

            $html .= htmlspecialchars($part, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return $html;
    }

    public function stripKeywordHighlights(string $text): string
    {
        return str_replace('**', '', $text);
    }
}
