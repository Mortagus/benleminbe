<?php

declare(strict_types=1);

namespace App\Tests\Functional\Private;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class WorkoutPrototypeWebTest extends WebTestCase
{
    public function testPrototypeRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/private/workout/prototype');

        self::assertResponseRedirects('/private/login');
    }

    public function testAuthenticatedUserCanOpenWorkoutPrototype(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/private/login');
        $form = $crawler->filter('form.private-form')->selectButton('Se connecter')->form([
            '_username' => 'private_admin',
            '_password' => 'private-dev-password',
        ]);

        $client->submit($form);
        $client->request('GET', '/private/workout/prototype');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Musculation A');
        self::assertSelectorCount(3, '[data-workout-exercise-id]');
        self::assertSelectorExists('[data-workout-save-status]');
    }
}
