<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\User\PayloadAwareUserProviderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Dummy user provider for future JWT implementation.
 *
 * @implements UserProviderInterface<User>
 */
/**
 * @implements UserProviderInterface<User>
 */
class UserProvider implements UserProviderInterface, PayloadAwareUserProviderInterface
{
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return new User($identifier);
    }

    public function loadUserByIdentifierAndPayload(string $identifier, array $payload): UserInterface
    {
        $id = $payload['id'] ?? $identifier;
        $roles = $payload['roles'] ?? [];

        return new User((string) $id, $roles);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException('Unsupported user class');
        }

        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
