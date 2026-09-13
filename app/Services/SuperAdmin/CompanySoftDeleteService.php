<?php

namespace App\Services\SuperAdmin;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanySoftDeleteService
{
    /**
     * Soft-delete a company and free unique slug / user emails so a new tenant
     * can reuse the same contact details. Values are restored on restore().
     */
    public function softDelete(Company $company): void
    {
        DB::transaction(function () use ($company) {
            $this->archiveSlug($company);
            $this->archiveUsers($company);
            $company->delete();
        });
    }

    public function restore(Company $company): void
    {
        DB::transaction(function () use ($company) {
            $company->restore();
            $this->restoreSlug($company);
            $this->restoreUsers($company);
        });
    }

    /**
     * Repair already soft-deleted companies that still occupy unique emails/slugs.
     */
    public function releaseIdentifiersForTrashedCompanies(): int
    {
        $count = 0;

        Company::onlyTrashed()->orderBy('id')->each(function (Company $company) use (&$count) {
            $changed = false;

            if (! $this->isArchivedSlug($company->slug, $company->id)) {
                $this->archiveSlug($company);
                $changed = true;
            }

            if ($this->archiveUsers($company) > 0) {
                $changed = true;
            }

            if ($changed) {
                $count++;
            }
        });

        return $count;
    }

    private function archiveSlug(Company $company): void
    {
        if ($this->isArchivedSlug($company->slug, $company->id)) {
            return;
        }

        $archived = $this->archivedSlug($company->slug, $company->id);
        $candidate = $archived;
        $i = 1;

        while (
            Company::withTrashed()
                ->where('slug', $candidate)
                ->whereKeyNot($company->id)
                ->exists()
        ) {
            $candidate = $archived.'-'.$i;
            $i++;
        }

        $company->forceFill(['slug' => $candidate])->saveQuietly();
    }

    private function restoreSlug(Company $company): void
    {
        $restored = $this->unarchivedSlug($company->slug, $company->id);

        if ($restored === $company->slug) {
            return;
        }

        $candidate = $restored;
        $i = 1;

        while (
            Company::withTrashed()
                ->where('slug', $candidate)
                ->whereKeyNot($company->id)
                ->exists()
        ) {
            $candidate = $restored.'-'.$i;
            $i++;
        }

        $company->forceFill(['slug' => $candidate])->saveQuietly();
    }

    private function archiveUsers(Company $company): int
    {
        $archived = 0;

        User::withoutCompanyScope()
            ->where('company_id', $company->id)
            ->where('is_super_admin', false)
            ->orderBy('id')
            ->each(function (User $user) use (&$archived) {
                $email = (string) $user->email;

                if ($this->isArchivedEmail($email, $user->id)) {
                    if ($user->status !== 'inactive') {
                        $user->forceFill(['status' => 'inactive'])->saveQuietly();
                        $archived++;
                    }

                    return;
                }

                $user->forceFill([
                    'email' => $this->archivedEmail($email, $user->id),
                    'status' => 'inactive',
                    'remember_token' => null,
                ])->saveQuietly();

                $archived++;
            });

        return $archived;
    }

    private function restoreUsers(Company $company): void
    {
        User::withoutCompanyScope()
            ->where('company_id', $company->id)
            ->where('is_super_admin', false)
            ->orderBy('id')
            ->each(function (User $user) {
                $email = $this->unarchivedEmail((string) $user->email, $user->id);
                $payload = [
                    'status' => 'active',
                ];

                if ($email !== $user->email) {
                    // Avoid colliding with a live account that reused the address.
                    if (
                        User::withoutCompanyScope()
                            ->where('email', $email)
                            ->whereKeyNot($user->id)
                            ->exists()
                    ) {
                        $email = $this->archivedEmail($email, $user->id);
                        $payload['status'] = 'inactive';
                    }

                    $payload['email'] = $email;
                }

                $user->forceFill($payload)->saveQuietly();
            });
    }

    private function archivedSlug(string $slug, int $companyId): string
    {
        $base = Str::limit(trim($slug, '-'), 80, '');

        return ($base !== '' ? $base : 'company').'-deleted-'.$companyId;
    }

    private function isArchivedSlug(string $slug, int $companyId): bool
    {
        return str_contains($slug, '-deleted-'.$companyId);
    }

    private function unarchivedSlug(string $slug, int $companyId): string
    {
        $marker = '-deleted-'.$companyId;

        if (! str_contains($slug, $marker)) {
            return $slug;
        }

        $restored = Str::before($slug, $marker);

        return $restored !== '' ? $restored : 'company-'.$companyId;
    }

    private function archivedEmail(string $email, int $userId): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, 'invalid.local');
        $local = Str::limit($local, 40, '');

        return $local.'.deleted.'.$userId.'.'.Str::lower(Str::random(6)).'@'.$domain;
    }

    private function isArchivedEmail(string $email, int $userId): bool
    {
        return (bool) preg_match('/\.deleted\.'.$userId.'\./i', Str::before($email, '@'));
    }

    private function unarchivedEmail(string $email, int $userId): string
    {
        if (! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $pattern = '/^(.*)\.deleted\.'.$userId.'\.[a-z0-9]+$/i';

        if (preg_match($pattern, $local, $matches)) {
            return $matches[1].'@'.$domain;
        }

        return $email;
    }
}
