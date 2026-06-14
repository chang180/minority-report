<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guest can register and is redirected to dashboard', function () {
    $response = $this->post('/register', [
        'name' => 'M7 User',
        'email' => 'm7@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()->where('email', 'm7@example.com')->firstOrFail();

    $response->assertRedirect('/dashboard');

    expect($this->app['auth']->user()?->is($user))->toBeTrue()
        ->and($user->role)->toBe('user');
});

test('user can login and logout', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
    ]);

    $this->post('/login', [
        'email' => 'login@example.com',
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);

    $this->post('/logout')->assertRedirect('/');

    $this->assertGuest();
});

test('guest is redirected away from authenticated pages', function () {
    $this->get('/dashboard')
        ->assertRedirect('/login');

    $this->get('/settings/profile')
        ->assertRedirect('/login');
});

test('admin middleware only allows admin users', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertRedirect('/dashboard');
});

test('admin seeder uses configured credentials', function () {
    config()->set('auth.admin.email', 'admin@example.com');
    config()->set('auth.admin.password', 'secret-password');

    Artisan::call('db:seed', [
        '--class' => 'Database\\Seeders\\AdminUserSeeder',
    ]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    expect($admin->isAdmin())->toBeTrue();
});

test('password reset link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'reset@example.com',
    ]);

    $this->post('/forgot-password', [
        'email' => 'reset@example.com',
    ])->assertRedirect();

    Notification::assertSentTo($user, ResetPassword::class);
});

test('auth pages render through inertia', function () {
    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Login')
            ->has('canResetPassword')
        );

    $this->get('/register')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Register')
            ->has('passwordRules')
        );
});
