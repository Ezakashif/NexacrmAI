<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiPhase2CProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
    }

    public function test_profile_uses_shared_header_and_form_sections(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('crm-page-header', false)
            ->assertSee('crm-form-section', false)
            ->assertSee('crm-required', false)
            ->assertSee('Profile information')
            ->assertSee('Update password')
            ->assertSee('Delete account');
    }

    public function test_profile_photo_upload_supports_drag_and_crop(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('data-image-crop-upload', false)
            ->assertSee('Drop a photo here')
            ->assertSee('Adjust photo in frame')
            ->assertSee('Drag the photo to position it inside the frame')
            ->assertSee('image-crop-upload.js', false)
            ->assertSee('cropper.min.js', false);
    }

    public function test_profile_photo_remove_uses_confirm_attr(): void
    {
        $user = User::factory()->admin()->create([
            'photo_path' => 'profile-photos/demo.jpg',
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('data-crm-confirm', false)
            ->assertSee('Remove photo')
            ->assertDontSee("onclick=\"return confirm(", false);
    }

    public function test_profile_status_flashes_are_exposed_as_toasts(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->withSession(['status' => 'profile-updated'])
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Profile saved.', false)
            ->assertSee('crm-flash-data', false);
    }
}
