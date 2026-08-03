<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_layout_exposes_temporary_workspace_tabs(): void
    {
        $user = User::factory()->create(['role' => 'operator']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-workspace-root', false)
            ->assertSee('data-workspace-tabs', false)
            ->assertSee('data-workspace-static-panel', false)
            ->assertSee('data-workspace-panels', false)
            ->assertSee('data-workspace-link', false)
            ->assertSee('data-workspace-title="Pegawai"', false)
            ->assertSee('data-workspace-title="Buat Formulir"', false)
            ->assertSee('data-workspace-dashboard-url', false)
            ->assertDontSee('data-workspace-tab-close data-workspace-tab-id="initial"', false);
    }

    public function test_non_dashboard_initial_tab_can_return_to_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'operator']);

        $this->actingAs($user)
            ->get(route('leave-requests.create'))
            ->assertOk()
            ->assertSee('data-workspace-dashboard-url', false)
            ->assertSee('data-workspace-tab-close data-workspace-tab-id="initial"', false)
            ->assertSee('aria-label="Tutup tab dan kembali ke Dashboard"', false);
    }
}
