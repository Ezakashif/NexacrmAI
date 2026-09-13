<?php

namespace Tests\Unit\Models;

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserHelpersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_is_super_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($admin->isSuperAdmin());
    }

    public function test_is_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $sales = User::factory()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($sales->isAdmin());
    }

    public function test_is_active(): void
    {
        $active = User::factory()->create(['status' => 'active']);
        $inactive = User::factory()->inactive()->create();

        $this->assertTrue($active->isActive());
        $this->assertFalse($inactive->isActive());
    }

    public function test_status_badge_class(): void
    {
        $this->assertSame('success', User::factory()->make(['status' => 'active'])->statusBadgeClass());
        $this->assertSame('secondary', User::factory()->make(['status' => 'inactive'])->statusBadgeClass());
        $this->assertSame('danger', User::factory()->make(['status' => 'suspended'])->statusBadgeClass());
        $this->assertSame('secondary', User::factory()->make(['status' => 'unknown'])->statusBadgeClass());
    }

    public function test_role_badge_class(): void
    {
        $admin = User::factory()->admin()->create();
        $sales = User::factory()->create();

        $this->assertSame('primary', $admin->roleBadgeClass());
        $this->assertSame('info', $sales->roleBadgeClass());
    }

    public function test_photo_url_uses_gravatar_fallback_when_no_photo(): void
    {
        $user = User::factory()->make([
            'name' => 'Jane Doe',
            'photo_path' => null,
        ]);

        $url = $user->photoUrl();

        $this->assertStringContainsString('ui-avatars.com', $url);
        $this->assertStringContainsString('Jane+Doe', $url);
    }

    public function test_photo_url_uses_stored_path_when_file_exists(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/test.jpg', 'image-data');

        $user = User::factory()->create(['photo_path' => 'avatars/test.jpg']);

        $this->assertSame('/storage/avatars/test.jpg', $user->photoUrl());
    }

    public function test_update_and_remove_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg');

        $user->updatePhoto($file);

        $this->assertNotNull($user->fresh()->photo_path);
        Storage::disk('public')->assertExists($user->photo_path);

        $path = $user->photo_path;
        $user->removePhoto();

        $this->assertNull($user->fresh()->photo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_scope_search_matches_name_and_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Unique Searchable Name',
            'email' => 'searchable@example.com',
        ]);
        User::factory()->create([
            'name' => 'Other Person',
            'email' => 'other@example.com',
        ]);

        $byName = User::query()->search('Unique Searchable')->pluck('id');
        $byEmail = User::query()->search('searchable@example.com')->pluck('id');

        $this->assertTrue($byName->contains($user->id));
        $this->assertTrue($byEmail->contains($user->id));
        $this->assertSame(1, $byName->count());
    }

    public function test_scope_search_ignores_blank_term(): void
    {
        User::factory()->count(2)->create();

        $this->assertSame(2, User::query()->search(null)->count());
        $this->assertSame(2, User::query()->search('')->count());
    }

    public function test_scope_role_filters_by_legacy_role_column_mapping(): void
    {
        $admin = User::factory()->admin()->create();
        $sales = User::factory()->create();

        $adminIds = User::query()->role('admin')->pluck('id');
        $userIds = User::query()->role('user')->pluck('id');

        $this->assertTrue($adminIds->contains($admin->id));
        $this->assertTrue($userIds->contains($sales->id));
        $this->assertFalse($adminIds->contains($sales->id));
    }

    public function test_scope_status_filters_users(): void
    {
        $active = User::factory()->create(['status' => 'active']);
        User::factory()->inactive()->create();

        $ids = User::query()->status('active')->pluck('id');

        $this->assertTrue($ids->contains($active->id));
        $this->assertSame(1, $ids->count());
    }

    public function test_adminlte_helpers(): void
    {
        $user = User::factory()->make(['name' => 'Jane Doe']);

        $this->assertSame($user->photoUrl(), $user->adminlte_image());
        $this->assertSame('profile.edit', $user->adminlte_profile_url());
    }
}
