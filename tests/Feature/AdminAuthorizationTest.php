<?php

namespace Tests\Feature;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

class AdminAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(OwnerAuthorizer::class, new class implements OwnerAuthorizer
        {
            public function authorize(?Authenticatable $user, string $ownerType, mixed $ownerId): bool
            {
                return false;
            }

            public function scope(Builder $query, ?Authenticatable $user, string $ownerTypeColumn = 'owner_type', string $ownerIdColumn = 'owner_id'): Builder
            {
                return $query->whereRaw('0 = 1');
            }
        });
    }

    #[Test]
    public function templates_are_hidden_and_writes_are_forbidden_when_authorization_denies(): void
    {
        $this->getJson('/api/admin/mail-templates')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/admin/mail-templates/welcome')->assertForbidden();
        $this->putJson('/api/admin/mail-templates/welcome', [
            'enabled' => true,
            'subject' => 'No',
            'body' => 'No',
        ])->assertForbidden();
    }

    #[Test]
    public function the_directory_and_the_report_are_forbidden_when_authorization_denies(): void
    {
        $this->getJson('/api/admin/users')->assertForbidden();
        $this->getJson('/api/admin/users/1')->assertForbidden();
        $this->getJson('/api/admin/metrics')->assertForbidden();
    }
}
