<?php

namespace App\Tests\Functional;

use App\Entity\Loan;
use App\Tests\DatabaseTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Parcours utilisateur de bout en bout, à travers le navigateur de test de Symfony. */
final class AppTest extends WebTestCase
{
    use DatabaseTrait;

    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->resetDatabase($this->em);
    }

    #[DataProvider('pages')]
    public function testLesPagesSAffichent(string $url): void
    {
        $this->client->request('GET', $url);

        self::assertResponseIsSuccessful();
    }

    public static function pages(): iterable
    {
        foreach (['/', '/objets', '/amis', '/prets', '/prets/nouveau', '/objets/nouveau', '/amis/nouveau'] as $url) {
            yield $url => [$url];
        }
    }

    public function testAjouterUnAmiPuisLeRetrouverDansLaListe(): void
    {
        $this->client->request('GET', '/amis/nouveau');
        $this->client->submitForm('Enregistrer', ['friend[name]' => 'Léa', 'friend[email]' => 'pas-un-mail']);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.field-errors', 'Adresse e-mail invalide.');

        $this->client->submitForm('Enregistrer', ['friend[name]' => 'Léa', 'friend[email]' => 'lea@example.com']);
        self::assertResponseRedirects('/amis');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.friend-card h3', 'Léa');
    }

    public function testMarquerUnPretCommeRenduDepuisLAccueil(): void
    {
        $loan = (new Loan())->setItem($this->createItem($this->em, 'Tente'))->setFriend($this->createFriend($this->em));
        $this->em->persist($loan);
        $this->em->flush();

        $this->client->request('GET', '/');
        self::assertSelectorTextContains('.loan-card h3', 'Tente');

        $this->client->submitForm('Rendu');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.toast-success', '« Tente » est revenu de chez Julien.');
        self::assertSelectorNotExists('.loan-card');
    }
}
