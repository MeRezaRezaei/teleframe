<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Methods\Generated;

/**
 * mtproto users.* curated method builders.
 *
 * @generated 2026-08-28 by bin/generate-method-builders.php from config/curated-methods.json — do not edit by hand.
 */
final class Users
{
    public function getUsers(): UsersGetUsersBuilder
    {
        return new UsersGetUsersBuilder();
    }

    public function getFullUser(): UsersGetFullUserBuilder
    {
        return new UsersGetFullUserBuilder();
    }

    public function setSecureValueErrors(): UsersSetSecureValueErrorsBuilder
    {
        return new UsersSetSecureValueErrorsBuilder();
    }
}

/**
 * Fluent builder for users.getUsers (mtproto, return: Vector<User>).
 * Returns basic user info according to their identifiers.
 * Docs: https://core.telegram.org/method/users.getUsers
 *
 * @generated 2026-08-28 by bin/generate-method-builders.php — do not edit by hand.
 */
final class UsersGetUsersBuilder
{
    /** @var array<string, mixed> */
    private array $p = [];

    public function id(mixed $id): self
    {
        $this->p['id'] = $id;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toRequest(): array
    {
        $missing = [];
        foreach (['id'] as $required) {
            if (! array_key_exists($required, $this->p)) {
                $missing[] = $required;
            }
        }

        if ($missing !== []) {
            throw new \InvalidArgumentException('users.getUsers: missing ' . implode(', ', $missing));
        }

        return array_merge(['_' => 'users.getUsers'], $this->p);
    }
}

/**
 * Fluent builder for users.getFullUser (mtproto, return: users.UserFull).
 * Returns extended user info by ID.
 * Docs: https://core.telegram.org/method/users.getFullUser
 *
 * @generated 2026-08-28 by bin/generate-method-builders.php — do not edit by hand.
 */
final class UsersGetFullUserBuilder
{
    /** @var array<string, mixed> */
    private array $p = [];

    public function id(mixed $id): self
    {
        $this->p['id'] = $id;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toRequest(): array
    {
        $missing = [];
        foreach (['id'] as $required) {
            if (! array_key_exists($required, $this->p)) {
                $missing[] = $required;
            }
        }

        if ($missing !== []) {
            throw new \InvalidArgumentException('users.getFullUser: missing ' . implode(', ', $missing));
        }

        return array_merge(['_' => 'users.getFullUser'], $this->p);
    }
}

/**
 * Fluent builder for users.setSecureValueErrors (mtproto, return: Bool).
 * Notify the user that the sent [passport](https://core.telegram.org/passport) data contains some errors The user will not be able to re-submit their Passport data to you until the errors are fixed (the contents of the field for which you returned the error must change).  Use this if the data submitted by the user doesn't satisfy the standards your service requires for any reason. For example, if a birthday date seems invalid, a submitted document is blurry, a scan shows evidence of tampering, etc. Supply some details in the error message to make sure the user knows how to correct the issues.
 * Docs: https://core.telegram.org/method/users.setSecureValueErrors
 *
 * @generated 2026-08-28 by bin/generate-method-builders.php — do not edit by hand.
 */
final class UsersSetSecureValueErrorsBuilder
{
    /** @var array<string, mixed> */
    private array $p = [];

    public function id(mixed $id): self
    {
        $this->p['id'] = $id;

        return $this;
    }

    public function errors(mixed $errors): self
    {
        $this->p['errors'] = $errors;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toRequest(): array
    {
        $missing = [];
        foreach (['id', 'errors'] as $required) {
            if (! array_key_exists($required, $this->p)) {
                $missing[] = $required;
            }
        }

        if ($missing !== []) {
            throw new \InvalidArgumentException('users.setSecureValueErrors: missing ' . implode(', ', $missing));
        }

        return array_merge(['_' => 'users.setSecureValueErrors'], $this->p);
    }
}
