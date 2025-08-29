<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Placeholder user model for future authentication implementation.
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function getRoles(): array
    {
        return [];
    }

    public function getPassword(): ?string
    {
        return null;
    }

    public function getUserIdentifier(): string
    {
        return 'anon';
    }

    public function eraseCredentials(): void
    {
        // noop
    }
}
