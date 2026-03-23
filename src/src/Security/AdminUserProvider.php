<?php

declare(strict_types=1);

namespace App\Security;

use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<AdminUser>
 */
final class AdminUserProvider implements UserProviderInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function loadUserByIdentifier(string $identifier): AdminUser
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, username, password, roles FROM admin_users WHERE username = :username',
            ['username' => $identifier],
        );

        if (false === $row) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        /** @var list<string> $roles */
        $roles = json_decode((string) $row['roles'], true) ?? ['ROLE_ADMIN'];

        $username = (string) $row['username'];
        \assert('' !== $username);

        return new AdminUser(
            (int) $row['id'],
            $username,
            (string) $row['password'],
            $roles,
        );
    }

    public function refreshUser(UserInterface $user): AdminUser
    {
        if (!$user instanceof AdminUser) {
            throw new UnsupportedUserException(sprintf('Unsupported user class "%s".', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return AdminUser::class === $class;
    }
}
