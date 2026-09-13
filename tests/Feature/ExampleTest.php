<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\SupportCase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    private array $browserHeaders = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/140.0 Safari/537.36',
    ];

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->withInvitation()->get('/');

        $response->assertStatus(200)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet')
            ->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_users_from_japan_receive_japanese_translation_mode(): void
    {
        $this->withInvitation()
            ->withHeaders(['CF-IPCountry' => 'JP'])
            ->get('/')
            ->assertOk()
            ->assertSee('<html lang="ja">', false)
            ->assertSee('translate.google.com/translate_a/element.js', false);
    }

    public function test_contact_submission_stores_data_and_redirects_to_password_verification(): void
    {
        $response = $this->withInvitation()->post('/contact', [
            'email' => 'user@example.com',
            'username' => '@example_user',
            'message' => 'I need help with my account.',
        ]);

        $response->assertRedirect('/identity');
        $this->assertSame('user@example.com', session('contact.email'));
        $this->assertTrue(session('password_verified'));
        $this->assertDatabaseHas('support_cases', [
            'email' => 'user@example.com',
            'username' => '@example_user',
            'status' => 'received',
            'access_enabled' => false,
        ]);
    }

    public function test_identity_continue_requires_admin_grant(): void
    {
        $this->withInvitation()->post('/contact', [
            'email' => 'user@example.com',
            'username' => '@example_user',
            'message' => 'I need help with my account.',
        ]);

        $supportCase = SupportCase::firstOrFail();

        $this->withInvitation()
            ->withSession([
                'invitation_accepted' => true,
                'contact' => ['email' => 'user@example.com'],
                'support_case_id' => $supportCase->id,
                'password_verified' => true,
            ])
            ->get('/identity/continue')
            ->assertRedirect('/identity')
            ->assertSessionHas('grant_error');

        $this->withHeaders($this->browserHeaders)
            ->withSession(['admin_authenticated' => true])
            ->post('/admin/cases/'.$supportCase->id.'/grant')
            ->assertRedirect('/admin/cases/'.$supportCase->id);

        $this->assertDatabaseHas('support_cases', [
            'id' => $supportCase->id,
            'access_enabled' => true,
        ]);

        $this->withInvitation()
            ->withSession([
                'invitation_accepted' => true,
                'contact' => ['email' => 'user@example.com'],
                'support_case_id' => $supportCase->id,
                'password_verified' => true,
            ])
            ->get('/identity/continue')
            ->assertRedirect('/verification-code');
    }

    public function test_verification_code_is_hidden_until_admin_grants_access(): void
    {
        config(['app.verification_code' => '987654']);
        $supportCase = SupportCase::create([
            'email' => 'user@example.com',
            'username' => '@example_user',
            'message' => 'Need help',
            'status' => 'identity_verified',
            'access_enabled' => false,
        ]);

        $this->withInvitation()
            ->withSession([
                'contact' => ['email' => 'user@example.com'],
                'support_case_id' => $supportCase->id,
                'password_verified' => true,
            ])
            ->get('/verification-code')
            ->assertOk()
            ->assertSee('Please follow the instructions above and wait for the administrator to grant access before viewing the verification code.')
            ->assertDontSee('987654');
    }

    public function test_contact_submission_requires_valid_input(): void
    {
        $response = $this->withInvitation()->from('/contact')->post('/contact', [
            'email' => 'not-an-email',
            'username' => '',
            'message' => '',
        ]);

        $response->assertRedirect('/contact');
        $response->assertSessionHasErrors(['email', 'username', 'message']);
    }

    public function test_known_crawlers_are_rejected(): void
    {
        $response = $this->withInvitation()->withHeaders([
            'User-Agent' => 'Mozilla/5.0 compatible; ExampleBot/1.0',
        ])->get('/');

        $response->assertForbidden();
    }

    public function test_robots_file_disallows_crawling(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk()
            ->assertSeeText("User-agent: *\nDisallow: /");
    }

    public function test_direct_access_without_an_invitation_is_not_found(): void
    {
        $response = $this->withHeaders($this->browserHeaders)->get('/');

        $response->assertNotFound();
    }

    public function test_signed_invitation_grants_access(): void
    {
        $url = URL::temporarySignedRoute(
            'invite.accept',
            now()->addHour(),
            ['token' => 'test-invitation']
        );

        $response = $this->withHeaders($this->browserHeaders)->get($url);

        $response->assertRedirect('/');
        $this->withHeaders($this->browserHeaders)->get('/')->assertOk();
    }

    public function test_expired_invitation_does_not_grant_access(): void
    {
        $url = URL::temporarySignedRoute(
            'invite.accept',
            now()->subMinute(),
            ['token' => 'expired-invitation']
        );

        $this->withHeaders($this->browserHeaders)->get($url)->assertForbidden();
    }

    public function test_admin_dashboard_requires_admin_authentication(): void
    {
        $this->withHeaders($this->browserHeaders)
            ->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_admin_can_generate_an_invitation_link(): void
    {
        $this->withHeaders($this->browserHeaders)
            ->post('/admin/login', ['access_key' => config('app.admin_access_key')])
            ->assertRedirect('/admin');

        $response = $this->withHeaders($this->browserHeaders)
            ->post('/admin/invites', ['hours' => 24]);

        $response->assertOk()
            ->assertSee('Invitation link generated')
            ->assertSee('/caseid/');
    }

    public function test_admin_login_rejects_an_invalid_key(): void
    {
        $this->withHeaders($this->browserHeaders)
            ->from('/admin/login')
            ->post('/admin/login', ['access_key' => 'wrong-key'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('access_key');
    }

    public function test_admin_can_update_the_support_email_displayed_to_users(): void
    {
        $this->withHeaders($this->browserHeaders)
            ->post('/admin/login', ['access_key' => config('app.admin_access_key')])
            ->assertRedirect('/admin');

        $this->withHeaders($this->browserHeaders)
            ->withSession(['admin_authenticated' => true])
            ->post('/admin/support-email', ['support_email' => 'custom-support@example.com'])
            ->assertRedirect('/admin');

        config(['app.support_email' => 'custom-support@example.com']);

        $supportCase = SupportCase::create([
            'email' => 'user@example.com',
            'username' => '@example_user',
            'message' => 'Need help',
            'status' => 'received',
            'access_enabled' => true,
        ]);

        $this->withInvitation()
            ->withSession([
                'invitation_accepted' => true,
                'contact' => [
                    'email' => 'user@example.com',
                    'username' => '@example_user',
                    'message' => 'Need help',
                ],
                'support_case_id' => $supportCase->id,
                'password_verified' => true,
            ])
            ->get('/identity')
            ->assertOk()
            ->assertSee('custom-support@example.com');
    }

    public function test_admin_can_update_the_verification_code_displayed_to_users(): void
    {
        $this->withHeaders($this->browserHeaders)
            ->post('/admin/login', ['access_key' => config('app.admin_access_key')])
            ->assertRedirect('/admin');

        $this->withHeaders($this->browserHeaders)
            ->withSession(['admin_authenticated' => true])
            ->post('/admin/verification-code', ['verification_code' => '987654'])
            ->assertRedirect('/admin');

        config(['app.verification_code' => '987654']);

        $supportCase = SupportCase::create([
            'email' => 'user@example.com',
            'username' => '@example_user',
            'message' => 'Need help',
            'status' => 'received',
            'access_enabled' => true,
        ]);

        $this->withInvitation()
            ->withSession([
                'invitation_accepted' => true,
                'contact' => [
                    'email' => 'user@example.com',
                    'username' => '@example_user',
                    'message' => 'Need help',
                ],
                'support_case_id' => $supportCase->id,
                'password_verified' => true,
            ])
            ->get('/verification-code')
            ->assertOk()
            ->assertSee('987654');
    }

    public function test_verified_user_and_admin_can_exchange_messages(): void
    {
        $supportCase = SupportCase::create([
            'email' => 'user@example.com',
            'username' => '@example_user',
            'message' => 'Please help.',
            'status' => 'verified',
        ]);

        $this->withInvitation()
            ->withSession([
                'support_case_id' => $supportCase->id,
                'verification_complete' => true,
            ])
            ->post('/messages/typing')
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->withInvitation()
            ->withSession([
                'support_case_id' => $supportCase->id,
                'verification_complete' => true,
            ])
            ->postJson('/messages', ['body' => 'Hello support'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('support_messages', [
            'support_case_id' => $supportCase->id,
            'sender' => 'user',
            'body' => 'Hello support',
        ]);

        $this->withInvitation()
            ->withSession([
                'support_case_id' => $supportCase->id,
                'verification_complete' => true,
            ])
            ->postJson('/messages', ['body' => 'Hello support'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(1, $supportCase->messages()->where('sender', 'user')->where('body', 'Hello support')->count());

        $this->withInvitation()
            ->withSession([
                'support_case_id' => $supportCase->id,
                'verification_complete' => true,
            ])
            ->get('/messages/poll')
            ->assertOk()
            ->assertJsonFragment(['body' => 'Hello support']);

        $this->withHeaders($this->browserHeaders)
            ->post('/admin/login', ['access_key' => config('app.admin_access_key')]);

        $this->withHeaders($this->browserHeaders)
            ->withSession(['admin_authenticated' => true])
            ->get('/admin/cases/'.$supportCase->id.'/typing')
            ->assertOk()
            ->assertJson(['typing' => true]);

        $this->withHeaders($this->browserHeaders)
            ->withSession(['admin_authenticated' => true])
            ->post('/admin/cases/'.$supportCase->id.'/typing')
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->withInvitation()
            ->withSession([
                'support_case_id' => $supportCase->id,
                'verification_complete' => true,
            ])
            ->get('/messages/typing')
            ->assertOk()
            ->assertJson(['typing' => true]);

        $this->withHeaders($this->browserHeaders)
            ->post('/admin/cases/'.$supportCase->id.'/messages', ['body' => 'How can we help?'])
            ->assertRedirect('/admin/cases/'.$supportCase->id);

        $this->assertDatabaseHas('support_messages', [
            'support_case_id' => $supportCase->id,
            'sender' => 'admin',
            'body' => 'How can we help?',
        ]);

        $this->withHeaders($this->browserHeaders)
            ->withSession(['admin_authenticated' => true])
            ->get('/admin/cases/'.$supportCase->id.'/messages')
            ->assertOk()
            ->assertJsonFragment(['body' => 'How can we help?']);
    }

    public function test_admin_can_open_a_clickable_user_record(): void
    {
        $supportCase = SupportCase::create([
            'email' => 'details@example.com',
            'username' => '@details_user',
            'message' => 'Full case details.',
        ]);

        $this->withHeaders($this->browserHeaders)
            ->withSession(['admin_authenticated' => true])
            ->get('/admin/cases/'.$supportCase->id)
            ->assertOk()
            ->assertSee('details@example.com')
            ->assertSee('Full case details.')
            ->assertSee('User details');
    }

    public function test_admin_can_end_a_user_session_and_revoke_access(): void
    {
        $supportCase = SupportCase::create([
            'email' => 'revoked@example.com',
            'username' => '@revoked_user',
            'message' => 'End this session.',
            'status' => 'verified',
        ]);
        $supportCase->messages()->create([
            'sender' => 'user',
            'body' => 'Remove this message too.',
        ]);

        $this->withHeaders($this->browserHeaders)
            ->withSession(['admin_authenticated' => true])
            ->post('/admin/cases/'.$supportCase->id.'/end-session')
            ->assertRedirect('/admin');

        $this->assertDatabaseMissing('support_cases', ['id' => $supportCase->id]);
        $this->assertDatabaseMissing('support_messages', [
            'support_case_id' => $supportCase->id,
        ]);

        $this->withInvitation()
            ->withSession(['support_case_id' => $supportCase->id])
            ->get('/messages/poll')
            ->assertNotFound();
    }

    private function withInvitation(): static
    {
        return $this->withHeaders($this->browserHeaders)
            ->withSession(['invitation_accepted' => true]);
    }
        }
