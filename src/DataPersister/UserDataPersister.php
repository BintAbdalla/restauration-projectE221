<?php
// src/DataPersister/UserDataPersister.php

namespace App\DataPersister;

use ApiPlatform\Doctrine\Common\Context\ItemNormalizerContextBuilderInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

class UserDataPersister implements ProcessorInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $em
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof User) {
            if ($data->getPassword()) {
                $data->setPassword($this->passwordHasher->hashPassword($data, $data->getPassword()));
            }
            $this->em->persist($data);
            $this->em->flush();
        }

        return $data;
    }
}
