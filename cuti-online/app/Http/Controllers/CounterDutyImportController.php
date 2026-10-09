<?php

namespace App\Http\Controllers;

use App\Services\CounterDutyImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CounterDutyImportController extends Controller
{
    public function create(Request $request): View
    {
        $this->ensureCanManage($request);

        return view('counter-duty.import', [
            'preview' => $request->session()->get('counter-duty-import-preview'),
            'currentYear' => now('Asia/Pontianak')->year,
        ]);
    }

    public function preview(Request $request, CounterDutyImportService $imports): RedirectResponse
    {
        $this->ensureCanManage($request);
        $request->session()->forget('counter-duty-import-preview');
        $input = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
            'year' => ['required', 'integer', 'between:1900,2100'],
        ], [], ['file' => 'file jadwal', 'year' => 'tahun impor']);
        $preview = $imports->preview($request->file('file'), (int) $input['year']);
        $request->session()->put('counter-duty-import-preview', $preview);

        return to_route('counter-duty.import.create');
    }

    public function store(Request $request, CounterDutyImportService $imports): RedirectResponse
    {
        $this->ensureCanManage($request);
        $preview = $request->session()->get('counter-duty-import-preview');
        if (! $preview) {
            return to_route('counter-duty.import.create')->withErrors(['preview' => 'Pratinjau tidak tersedia. Unggah dan periksa file terlebih dahulu.']);
        }
        $count = $imports->store($preview, $request->user()->id);
        $request->session()->forget('counter-duty-import-preview');
        $firstDate = collect($preview['rows'])->where('status', 'new')->min('date');

        return to_route('counter-duty.index', ['month' => substr($firstDate ?? $preview['year'].'-01-01', 0, 7)])
            ->with('status', $count.' jadwal piket tahun '.$preview['year'].' berhasil diimpor. Jadwal lama tidak ditimpa.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        $this->ensureCanManage($request);
        $request->session()->forget('counter-duty-import-preview');

        return to_route('counter-duty.index');
    }

    private function ensureCanManage(Request $request): void
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
    }
}
