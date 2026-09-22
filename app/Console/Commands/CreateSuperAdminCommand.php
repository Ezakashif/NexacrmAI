<?php

namespace App\Console\Commands;

use App\Services\Setup\SuperAdminBootstrap;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class CreateSuperAdminCommand extends Command
{
    protected $signature = 'nexacrm:create-super-admin
                            {--name= : Display name (default: Super Admin)}
                            {--email= : Super Admin email}
                            {--password= : Super Admin password (prefer a prompt or SETUP_SUPERADMIN_PASSWORD)}';

    protected $description = 'Create the first platform Super Admin for a fresh NexaCRM AI install';

    public function handle(SuperAdminBootstrap $bootstrap): int
    {
        if ($bootstrap->hasExistingSuperAdmin()) {
            $this->error('A Super Admin already exists. Create additional operators from the Super Admin console.');

            return self::FAILURE;
        }

        try {
            $name = $this->resolveName($bootstrap);
            $email = $this->resolveEmail($bootstrap);
            $password = $this->resolvePassword($bootstrap);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        try {
            $user = $bootstrap->create($name, $email, $password, 'artisan');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Super Admin created: '.$user->email);
        $this->comment('Sign in at /login, then open /superadmin. Public registration stays disabled until you enable it in Super Admin → Settings.');

        return self::SUCCESS;
    }

    private function resolveName(SuperAdminBootstrap $bootstrap): string
    {
        $name = trim((string) $this->option('name'));
        if ($name !== '') {
            return $name;
        }

        $configured = $bootstrap->configuredName();

        if (! $this->shouldPrompt()) {
            return $configured;
        }

        return trim((string) $this->ask('Name', $configured)) ?: SuperAdminBootstrap::DEFAULT_NAME;
    }

    private function resolveEmail(SuperAdminBootstrap $bootstrap): string
    {
        $email = strtolower(trim((string) $this->option('email')));
        if ($email !== '') {
            return $email;
        }

        $configured = $bootstrap->configuredEmail();
        if ($configured !== null) {
            return $configured;
        }

        if (! $this->shouldPrompt()) {
            throw new RuntimeException(
                'Email is required. Pass --email or set SETUP_SUPERADMIN_EMAIL.'
            );
        }

        $email = strtolower(trim((string) $this->ask('Email')));
        if ($email === '') {
            throw new RuntimeException('Email is required.');
        }

        return $email;
    }

    private function resolvePassword(SuperAdminBootstrap $bootstrap): string
    {
        $password = (string) $this->option('password');
        if ($password !== '') {
            return $password;
        }

        $configured = $bootstrap->configuredPassword();
        if ($configured !== null) {
            return $configured;
        }

        if (! $this->shouldPrompt()) {
            throw new RuntimeException(
                'Password is required. Pass --password, set SETUP_SUPERADMIN_PASSWORD, or run this command interactively.'
            );
        }

        $password = (string) $this->secret('Password (min 10 characters, mixed case, and a symbol)');
        $confirm = (string) $this->secret('Confirm password');

        if ($password === '' || $confirm === '') {
            throw new RuntimeException('Password is required.');
        }

        if (! hash_equals($password, $confirm)) {
            throw new RuntimeException('Password confirmation does not match.');
        }

        return $password;
    }

    private function shouldPrompt(): bool
    {
        return $this->input->isInteractive() && ! app()->runningUnitTests();
    }
}
