<?php

namespace Tests\Feature;

use App\Models\CeremonySchedule;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_schedule_page_is_available_without_login(): void
    {
        CeremonySchedule::query()->create([
            'title' => 'Apel Oktober',
            'type' => 'apel',
            'event_date' => '2026-10-12',
            'ceremony_group_number' => 3,
        ]);

        $this->get(route('public-schedules.index', ['tab' => 'apel', 'month' => '2026-10']))
            ->assertOk()
            ->assertSee('Apel / Upacara Oktober 2026')
            ->assertSee('Kelompok 3')
            ->assertSee('View only')
            ->assertDontSee('Simpan kalender');
    }

    public function test_public_schedule_page_switches_between_apel_and_piket_by_month(): void
    {
        CeremonySchedule::query()->create([
            'title' => 'Piket Oktober',
            'type' => 'piket_loket',
            'event_date' => '2026-10-07',
            'duty_roster' => [
                'number' => 2,
                'coordinator' => 'Petugas Piket',
                'members' => ['Anggota Satu'],
            ],
        ]);
        CeremonySchedule::query()->create([
            'title' => 'Piket November',
            'type' => 'piket_loket',
            'event_date' => '2026-11-04',
            'duty_roster' => ['number' => 3, 'coordinator' => 'November'],
        ]);

        $this->get(route('public-schedules.index', ['tab' => 'piket', 'month' => '2026-10']))
            ->assertOk()
            ->assertSee('Piket Loket Oktober 2026')
            ->assertSee('Petugas Piket')
            ->assertSee('grid-cols-7', false)
            ->assertDontSee('<table', false)
            ->assertDontSee('Piket November');

        $this->get(route('public-schedules.index', ['tab' => 'piket', 'month' => '2026-11']))
            ->assertOk()
            ->assertSee('Piket Loket November 2026')
            ->assertSee('November');
    }

    public function test_public_piket_kecamatan_does_not_show_kelurahan_schedules(): void
    {
        $kelurahan = Department::query()->create([
            'name' => 'Kelurahan Akcaya',
            'department_type' => 'kelurahan',
            'is_active' => true,
        ]);
        CeremonySchedule::query()->create([
            'title' => 'Piket Akcaya',
            'type' => 'piket_loket',
            'event_date' => '2026-10-07',
            'department_id' => $kelurahan->id,
            'duty_roster' => [
                'number' => 2,
                'coordinator' => 'Petugas Akcaya',
                'members' => ['Anggota Akcaya'],
            ],
        ]);

        $this->get(route('public-schedules.index', ['tab' => 'piket', 'month' => '2026-10']))
            ->assertOk()
            ->assertDontSee('Kelurahan Akcaya')
            ->assertDontSee('Petugas Akcaya')
            ->assertDontSee('Anggota Akcaya');
    }

    public function test_public_landing_page_offers_three_readonly_schedule_types(): void
    {
        $this->get(route('public-schedules.index'))->assertOk()
            ->assertDontSee('aria-label="Menu jadwal publik"', false)
            ->assertSee('id="public-content"', false)
            ->assertSee('Apel / Upacara')->assertSee('Piket Kecamatan')->assertSee('Piket Kelurahan')
            ->assertSee(route('public-schedules.index', ['tab' => 'kelurahan']), false);
        $this->get(route('public-schedules.index', ['tab' => 'kelurahan']))->assertOk()
            ->assertDontSee('aria-label="Menu jadwal publik"', false)
            ->assertSee('Jadwal piket kelurahan')->assertSee('Kembali ke pilihan jadwal');
    }
}
