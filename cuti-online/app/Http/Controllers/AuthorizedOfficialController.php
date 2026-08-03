<?php

namespace App\Http\Controllers;

use App\Models\Official;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthorizedOfficialController extends Controller
{
    public const ROLES = [
        'authorized_official' => 'Pejabat berwenang',
        'sekda' => 'Sekretaris Daerah (Sekda)',
        'walikota' => 'Wali Kota',
    ];

    public function index(): View
    {
        $officials = Official::query()
            ->whereNull('department_id')
            ->where('is_active', true)
            ->whereIn('signature_role', array_keys(self::ROLES))
            ->get()
            ->keyBy('signature_role');

        return view('authorized-official.index', [
            'officials' => $officials,
            'roleLabels' => self::ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            'full_name' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:32'],
            'position_title' => ['required', 'string', 'max:255'],
        ]);

        Official::query()->updateOrCreate(
            [
                'department_id' => null,
                'signature_role' => $data['role'],
                'is_active' => true,
            ],
            [
                'full_name' => $data['full_name'],
                'nip' => $data['nip'] ?? null,
                'position_title' => $data['position_title'],
            ],
        );

        $label = self::ROLES[$data['role']];

        return to_route('authorized-official.index')
            ->with('status', "{$label} berhasil disimpan.");
    }
}
