<?php

namespace Tests\Feature;

use App\Models\Invite;
use App\Models\Script;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_first_signup_needs_no_invite_and_becomes_admin(): void
    {
        $this->post('/register', [
            'name' => 'First',
            'email' => 'first@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('scripts.index'));

        $this->assertTrue(User::where('email', 'first@example.com')->first()->isAdmin());
    }

    public function test_registration_is_blocked_without_a_valid_invite(): void
    {
        User::factory()->admin()->create();

        $this->post('/register', [
            'name' => 'Nope',
            'email' => 'nope@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('code');

        $this->post('/register', [
            'name' => 'Nope',
            'email' => 'nope@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'code' => 'BOGUS-CODE',
        ])->assertSessionHasErrors('code');

        $this->assertDatabaseMissing('users', ['email' => 'nope@example.com']);
    }

    public function test_a_valid_invite_allows_registration_once(): void
    {
        $admin = User::factory()->admin()->create();
        $invite = Invite::create(['code' => 'ABCD-EFGH', 'created_by' => $admin->id]);

        $this->post('/register', [
            'name' => 'Guest',
            'email' => 'guest@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'code' => 'abcd-efgh', // case-insensitive
        ])->assertRedirect(route('scripts.index'));

        $user = User::where('email', 'guest@example.com')->firstOrFail();
        $this->assertFalse($user->isAdmin());
        $this->assertNotNull($invite->fresh()->used_at);

        $this->post('/logout');

        $this->post('/register', [
            'name' => 'Second',
            'email' => 'second@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'code' => 'ABCD-EFGH',
        ])->assertSessionHasErrors('code');
    }

    public function test_expired_invites_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        Invite::create(['code' => 'OLD-CODE', 'created_by' => $admin->id, 'expires_at' => now()->subDay()]);

        $this->post('/register', [
            'name' => 'Late',
            'email' => 'late@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'code' => 'OLD-CODE',
        ])->assertSessionHasErrors('code');
    }

    public function test_users_only_see_their_own_scripts(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();

        $a = Script::create(['user_id' => $mine->id, 'title' => 'Mine', 'body' => 'A: hi']);
        $b = Script::create(['user_id' => $theirs->id, 'title' => 'Theirs', 'body' => 'B: hi']);

        $this->actingAs($mine)->get('/')
            ->assertSee('Mine')
            ->assertDontSee('Theirs');

        $this->actingAs($mine)->get(route('scripts.show', $a))->assertOk();
        $this->actingAs($mine)->get(route('scripts.show', $b))->assertForbidden();
        $this->actingAs($mine)->delete(route('scripts.destroy', $b))->assertForbidden();
        $this->actingAs($mine)->delete(route('recordings.destroy', ['script' => $b->id, 'lineIndex' => 0]))
            ->assertForbidden();
    }

    public function test_unowned_scripts_are_admin_only(): void
    {
        $member = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $orphan = Script::create(['title' => 'Legacy', 'body' => 'A: hi']);

        $this->actingAs($member)->get(route('scripts.show', $orphan))->assertForbidden();
        $this->actingAs($admin)->get(route('scripts.show', $orphan))->assertOk();
    }

    public function test_admin_can_reassign_a_script_owner(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $orphan = Script::create(['title' => 'Legacy', 'body' => 'A: hi']);

        $this->actingAs($admin)
            ->put(route('admin.scripts.owner', $orphan), ['user_id' => $member->id])
            ->assertRedirect();

        $this->assertSame($member->id, $orphan->fresh()->user_id);

        $this->actingAs($member)->get(route('scripts.show', $orphan))->assertOk();
    }

    public function test_admin_can_bulk_claim_unowned_scripts(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        Script::create(['title' => 'One', 'body' => 'A: hi']);
        Script::create(['title' => 'Two', 'body' => 'B: hi']);

        $this->actingAs($admin)
            ->post(route('admin.scripts.claim'), ['user_id' => $member->id])
            ->assertRedirect();

        $this->assertSame(2, Script::where('user_id', $member->id)->count());
    }

    public function test_members_cannot_reach_the_admin_area(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($member)->get(route('admin.invites.index'))->assertForbidden();
        $this->actingAs($member)->get(route('admin.scripts.index'))->assertForbidden();
    }

    public function test_admin_can_create_and_revoke_invites(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.invites.store'), ['note' => 'For Ivan'])
            ->assertRedirect();

        $invite = Invite::firstOrFail();
        $this->assertSame($admin->id, $invite->created_by);

        $this->actingAs($admin)->delete(route('admin.invites.destroy', $invite))->assertRedirect();
        $this->assertSame(0, Invite::count());
    }

    public function test_the_last_admin_cannot_be_demoted_or_deleted(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
        ])->assertSessionHasErrors('is_admin');

        $this->assertTrue($admin->fresh()->isAdmin());

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))
            ->assertSessionHasErrors('user');
    }

    public function test_deleting_a_user_can_reassign_their_scripts(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $script = Script::create(['user_id' => $member->id, 'title' => 'Mine', 'body' => 'A: hi']);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $member), [
            'scripts' => 'reassign',
            'new_owner_id' => $admin->id,
        ])->assertRedirect();

        $this->assertSame($admin->id, $script->fresh()->user_id);
        $this->assertDatabaseMissing('users', ['id' => $member->id]);
    }

    public function test_deleting_a_user_leaves_kept_scripts_unowned(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $script = Script::create(['user_id' => $member->id, 'title' => 'Mine', 'body' => 'A: hi']);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $member), ['scripts' => 'keep'])
            ->assertRedirect();

        $this->assertNull($script->fresh()->user_id);
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->create(['email' => 'me@example.com']);

        $this->post('/login', ['email' => 'me@example.com', 'password' => 'password'])
            ->assertRedirect(route('scripts.index'));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
