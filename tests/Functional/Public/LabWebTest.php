<?php

declare(strict_types=1);

namespace App\Tests\Functional\Public;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LabWebTest extends WebTestCase
{
    public function testLabIndexPresentsTheSimulationsCard(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lab');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[href="/lab/simulations"]');
        self::assertSelectorTextContains('body', 'Voir les simulations');
    }

    public function testSimulationsIndexListsEachSimulationAndLoadsNoSimulationCode(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lab/simulations');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Simulations');
        self::assertSelectorExists('a[href="/lab/simulations/boids"]');
        self::assertSelectorExists('a[href="/lab/simulations/boids3d"]');
        // The index only presents the simulations: neither of them runs on it. (The name of every entrypoint appears in the
        // importmap of every page; what shows that a page runs a script is the import statement.)
        self::assertSelectorNotExists('[data-boids-simulation]');
        self::assertSelectorNotExists('[data-boids3d-simulation]');
        self::assertStringNotContainsString("import 'lab_simulations_boids", $client->getResponse()->getContent());
    }

    public function testBoidsPageLoadsTheBoidsEntrypointAndLinksBackToTheIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lab/simulations/boids');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Boids en 2D');
        self::assertSelectorExists('[data-boids-simulation] [data-boids-canvas]');
        self::assertSelectorCount(10, '[data-boids-parameter]');
        self::assertStringContainsString("import 'lab_simulations_boids';", $client->getResponse()->getContent());
        self::assertStringNotContainsString("import 'lab_simulations_boids3d'", $client->getResponse()->getContent());
        self::assertSelectorExists('main a[href="/lab/simulations"]');
    }

    public function testBoids3dPageLoadsItsOwnEntrypointAndItsControls(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lab/simulations/boids3d');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Boids en 3D');
        // The sky is a CSS background behind a transparent canvas, so the container must carry its class.
        self::assertSelectorExists('[data-boids3d-simulation] .boids-canvas--sunset[data-boids3d-canvas][data-canvas-label]');
        self::assertSelectorExists('[data-boids3d-toggle]');
        self::assertSelectorExists('[data-boids3d-reset]');
        self::assertStringContainsString("import 'lab_simulations_boids3d';", $client->getResponse()->getContent());
        self::assertStringNotContainsString("import 'lab_simulations_boids';", $client->getResponse()->getContent());
        self::assertSelectorExists('main a[href="/lab/simulations"]');
    }

    public function testBoids3dPageHasLiveSlidersForTheCountTheLeadersAndTheWave(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/lab/simulations/boids3d');

        self::assertResponseIsSuccessful();

        $names = $crawler->filter('input[data-boids3d-parameter]')->each(static fn ($node): string => $node->attr('data-boids3d-parameter'));

        self::assertSame(['count', 'pointerFraction', 'waveWeight'], $names);
        // The share of leaders is shown as a percentage but is a fraction for the simulation: its scale must be 0.01.
        self::assertSame('0.01', $crawler->filter('input[data-boids3d-parameter="pointerFraction"]')->attr('data-scale'));
        self::assertSame('100', $crawler->filter('input[data-boids3d-parameter="count"]')->attr('min'));
        self::assertSame('500', $crawler->filter('input[data-boids3d-parameter="count"]')->attr('max'));
        // Each slider shows its value in an output.
        self::assertSelectorCount(3, 'output[data-boids3d-output]');
    }

    public function testTheSimulationsMenuStaysActiveOnEachSimulationPage(): void
    {
        $client = static::createClient();

        foreach (['/lab/simulations', '/lab/simulations/boids', '/lab/simulations/boids3d'] as $url) {
            $client->request('GET', $url);

            self::assertResponseIsSuccessful();
            self::assertSelectorExists('header a[href="/lab/simulations"][aria-current="page"]', \sprintf('The menu entry is not active on %s.', $url));
        }
    }

    public function testTheAddressOfTheBoids3dPageIsLowerCase(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lab/simulations/boids3D');

        self::assertResponseStatusCodeSame(404);
    }
}
