<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_signed_in_operator_is_redirected_from_login_to_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect('/dashboard');
    }

    public function test_guests_cannot_read_events_or_change_alerts(): void
    {
        $alert = Alert::factory()->create();
        $this->get('/dashboard')->assertRedirect('/');
        $this->patch('/alerts/'.$alert->id, ['status' => 'resolved'])->assertRedirect('/');
        $this->assertSame('open', $alert->fresh()->status);
    }

    public function test_operator_can_acknowledge_and_resolve_an_alert(): void
    {
        $user = User::factory()->create();
        $alert = Alert::factory()->create();
        $this->actingAs($user)->patch('/alerts/'.$alert->id, ['status' => 'acknowledged'])->assertRedirect('/dashboard');
        $this->assertNotNull($alert->fresh()->acknowledged_at);
        $this->actingAs($user)->patch('/alerts/'.$alert->id, ['status' => 'resolved'])->assertRedirect('/dashboard');
        $this->assertSame('resolved', $alert->fresh()->status);
        $this->assertNotNull($alert->fresh()->resolved_at);
        $this->assertSame($user->id, $alert->fresh()->resolved_by);
        $this->actingAs($user)->patch('/alerts/'.$alert->id, ['status' => 'acknowledged'])->assertConflict();
    }

    public function test_dashboard_escapes_event_text(): void
    {
        $user = User::factory()->create();
        Event::factory()->create(['message' => '<script>alert(1)</script>']);
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_valid_login_starts_session_and_invalid_login_does_not(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }
}
