<?php

namespace App\Services;

use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Models\TenantSlugHistory;
use App\Models\TenantUser as CentralTenantUser;
use App\Models\User;
use App\Models\CompanyCategory;
use App\Traits\Loggable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Models\Audit;

class TenantService
{
    use Loggable;
    
    public static function store(array $input)
    {
        // Ambil user yang sedang login
        $user = Auth::user();

        // tambahkan pengecekan apakah user login sudah mempunyai tenant yang di create
        // if (Tenant::where('owner_id', $user->id)->exists()) {
        //     return [
        //         'status' => 'warning',
        //         'message' => __('You already have a portal'),
        //     ];
        // }

        // Cek apakah tenant dengan name dan code sudah ada
        $tenantExists = Tenant::where('name', $input['name'])
            ->when(!empty($input['slug']), function ($query) use ($input) {
                return $query->orWhere('slug', $input['slug']);
            })
            ->first();

        if ($tenantExists) {
            // Kembalikan warning jika portal sudah ada
            return [
                'status' => 'warning',
                'message' => __('Portal already exists. Use a different name'),
            ];
        }

        try {
            // Create tenant, akan langsung menjalankan event SyncOAuthDataToTenant
            $tenant = new Tenant();
            $tenant->name = $input['name'];
            $tenant->code = $input['code'];
            $tenant->slug = $input['slug'] ?? $tenant->generateSlug($input['name']);
            $tenant->theme_color = '#336AFF';
            $tenant->owner_id = $user->id;
            $tenant->save();

            // Create domain untuk tenant
            $tenant->domains()->create([
                'domain' => $input['code'],
            ]);

            return [
                'status' => 'success',
                'message' => __('Portal created successfully'),
                'portal' => $tenant,  // return tenant
            ];
        // @codeCoverageIgnoreStart
        } catch (\Throwable $th) {
            // Log error jika terjadi exception
            static::logError('Failed to create portal', [
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'message' => $th->getMessage(),
                'error' => $th->getTrace(),
            ]);

            // Lempar error jika terjadi masalah
            return [
                'status' => 'error',
                'message' => __('Failed to create portal'),
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ];
        }
        // @codeCoverageIgnoreEnd
    }

    public static function updateSlug(string $slug, string $id)
    {
        $tenant = Tenant::findOrFail($id);

        if ($slug === $tenant->slug) {
            return [
                'status' => 'success',
                'message' => __('Portal slug berhasil diperbarui.'),
                'portal' => TenantResource::make($tenant),
            ];
        }

        $authUser = Auth::user();
        if (!$authUser || !$authUser instanceof User) {
            return [
                'status' => 'forbidden',
                'message' => __('Only portal owner is allowed to perform this action.'),
            ];
        }

        $isOwner = (string) $tenant->owner_id === (string) $authUser->id;
        if (!$isOwner && !empty($authUser->global_id)) {
            $centralTenantUser = CentralTenantUser::query()
                ->where('tenant_id', $tenant->id)
                ->where('global_user_id', $authUser->global_id)
                ->first();
            $isOwner = (bool) ($centralTenantUser?->is_owner ?? false);
        }

        if (!$isOwner) {
            return [
                'status' => 'forbidden',
                'message' => __('Only portal owner is allowed to perform this action.'),
            ];
        }

        $lastChangedAt = $tenant->slug_changed_at;
        $cooldownDays = (int) config('custom.portal_slug_change_cooldown_days', 30);
        $cooldownDays = $cooldownDays < 0 ? 0 : $cooldownDays;

        if ($lastChangedAt && $cooldownDays > 0) {
            $nextAllowedAt = Carbon::parse($lastChangedAt)->addDays($cooldownDays);
            if (now()->lessThan($nextAllowedAt)) {
                return [
                    'status' => 'warning',
                    'message' => __('Portal slug hanya dapat diubah setiap :days hari. Anda dapat mengubah kembali pada :date.', [
                        'days' => $cooldownDays,
                        'date' => $nextAllowedAt->format('Y-m-d'),
                    ]),
                    'next_slug_change_at' => $nextAllowedAt->toISOString(),
                ];
            }
        }

        DB::beginTransaction();

        try {
            TenantSlugHistory::firstOrCreate([
                'slug' => $tenant->slug,
            ], [
                'tenant_id' => $tenant->id,
            ]);

            $oldValues = [
                'slug' => $tenant->slug,
            ];

            $tenant->update([
                'slug' => $slug,
                'slug_changed_at' => now(),
            ]);

            $tenant->refresh();

            static::createPortalAuditLog($tenant, $oldValues, [
                'slug' => $tenant->slug,
            ]);

            DB::commit();

            return [
                'status' => 'success',
                'message' => __('Portal slug berhasil diperbarui.'),
                'portal' => TenantResource::make($tenant),
            ];
        } catch (\Throwable $th) {
            DB::rollBack();

            static::logError('Failed to update portal slug', [
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'message' => $th->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => __('Failed to update portal slug'),
                'error' => $th->getMessage(),
            ];
        }
    }

    private static function createPortalAuditLog(Tenant $tenant, array $oldValues, array $newValues): void
    {
        if (empty($newValues)) {
            return;
        }

        try {
            Log::info('Creating portal settings audit log', [
                'tenant_id' => $tenant->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ]);

            if (function_exists('tenancy')) {
                tenancy()->initialize($tenant);
            }

            Audit::create([
                'user_type' => Auth::user() ? get_class(Auth::user()) : null,
                'user_id' => Auth::id(),
                'auditable_type' => 'App\\Models\\Tenant\\PortalSetting',
                'auditable_id' => 1,
                'event' => 'updated',
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'url' => request()->fullUrl(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'tags' => 'portal_settings',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Portal settings audit log created successfully', [
                'tenant_id' => $tenant->id,
            ]);
            // @codeCoverageIgnoreStart
        } catch (\Throwable $th) {
            static::logError('Failed to create portal settings audit log', [
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'message' => $th->getMessage(),
            ]);
        } finally {
            if (function_exists('tenancy')) {
                try {
                    tenancy()->end();
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            // @codeCoverageIgnoreEnd
        }
    }

    public static function update(array $validatedData, string $id)
    {
        $tenant = Tenant::findOrFail($id);

        $trackedFields = [
            'name',
            'code',
            'enable_slug_history_redirect',
            'theme_color',
            'header_image',
            'profile_image',
            'company_values',
            'employee_range_start',
            'employee_range_end',
            'company_category_id',
            'linkedin',
            'instagram',
            'website',
        ];

        $oldValues = [];
        foreach ($trackedFields as $field) {
            $oldValues[$field] = $tenant->getAttribute($field);
        }

        DB::beginTransaction();

        try {
            // @codeCoverageIgnoreStart
            // Upload header_image jika ada
            if (isset($validatedData['header_image'])) {
                $headerImagePath = $validatedData['header_image']->store("portal/{$tenant->id}/images", 'public');
                $validatedData['header_image'] = $headerImagePath;
            } else {
                $validatedData['header_image'] = $tenant->header_image; // fallback ke data lama
            }

            // Upload profile_image jika ada
            if (isset($validatedData['profile_image'])) {
                $profileImagePath = $validatedData['profile_image']->store("portal/{$tenant->id}/images", 'public');
                $validatedData['profile_image'] = $profileImagePath;
            } else {
                $validatedData['profile_image'] = $tenant->profile_image;
            }
            // @codeCoverageIgnoreEnd

            $tenant->update([
                'name' => $validatedData['name'],
                'code' => $validatedData['code'],
                'enable_slug_history_redirect' => array_key_exists('enable_slug_history_redirect', $validatedData)
                    ? (bool) filter_var($validatedData['enable_slug_history_redirect'], FILTER_VALIDATE_BOOLEAN)
                    : (bool) ($tenant->enable_slug_history_redirect ?? false),
                'theme_color' => $validatedData['theme_color'] ?? $tenant->theme_color,
                'header_image' => $validatedData['header_image'],
                'profile_image' => $validatedData['profile_image'],
                'company_values' => $validatedData['company_values'] ?? $tenant->company_values,
                'employee_range_start' => $validatedData['employee_range_start'] ?? $tenant->employee_range_start,
                'employee_range_end' => $validatedData['employee_range_end'] ?? $tenant->employee_range_end,
                'company_category_id' => $validatedData['company_category_id'] ?? $tenant->company_category_id,
                'linkedin' => array_key_exists('linkedin', $validatedData) ? $validatedData['linkedin'] : $tenant->linkedin,
                'instagram' => array_key_exists('instagram', $validatedData) ? $validatedData['instagram'] : $tenant->instagram,
                'website' => array_key_exists('website', $validatedData) ? $validatedData['website'] : $tenant->website,
            ]);

            $tenant->refresh();

            $newValues = [];
            foreach ($trackedFields as $field) {
                $newValues[$field] = $tenant->getAttribute($field);
            }

            $changedOld = [];
            $changedNew = [];

            foreach ($trackedFields as $field) {
                $before = $oldValues[$field] ?? null;
                $after = $newValues[$field] ?? null;

                // @codeCoverageIgnoreStart
                // Array comparison untuk future-proofing jika ada field yang di-cast sebagai array
                if (is_array($before) || is_array($after)) {
                    $beforeJson = is_array($before) ? json_encode($before) : $before;
                    $afterJson = is_array($after) ? json_encode($after) : $after;

                    if ($beforeJson === $afterJson) {
                        continue;
                    }
                // @codeCoverageIgnoreEnd
                } elseif ($before === $after) {
                    continue;
                }

                $changedOld[$field] = $before;
                $changedNew[$field] = $after;
            }

            if (! empty($changedNew)) {
                // Format nilai khusus portal sebelum disimpan ke audit log
                $logOldValues = $changedOld;
                $logNewValues = $changedNew;

                // company_category_id: simpan nama kategori, bukan ID
                if (array_key_exists('company_category_id', $logOldValues) || array_key_exists('company_category_id', $logNewValues)) {
                    $oldId = $logOldValues['company_category_id'] ?? null;
                    $newId = $logNewValues['company_category_id'] ?? null;

                    if ($oldId !== null) {
                        $oldCategory = CompanyCategory::find($oldId);
                        $logOldValues['company_category_id'] = $oldCategory?->name ?? $oldId;
                    }

                    if ($newId !== null) {
                        $newCategory = CompanyCategory::find($newId);
                        $logNewValues['company_category_id'] = $newCategory?->name ?? $newId;
                    }
                }

                // company_values: simpan teks bersih tanpa tag HTML
                if (array_key_exists('company_values', $logOldValues)) {
                    $logOldValues['company_values'] = trim(strip_tags((string) $logOldValues['company_values']));
                }
                if (array_key_exists('company_values', $logNewValues)) {
                    $logNewValues['company_values'] = trim(strip_tags((string) $logNewValues['company_values']));
                }

                // header_image & profile_image: hanya nama file tanpa path
                foreach (['header_image', 'profile_image'] as $fileField) {
                    if (array_key_exists($fileField, $logOldValues) && is_string($logOldValues[$fileField]) && $logOldValues[$fileField] !== '') {
                        $logOldValues[$fileField] = basename($logOldValues[$fileField]);
                    }

                    if (array_key_exists($fileField, $logNewValues) && is_string($logNewValues[$fileField]) && $logNewValues[$fileField] !== '') {
                        $logNewValues[$fileField] = basename($logNewValues[$fileField]);
                    }
                }

                static::createPortalAuditLog($tenant, $logOldValues, $logNewValues);
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => __('Portal updated successfully'),
                'portal' => TenantResource::make($tenant),
            ];
        // @codeCoverageIgnoreStart
        } catch (\Throwable $th) {
            DB::rollBack();

            static::logError('Failed to update portal', [
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'message' => $th->getMessage(),
                'error' => $th->getTrace(),
            ]);

            // Return error array (konsisten dengan store method)
            return [
                'status' => 'error',
                'message' => __('Failed to update portal'),
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ];
        }
        // @codeCoverageIgnoreEnd
    }

    public static function destroy(string $id)
    {
        $tenant = Tenant::findOrFail($id);

        try {
            // @codeCoverageIgnoreStart
            // Hapus gambar terkait (header_image dan profile_image) jika ada
            if ($tenant->header_image) {
                Storage::disk('public')->delete($tenant->header_image);
            }

            if ($tenant->profile_image) {
                Storage::disk('public')->delete($tenant->profile_image);
            }
            // @codeCoverageIgnoreEnd

            // Hapus tenant
            $tenant->delete();

            return [
                'status' => 'success',
                'message' => __('Portal deleted successfully'),
            ];
        // @codeCoverageIgnoreStart
        } catch (\Throwable $th) {
            // Log error jika terjadi kesalahan
            static::logError('Failed to delete portal', [
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'message' => $th->getMessage(),
                'error' => $th->getTrace(),
            ]);

            // Return error array (konsisten dengan store method)
            return [
                'status' => 'error',
                'message' => __('Failed to delete portal'),
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ];
        }
        // @codeCoverageIgnoreEnd
    }
}
