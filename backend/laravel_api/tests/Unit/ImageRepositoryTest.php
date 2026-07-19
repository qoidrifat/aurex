<?php

namespace Tests\Unit;

use App\Models\Analysis;
use App\Models\Image;
use App\Models\User;
use App\Repositories\ImageRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ImageRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ImageRepository();
    }

    // ==================== findById ====================

    public function test_find_by_id_returns_image_when_exists()
    {
        $user = User::factory()->create();
        $image = Image::create([
            'user_id' => $user->id,
            'image_path' => 'selfies/test.jpg',
        ]);

        $found = $this->repository->findById($image->id);

        $this->assertInstanceOf(Image::class, $found);
        $this->assertEquals($image->id, $found->id);
        $this->assertEquals($user->id, $found->user_id);
        $this->assertEquals('selfies/test.jpg', $found->image_path);
    }

    public function test_find_by_id_returns_null_when_not_exists()
    {
        $found = $this->repository->findById(999);
        $this->assertNull($found);
    }

    // ==================== findOrFail ====================

    public function test_find_or_fail_returns_image_when_exists()
    {
        $user = User::factory()->create();
        $image = Image::create([
            'user_id' => $user->id,
            'image_path' => 'selfies/test.jpg',
        ]);

        $found = $this->repository->findOrFail($image->id);

        $this->assertInstanceOf(Image::class, $found);
        $this->assertEquals($image->id, $found->id);
    }

    public function test_find_or_fail_throws_exception_when_not_exists()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repository->findOrFail(999);
    }

    // ==================== create ====================

    public function test_create_image_with_valid_data()
    {
        $user = User::factory()->create();

        $image = $this->repository->create([
            'user_id' => $user->id,
            'image_path' => 'selfies/uploaded.jpg',
        ]);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals($user->id, $image->user_id);
        $this->assertEquals('selfies/uploaded.jpg', $image->image_path);
        $this->assertNull($image->analysis_id);
        $this->assertDatabaseHas('images', [
            'id' => $image->id,
            'user_id' => $user->id,
            'image_path' => 'selfies/uploaded.jpg',
        ]);
    }

    public function test_create_image_with_all_fields()
    {
        $user = User::factory()->create();
        $analysis = Analysis::factory()->for($user)->create();

        $image = $this->repository->create([
            'user_id' => $user->id,
            'image_path' => 'selfies/analyzed.jpg',
            'analysis_id' => $analysis->id,
        ]);

        $this->assertEquals($analysis->id, $image->analysis_id);
    }

    // ==================== attachToAnalysis ====================

    public function test_attach_to_analysis_updates_image()
    {
        $user = User::factory()->create();
        $analysis = Analysis::factory()->for($user)->create();
        $image = Image::create([
            'user_id' => $user->id,
            'image_path' => 'selfies/to_attach.jpg',
        ]);

        $result = $this->repository->attachToAnalysis($image->id, $analysis->id);

        $this->assertTrue($result);
        $image->refresh();
        $this->assertEquals($analysis->id, $image->analysis_id);
    }

    public function test_attach_to_analysis_returns_false_for_non_existent_image()
    {
        $result = $this->repository->attachToAnalysis(999, 1);

        $this->assertFalse($result);
    }

    // ==================== getByUserId ====================

    public function test_get_by_user_id_returns_user_images()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Image::create(['user_id' => $user->id, 'image_path' => 'selfies/user1_1.jpg']);
        Image::create(['user_id' => $user->id, 'image_path' => 'selfies/user1_2.jpg']);
        Image::create(['user_id' => $otherUser->id, 'image_path' => 'selfies/user2_1.jpg']);

        $userImages = $this->repository->getByUserId($user->id);

        $this->assertCount(2, $userImages);
        $this->assertEquals('selfies/user1_2.jpg', $userImages->first()->image_path); // latest first
    }

    public function test_get_by_user_id_returns_empty_for_no_images()
    {
        $user = User::factory()->create();

        $images = $this->repository->getByUserId($user->id);

        $this->assertCount(0, $images);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $images);
    }

    // ==================== deleteStorageFile ====================

    public function test_delete_storage_file_deletes_when_exists()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('test.jpg');
        $path = $file->store('selfies', 'public');

        $image = new Image();
        $image->image_path = $path;

        $this->assertTrue(Storage::disk('public')->exists($path));

        $result = $this->repository->deleteStorageFile($image);

        $this->assertTrue($result);
        $this->assertFalse(Storage::disk('public')->exists($path));
    }

    public function test_delete_storage_file_returns_false_when_file_not_exists()
    {
        $image = new Image();
        $image->image_path = 'selfies/non_existent.jpg';

        $result = $this->repository->deleteStorageFile($image);

        $this->assertFalse($result);
    }

    public function test_delete_storage_file_returns_false_when_path_is_null()
    {
        $image = new Image();
        $image->image_path = null;

        $result = $this->repository->deleteStorageFile($image);

        $this->assertFalse($result);
    }

    // ==================== deleteByUserId ====================

    public function test_soft_deletes_images_by_user_id()
    {
        $user = User::factory()->create();
        Image::create(['user_id' => $user->id, 'image_path' => 'selfies/del1.jpg']);
        Image::create(['user_id' => $user->id, 'image_path' => 'selfies/del2.jpg']);

        $deleted = $this->repository->deleteByUserId($user->id);

        $this->assertEquals(2, $deleted);
        $this->assertEquals(2, Image::onlyTrashed()->where('user_id', $user->id)->count());
    }

    public function test_soft_delete_images_does_not_affect_other_users()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Image::create(['user_id' => $user->id, 'image_path' => 'selfies/user1.jpg']);
        Image::create(['user_id' => $otherUser->id, 'image_path' => 'selfies/user2.jpg']);

        $this->repository->deleteByUserId($user->id);

        $this->assertEquals(0, Image::onlyTrashed()->where('user_id', $otherUser->id)->count());
        $this->assertEquals(1, Image::where('user_id', $otherUser->id)->count());
    }

    // ==================== forceDeleteByUserId ====================

    public function test_force_deletes_images_permanently()
    {
        $user = User::factory()->create();
        Image::create(['user_id' => $user->id, 'image_path' => 'selfies/fdel1.jpg']);
        Image::create(['user_id' => $user->id, 'image_path' => 'selfies/fdel2.jpg']);

        $deleted = $this->repository->forceDeleteByUserId($user->id);

        $this->assertEquals(2, $deleted);
        $this->assertEquals(0, Image::withTrashed()->where('user_id', $user->id)->count());
    }

    public function test_force_delete_returns_zero_for_no_images()
    {
        $user = User::factory()->create();

        $deleted = $this->repository->forceDeleteByUserId($user->id);

        $this->assertEquals(0, $deleted);
    }

    // ==================== Soft Delete Behavior ====================

    public function test_soft_deleted_images_not_in_find_by_id()
    {
        $user = User::factory()->create();
        $image = Image::create([
            'user_id' => $user->id,
            'image_path' => 'selfies/soft_deleted.jpg',
        ]);
        $image->delete(); // soft delete

        $found = $this->repository->findById($image->id);

        $this->assertNull($found);
    }
}
