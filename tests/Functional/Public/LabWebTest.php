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

    public function testSimulationsPageLoadsTheBoidsEntrypoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lab/simulations');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Simulations');
        self::assertSelectorExists('[data-boids-simulation] [data-boids-canvas]');
        self::assertStringContainsString('lab_simulations_boids', $client->getResponse()->getContent());
    }
}
