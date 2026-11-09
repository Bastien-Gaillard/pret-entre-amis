<?php

namespace App\Command;

use App\Entity\Friend;
use App\Entity\Item;
use App\Entity\Loan;
use App\Enum\ItemCategory;
use App\Repository\FriendRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:demo:load', description: 'Remplit la base avec des données de démonstration')]
final class LoadDemoDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FriendRepository $friends,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('if-empty', null, InputOption::VALUE_NONE, 'Ne fait rien si la base contient déjà des données')
            ->addOption('purge', null, InputOption::VALUE_NONE, 'Vide la base avant de charger les données');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $hasData = $this->friends->count() > 0;

        if ($hasData && $input->getOption('if-empty')) {
            $io->note('La base contient déjà des données, rien à faire.');

            return Command::SUCCESS;
        }
        if ($hasData && !$input->getOption('purge')) {
            $io->error('La base contient déjà des données. Utilise --purge pour la vider d\'abord.');

            return Command::FAILURE;
        }
        if ($input->getOption('purge')) {
            $connection = $this->em->getConnection();
            foreach (['loans', 'items', 'friends'] as $table) {
                $connection->executeStatement("DELETE FROM $table");
            }
        }

        $friends = [];
        foreach ([
            ['Camille', 'camille@example.com', '06 12 34 56 78'],
            ['Julien Martin', 'julien@example.com', null],
            ['Sarah', null, '07 98 76 54 32'],
            ['Thomas', 'thomas@example.com', null],
            ['Inès', 'ines@example.com', null],
        ] as [$name, $email, $phone]) {
            $friends[$name] = (new Friend($name))->setEmail($email)->setPhone($phone);
            $this->em->persist($friends[$name]);
        }

        $items = [];
        foreach ([
            ['Perceuse visseuse Bosch', ItemCategory::Bricolage, 'Avec la mallette et 2 batteries'],
            ['Le Seigneur des Anneaux (T1)', ItemCategory::Livre, null],
            ['Tente 3 places', ItemCategory::Camping, 'Vérifier les sardines au retour'],
            ['Catan', ItemCategory::Jeu, 'Extension Marins incluse'],
            ['Escabeau 5 marches', ItemCategory::Bricolage, null],
            ['Dune', ItemCategory::Livre, 'Édition poche'],
            ['Appareil à raclette', ItemCategory::Cuisine, '8 poêlons'],
            ['Taille-haie', ItemCategory::Jardin, null],
            ['Crash pad', ItemCategory::Sport, 'Pour la grimpe en bloc'],
            ['Enceinte Bluetooth', ItemCategory::Multimedia, null],
        ] as [$name, $category, $notes]) {
            $items[$name] = (new Item($name, $category))->setNotes($notes);
            $this->em->persist($items[$name]);
        }

        $day = static fn (int $offset) => new \DateTimeImmutable("today $offset days");
        foreach ([
            // [objet, ami, prêté il y a, à rendre dans, rendu il y a, notes]
            ['Perceuse visseuse Bosch', 'Julien Martin', -30, -9, null, 'Pour monter sa cuisine'],
            ['Tente 3 places', 'Camille', -20, -2, null, null],
            ['Le Seigneur des Anneaux (T1)', 'Sarah', -5, 25, null, null],
            ['Catan', 'Thomas', -2, null, null, 'Pas de date fixée'],
            ['Crash pad', 'Inès', -3, 4, null, null],
            ['Escabeau 5 marches', 'Julien Martin', -60, -50, -52, null],
            ['Appareil à raclette', 'Camille', -45, -40, -38, null],
            ['Dune', 'Julien Martin', -90, -60, -55, null],
            ['Taille-haie', 'Inès', -120, -100, -101, null],
        ] as [$item, $friend, $loaned, $due, $returned, $notes]) {
            $loan = (new Loan())
                ->setItem($items[$item])
                ->setFriend($friends[$friend])
                ->setLoanedAt($day($loaned))
                ->setDueAt($due !== null ? $day($due) : null)
                ->setNotes($notes);
            if ($returned !== null) {
                $loan->markReturned($day($returned));
            }
            $this->em->persist($loan);
        }

        $this->em->flush();
        $io->success(sprintf('%d amis, %d objets et 9 prêts créés.', count($friends), count($items)));

        return Command::SUCCESS;
    }
}
