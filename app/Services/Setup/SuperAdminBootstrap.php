<?php

namespace App\Services\Setup;

use App\Models\Scopes\CompanyScope;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SuperAdminBootstrap
{
    public const DEFAULT_NAME = 'Super Admin';

    public function configuredName(): string
    {
        $name = trim((string) config('setup.super_admin.name', self::DEFAULT_NAME));

        return $name !== '' ? $name : self::DEFAULT_NAME;
    }

    public function configuredEmail(): ?string
    {
        $email = strtolower(trim((string) config('setup.super_admin.email', '')));

        return $email !== '' ? $email : null;
    }

    public function configuredPassword(): ?string
    {
        $password = (string) config('setup.super_admin.password', '');

        return $password !== '' ? $password : null;
    }

    public function hasConfiguredCredentials(): bool
    {
        return $this->configuredEmail() !== null && $this->configuredPassword() !== null;
    }

    public function hasPartialConfiguredCredentials(): bool
    {
        $hasEmail = $this->configuredEmail() !== null;
        $hasPassword = $this->configuredPassword() !== null;

        return $hasEmail !== $hasPassword;
    }

    public function hasExistingSuperAdmin(): bool
    {
        return User::withoutGlobalScope(CompanyScope::class)
            ->where('is_super_admin', true)
            ->exists();
    }

    /**
     * Create the first platform Super Admin.
     *
     * @throws ValidationException
     * @throws RuntimeException
     */
    public function create(?string $name, string $email, string $password, string $source = 'first_run'): User
    {
        $name = trim((string) $name);
        if ($name === '') {
            $name = self::DEFAULT_NAME;
        }

        $email = strtolower(trim($email));

        Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', Password::defaults()],
            ],
        )->validate();

        if ($this->hasExistingSuperAdmin()) {
            throw new RuntimeException(
                'A Super Admin already exists. Create additional operators from the Super Admin console.'
            );
        }

        $existing = User::withoutGlobalScope(CompanyScope::class)
            ->where('email', $email)
            ->first();

        if ($existing !== null) {
            throw new RuntimeException("The email {$email} is already in use.");
        }

        $user = new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
            'is_super_admin' => true,
        ]);
        $user->company_id = null;
        $user->save();

        ActivityLogger::log('platform.super_admin_created', $user, [
            'name' => $user->name,
            'email' => $user->email,
            'source' => $source,
        ], $user->id);

        return $user;
    }
}
