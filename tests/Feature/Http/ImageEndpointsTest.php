<?php

namespace Tests\Feature\Http;

use App\Domain\Image\ProcessingOutcome;
use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pins the image endpoints' HTTP contract (status codes and JSON shape the
 * frontend polls against). Queue-dispatch details live in
 * ProcessStandardImageJobTest.
 */
class ImageEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Queue::fake();
    }

    public function test_upload_requires_login(): void
    {
        $this->postJson('/api/images/upload', ['image' => UploadedFile::fake()->image('food.jpg')])
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    public function test_upload_without_credits_is_forbidden_and_stores_nothing(): void
    {
        $user = User::factory()->create(['credits' => 0]);

        $this->actingAs($user)
            ->postJson('/api/images/upload', ['image' => UploadedFile::fake()->image('food.jpg')])
            ->assertForbidden()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseCount('images', 0);
        Queue::assertNothingPushed();
    }

    public function test_concurrent_spend_cannot_drive_credits_negative(): void
    {
        $user = User::factory()->create(['credits' => 1]);
        // Another request spends the last credit after this request's user was loaded.
        User::whereKey($user->id)->update(['credits' => 0]);

        $this->actingAs($user) // stale model still says credits = 1
            ->postJson('/api/images/upload', ['image' => UploadedFile::fake()->image('food.jpg')])
            ->assertForbidden();

        $this->assertSame(0, $user->fresh()->credits);
        $this->assertDatabaseCount('images', 0);
        Queue::assertNothingPushed();
    }

    public function test_status_explains_the_outcome_to_the_merchant(): void
    {
        $user = User::factory()->create();
        $review = Image::create([
            'user_id' => $user->id, 'original_path' => 'images/original/r.jpg', 'processed_path' => 'images/processed/r.jpg',
            'status' => 'completed', 'segmentation_status' => 'REVIEW', 'validator_status' => 'PASS', 'is_infra_error' => false,
        ]);
        $rejected = Image::create([
            'user_id' => $user->id, 'original_path' => 'images/original/x.jpg',
            'status' => 'failed', 'segmentation_status' => 'REJECT', 'is_infra_error' => false,
        ]);

        $this->actingAs($user)->getJson("/api/images/{$review->id}/status")
            ->assertJsonPath('review_required', true)
            ->assertJsonPath('message', ProcessingOutcome::MESSAGE_REVIEW);

        $this->actingAs($user)->getJson("/api/images/{$rejected->id}/status")
            ->assertJsonPath('review_required', false)
            ->assertJsonPath('message', ProcessingOutcome::MESSAGE_UNSUPPORTED);
    }

    public function test_upload_rejects_non_images(): void
    {
        $user = User::factory()->create(['credits' => 1]);

        $this->actingAs($user)
            ->postJson('/api/images/upload', ['image' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')])
            ->assertUnprocessable();

        $this->assertSame(1, $user->fresh()->credits);
    }

    public function test_upload_returns_the_pending_image_and_remaining_credits(): void
    {
        $user = User::factory()->create(['credits' => 2]);

        $response = $this->actingAs($user)
            ->postJson('/api/images/upload', ['image' => UploadedFile::fake()->image('food.jpg'), 'prompt' => 'cafe']);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.remaining_credits', 1)
            ->assertJsonStructure(['message', 'data' => ['id', 'status', 'original_url', 'remaining_credits']]);

        $image = Image::findOrFail($response->json('data.id'));
        $this->assertSame($user->id, $image->user_id);
        $this->assertSame('cafe', $image->prompt);
        Storage::disk('public')->assertExists($image->original_path);
    }

    public function test_status_is_visible_to_the_owner(): void
    {
        $user = User::factory()->create();
        $image = Image::create([
            'user_id' => $user->id,
            'original_path' => 'images/original/a.jpg',
            'processed_path' => 'images/processed/a.jpg',
            'status' => 'completed',
        ]);

        $this->actingAs($user)->getJson("/api/images/{$image->id}/status")
            ->assertOk()
            ->assertJsonPath('id', $image->id)
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('original_url', asset('storage/images/original/a.jpg'))
            ->assertJsonPath('processed_url', asset('storage/images/processed/a.jpg'));
    }

    public function test_status_of_someone_elses_image_is_forbidden(): void
    {
        $owner = User::factory()->create();
        $image = Image::create(['user_id' => $owner->id, 'original_path' => 'images/original/a.jpg', 'status' => 'pending']);

        $this->actingAs(User::factory()->create())
            ->getJson("/api/images/{$image->id}/status")
            ->assertForbidden();
    }

    public function test_image_list_requires_login(): void
    {
        $this->getJson('/api/images')->assertUnauthorized();
    }

    public function test_image_list_returns_only_own_images_newest_first(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $older = Image::create(['user_id' => $user->id, 'original_path' => 'images/original/1.jpg', 'status' => 'completed', 'processed_path' => 'images/processed/1.jpg']);
        $newer = Image::create(['user_id' => $user->id, 'original_path' => 'images/original/2.jpg', 'status' => 'pending']);
        Image::create(['user_id' => $other->id, 'original_path' => 'images/original/3.jpg', 'status' => 'pending']);

        $response = $this->actingAs($user)->getJson('/api/images');

        $response->assertOk()
            ->assertJsonCount(2, 'images')
            ->assertJsonPath('images.0.id', $newer->id)
            ->assertJsonPath('images.0.processed_url', null)
            ->assertJsonPath('images.1.id', $older->id)
            ->assertJsonStructure(['images' => [['id', 'status', 'original_url', 'processed_url', 'created_at']]]);
    }

    public function test_menu_board_pdf_is_a_placeholder(): void
    {
        $this->getJson('/api/menu-boards/pdf')->assertOk()->assertJsonStructure(['message']);
    }
}
