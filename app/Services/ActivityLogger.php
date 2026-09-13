<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use App\Support\CurrentCompany;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public static function log(
        string $action,
        ?Model $subject = null,
        array $properties = [],
        ?int $userId = null,
    ): ActivityLog {
        $actorId = $userId ?? auth()->id();
        $actor = self::resolveActor($actorId);
        $isPlatformActor = $actor?->isSuperAdmin() === true;

        $relatedCompanyId = self::resolveRelatedCompanyId($subject, $properties);

        if ($isPlatformActor) {
            // Super Admin actions belong on the platform audit trail only.
            // Keep related company metadata in properties for Super Admin UI.
            if ($relatedCompanyId !== null) {
                $properties['company_id'] ??= $relatedCompanyId;

                if (! isset($properties['company_name'])) {
                    $properties['company_name'] = Company::query()
                        ->whereKey($relatedCompanyId)
                        ->value('name');
                }
            }

            $companyId = null;
        } else {
            $companyId = $relatedCompanyId;
        }

        $log = new ActivityLog([
            'user_id' => $actorId,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => request()->ip(),
        ]);

        // Always set company_id (including explicit null) so BelongsToCompany
        // does not fall back to the default tenant for platform events.
        $log->company_id = $companyId;
        $log->save();

        return $log;
    }

    private static function resolveActor(?int $actorId): ?User
    {
        if ($actorId === null) {
            return null;
        }

        return User::withoutCompanyScope()->find($actorId);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private static function resolveRelatedCompanyId(?Model $subject, array $properties): ?int
    {
        if ($subject instanceof Company) {
            return $subject->id;
        }

        if ($subject !== null && $subject->getAttribute('company_id') !== null) {
            return (int) $subject->getAttribute('company_id');
        }

        $fromProperties = $properties['company_id'] ?? null;

        if ($fromProperties !== null) {
            return (int) $fromProperties;
        }

        return app(CurrentCompany::class)->id();
    }
}
