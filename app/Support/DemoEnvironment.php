<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use RuntimeException;

/**
 * Server-side identity for the public Northstar live-demo tenant.
 *
 * Never trust client-supplied company_id / user_id / role_id.
 */
class DemoEnvironment
{
    public static function companySlug(): string
    {
        return (string) config('demo.company_slug', DemoDataSeeder::COMPANY_SLUG);
    }

    public static function companyName(): string
    {
        return (string) config('demo.company_name', DemoDataSeeder::COMPANY_NAME);
    }

    /**
     * @return array<string, array{email: string, label: string, description: string}>
     */
    public static function personas(): array
    {
        return (array) config('demo.personas', []);
    }

    /**
     * @return list<string>
     */
    public static function seededEmails(): array
    {
        return collect(self::personas())->pluck('email')->filter()->values()->all();
    }

    public static function emailForPersona(string $persona): ?string
    {
        $persona = strtolower(trim($persona));

        return self::personas()[$persona]['email'] ?? null;
    }

    public static function isAllowedPersona(string $persona): bool
    {
        return self::emailForPersona($persona) !== null;
    }

    public static function isDemoCompany(?Company $company): bool
    {
        if ($company === null || $company->slug === null) {
            return false;
        }

        return $company->slug === self::companySlug()
            && $company->slug !== Company::DEFAULT_SLUG
            && $company->slug === DemoDataSeeder::COMPANY_SLUG;
    }

    public static function isDemoUser(?User $user): bool
    {
        if ($user === null || $user->company_id === null) {
            return false;
        }

        $company = $user->relationLoaded('company')
            ? $user->company
            : Company::query()->find($user->company_id);

        return self::isDemoCompany($company);
    }

    public static function isSeededDemoUser(?User $user): bool
    {
        if ($user === null || ! filled($user->email)) {
            return false;
        }

        return in_array($user->email, self::seededEmails(), true) && self::isDemoUser($user);
    }

    public static function isProtectedDemoEmail(?string $email): bool
    {
        if (! filled($email)) {
            return false;
        }

        return in_array(strtolower(trim($email)), array_map('strtolower', self::seededEmails()), true);
    }

    public static function requireSeedPassword(): string
    {
        $password = config('demo.seed_password');

        if (! is_string($password) || trim($password) === '') {
            throw new RuntimeException(
                'DEMO_SEED_PASSWORD is not configured. Set it in the environment before seeding or resetting the demo tenant.'
            );
        }

        return $password;
    }

    /**
     * Verify the resolved company is exactly the Northstar demo tenant.
     *
     * @throws RuntimeException
     */
    public static function assertResettableCompany(?Company $company): Company
    {
        if ($company === null) {
            throw new RuntimeException('Demo company was not found. Aborting without deleting anything.');
        }

        if ($company->slug !== DemoDataSeeder::COMPANY_SLUG) {
            throw new RuntimeException('Resolved company slug does not match the demo tenant. Aborting.');
        }

        if ($company->slug !== self::companySlug()) {
            throw new RuntimeException('Demo company slug configuration mismatch. Aborting.');
        }

        if ($company->slug === Company::DEFAULT_SLUG) {
            throw new RuntimeException('Refusing to reset the default company.');
        }

        if ($company->id === Company::default()?->id) {
            throw new RuntimeException('Refusing to reset the default company.');
        }

        return $company;
    }
}
