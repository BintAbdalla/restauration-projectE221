<?php
// src/Command/CreateUserCommand.php
namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-user')]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setNom('Admin');
        $user->setPrenom('Test');
        $user->setTelephone('0123456789');
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password');
        $user->setPassword($hashedPassword);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $io->success('User created successfully.');
        
        return Command::SUCCESS;
    }
}