<?php

namespace App\Support;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CrmValidation
{
    /**
     * Tables that soft-delete and must exclude trashed rows from exists checks.
     *
     * @var list<string>
     */
    private const SOFT_DELETE_TABLES = [
        'leads',
        'customers',
        'tasks',
    ];

    /**
     * @return \Illuminate\Validation\Rules\Exists
     */
    public static function existsInCompany(string $table, string $column, ?int $companyId)
    {
        if ($companyId === null) {
            return Rule::exists($table, $column)->where(fn ($query) => $query->whereRaw('0 = 1'));
        }

        return Rule::exists($table, $column)->where(function ($query) use ($table, $companyId) {
            $query->where('company_id', $companyId);

            if (in_array($table, self::SOFT_DELETE_TABLES, true)) {
                $query->whereNull('deleted_at');
            }
        });
    }

    /**
     * @return \Illuminate\Validation\Rules\Unique
     */
    public static function uniqueInCompany(string $table, string $column, ?int $companyId, mixed $ignore = null)
    {
        if ($companyId === null) {
            $rule = Rule::unique($table, $column)->where(fn ($query) => $query->whereRaw('0 = 1'));
        } else {
            $rule = Rule::unique($table, $column)->where(function ($query) use ($table, $companyId) {
                $query->where('company_id', $companyId);

                if (in_array($table, self::SOFT_DELETE_TABLES, true)) {
                    $query->whereNull('deleted_at');
                }
            });
        }

        if ($ignore !== null) {
            $rule->ignore($ignore);
        }

        return $rule;
    }

    /**
     * Validation rules for creating a lead (manual create + CSV import).
     *
     * @return array<string, mixed>
     */
    public static function leadStoreRules(User $user, bool $forImport = false): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'source' => 'nullable|in:'.implode(',', Lead::SOURCES),
            'estimated_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'follow_up_date' => 'nullable|date',
        ];

        if ($user->canAssignLeads()) {
            $rules['assigned_to'] = $forImport
                ? ['nullable', 'email', 'max:255', self::existsInCompany('users', 'email', $user->company_id)]
                : ['nullable', self::existsInCompany('users', 'id', $user->company_id)];
        }

        return $rules;
    }

    /**
     * Validation rules for creating a customer (manual create + CSV import).
     *
     * @return array<string, mixed>
     */
    public static function customerStoreRules(bool $forImport = false): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Validation rules for updating a customer.
     *
     * @return array<string, mixed>
     */
    public static function customerUpdateRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Validation rules for creating a user (manual create + CSV import).
     *
     * @return array<string, mixed>
     */
    public static function userStoreRules(bool $forImport = false): array
    {
        $companyId = auth()->user()?->company_id;

        if ($forImport) {
            return [
                'name' => 'required|string|max:255',
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
                'password' => ['required', 'string', Password::defaults()],
                'roles' => ['required', 'string', 'max:255'],
                'status' => ['required', Rule::in(array_keys(User::STATUSES))],
            ];
        }

        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'email_credentials' => ['sometimes', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [
                'integer',
                self::existsInCompany('roles', 'id', $companyId),
            ],
            'status' => ['required', Rule::in(array_keys(User::STATUSES))],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ];
    }
}
