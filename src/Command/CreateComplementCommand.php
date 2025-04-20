<?php

namespace App\Command;

use App\Entity\Complement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

#[AsCommand(
    name: 'app:create-complement',
    description: 'Crée un nouveau complément dans la base de données',
)]
class CreateComplementCommand extends Command
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
            ->addArgument('name', InputArgument::OPTIONAL, 'Nom du complément')
            ->addArgument('price', InputArgument::OPTIONAL, 'Prix du complément')
            ->addArgument('type', InputArgument::OPTIONAL, 'Type du complément (DRINK, SIDE ou DESSERT)')
            ->addArgument('description', InputArgument::OPTIONAL, 'Description du complément')
            ->addArgument('image', InputArgument::OPTIONAL, 'URL de l\'image du complément');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');
        
        // Récupérer ou demander le nom
        $name = $input->getArgument('name');
        if (!$name) {
            $question = new Question('Nom du complément : ');
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
            $question = new Question('Prix du complément (ex: 3.50) : ');
            $question->setValidator(function ($answer) {
                if (!is_numeric($answer) || (float)$answer <= 0) {
                    throw new \RuntimeException('Le prix doit être un nombre positif');
                }
                return $answer;
            });
            $price = $helper->ask($input, $output, $question);
        }
        $price = (float) $price;
        
        // Récupérer ou demander le type
        $type = $input->getArgument('type');
        $validTypes = ['DRINK', 'SIDE', 'DESSERT'];
        if (!$type) {
            $question = new ChoiceQuestion(
                'Type du complément :',
                $validTypes,
                0
            );
            $question->setErrorMessage('Type %s invalide.');
            $type = $helper->ask($input, $output, $question);
        } else {
            $type = strtoupper($type);
        }
        
        // Vérifier que le type est valide
        if (!in_array($type, $validTypes)) {
            $io->error(sprintf('Type invalide "%s". Les types valides sont: %s', $type, implode(', ', $validTypes)));
            return Command::FAILURE;
        }
        
        // Récupérer ou demander la description
        $description = $input->getArgument('description');
        if (!$description) {
            $question = new Question('Description du complément (optionnel, appuyez sur Entrée pour ignorer) : ', null);
            $description = $helper->ask($input, $output, $question);
        }
        
        // Récupérer ou demander l'image
        $image = $input->getArgument('image');
        if (!$image) {
            $question = new Question('URL de l\'image du complément (optionnel, appuyez sur Entrée pour ignorer) : ', null);
            $image = $helper->ask($input, $output, $question);
        }

        $complement = new Complement();
        $complement->setName($name);
        $complement->setPrice($price);
        $complement->setType($type);
        $complement->setArchived(false);
        
        if ($description) {
            $complement->setDescription($description);
        }
        
        if ($image) {
            $complement->setImage($image);
        }

        try {
            $this->entityManager->persist($complement);
            $this->entityManager->flush();
            $io->success(sprintf('Complément "%s" créé avec succès avec l\'ID: %d', $name, $complement->getId()));
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Erreur lors de la création du complément : %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}