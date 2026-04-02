<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class ProfileControllerTest extends DatabaseWebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
    }

    public function testProfileUpdateSetsHourlyDefaultsAndPassword(): void
    {
        $client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $user = (new User())
            ->setEmail('user@example.com')
            ->setPassword('old-hash')
            ->setRoles(['ROLE_USER'])
            ->setDefaultDailyRate('800.00')
            ->setDefaultDailyRateCurrency('CAD');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $client->loginUser($user);
        $crawler = $client->request('GET', '/profile');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Salvar alterações')->form([
            'profile[jobDescription]' => 'Senior Consultant',
            'profile[defaultRateValue]' => '120',
            'profile[defaultRateType]' => 'hourly_rate',
            'profile[defaultHourlyHoursPerBusinessDay]' => '8',
            'profile[defaultDailyRateCurrency]' => 'USD',
            'profile[newPassword][first]' => 'Nova@123',
            'profile[newPassword][second]' => 'Nova@123',
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/profile');
        $this->entityManager->clear();

        $updated = $this->entityManager->getRepository(User::class)->find($user->getId());
        self::assertInstanceOf(User::class, $updated);
        self::assertSame('Senior Consultant', $updated->getJobDescription());
        self::assertSame('120', $updated->getDefaultHourlyRate());
        self::assertNull($updated->getDefaultDailyRate());
        self::assertSame('8', $updated->getDefaultHourlyHoursPerBusinessDay());
        self::assertSame('USD', $updated->getDefaultDailyRateCurrency());
        self::assertNotSame('old-hash', $updated->getPassword());
    }

    public function testProfileUpdateSetsDailyDefaultAndClearsHourlyRate(): void
    {
        $client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $user = (new User())
            ->setEmail('daily@example.com')
            ->setPassword('old-hash')
            ->setRoles(['ROLE_USER'])
            ->setDefaultHourlyRate('100.00')
            ->setDefaultHourlyHoursPerBusinessDay('7.5')
            ->setDefaultDailyRateCurrency('CAD');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $client->loginUser($user);
        $crawler = $client->request('GET', '/profile');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Salvar alterações')->form([
            'profile[jobDescription]' => 'Daily Consultant',
            'profile[defaultRateValue]' => '950',
            'profile[defaultRateType]' => 'daily_rate',
            'profile[defaultHourlyHoursPerBusinessDay]' => '',
            'profile[defaultDailyRateCurrency]' => 'CAD',
            'profile[newPassword][first]' => '',
            'profile[newPassword][second]' => '',
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/profile');
        $this->entityManager->clear();

        $updated = $this->entityManager->getRepository(User::class)->find($user->getId());
        self::assertInstanceOf(User::class, $updated);
        self::assertSame('950', $updated->getDefaultDailyRate());
        self::assertNull($updated->getDefaultHourlyRate());
    }
}
