<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OperatorSetupTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_operator_setup_hashes_password_and_rejects_duplicates(): void
    {
        $this->artisan('watchpost:operator', ['email' => 'operator@example.com', 'name' => 'Operator'])->expectsQuestion('Password (at least 14 characters)', 'a-long-test-password')->assertSuccessful();
        $this->assertDatabaseHas('users', ['email' => 'operator@example.com']);
        $this->assertDatabaseMissing('users', ['password' => 'a-long-test-password']);
        $this->artisan('watchpost:operator', ['email' => 'operator@example.com', 'name' => 'Operator'])->expectsQuestion('Password (at least 14 characters)', 'a-long-test-password')->assertFailed();
        $this->assertDatabaseCount('users', 1);
    }
}
