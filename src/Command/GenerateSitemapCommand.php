<?php /** @noinspection HttpUrlsUsage */

namespace App\Command;

use App\Service\ProjectProvider;
use DOMDocument;
use DOMElement;
use DOMException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(
    name: 'app:generate-sitemap',
    description: 'Génère le fichier public/sitemap.xml',
)]
class GenerateSitemapCommand extends Command
{
    private const string DEFAULT_LOCALE = 'fr';
    private const array LOCALES = ['fr', 'en'];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly RouterInterface $router,
        private readonly ProjectProvider $projectProvider,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        try {
            $urlset = $document->createElement('urlset');
            $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
            $urlset->setAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');

            $document->appendChild($urlset);

            $urls = $this->buildSitemapUrls();

            foreach ($urls as $url) {
                $urlset->appendChild($this->createUrlNode($document, $url));
            }
        } catch (DOMException $exception) {
            $io->error(sprintf(
                'Impossible de construire le document XML du sitemap : %s',
                $exception->getMessage()
            ));

            return Command::FAILURE;
        }

        $sitemapPath = $this->projectDir . '/public/sitemap.xml';

        if ($document->save($sitemapPath) === false) {
            $io->error(sprintf('Impossible de générer le fichier sitemap : %s', $sitemapPath));

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Le fichier sitemap.xml a été mis à jour avec %d URL(s) : %s',
            count($urls),
            $sitemapPath
        ));

        return Command::SUCCESS;
    }

    /**
     * @return array<int, array{
     *     loc: string,
     *     alternates: array<string, string>,
     *     lastmod?: string
     * }>
     */
    private function buildSitemapUrls(): array
    {
        $urls = [];

        foreach ($this->router->getRouteCollection() as $routeName => $route) {
            $sitemap = $route->getOption('sitemap');

            if (!is_array($sitemap) || ($sitemap['enabled'] ?? false) !== true) {
                continue;
            }

            $locales = $sitemap['locales'] ?? self::LOCALES;
            $lastmod = $sitemap['lastmod'] ?? null;

            if ($routeName === 'app_projects_show') {
                foreach ($this->projectProvider->getProjects() as $project) {
                    $urls = [
                        ...$urls,
                        ...$this->buildLocalizedUrls(
                            $routeName,
                            $locales,
                            ['project' => $project['key']],
                            is_string($lastmod) ? $lastmod : null,
                        ),
                    ];
                }

                continue;
            }

            $urls = [
                ...$urls,
                ...$this->buildLocalizedUrls(
                    $routeName,
                    $locales,
                    [],
                    is_string($lastmod) ? $lastmod : null,
                ),
            ];
        }

        return $urls;
    }

    /**
     * @param array<int, string> $locales
     * @param array<string, string> $parameters
     *
     * @return array<int, array{
     *     loc: string,
     *     alternates: array<string, string>,
     *     lastmod?: string
     * }>
     */
    private function buildLocalizedUrls(
        string $routeName,
        array $locales,
        array $parameters = [],
        ?string $lastmod = null,
    ): array {
        $urls = [];
        $alternates = [];

        foreach ($locales as $locale) {
            $alternates[$locale] = $this->router->generate(
                $routeName,
                [
                    ...$parameters,
                    '_locale' => $locale,
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        $alternates['x-default'] = $this->router->generate(
            $routeName,
            [
                ...$parameters,
                '_locale' => self::DEFAULT_LOCALE,
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        foreach ($locales as $locale) {
            $url = [
                'loc' => $alternates[$locale],
                'alternates' => $alternates,
            ];

            if (is_string($lastmod)) {
                $url['lastmod'] = $lastmod;
            }

            $urls[] = $url;
        }

        return $urls;
    }

    /**
     * @param DOMDocument $document
     * @param array{
     *      loc: string,
     *      alternates: array<string, string>,
     *      lastmod?: string
     *  } $url
     * @return DOMElement
     * @throws DOMException
     */
    private function createUrlNode(DOMDocument $document, array $url): DOMElement
    {
        $urlNode = $document->createElement('url');

        $urlNode->appendChild($document->createElement('loc', $url['loc']));

        if (isset($url['lastmod'])) {
            $urlNode->appendChild($document->createElement('lastmod', $url['lastmod']));
        }

        foreach ($url['alternates'] as $hreflang => $href) {
            $linkNode = $document->createElementNS(
                'http://www.w3.org/1999/xhtml',
                'xhtml:link'
            );

            $linkNode->setAttribute('rel', 'alternate');
            $linkNode->setAttribute('hreflang', $hreflang);
            $linkNode->setAttribute('href', $href);

            $urlNode->appendChild($linkNode);
        }

        return $urlNode;
    }
}
