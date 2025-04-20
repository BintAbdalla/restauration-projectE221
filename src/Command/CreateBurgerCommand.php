<?php

namespace App\Command;

use App\Entity\Burger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'app:create-burger',
    description: 'Crée un nouveau burger dans la base de données',
)]
class CreateBurgerCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'Nom du burger')
            ->addArgument('price', InputArgument::OPTIONAL, 'Prix du burger')
            ->addArgument('description', InputArgument::OPTIONAL, 'Description du burger')
            ->addArgument('image', InputArgument::OPTIONAL, 'URL de l\'image du burger');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');
        
        // Récupérer ou demander le nom
        $name = $input->getArgument('name');
        if (!$name) {
            $question = new Question('Nom du burger : ');
            $question->setValidator(function ($answer) {
                if (empty($answer)) {
                    throw new \RuntimeException('Le nom ne peut pas être vide');
                }
                return $answer;
            });
            $name = $helper->ask($input, $output, $question);
        }
        
        // Récupérer ou demander le prix
        $price = $input->getArgument('price');
        if (!$price) {
            $question = new Question('Prix du burger (ex: 8.90) : ');
            $question->setValidator(function ($answer) {
                if (!is_numeric($answer) || (float)$answer <= 0) {
                    throw new \RuntimeException('Le prix doit être un nombre positif');
                }
                return $answer;
            });
            $price = $helper->ask($input, $output, $question);
        }
        $price = (float) $price;
        
        // Récupérer ou demander la description
        $description = $input->getArgument('description');
        if (!$description) {
            $question = new Question('Description du burger (optionnel, appuyez sur Entrée pour ignorer) : ', null);
            $description = $helper->ask($input, $output, $question);
        }
        
        // Récupérer ou demander l'image
        $image = $input->getArgument('image');
        if (!$image) {
            $question = new Question('URL de l\'image du burger (optionnel, appuyez sur Entrée pour ignorer) : ', null);
            $image = $helper->ask($input, $output, $question);
        }

        $burger = new Burger();
        $burger->setName($name);
        $burger->setPrice($price);
        $burger->setArchived(false);
        
        if ($description) {
            $burger->setDescription($description);
        }
        
        if ($image) {
            $burger->setImage($image);
        }

        try {
            $this->entityManager->persist($burger);
            $this->entityManager->flush();
            $io->success(sprintf('Burger "%s" créé avec succès avec l\'ID: %d', $name, $burger->getId()));
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Erreur lors de la création du burger : %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}