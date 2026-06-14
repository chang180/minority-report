<?php

use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can delete their own verification', function () {
    $user = User::factory()->create();
    $verification = VerificationRequest::create([
        'user_id' => $user->id,
        'question' => 'Delete me please?',
        'processing_status' => 'running',
    ]);

    $this->actingAs($user)
        ->delete(route('verifications.destroy', $verification))
        ->assertRedirect(route('verifications.index'))
        ->assertSessionHas('status', 'verification-deleted');

    expect(VerificationRequest::find($verification->id))->toBeNull();
});

test('user cannot delete another users verification', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $verification = VerificationRequest::create([
        'user_id' => $owner->id,
        'question' => 'Not yours to delete?',
        'processing_status' => 'completed',
    ]);

    $this->actingAs($attacker)
        ->delete(route('verifications.destroy', $verification))
        ->assertForbidden();

    expect(VerificationRequest::find($verification->id))->not->toBeNull();
});

test('user can clear all of their verifications', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    VerificationRequest::create(['user_id' => $user->id, 'question' => 'Mine one?', 'processing_status' => 'pending']);
    VerificationRequest::create(['user_id' => $user->id, 'question' => 'Mine two?', 'processing_status' => 'failed']);
    VerificationRequest::create(['user_id' => $other->id, 'question' => 'Others record?', 'processing_status' => 'completed']);

    $this->actingAs($user)
        ->delete(route('verifications.destroyAll'))
        ->assertRedirect(route('verifications.index'))
        ->assertSessionHas('status', 'verifications-cleared');

    expect(VerificationRequest::where('user_id', $user->id)->count())->toBe(0)
        ->and(VerificationRequest::where('user_id', $other->id)->count())->toBe(1);
});

test('admin clear all deletes every verification in the list scope', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();

    VerificationRequest::create(['user_id' => $admin->id, 'question' => 'Admin row?', 'processing_status' => 'completed']);
    VerificationRequest::create(['user_id' => $user->id, 'question' => 'User row?', 'processing_status' => 'completed']);

    $this->actingAs($admin)
        ->delete(route('verifications.destroyAll'))
        ->assertRedirect(route('verifications.index'));

    expect(VerificationRequest::count())->toBe(0);
});
