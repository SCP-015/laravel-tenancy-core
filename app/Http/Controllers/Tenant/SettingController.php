<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Traits\HasPermissionTrait;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use HasPermissionTrait;

    /**
     * Generate a new random code.
     *
     * @return string
     */
    public function generateCode(Request $request)
    {
        $this->checkPermission('settings.generate_code');

        $tenant = tenant();
        $code = Tenant::generateCode();

        Tenant::where('id', $tenant->id)->update([
            'code' => $code,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('Company code refreshed successfully'),
            'code' => $code,
        ]);
    }
}
