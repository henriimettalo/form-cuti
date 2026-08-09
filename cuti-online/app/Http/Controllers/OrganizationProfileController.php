<?php

namespace App\Http\Controllers;

use App\Models\OrganizationProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrganizationProfileController extends Controller
{
    public function index(): View
    {
        return view('organization-profile.index', [
            'organizationProfile' => OrganizationProfile::current(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data): void {
            $organizationProfile = OrganizationProfile::current();
            $organizationProfile->update(['name' => trim($data['name'])]);
            $organizationProfile->resolveDepartment();
        });

        return to_route('organization-profile.index')
            ->with('status', 'Profil instansi berhasil disimpan sebagai unit kerja bawaan untuk data baru.');
    }
}
