<?php

namespace Tests\Feature;

use App\Models\Download;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_downloads_index(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.downloads.index'));

        $response->assertOk();
        $response->assertSee('Downloads');
    }

    public function test_admin_can_create_download(): void
    {
        $admin = User::factory()->admin()->create();

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($admin)->post(route('admin.downloads.store'), [
            'name' => 'A320 Livery Pack',
            'category' => 'Liveries',
            'version' => 'v1.0',
            'file' => $file,
            'description' => 'High resolution livery pack.',
        ]);

        $response->assertRedirect(route('admin.downloads.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('downloads', [
            'name' => 'A320 Livery Pack',
            'category' => 'Liveries',
            'version' => 'v1.0',
        ]);
        $this->assertNotNull(Download::first()->file_path);
        $this->assertGreaterThan(0, Download::first()->size_bytes);
    }

    public function test_admin_can_update_download(): void
    {
        $admin = User::factory()->admin()->create();
        $download = Download::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($admin)->put(route('admin.downloads.update', $download), [
            'name' => 'New Name',
            'category' => 'Documents',
            'version' => 'v2.0',
            'description' => 'Updated description.',
        ]);

        $response->assertRedirect(route('admin.downloads.index'));
        $this->assertDatabaseHas('downloads', [
            'id' => $download->id,
            'name' => 'New Name',
            'category' => 'Documents',
            'version' => 'v2.0',
            'description' => 'Updated description.',
        ]);
    }

    public function test_admin_can_delete_download(): void
    {
        $admin = User::factory()->admin()->create();
        $download = Download::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.downloads.destroy', $download));

        $response->assertRedirect(route('admin.downloads.index'));
        $this->assertDatabaseMissing('downloads', ['id' => $download->id]);
    }

    public function test_approved_pilot_can_download_file(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);
        $download = Download::factory()->create([
            'file_path' => 'test_file.pdf',
            'name' => 'Test File',
        ]);

        Storage::disk('local')->put('downloads/test_file.pdf', 'fake file content');

        $response = $this->actingAs($user)->get(route('downloads.download', $download));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="Test File"');
    }

    public function test_guest_cannot_download(): void
    {
        $download = Download::factory()->create();

        $this->get(route('downloads.download', $download))->assertRedirect(route('login'));
    }

    public function test_unapproved_pilot_cannot_download(): void
    {
        $user = User::factory()->create(['approval_status' => User::APPROVAL_PENDING]);
        $download = Download::factory()->create();

        $this->actingAs($user)->get(route('downloads.download', $download))->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_admin_downloads(): void
    {
        $pilot = User::factory()->create(['approval_status' => User::APPROVAL_APPROVED]);

        $this->actingAs($pilot)->get(route('admin.downloads.index'))->assertForbidden();
    }

    public function test_download_requires_file(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.downloads.store'), [
            'name' => 'Test Download',
            'category' => 'Documents',
        ])->assertSessionHasErrors('file');
    }
}
