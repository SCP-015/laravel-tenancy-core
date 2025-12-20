<?php

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\EducationLevel;
use App\Models\Tenant\ExperienceLevel;
use App\Models\Tenant\JobLevel;
use App\Models\Tenant\JobPosition;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $rawOld = $this->old_values ?? [];
        $rawNew = $this->new_values ?? [];

        [$rawOld, $rawNew] = $this->normalizeLegacyRoleValues($rawOld, $rawNew);

        [$rawOld, $rawNew] = $this->filterUserLoginValues($rawOld, $rawNew);
        [$rawOld, $rawNew] = $this->filterJobApplicationValues($rawOld, $rawNew);
        [$rawOld, $rawNew] = $this->enrichCandidateCreatedValues($rawOld, $rawNew);

        $formattedOld = $this->formatValues($rawOld);
        $formattedNew = $this->formatValues($rawNew);

        if ($this->isCandidateFileAdd()) {
            $formattedOld = null;
        }

        // HOTFIX: Untuk event 'created' pada Interview, old_values harus null
        if ($this->event === 'created' && $this->getModelTypeName() === 'Interview') {
            $formattedOld = null;
        }

        return [
            'id' => $this->id,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : null,
            'model_type' => $this->getModelTypeName(),
            'model_type_label' => $this->getModelTypeLabel(),
            'model_id' => $this->auditable_id,
            'event' => $this->event,
            'event_label' => $this->getEventLabel(),
            'old_values' => $formattedOld,
            'new_values' => $formattedNew,
            'changes_summary' => $this->getChangesSummary(),
            'is_file_only_update' => $this->isCandidateFileAdd(),
            'url' => $this->url,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'tags' => $this->tags,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }

    /**
     * Filter field untuk User model saat event 'login'.
     * Hanya tampilkan field yang signifikan untuk login (role, name, email, last_login_*).
     *
     * @param  array  $rawOld
     * @param  array  $rawNew
     * @return array
     */
    private function filterUserLoginValues(array $rawOld, array $rawNew): array
    {
        if ($this->getModelTypeName() === 'User' && $this->event === 'login') {
            $allowedKeys = ['role', 'name', 'email', 'last_login_ip', 'last_login_at'];
            $allowedMap = array_flip($allowedKeys);

            $rawOld = array_intersect_key($rawOld, $allowedMap);
            $rawNew = array_intersect_key($rawNew, $allowedMap);
        }

        return [$rawOld, $rawNew];
    }

    private function normalizeLegacyRoleValues(array $rawOld, array $rawNew): array
    {
        $normalize = static function (array $values): array {
            if (($values['role'] ?? null) === 'recruiter') {
                $values['role'] = 'admin';
            }

            if (array_key_exists('is_new_recruiter', $values)) {
                if (!array_key_exists('is_new_admin', $values) && $values['is_new_recruiter'] === true) {
                    $values['is_new_admin'] = true;
                }
                unset($values['is_new_recruiter']);
            }

            return $values;
        };

        return [$normalize($rawOld), $normalize($rawNew)];
    }

    /**
     * Filter field untuk JobApplication agar hanya field penting yang ditampilkan.
     * recruiter_notes hanya disertakan pada event updated ketika memang berubah.
     *
     * @param  array  $rawOld
     * @param  array  $rawNew
     * @return array
     */
    private function filterJobApplicationValues(array $rawOld, array $rawNew): array
    {
        if ($this->getModelTypeName() !== 'JobApplication') {
            return [$rawOld, $rawNew];
        }

        return [$rawOld, $rawNew];
    }

    /**
     * Lengkapi nilai file path untuk Candidate ketika event created,
     * sehingga semua berkas awal tercatat di audit log.
     *
     * @param  array  $rawOld
     * @param  array  $rawNew
     * @return array
     */
    private function enrichCandidateCreatedValues(array $rawOld, array $rawNew): array
    {
        if ($this->getModelTypeName() !== 'Candidate' || $this->event !== 'created') {
            return [$rawOld, $rawNew];
        }

        return [$rawOld, $rawNew];
    }

    /**
     * Get model type name (short name without namespace)
     * 
     * @return string
     */
    private function getModelTypeName(): string
    {
        $parts = explode('\\', $this->auditable_type);
        return end($parts);
    }

    /**
     * Get Indonesian label untuk model type
     * 
     * @return string
     */
    private function getModelTypeLabel(): string
    {
        $modelType = $this->getModelTypeName();

        // Khusus JobVacancy: tampilkan nama posisi di label model
        if ('JobVacancy' === $modelType) {
            return $modelType;
        }

        // Khusus JobVacancyQuestion: pertanyaan kustom lowongan, sertakan nama lowongan
        if ('JobVacancyQuestion' === $modelType) {
            return $modelType;
        }

        // JobApplicationAnswer: tampilkan sebagai Pertanyaan Kustom Lowongan: {nama lowongan}
        if ('JobApplicationAnswer' === $modelType) {
            return $modelType;
        }

        // Khusus JobApplication:
        // - Event created  -> Lamaran: {nama kandidat}
        // - Event updated yang mengubah current_stage_id -> Tahap Lamaran: {nama kandidat}
        if ('JobApplication' === $modelType) {
            return $modelType;
        }

        // Khusus Interview: sertakan nama kandidat di label
        if ('Interview' === $modelType) {
            return $modelType;
        }

        if ('PortalSetting' === $modelType) {
            return 'Company';
        }

        $labels = [
            'User' => 'Pengguna',
            'JobPosition' => 'Variable: Posisi Pekerjaan',
            'JobLevel' => 'Variable: Tingkat Pekerjaan',
            'EducationLevel' => 'Variable: Edukasi',
            'ExperienceLevel' => 'Variable: Level Pengalaman',
        ];

        return $labels[$modelType] ?? $modelType;
    }

    /**
     * Get Indonesian label untuk event type
     * 
     * @return string
     */
    private function getEventLabel(): string
    {
        $labels = [
            'created' => 'Dibuat',
            'updated' => 'Diubah',
            'deleted' => 'Dihapus',
            'restored' => 'Dipulihkan',
            'login' => 'Login',
        ];

        return $labels[$this->event] ?? $this->event;
    }

    /**
     * Get human-readable summary of changes
     * 
     * @return string
     */
    private function getChangesSummary(): string
    {
        [, $normalizedNewValues] = $this->normalizeLegacyRoleValues($this->old_values ?? [], $this->new_values ?? []);

        // Khusus event login: bedakan antara login normal vs login recruiter baru
        if ($this->event === 'login') {
            $modelType = $this->getModelTypeName();
            
            // Jika model adalah User, format summary berdasarkan role dan sumber login
            if ($modelType === 'User') {
                $newValues = $normalizedNewValues;
                
                // Login admin baru ditandai dengan flag is_new_admin
                if (isset($newValues['is_new_admin']) && $newValues['is_new_admin'] === true) {
                    return 'Login admin baru';
                }
                
                // Login dengan role
                if (isset($newValues['role'])) {
                    $roleLabels = [
                        'super_admin' => 'Super Admin',
                        'admin' => 'Admin',
                    ];
                    
                    $roleLabel = $roleLabels[$newValues['role']] ?? ucfirst($newValues['role']);
                    
                    // Deteksi sumber login dari URL atau tags
                    $url = $this->url ?? '';
                    $tags = $this->tags ?? '';
                    
                    if (stripos($url, 'google') !== false || stripos($tags, 'google') !== false) {
                        return "Login via Google sebagai {$roleLabel}";
                    } elseif (stripos($url, 'nusawork') !== false || stripos($tags, 'nusawork') !== false) {
                        return "Login via Nusawork sebagai {$roleLabel}";
                    }
                    
                    // Default: tidak menyebutkan sumber login
                    return "Login sebagai {$roleLabel}";
                }
                
                // Fallback jika tidak ada role
                return 'Login ke Nusahire';
            }
            
            // Fallback jika model bukan User (edge case)
            return 'Login ke sistem';
        }

        if ($this->event === 'created') {
            $modelType = $this->getModelTypeName();

            // Summary spesifik per model untuk event created
            return match ($modelType) {
                'User' => 'Membuat pengguna baru',
                'JobPosition' => 'Membuat variable posisi pekerjaan',
                'JobLevel' => 'Membuat variable tingkat pekerjaan',
                'EducationLevel' => 'Membuat variable edukasi',
                'ExperienceLevel' => 'Membuat variable level pengalaman',
                default => 'Data baru dibuat',
            };
        }

        if ($this->event === 'deleted') {
            $modelType = $this->getModelTypeName();

            // Summary spesifik per model untuk event deleted
            return match ($modelType) {
                'JobPosition' => 'Menghapus variable posisi pekerjaan',
                'JobLevel' => 'Menghapus variable tingkat pekerjaan',
                'EducationLevel' => 'Menghapus variable edukasi',
                'ExperienceLevel' => 'Menghapus variable level pengalaman',
                'User' => 'Menghapus pengguna',
                default => 'Data dihapus',
            };
        }

        if ($this->event === 'restored') {
            return 'Data dipulihkan';
        }

        if ($this->event === 'updated' && !empty($normalizedNewValues)) {

            // Khusus User: cek apakah ini update role
            if ($this->getModelTypeName() === 'User') {
                $newValues = $normalizedNewValues;
                $oldValues = $this->old_values ?? [];
                
                // Jika ada changed_by dan role berubah, ini adalah update role
                if (isset($newValues['changed_by']) && isset($newValues['role']) && isset($oldValues['role'])) {
                    return 'Mengubah role pengguna';
                }
            }

            // Khusus Variable Models: tambahkan nama model di summary
            $modelType = $this->getModelTypeName();
            if (in_array($modelType, ['JobPosition', 'JobLevel', 'EducationLevel', 'ExperienceLevel'], true)) {
                $changedKeys = array_keys($normalizedNewValues);
                
                // Mapping model ke nama variable
                $variableNames = [
                    'JobPosition' => 'posisi pekerjaan',
                    'JobLevel' => 'tingkat pekerjaan',
                    'EducationLevel' => 'edukasi',
                    'ExperienceLevel' => 'level pengalaman',
                ];
                
                $variableName = $variableNames[$modelType] ?? 'variable';
                
                // Hanya index yang berubah (drag & drop)
                if (count($changedKeys) === 1 && $changedKeys[0] === 'index') {
                    return "Mengubah urutan variable {$variableName}";
                }
                
                // Hanya nama yang berubah
                if (count($changedKeys) === 1 && $changedKeys[0] === 'name') {
                    return "Mengubah nama variable {$variableName}";
                }
                
                // Hanya parent yang berubah (khusus JobPosition)
                if ($modelType === 'JobPosition' && count($changedKeys) === 1 && $changedKeys[0] === 'id_parent') {
                    return "Mengubah parent variable posisi pekerjaan";
                }
                
                // Sync/Unsync Nusawork
                if (in_array('nusawork_id', $changedKeys, true) || in_array('nusawork_name', $changedKeys, true)) {
                    $hasNusaworkId = in_array('nusawork_id', $changedKeys, true);
                    $hasNusaworkName = in_array('nusawork_name', $changedKeys, true);
                    
                    if ($hasNusaworkId && $hasNusaworkName && count($changedKeys) === 2) {
                        return "Mengubah sinkronisasi Nusawork variable {$variableName}";
                    }
                }
            }

            $changes = [];
            foreach ($normalizedNewValues as $key => $value) {
                // Untuk Interview, abaikan field notes agar tidak menghasilkan summary
                // "Mengubah notes" ketika user tidak mengubah apa-apa selain notes kosong.
                if ($this->getModelTypeName() === 'Interview' && $key === 'notes') {
                    continue;
                }

                // Khusus candidate_form_settings: kita hanya ingin tampil di summary
                // jika memang ada field yang berubah (berdasarkan logic formatValue).
                if ($key === 'candidate_form_settings') {
                    $formatted = $this->formatValue('candidate_form_settings', $value);

                    if (is_array($formatted) && empty($formatted)) {
                        // Tidak ada key yang benar-benar berubah, skip dari summary.
                        continue;
                    }
                }

                $changes[] = $this->getFieldLabel($key);
            }

            // Jika setelah filtering tidak ada field yang tersisa (misal hanya notes yang berubah),
            // gunakan summary netral.
            if (count($changes) === 0) {
                return 'Data diubah';
            }

            if (count($changes) === 1) {
                return "Mengubah {$changes[0]}";
            } elseif (count($changes) === 2) {
                return "Mengubah {$changes[0]} dan {$changes[1]}";
            } else {
                $first = array_shift($changes);
                $count = count($changes);
                return "Mengubah {$first} dan {$count} field lainnya";
            }
        }

        return 'Data diubah';
    }

    private function getFieldLabel(string $field): string
    {
        $labels = [
            'job_level_id' => 'level pekerjaan',
            'education_level_id' => 'level pendidikan',
            'experience_level_id' => 'level pengalaman kerja',
            'gender_id' => 'jenis kelamin',
            'status' => 'status lowongan',
            'current_stage_id' => 'tahap lamaran',
            'stage_id' => 'tahap lamaran',
            'candidate_form_settings' => 'pengaturan form pelamar',
            'salary_min' => 'gaji minimum',
            'salary_max' => 'gaji maksimum',
            'applicant_limit' => 'batas pelamar',
            'show_salary_min' => 'tampilan gaji minimum',
            'show_salary_max' => 'tampilan gaji maksimum',
            'headcount' => 'jumlah kebutuhan',
            'show_headcount' => 'tampilan jumlah kebutuhan',
            'age_min' => 'usia minimum',
            'age_max' => 'usia maksimum',
            'send_email' => 'status kirim email notifikasi',
            'notification_header' => 'header email notifikasi',
            'notification_messages' => 'isi email notifikasi',
            'order' => 'urutan pertanyaan kustom',
            'recruiter_notes' => 'alasan penolakan',
            'scheduled_at' => 'jadwal interview',
            'header_image' => 'foto header portal',
            'profile_image' => 'foto profil portal',
            'theme_color' => 'warna tema portal',
            'company_values' => 'nilai perusahaan (company values)',
            'employee_range_start' => 'jumlah karyawan minimum',
            'employee_range_end' => 'jumlah karyawan maksimum',
            'company_category_id' => 'kategori perusahaan',
            'linkedin' => 'url LinkedIn',
            'instagram' => 'url Instagram',
            'website' => 'url Website',
            'profile_photo_path' => 'foto profil',
            'current_resume_path' => 'CV',
            'portfolio_file_path' => 'file portofolio',
            'work_experience_letter_path' => 'surat pengalaman kerja',
            'email' => 'email',
            'password' => 'password',
            'title' => 'judul',
            'description' => 'deskripsi',
            'role' => 'peran pengguna',
            'id_parent' => 'parent posisi pekerjaan',
            'nusawork_id' => 'ID Nusawork',
            'nusawork_name' => 'nama Nusawork',
            'index' => 'urutan',
            'last_login_ip' => 'IP login terakhir',
            'last_login_at' => 'waktu login terakhir',
            'is_new_admin' => 'admin baru',
            'changed_by' => 'diubah oleh',
            'deleted_by' => 'dihapus oleh',
        ];

        return $labels[$field] ?? $field;
    }

    /**
     * Deteksi kasus khusus: update Candidate yang hanya berisi penambahan berkas (foto/CV/portofolio/surat)
     * dari nilai null menjadi ada nilai.
     *
     * @return bool
     */
    private function isCandidateFileAdd(): bool
    {
        if ($this->event !== 'updated') {
            return false;
        }

        if (empty($this->new_values)) {
            return false;
        }

        $fileFields = [
            'profile_photo_path',
            'current_resume_path',
            'portfolio_file_path',
            'work_experience_letter_path',
        ];

        foreach ($this->new_values as $key => $value) {
            if (!in_array($key, $fileFields, true)) {
                return false;
            }

            $old = $this->old_values[$key] ?? null;
            if (!is_null($old) || is_null($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Format value list untuk ditampilkan ke user (konversi key ID ke name).
     *
     * @param array $values
     * @return array
     */
    private function formatValues(array $values): array
    {
        $formatted = [];

        // Khusus User (tenant): tambahkan informasi nama & email ketika role berubah
        if ($this->getModelTypeName() === 'User') {
            $values = $this->formatValuesForUser($values);
        }

        // Khusus Variable Models: tambahkan nama ketika index berubah
        if (in_array($this->getModelTypeName(), ['JobPosition', 'JobLevel', 'EducationLevel', 'ExperienceLevel'], true)) {
            $values = $this->formatValuesForVariableModels($values);
        }

        foreach ($values as $key => $value) {
            $friendlyKey = $this->getFriendlyFieldKey($key);
            $formatted[$friendlyKey] = $this->formatValue($key, $value);
        }

        return $formatted;
    }

    private function formatValuesForUser(array $values): array
    {
        /** @var TenantUser|null $user */
        $user = TenantUser::find($this->auditable_id);

        if ($user && array_key_exists('role', $values)) {
            if (! array_key_exists('name', $values)) {
                $values['name'] = $user->name;
            }

            if (! array_key_exists('email', $values)) {
                $values['email'] = $user->email;
            }
        }

        return $values;
    }


    private function formatValuesForVariableModels(array $values): array
    {
        $modelType = $this->getModelTypeName();
        
        // Ketika yang berubah hanya index (urutan), sertakan juga name
        // supaya jelas variable mana yang diubah urutannya
        // Note: JobPosition tidak punya field index, jadi tidak perlu di-handle di sini
        if (array_key_exists('index', $values) && ! array_key_exists('name', $values)) {
            $model = null;
            
            switch ($modelType) {
                case 'JobLevel':
                    $model = JobLevel::find($this->auditable_id);
                    break;
                case 'EducationLevel':
                    $model = EducationLevel::find($this->auditable_id);
                    break;
                case 'ExperienceLevel':
                    $model = ExperienceLevel::find($this->auditable_id);
                    break;
            }
            
            if ($model && $model->name) {
                // Tambahkan name di awal untuk konteks yang lebih jelas
                $ordered = ['name' => $model->name];
                foreach ($values as $k => $v) {
                    $ordered[$k] = $v;
                }
                $values = $ordered;
            }
        }
        
        return $values;
    }

    /**
     * Format single value berdasarkan field name.
     *
     * @param string $field
     * @param mixed $value
     * @return mixed
     */
    private function formatValue(string $field, $value)
    {
        if (null === $value) {
            return $value;
        }

        switch ($field) {
            case 'job_position_id':
                $position = JobPosition::find($value);

                return $position?->name ?? $value;

            case 'job_application_id':
                return $value;

            case 'job_vacancy_id':
                return $value;

            case 'job_level_id':
                $level = JobLevel::find($value);

                return $level?->name ?? $value;

            case 'id_parent':
                // Format parent job position ID menjadi nama parent
                $parent = JobPosition::find($value);

                return $parent?->name ?? $value;

            case 'answer':
                // Untuk JobApplicationAnswer, tampilkan jawaban dengan label yang lebih manusiawi
                if ($this->getModelTypeName() === 'JobApplicationAnswer') {
                    return $value;
                }

                return $value;

            case 'current_stage_id':
            case 'stage_id':
                return $value;

            case 'education_level_id':
                $education = EducationLevel::find($value);

                return $education?->name ?? $value;

            case 'experience_level_id':
                $experience = ExperienceLevel::find($value);

                return $experience?->name ?? $value;

            case 'gender_id':
                return $value;

            case 'description':
            case 'requirements':
            case 'benefits':
                return trim(strip_tags((string) $value));

            case 'notification_messages':
                // Membuat konten HTML email menjadi satu paragraf teks yang bersih
                $text = (string) $value;
                $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $plain = strip_tags($decoded);
                $plain = preg_replace('/\s+/u', ' ', $plain) ?? $plain;

                return trim($plain);
            case 'profile_photo_path':
            case 'current_resume_path':
            case 'portfolio_file_path':
            case 'work_experience_letter_path':
                if (! is_string($value) || '' === $value) {
                    return $value;
                }

                $basename = basename($value);
                $extension = strtoupper(pathinfo($basename, PATHINFO_EXTENSION));

                $candidateNameSuffix = '';
                if ($this->getModelTypeName() === 'Candidate') {
                    $candidateNameSuffix = '';
                }

                // Nama yang lebih mudah dipahami berdasarkan jenis field
                $label = match ($field) {
                    'profile_photo_path' => 'Foto profil',
                    'current_resume_path' => 'CV',
                    'portfolio_file_path' => 'Portofolio',
                    'work_experience_letter_path' => 'Surat pengalaman kerja',
                    default => 'Berkas', // @codeCoverageIgnore
                };

                $label .= $candidateNameSuffix;

                return $extension ? $label . ' (' . $extension . ')' : $label;

            case 'changed_by':
            case 'deleted_by':
                // Kembalikan dalam format object/array, jangan diubah ke string
                return $value;
                
            case 'candidate_form_settings':
                // Hanya tampilkan field yang benar-benar berubah saja, bukan seluruh struktur
                $oldRaw = $this->old_values['candidate_form_settings'] ?? [];
                $newRaw = $this->new_values['candidate_form_settings'] ?? [];

                $oldSettings = is_string($oldRaw) ? json_decode($oldRaw, true) ?? [] : (array) $oldRaw;
                $newSettings = is_string($newRaw) ? json_decode($newRaw, true) ?? [] : (array) $newRaw;

                // Tentukan key yang berubah
                $changedKeys = [];
                foreach (array_keys(array_merge($oldSettings, $newSettings)) as $key) {
                    $old = $oldSettings[$key] ?? null;
                    $new = $newSettings[$key] ?? null;

                    if (json_encode($old) !== json_encode($new)) {
                        $changedKeys[] = $key;
                    }
                }

                if (empty($changedKeys)) {
                    return [];
                }

                $source = null;
                if ($value === $oldRaw) {
                    $source = $oldSettings;
                } elseif ($value === $newRaw) {
                    $source = $newSettings;
                }

                // Jika tidak bisa ditentukan, fallback: kembalikan hanya changedKeys dari value yang sudah dinormalisasi
                if ($source === null) {
                    $source = is_string($value) ? json_decode($value, true) ?? [] : (array) $value;
                }

                $result = [];
                foreach ($changedKeys as $key) {
                    if (array_key_exists($key, $source)) {
                        $result[$key] = $source[$key];
                    }
                }

                return $result;

            default:
                return $value;
        }
    }

    /**
     * Map raw field key ke nama yang lebih manusiawi untuk JSON detail (tanpa mengubah struktur audit asli).
     *
     * @param string $field
     * @return string
     */
    private function getFriendlyFieldKey(string $field): string
    {
        return match ($field) {
            'job_position_id' => 'job_position_name',
            'job_level_id' => 'job_level_name',
            'education_level_id' => 'education_level_name',
            'experience_level_id' => 'experience_level_name',
            'gender_id' => 'gender',
            'company_category_id' => 'company_category_name',
            'job_application_id' => 'job_vacancy_name',
            'profile_photo_path' => 'profile_photo_name',
            'current_resume_path' => 'current_resume_name',
            'portfolio_file_path' => 'portfolio_file_name',
            'work_experience_letter_path' => 'work_experience_letter_name',
            default => $field,
        };
    }
}
