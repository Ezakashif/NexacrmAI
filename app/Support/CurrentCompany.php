<?php

namespace App\Support;

use App\Models\Company;
use InvalidArgumentException;

/**
 * Request/process-level tenant context.
 *
 * Phase 1B: building block only — middleware (1C) will set this per request.
 * When unset, BelongsToCompany global scopes are intentionally a no-op so the
 * existing single-company CRM keeps working until tenant resolution is wired.
 */
class CurrentCompany
{
    private ?int $id = null;

    private ?Company $company = null;

    public function id(): ?int
    {
        return $this->id;
    }

    public function get(): ?Company
    {
        if ($this->id === null) {
            return null;
        }

        if ($this->company?->id === $this->id) {
            return $this->company;
        }

        $this->company = Company::query()->find($this->id);

        return $this->company;
    }

    public function check(): bool
    {
        return $this->id !== null;
    }

    public function require(): int
    {
        if ($this->id === null) {
            throw new InvalidArgumentException('No current company is set.');
        }

        return $this->id;
    }

    public function set(Company|int|null $company): void
    {
        if ($company === null) {
            $this->clear();

            return;
        }

        if ($company instanceof Company) {
            $this->id = (int) $company->id;
            $this->company = $company;

            return;
        }

        $this->id = $company;
        $this->company = null;
    }

    public function clear(): void
    {
        $this->id = null;
        $this->company = null;
    }
}
