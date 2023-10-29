<?php

declare(strict_types=1);

namespace Devscast\Bundle\HexaBundle\Infrastructure\Twig;

use Parsedown;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * class MarkdownExtension.
 *
 * @author bernard-ng <bernard@devscast.tech>
 */
class TwigMarkdownExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('excerpt', [$this, 'excerpt']),
            new TwigFilter('markdown', [$this, 'markdown'], [
                'is_safe' => ['html'],
            ]),
            new TwigFilter('markdown_excerpt', [$this, 'markdownExcerpt'], [
                'is_safe' => ['html'],
            ]),
            new TwigFilter('markdown_untrusted', [$this, 'markdownUntrusted'], [
                'is_safe' => ['html',
                ],
            ]),
        ];
    }

    /**
     * Renvoie un extrait d'un texte.
     */
    public function excerpt(?string $content, int $characterLimit = 135): string
    {
        if ($content === null) {
            return '';
        }
        if (mb_strlen($content) <= $characterLimit) {
            return $content;
        }
        $lastSpace = strpos($content, ' ', $characterLimit);
        if ($lastSpace === false) {
            return $content;
        }

        return sprintf('%s...', substr($content, 0, $lastSpace));
    }

    /**
     * Convertit le contenu markdown en HTML.
     */
    public function markdown(?string $content): string
    {
        if ($content === null) {
            return '';
        }
        $content = (new Parsedown())->setBreaksEnabled(true)->setSafeMode(false)->text($content);
        // On remplace les liens youtube par un embed
        $content = (string) preg_replace(
            '/<p><a href\="(http|https):\/\/www.youtube.com\/watch\?v=([^\""]+)">[^<]*<\/a><\/p>/',
            '<iframe width="560" height="315" src="//www.youtube-nocookie.com/embed/$2" frameborder="0" allowfullscreen=""></iframe>',
            (string) $content
        );
        // Spoiler tag
        $content = (string) preg_replace(
            '/<p>!!<\/p>/',
            '<spoiler-box>',
            (string) $content
        );
        $content = (string) preg_replace(
            '/<p>\/!!<\/p>/',
            '</spoiler-box>',
            (string) $content
        );
        // On ajoute des liens sur les nombres représentant un timestamp "00:01"
        return preg_replace_callback('/((\d{2}:){1,2}\d{2}) ([^<]*)/', function ($matches) {
            $times = array_reverse(explode(':', $matches[1]));
            $title = $matches[3];
            $timecode = (int) ($times[2] ?? 0) * 60 * 60 + (int) $times[1] * 60 + (int) $times[0];

            return "<a href=\"#t{$timecode}\">{$matches[1]}</a> {$title}";
        }, $content) ?: $content;
    }

    public function markdownExcerpt(?string $content, int $characterLimit = 135): string
    {
        return $this->excerpt(strip_tags($this->markdown($content)), $characterLimit);
    }

    public function markdownUntrusted(?string $content): string
    {
        $content = strip_tags((new Parsedown())
            ->setSafeMode(true)
            ->setBreaksEnabled(true)
            ->text($content), '<p><pre><code><ul><ol><li><h4><h3><h5><a><strong><br><em>');

        $content = str_replace('<a href="http', '<a target="_blank" rel="noreferrer nofollow" href="http', $content);

        return str_replace('<a href="//', '<a target="_blank" rel="noreferrer nofollow" href="http', $content);
    }
}
