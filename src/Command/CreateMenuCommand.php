<?php

namespace App\Command;

use App\Entity\Menu;
use App\Repository\BurgerRepository;
use App\Repository\ComplementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

#[AsCommand(
    name: 'app:create-menu',
    description: 'Crée un nouveau menu dans la base de données',
)]
class CreateMenuCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private BurgerRepository $burgerRepository;
    private ComplementRepository $complementRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        BurgerRepository $burgerRepository,
        ComplementRepository $complementRepository
    ) {
        $this->entityManager = $entityManager;
        $this->burgerRepository = $burgerRepository;
        $this->complementRepository = $complementRepository;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'Nom du menu')
            ->addArgument('description', InputArgument::OPTIONAL, 'Description du menu')
            ->addArgument('image', InputArgument::OPTIONAL, 'URL de l\'image du menu')
            ->addOption('burgers', 'b', InputOption::VALUE_OPTIONAL, 'IDs des burgers (séparés par des virgules)')
            ->addOption('complements', 'c', InputOption::VALUE_OPTIONAL, 'IDs des compléments (séparés par des virgules)')
            ->addOption('reduction', 'r', InputOption::VALUE_OPTIONAL, 'Pourcentage de réduction (défaut: 10%)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');
        
        // Récupérer ou demander le nom
        $name = $input->getArgument('name');
        if (!$name) {
            $question = new Question('Nom du menu : ');
            $question->setValidator(function ($answer) {
                if (empty($answer)) {
                    throw new \RuntimeException('Le nom ne peut pas être vide');
                }
                return $answer;
            });
            $name = $helper->ask($input, $output, $question);
        }
        
        // Récupérer ou demander la description
        $description = $input->getArgument('description');
        if (!$description) {
            $question = new Question('Description du menu (optionnel, appuyez sur Entrée pour ignorer) : ', null);
            $description = $helper->ask($input, $output, $question);
        }
        
        // Récupérer ou demander l'image
        $image = $input->getArgument('image');
        if (!$image) {
            $question = new Question('URL de l\'image du menu (optionnel, appuyez sur Entrée pour ignorer) : ', null);
            $image = $helper->ask($input, $output, $question);
        }
        
        // Récupérer ou demander les burgers
        $burgerOption = $input->getOption('burgers');
        $burgerIds = [];
        
        if ($burgerOption) {
            $burgerIds = explode(',', $burgerOption);
        } else {
            // Récupérer la liste des burgers disponibles
            $allBurgers = $this->burgerRepository->findBy(['archived' => false]);
            
            if (empty($allBurgers)) {
                $io->warning('Aucun burger disponible. Veuillez d\'abord créer des burgers avec la commande app:create-burger.');
            } else {
                $burgerChoices = [];
                foreach ($allBurgers as $burger) {
                    $burgerChoices[$burger->getId()] = sprintf('%s (%.2f €) - %s', 
                        $burger->getName(), 
                        $burger->getPrice(),
                        $burger->getDescription() ?? 'Pas de description'
                    );
                }
                
                $io->title('Sélection des burgers');
                $stopSelecting = false;
                
                while (!$stopSelecting && !empty($burgerChoices)) {
                    $burgerQuestion = new ChoiceQuestion(
                        'Sélectionnez un burger à ajouter (ou appuyez sur Entrée pour terminer la sélection) :',
                        array_merge(['0' => 'Terminer la sélection'], $burgerChoices),
                        '0'
                    );
                    $selectedBurgerId = $helper->ask($input, $output, $burgerQuestion);
                    
                    if ($selectedBurgerId === '0') {
                        $stopSelecting = true;
                    } else {
                        $burgerIds[] = $selectedBurgerId;
                        unset($burgerChoices[$selectedBurgerId]); // Retirer le burger sélectionné de la liste
                        $io->note(sprintf('Burger ajouté au menu. Total: %d burger(s)', count($burgerIds)));
                        
                        if (empty($burgerChoices)) {
                            $io->note('Tous les burgers disponibles ont été ajoutés.');
                            $stopSelecting = true;
                        }
                    }
                }
            }
        }
        
        // Récupérer ou demander les compléments
        $complementOption = $input->getOption('complements');
        $complementIds = [];
        
        if ($complementOption) {
            $complementIds = explode(',', $complementOption);
        } else {
            // Récupérer la liste des compléments disponibles
            $allComplements = $this->complementRepository->findBy(['archived' => false]);
            
            if (empty($allComplements)) {
                $io->warning('Aucun complément disponible. Veuillez d\'abord créer des compléments avec la commande app:create-complement.');
            } else {
                $complementChoices = [];
                foreach ($allComplements as $complement) {
                    $complementChoices[$complement->getId()] = sprintf('%s (%s) - %.2f € - %s', 
                        $complement->getName(),
                        $complement->getType(),
                        $complement->getPrice(),
                        $complement->getDescription() ?? 'Pas de description'
                    );
                }
                
                $io->title('Sélection des compléments');
                $stopSelecting = false;
                
                while (!$stopSelecting && !empty($complementChoices)) {
                    $complementQuestion = new ChoiceQuestion(
                        'Sélectionnez un complément à ajouter (ou appuyez sur Entrée pour terminer la sélection) :',
                        array_merge(['0' => 'Terminer la sélection'], $complementChoices),
                        '0'
                    );
                    $selectedComplementId = $helper->ask($input, $output, $complementQuestion);
                    
                    if ($selectedComplementId === '0') {
                        $stopSelecting = true;
                    } else {
                        $complementIds[] = $selectedComplementId;
                        unset($complementChoices[$selectedComplementId]); // Retirer le complément sélectionné de la liste
                        $io->note(sprintf('Complément ajouté au menu. Total: %d complément(s)', count($complementIds)));
                        
                        if (empty($complementChoices)) {
                            $io->note('Tous les compléments disponibles ont été ajoutés.');
                            $stopSelecting = true;
                        }
                    }
                }
            }
        }
        
        // Récupérer ou demander le pourcentage de réduction
        $reductionOption = $input->getOption('reduction');
        $reduction = 0.1; // Par défaut 10%
        
        if ($reductionOption !== null) {
            $reduction = (float) $reductionOption / 100;
        } else {
            $question = new Question('Pourcentage de réduction (défaut: 10) : ', 10);
            $question->setValidator(function ($answer) {
                if (!is_numeric($answer) || (float)$answer < 0 || (float)$answer > 100) {
                    throw new \RuntimeException('Le pourcentage doit être un nombre entre 0 et 100');
                }
                return $answer;
            });
            $reductionPercent = $helper->ask($input, $output, $question);
            $reduction = (float) $reductionPercent / 100;
        }

        $menu = new Menu();
        $menu->setName($name);
        $menu->setArchived(false);
        
        if ($description) {
            $menu->setDescription($description);
        }
        
        if ($image) {
            $menu->setImage($image);
        }

        // Ajouter les burgers
        $totalPrice = 0;
        foreach ($burgerIds as $burgerId) {
            $burger = $this->burgerRepository->find($burgerId);
            if (!$burger) {
                $io->error(sprintf('Burger avec l\'ID %d non trouvé', $burgerId));
                return Command::FAILURE;
            }
            $menu->addBurger($burger);
            $totalPrice += $burger->getPrice();
            $io->text(sprintf('Ajout du burger "%s" (%.2f €)', $burger->getName(), $burger->getPrice()));
        }

        // Ajouter les compléments
        foreach ($complementIds as $complementId) {
            $complement = $this->complementRepository->find($complementId);
            if (!$complement) {
                $io->error(sprintf('Complément avec l\'ID %d non trouvé', $complementId));
                return Command::FAILURE;
            }
            $menu->addComplement($complement);
            $totalPrice += $complement->getPrice();
            $io->text(sprintf('Ajout du complément "%s" (%.2f €)', $complement->getName(), $complement->getPrice()));
        }

        // Vérifier qu'il y a au moins un burger ou un complément
        if (count($burgerIds) === 0 && count($complementIds) === 0) {
            $io->error('Un menu doit contenir au moins un burger ou un complément');
            return Command::FAILURE;
        }

        // Calculer le prix avec réduction
        $finalPrice = $totalPrice * (1 - $reduction);
        $menu->setPrice($finalPrice);

        try {
            $this->entityManager->persist($menu);
            $this->entityManager->flush();

            $io->success([
                sprintf('Menu "%s" créé avec succès avec l\'ID: %d', $name, $menu->getId()),
                sprintf('Prix total avant réduction: %.2f €', $totalPrice),
                sprintf('Réduction appliquée: %.0f%%', $reduction * 100),
                sprintf('Prix final: %.2f €', $finalPrice)
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Erreur lors de la création du menu : %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}