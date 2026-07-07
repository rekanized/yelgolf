<?php

namespace Tests\Feature;

use App\Livewire\PlaySessionPage;
use App\Livewire\PlaySessionGamePage;
use App\Livewire\PlayerConsole;
use App\Models\Course;
use App\Models\Hole;
use App\Models\PlaySession;
use App\Models\PlaySessionScore;
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
        $response->assertSee('Sessions');
        $response->assertSee(route('sessions.index'), false);
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
            ->assertDontSee('Active sessions')
            ->assertDontSee(route('sessions.show', $session), false);

        $this->withSession(['current_player_id' => $player->id])
            ->get(route('sessions.index'))
            ->assertOk()
            ->assertSee('Sessions')
            ->assertSee('Active')
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

    public function test_host_can_end_session_after_warning_modal(): void
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

        $session->players()->attach($invitee->id, [
            'status' => 'invited',
            'invited_at' => now(),
        ]);

        $this->withSession(['current_player_id' => $host->id]);

        Livewire::withQueryParams([])
            ->test(PlaySessionPage::class, ['playSession' => $session])
            ->assertSee('End session')
            ->call('openEndSessionModal')
            ->assertSet('showEndSessionModal', true)
            ->assertSee('End this session?')
            ->call('closeEndSessionModal')
            ->assertSet('showEndSessionModal', false)
            ->call('openEndSessionModal')
            ->call('endSession')
            ->assertRedirect(route('courses.show', $course));

        $endedSession = $session->fresh();

        $this->assertSame('ended', $endedSession->status);
        $this->assertNotNull($endedSession->ended_at);

        $this->withSession(['current_player_id' => $host->id])
            ->get('/')
            ->assertOk()
            ->assertDontSee(route('sessions.show', $session), false);

        $this->withSession(['current_player_id' => $invitee->id]);

        Livewire::withQueryParams([])
            ->test(PlayerConsole::class)
            ->assertDontSee('Join session');

        $this->withSession(['current_player_id' => $host->id])
            ->get(route('sessions.show', $session))
            ->assertOk()
            ->assertSee('Ended')
            ->assertDontSee('End session')
            ->assertDontSee(route('sessions.game', $session), false);

        $this->withSession(['current_player_id' => $host->id])
            ->get(route('sessions.index'))
            ->assertOk()
            ->assertSee('Sessions')
            ->assertSee('Ended')
            ->assertSee(route('sessions.show', $session), false);

        $this->withSession(['current_player_id' => $invitee->id])
            ->get(route('sessions.show', $session))
            ->assertForbidden();
    }

    public function test_joined_non_host_cannot_end_session(): void
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

        $session->players()->attach($invitee->id, [
            'status' => 'joined',
            'invited_at' => now(),
            'joined_at' => now(),
        ]);

        $this->withSession(['current_player_id' => $invitee->id]);

        Livewire::withQueryParams([])
            ->test(PlaySessionPage::class, ['playSession' => $session])
            ->assertDontSee('End session')
            ->call('openEndSessionModal')
            ->assertForbidden();

        $this->assertDatabaseHas('play_sessions', [
            'id' => $session->id,
            'status' => 'active',
            'ended_at' => null,
        ]);
    }

    public function test_session_participant_can_open_game_from_session_page(): void
    {
        $host = User::factory()->create(['name' => 'Session Host']);
        $course = $this->createCourse();
        $session = $this->createPlaySession($course, $host);

        $this->attachSessionPlayer($session, $host, selectedLayoutId: 37576);

        $this->withSession(['current_player_id' => $host->id])
            ->get(route('sessions.show', $session))
            ->assertOk()
            ->assertSee('Game')
            ->assertSee(route('sessions.game', $session), false);
    }

    public function test_game_page_requires_active_joined_participant(): void
    {
        $host = User::factory()->create(['name' => 'Session Host']);
        $invitee = User::factory()->create(['name' => 'Guest Player']);
        $stranger = User::factory()->create(['name' => 'Outside Player']);
        $course = $this->createCourse();
        $session = $this->createPlaySession($course, $host);

        $this->attachSessionPlayer($session, $host, selectedLayoutId: 37576);
        $this->attachSessionPlayer($session, $invitee, status: 'invited');

        $this->get(route('sessions.game', $session))
            ->assertForbidden();

        $this->withSession(['current_player_id' => $invitee->id])
            ->get(route('sessions.game', $session))
            ->assertForbidden();

        $this->withSession(['current_player_id' => $stranger->id])
            ->get(route('sessions.game', $session))
            ->assertForbidden();

        $session->forceFill([
            'status' => 'ended',
            'ended_at' => now(),
        ])->save();

        $this->withSession(['current_player_id' => $host->id])
            ->get(route('sessions.game', $session))
            ->assertForbidden();
    }

    public function test_game_page_scores_selected_layout_players_and_skips_missing_layouts(): void
    {
        $host = User::factory()->create(['name' => 'Session Host']);
        $invitee = User::factory()->create(['name' => 'Guest Player']);
        $course = $this->createCourse();
        $session = $this->createPlaySession($course, $host);

        $this->attachSessionPlayer($session, $host, selectedLayoutId: 37576);
        $this->attachSessionPlayer($session, $invitee);

        $this->withSession(['current_player_id' => $host->id]);

        Livewire::withQueryParams([])
            ->test(PlaySessionGamePage::class, ['playSession' => $session])
            ->assertSee('Hole 1')
            ->assertSee('Session Host')
            ->assertSee('Hästhagen Främre')
            ->assertSee('Par 3')
            ->assertSee('Waiting on layouts')
            ->assertSee('Guest Player');
    }

    public function test_any_joined_participant_can_score_players_and_navigate_holes(): void
    {
        $host = User::factory()->create(['name' => 'Session Host']);
        $invitee = User::factory()->create(['name' => 'Guest Player']);
        $course = $this->createCourse();
        $this->createHole($course, layoutId: 37576, layoutName: 'Hästhagen Främre', sortOrder: 2, number: 2, par: 4);
        $this->createHole($course, layoutId: 44317, layoutName: 'Hästhagen Bakre', sortOrder: 1, number: 1, par: 4);
        $this->createHole($course, layoutId: 44317, layoutName: 'Hästhagen Bakre', sortOrder: 2, number: 2, par: 5);
        $session = $this->createPlaySession($course, $host);

        $this->attachSessionPlayer($session, $host, selectedLayoutId: 37576);
        $this->attachSessionPlayer($session, $invitee, selectedLayoutId: 44317);

        $this->withSession(['current_player_id' => $invitee->id]);

        Livewire::withQueryParams([])
            ->test(PlaySessionGamePage::class, ['playSession' => $session])
            ->call('incrementScore', $host->id)
            ->call('saveScore', $invitee->id, '5')
            ->call('saveScore', $host->id, '6')
            ->call('nextHole')
            ->assertSee('Hole 2')
            ->call('previousHole')
            ->assertSee('Hole 1');

        $this->assertDatabaseHas('play_session_scores', [
            'play_session_id' => $session->id,
            'user_id' => $host->id,
            'hole_index' => 1,
            'strokes' => 6,
        ]);

        $this->assertDatabaseHas('play_session_scores', [
            'play_session_id' => $session->id,
            'user_id' => $invitee->id,
            'hole_index' => 1,
            'strokes' => 5,
        ]);

        $this->assertSame(2, PlaySessionScore::query()->where('play_session_id', $session->id)->count());
        $this->assertSame(1, $session->fresh()->current_hole_index);
    }

    public function test_next_hole_waits_for_every_visible_player_score(): void
    {
        $host = User::factory()->create(['name' => 'Session Host']);
        $invitee = User::factory()->create(['name' => 'Guest Player']);
        $course = $this->createCourse();
        $this->createHole($course, layoutId: 37576, layoutName: 'Hästhagen Främre', sortOrder: 2, number: 2, par: 4);
        $session = $this->createPlaySession($course, $host);

        $this->attachSessionPlayer($session, $host, selectedLayoutId: 37576);
        $this->attachSessionPlayer($session, $invitee, selectedLayoutId: 37576);

        $this->withSession(['current_player_id' => $host->id]);

        Livewire::withQueryParams([])
            ->test(PlaySessionGamePage::class, ['playSession' => $session])
            ->call('saveScore', $host->id, '3')
            ->call('nextHole')
            ->assertSee('Hole 1')
            ->call('saveScore', $invitee->id, '4')
            ->call('nextHole')
            ->assertSee('Hole 2');

        $this->assertSame(2, $session->fresh()->current_hole_index);
    }

    public function test_player_specific_layouts_map_shared_hole_index_to_each_selected_layout(): void
    {
        $host = User::factory()->create(['name' => 'Session Host']);
        $invitee = User::factory()->create(['name' => 'Guest Player']);
        $course = $this->createCourse();
        $frontHole = Hole::query()->where('course_id', $course->id)->where('layout_id', 37576)->firstOrFail();
        $backHole = $this->createHole($course, layoutId: 44317, layoutName: 'Hästhagen Bakre', sortOrder: 1, number: 1, par: 4);
        $session = $this->createPlaySession($course, $host);

        $this->attachSessionPlayer($session, $host, selectedLayoutId: 37576);
        $this->attachSessionPlayer($session, $invitee, selectedLayoutId: 44317);

        $this->withSession(['current_player_id' => $host->id]);

        Livewire::withQueryParams([])
            ->test(PlaySessionGamePage::class, ['playSession' => $session])
            ->assertSee('Hästhagen Främre')
            ->assertSee('Hästhagen Bakre')
            ->assertSee('Par 3')
            ->assertSee('Par 4')
            ->call('saveScore', $host->id, '3')
            ->call('saveScore', $invitee->id, '4');

        $this->assertDatabaseHas('play_session_scores', [
            'play_session_id' => $session->id,
            'user_id' => $host->id,
            'hole_id' => $frontHole->id,
            'hole_index' => 1,
            'strokes' => 3,
        ]);

        $this->assertDatabaseHas('play_session_scores', [
            'play_session_id' => $session->id,
            'user_id' => $invitee->id,
            'hole_id' => $backHole->id,
            'hole_index' => 1,
            'strokes' => 4,
        ]);
    }

    public function test_game_score_cards_show_cumulative_golf_relative_score(): void
    {
        $host = User::factory()->create(['name' => 'Session Host']);
        $course = $this->createCourse();
        $this->createHole($course, layoutId: 37576, layoutName: 'Hästhagen Främre', sortOrder: 2, number: 2, par: 4);
        $session = $this->createPlaySession($course, $host);

        $this->attachSessionPlayer($session, $host, selectedLayoutId: 37576);
        $this->withSession(['current_player_id' => $host->id]);

        Livewire::withQueryParams([])
            ->test(PlaySessionGamePage::class, ['playSession' => $session])
            ->call('saveScore', $host->id, '4')
            ->assertSee('+1')
            ->call('nextHole')
            ->call('saveScore', $host->id, '2')
            ->assertSee('-1')
            ->assertDontSee(__('ui.game.chart_title'));

        Livewire::withQueryParams([])
            ->test(PlaySessionPage::class, ['playSession' => $session->fresh()])
            ->assertSee(__('ui.game.chart_title'))
            ->assertViewHas('scoreCharts', function ($charts) use ($host): bool {
                $chart = $charts->firstWhere('id', $host->id);

                return $chart
                    && $chart['labels'] === ['Hole 1', 'Hole 2']
                    && $chart['values'] === [1, -1];
            });
    }

    public function test_ended_session_keeps_history_charts_and_blocks_edits(): void
    {
        $host = User::factory()->create(['name' => 'Session Host']);
        $invitee = User::factory()->create(['name' => 'Guest Player']);
        $course = $this->createCourse();
        $firstHole = Hole::query()
            ->where('course_id', $course->id)
            ->where('layout_id', 37576)
            ->firstOrFail();
        $secondHole = $this->createHole($course, layoutId: 37576, layoutName: 'Hästhagen Främre', sortOrder: 2, number: 2, par: 4);
        $session = $this->createPlaySession($course, $host);

        $this->attachSessionPlayer($session, $host, selectedLayoutId: 37576);
        $this->attachSessionPlayer($session, $invitee, selectedLayoutId: 37576);

        PlaySessionScore::query()->create([
            'play_session_id' => $session->id,
            'user_id' => $host->id,
            'hole_id' => $firstHole->id,
            'hole_index' => 1,
            'strokes' => 4,
        ]);

        PlaySessionScore::query()->create([
            'play_session_id' => $session->id,
            'user_id' => $host->id,
            'hole_id' => $secondHole->id,
            'hole_index' => 2,
            'strokes' => 2,
        ]);

        $session->forceFill([
            'status' => 'ended',
            'ended_at' => now(),
        ])->save();

        $this->withSession(['current_player_id' => $host->id]);

        Livewire::withQueryParams([])
            ->test(PlaySessionPage::class, ['playSession' => $session->fresh()])
            ->assertSee('Ended')
            ->assertSee(__('ui.game.chart_title'))
            ->assertDontSee(route('sessions.game', $session), false)
            ->assertViewHas('scoreCharts', function ($charts) use ($host): bool {
                $chart = $charts->firstWhere('id', $host->id);

                return $chart
                    && $chart['labels'] === ['Hole 1', 'Hole 2']
                    && $chart['values'] === [1, -1];
            });

        Livewire::withQueryParams([])
            ->test(PlaySessionPage::class, ['playSession' => $session->fresh()])
            ->call('updateParticipantLayout', 'user-'.$host->id, '')
            ->assertForbidden();

        $this->assertDatabaseHas('play_session_scores', [
            'play_session_id' => $session->id,
            'user_id' => $host->id,
            'hole_index' => 1,
            'strokes' => 4,
        ]);

        $this->withSession(['current_player_id' => $invitee->id])
            ->get(route('sessions.index'))
            ->assertOk()
            ->assertSee(route('sessions.show', $session), false);

        $this->withSession(['current_player_id' => $invitee->id])
            ->get(route('sessions.show', $session))
            ->assertOk()
            ->assertSee('Ended')
            ->assertSee('Guest Player');
    }

    public function test_sessions_index_lists_active_sessions_first_with_active_tag(): void
    {
        $host = User::factory()->create(['name' => 'Session Host']);
        $endedCourse = $this->createCourse();
        $activeCourse = Course::query()->create([
            'name' => 'Active Course',
            'slug' => 'active-course',
            'udisc_url' => 'https://udisc.com/courses/active-course',
            'location_name' => 'Örebro, Sweden',
            'description' => 'Active course summary.',
            'holes_count' => 18,
            'rating' => 4.1,
            'ratings_count' => 42,
            'target_type' => 'DISCatcher Pro (original)',
            'photos' => [],
        ]);

        $endedSession = PlaySession::query()->create([
            'course_id' => $endedCourse->id,
            'host_id' => $host->id,
            'host_session_key' => null,
            'host_name' => $host->name,
            'status' => 'ended',
            'started_at' => now(),
            'ended_at' => now(),
        ]);

        $activeSession = PlaySession::query()->create([
            'course_id' => $activeCourse->id,
            'host_id' => $host->id,
            'host_session_key' => null,
            'host_name' => $host->name,
            'status' => 'active',
            'started_at' => now()->subDay(),
        ]);

        $this->attachSessionPlayer($endedSession, $host);
        $this->attachSessionPlayer($activeSession, $host);

        $this->withSession(['current_player_id' => $host->id])
            ->get(route('sessions.index'))
            ->assertOk()
            ->assertSee('badge--active-session', false)
            ->assertSeeInOrder(['Active Course', 'Active', 'Hästhagen', 'Ended']);
    }

    public function test_layout_switch_keeps_strokes_by_hole_index_and_recalculates_relative_score(): void
    {
        $host = User::factory()->create(['name' => 'Session Host']);
        $course = $this->createCourse();
        $frontHole = Hole::query()
            ->where('course_id', $course->id)
            ->where('layout_id', 37576)
            ->firstOrFail();
        $backHole = $this->createHole($course, layoutId: 44317, layoutName: 'Hästhagen Bakre', sortOrder: 1, number: 1, par: 5);
        $session = $this->createPlaySession($course, $host);

        $this->attachSessionPlayer($session, $host, selectedLayoutId: 37576);
        $this->withSession(['current_player_id' => $host->id]);

        Livewire::withQueryParams([])
            ->test(PlaySessionGamePage::class, ['playSession' => $session])
            ->call('saveScore', $host->id, '4')
            ->assertSet('scoreInputs.'.$host->id, '4')
            ->assertSee('+1');

        $this->assertDatabaseHas('play_session_scores', [
            'play_session_id' => $session->id,
            'user_id' => $host->id,
            'hole_id' => $frontHole->id,
            'hole_index' => 1,
            'strokes' => 4,
        ]);

        $session->players()->updateExistingPivot($host->id, [
            'selected_layout_id' => 44317,
            'updated_at' => now(),
        ]);

        Livewire::withQueryParams([])
            ->test(PlaySessionGamePage::class, ['playSession' => $session->fresh()])
            ->assertSet('scoreInputs.'.$host->id, '4')
            ->assertSee('Hästhagen Bakre')
            ->assertSee('-1');

        Livewire::withQueryParams([])
            ->test(PlaySessionPage::class, ['playSession' => $session->fresh()])
            ->assertViewHas('scoreCharts', function ($charts) use ($host): bool {
                $chart = $charts->firstWhere('id', $host->id);

                return $chart
                    && $chart['layout_name'] === 'Hästhagen Bakre'
                    && $chart['values'] === [-1];
            });

        Livewire::withQueryParams([])
            ->test(PlaySessionGamePage::class, ['playSession' => $session->fresh()])
            ->call('saveScore', $host->id, '3');

        $this->assertDatabaseMissing('play_session_scores', [
            'play_session_id' => $session->id,
            'user_id' => $host->id,
            'hole_id' => $frontHole->id,
            'hole_index' => 1,
        ]);

        $this->assertDatabaseHas('play_session_scores', [
            'play_session_id' => $session->id,
            'user_id' => $host->id,
            'hole_id' => $backHole->id,
            'hole_index' => 1,
            'strokes' => 3,
        ]);

        $this->assertSame(1, PlaySessionScore::query()
            ->where('play_session_id', $session->id)
            ->where('user_id', $host->id)
            ->where('hole_index', 1)
            ->count());
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
            ->call('updateParticipantLayout', 'user-'.$host->id, '44317')
            ->assertSee('Hästhagen Bakre');

        $this->assertDatabaseHas('play_session_user', [
            'play_session_id' => $session->id,
            'user_id' => $host->id,
            'selected_layout_id' => 44317,
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

    public function test_participant_cannot_update_another_players_layout(): void
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

        $session->players()->attach($invitee->id, [
            'status' => 'joined',
            'invited_at' => now(),
            'joined_at' => now(),
        ]);

        $this->withSession(['current_player_id' => $invitee->id]);

        Livewire::withQueryParams([])
            ->test(PlaySessionPage::class, ['playSession' => $session])
            ->call('updateParticipantLayout', 'user-'.$host->id, '44317')
            ->assertForbidden();

        $this->assertDatabaseHas('play_session_user', [
            'play_session_id' => $session->id,
            'user_id' => $host->id,
            'selected_layout_id' => null,
        ]);
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
            ->assertDontSee('Active sessions')
            ->assertDontSee(route('sessions.show', $session), false);

        $this->withSession(['current_player_id' => $inviteeOne->id])
            ->get(route('sessions.index'))
            ->assertOk()
            ->assertSee('Active')
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
            ->assertDontSee('Active sessions')
            ->assertDontSee(route('sessions.show', $session), false);

        $this->withSession(['current_player_id' => $inviteeTwo->id])
            ->get(route('sessions.index'))
            ->assertOk()
            ->assertSee('Active')
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

    protected function createPlaySession(Course $course, User $host): PlaySession
    {
        return PlaySession::query()->create([
            'course_id' => $course->id,
            'host_id' => $host->id,
            'host_session_key' => null,
            'host_name' => $host->name,
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    protected function attachSessionPlayer(PlaySession $session, User $player, ?int $selectedLayoutId = null, string $status = 'joined'): void
    {
        $session->players()->attach($player->id, [
            'status' => $status,
            'invited_at' => now(),
            'joined_at' => $status === 'joined' ? now() : null,
            'selected_layout_id' => $selectedLayoutId,
        ]);
    }

    protected function createHole(
        Course $course,
        int $layoutId,
        string $layoutName,
        int $sortOrder,
        int $number,
        int $par,
    ): Hole {
        return Hole::query()->create([
            'course_id' => $course->id,
            'layout_id' => $layoutId,
            'layout_name' => $layoutName,
            'layout_order' => $layoutId === 37576 ? 1 : 2,
            'sort_order' => $sortOrder,
            'number' => $number,
            'hole_label' => (string) $number,
            'par' => $par,
            'distance_meters' => 60 + $number,
        ]);
    }
}
