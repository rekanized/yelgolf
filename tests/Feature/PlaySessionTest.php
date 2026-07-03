<?php

namespace Tests\Feature;

use App\Livewire\PlaySessionPage;
use App\Livewire\PlayerConsole;
use App\Models\Course;
use App\Models\Hole;
use App\Models\PlaySession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlaySessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_start_session_disabled_and_cannot_start(): void
    {
        User::factory()->create(['name' => 'Unrelated User']);
        $course = $this->createCourse();

        $response = $this->get(route('courses.show', $course));

        $response->assertOk();
        $response->assertSee('Start session');
        $response->assertDontSee('Active player');
        $response->assertSee('type="submit" disabled', false);
        $response->assertSee('aria-disabled="true"', false);
        $response->assertSee('Sign in to start a session.');
        $response->assertSee(route('sessions.store', $course), false);

        $this->post(route('sessions.store', $course))
            ->assertForbidden();

        $this->assertDatabaseCount('play_sessions', 0);
    }

    public function test_authenticated_player_can_start_session_from_course_page(): void
    {
        $player = User::factory()->create(['name' => 'Guest Player']);
        $course = $this->createCourse();

        $response = $this->withSession(['current_player_id' => $player->id])
            ->get(route('courses.show', $course));

        $response->assertOk();
        $response->assertSee('Start session');
        $response->assertDontSee('type="submit" disabled', false);
        $response->assertSee('aria-disabled="false"', false);

        $redirectResponse = $this->withSession(['current_player_id' => $player->id])
            ->post(route('sessions.store', $course));

        $session = PlaySession::query()->first();

        $redirectResponse->assertRedirect(route('sessions.show', $session));
        $this->assertNotNull($session);
        $this->assertDatabaseHas('play_sessions', [
            'course_id' => $course->id,
            'host_id' => $player->id,
            'status' => 'active',
        ]);
        $this->assertSame(1, $session->fresh()->load('players')->participantCount());

        $this->withSession(['current_player_id' => $player->id])
            ->get(route('sessions.show', $session))
            ->assertOk()
            ->assertSee('1 player')
            ->assertSee('Guest Player');

        $this->withSession(['current_player_id' => $player->id])
            ->get('/')
            ->assertOk()
            ->assertSee('Active sessions')
            ->assertSee(route('sessions.show', $session), false)
            ->assertSee('Open session');
    }

    public function test_host_can_invite_other_players_from_session_page(): void
    {
        $host = User::factory()->create(['name' => 'Session Host']);
        $invitee = User::factory()->create(['name' => 'Guest Player']);
        $course = $this->createCourse();

        $session = PlaySession::query()->create([
            'course_id' => $course->id,
            'host_id' => $host->id,
            'host_session_key' => null,
            'host_name' => $host->name,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $session->players()->attach($host->id, [
            'status' => 'joined',
            'invited_at' => now(),
            'joined_at' => now(),
        ]);

        $this->withSession(['current_player_id' => $host->id]);

        Livewire::withQueryParams([])
            ->test(PlaySessionPage::class, ['playSession' => $session])
            ->call('openInvitePicker')
            ->set('selectedInviteeIds', [(string) $invitee->id])
            ->call('invitePlayers')
            ->assertSee('Players in session')
            ->assertSee('Session Host')
            ->assertSee('Guest Player');

        $this->assertDatabaseHas('play_session_user', [
            'play_session_id' => $session->id,
            'user_id' => $invitee->id,
            'status' => 'invited',
        ]);
    }

    public function test_session_participants_can_choose_layout_settings(): void
    {
        $host = User::factory()->create(['name' => 'Session Host']);
        $invitee = User::factory()->create(['name' => 'Guest Player']);
        $course = $this->createCourse();

        Hole::query()->create([
            'course_id' => $course->id,
            'layout_id' => 44317,
            'layout_name' => 'Hästhagen Bakre',
            'layout_order' => 2,
            'sort_order' => 1,
            'number' => 1,
            'hole_label' => '1',
            'par' => 3,
            'distance_meters' => 74,
        ]);

        $session = PlaySession::query()->create([
            'course_id' => $course->id,
            'host_id' => $host->id,
            'host_session_key' => null,
            'host_name' => $host->name,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $session->players()->attach($host->id, [
            'status' => 'joined',
            'invited_at' => now(),
            'joined_at' => now(),
        ]);

        $this->withSession(['current_player_id' => $host->id]);

        Livewire::withQueryParams([])
            ->test(PlaySessionPage::class, ['playSession' => $session])
            ->call('updateParticipantLayout', 'host', '44317')
            ->assertSee('Hästhagen Bakre');

        $this->assertDatabaseHas('play_sessions', [
            'id' => $session->id,
            'host_layout_id' => 44317,
        ]);

        $session->players()->attach($invitee->id, [
            'status' => 'joined',
            'invited_at' => now(),
            'joined_at' => now(),
        ]);

        $this->withSession(['current_player_id' => $invitee->id]);

        Livewire::withQueryParams([])->test(PlaySessionPage::class, ['playSession' => $session->fresh()])
            ->call('updateParticipantLayout', 'user-'.$invitee->id, '37576')
            ->assertSee('Guest Player');

        $this->assertDatabaseHas('play_session_user', [
            'play_session_id' => $session->id,
            'user_id' => $invitee->id,
            'selected_layout_id' => 37576,
        ]);

        $this->get(route('sessions.show', $session->fresh()))
            ->assertOk()
            ->assertDontSee('Save view settings')
            ->assertSee('Session Host')
            ->assertSee('Guest Player')
            ->assertSee('Hästhagen Främre')
            ->assertSee('Hästhagen Bakre');
    }

    public function test_multiple_invited_players_can_join_same_session_and_find_it_again_from_home(): void
    {
        $host = User::factory()->create(['name' => 'Session Host']);
        $inviteeOne = User::factory()->create(['name' => 'Guest Player One']);
        $inviteeTwo = User::factory()->create(['name' => 'Guest Player Two']);
        $course = $this->createCourse();

        $session = PlaySession::query()->create([
            'course_id' => $course->id,
            'host_id' => $host->id,
            'host_session_key' => null,
            'host_name' => $host->name,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $session->players()->attach($host->id, [
            'status' => 'joined',
            'invited_at' => now(),
            'joined_at' => now(),
        ]);

        $session->players()->attach($inviteeOne->id, [
            'status' => 'invited',
            'invited_at' => now(),
        ]);

        $session->players()->attach($inviteeTwo->id, [
            'status' => 'invited',
            'invited_at' => now(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Active player')
            ->assertDontSee('Join session')
            ->assertDontSee('Active sessions')
            ->assertDontSee('Session Host');

        $this->withSession(['current_player_id' => $inviteeOne->id]);

        Livewire::withQueryParams([])
            ->test(PlayerConsole::class)
            ->call('joinSession', $session->id)
            ->assertRedirect(route('sessions.show', $session));

        $this->withSession(['current_player_id' => $inviteeOne->id])
            ->get(route('sessions.show', $session))
            ->assertOk()
            ->assertSee('3 players')
            ->assertSee('Session Host')
            ->assertSee('Guest Player One');

        $this->withSession(['current_player_id' => $inviteeOne->id])
            ->get('/')
            ->assertOk()
            ->assertSee('Active sessions')
            ->assertSee(route('sessions.show', $session), false)
            ->assertSee('Open session');

        $this->withSession(['current_player_id' => $inviteeTwo->id]);

        Livewire::withQueryParams([])->test(PlayerConsole::class)
            ->call('joinSession', $session->id)
            ->assertRedirect(route('sessions.show', $session));

        $this->assertDatabaseHas('play_session_user', [
            'play_session_id' => $session->id,
            'user_id' => $inviteeOne->id,
            'status' => 'joined',
        ]);

        $this->assertDatabaseHas('play_session_user', [
            'play_session_id' => $session->id,
            'user_id' => $inviteeTwo->id,
            'status' => 'joined',
        ]);

        $this->withSession(['current_player_id' => $inviteeTwo->id])
            ->get(route('sessions.show', $session))
            ->assertOk()
            ->assertSee('3 players')
            ->assertSee('Guest Player Two');

        $this->withSession(['current_player_id' => $inviteeTwo->id])
            ->get('/')
            ->assertOk()
            ->assertSee('Active sessions')
            ->assertSee(route('sessions.show', $session), false)
            ->assertSee('Open session');
    }

    public function test_invited_but_not_joined_player_cannot_view_session_or_home_listing(): void
    {
        $host = User::factory()->create(['name' => 'Session Host']);
        $invitee = User::factory()->create(['name' => 'Guest Player']);
        $course = $this->createCourse();

        $session = PlaySession::query()->create([
            'course_id' => $course->id,
            'host_id' => $host->id,
            'host_name' => $host->name,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $session->players()->attach($host->id, [
            'status' => 'joined',
            'invited_at' => now(),
            'joined_at' => now(),
        ]);

        $session->players()->attach($invitee->id, [
            'status' => 'invited',
            'invited_at' => now(),
        ]);

        $this->withSession(['current_player_id' => $invitee->id])
            ->get(route('sessions.show', $session))
            ->assertForbidden();

        $this->withSession(['current_player_id' => $invitee->id])
            ->get('/')
            ->assertOk()
            ->assertDontSee('Active sessions')
            ->assertDontSee(route('sessions.show', $session), false);
    }

    protected function createCourse(): Course
    {
        $course = Course::query()->create([
            'name' => 'Hästhagen',
            'slug' => 'haesthagen-M8Wu',
            'udisc_url' => 'https://udisc.com/courses/haesthagen-M8Wu',
            'location_name' => 'Örebro, Sweden',
            'description' => 'Detailed course summary.',
            'holes_count' => 18,
            'rating' => 3.8,
            'ratings_count' => 2789,
            'target_type' => 'DISCatcher Pro (original)',
            'photos' => [
                'https://udisc-parse.s3.amazonaws.com/photo-1.jpg',
            ],
        ]);

        Hole::query()->create([
            'course_id' => $course->id,
            'layout_id' => 37576,
            'layout_name' => 'Hästhagen Främre',
            'layout_order' => 1,
            'sort_order' => 1,
            'number' => 1,
            'hole_label' => '1',
            'par' => 3,
            'distance_meters' => 58,
        ]);

        return $course;
    }
}