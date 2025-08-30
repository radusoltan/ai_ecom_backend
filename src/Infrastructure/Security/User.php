<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        public string $id,
        private array $roles = []
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): ?string
    {
        return null;
    }

    public function getUserIdentifier(): string
    {
        return $this->id;
    }

    public function eraseCredentials(): void
    {
    }
}

