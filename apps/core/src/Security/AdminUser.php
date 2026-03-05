<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @param non-empty-string $username
     * @param list<string>     $roles
     */
    public function __construct(
        private readonly int $id,
        private readonly string $username,
        private readonly string $password,
        private readonly array $roles = ['ROLE_ADMIN'],
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    /** @return non-empty-string */
    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }
}
