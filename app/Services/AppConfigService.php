<?php

declare(strict_types=1);

namespace App\Services;

class AppConfigService
{
    public function getSlugCooldownConfig(): array
    {
        $portalDays = (int) config('custom.portal_slug_change_cooldown_days', 30);
        $jobDays = (int) config('custom.job_vacancy_slug_change_cooldown_days', 30);

        $portalDays = $portalDays < 0 ? 0 : $portalDays;
        $jobDays = $jobDays < 0 ? 0 : $jobDays;

        return [
            'portal_slug_change_cooldown_days' => $portalDays,
            'job_vacancy_slug_change_cooldown_days' => $jobDays,
        ];
    }
}
