<?php

namespace Tests\Feature\NonFunctional;

use App\Models\Gig;
use App\Models\Lesson;
use App\Models\MentorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * File System Handling Tests.
 *
 * Validates:
 * - Cover image upload on gig creation uses Storage::fake('public')
 * - Old cover image is deleted when replaced with a new one
 * - PDF upload for lessons is stored and tracked
 * - Old PDF is cleaned up when replaced
 * - MIME type restrictions are enforced (only image/* for covers, only PDF for notes)
 */
class FileSystemHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeMentor(): User
    {
        $mentor = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);
        return $mentor;
    }

    private function validGigPayload(array $overrides = []): array
    {
        return array_merge([
            'title'                   => 'Test Gig',
            'description'             => 'A test gig description for testing purposes.',
            'what_to_expect'          => 'You will learn a lot.',
            'delivery_format'         => 'video_call',
            'experience_level'        => 'beginner',
            'duration_minutes'        => 60,
            'price'                   => 1500,
            'status'                  => 'draft',
            'max_sessions_per_week'   => 5,
            'booking_lead_time_hours' => 24,
        ], $overrides);
    }

    // ─── Cover Image Upload ───────────────────────────────────────────────────

    /** @test */
    public function gig_cover_image_is_stored_to_public_disk_on_creation(): void
    {
        $mentor = $this->makeMentor();
        $file   = UploadedFile::fake()->image('cover.jpg', 800, 600);

        $this->actingAs($mentor)
             ->post(route('mentor.gigs.store'), $this->validGigPayload([
                 'cover_image' => $file,
             ]))
             ->assertSessionHasNoErrors();

        Storage::disk('public')->assertExists('gig-covers/' . $file->hashName());
    }

    /** @test */
    public function gig_creation_without_cover_image_succeeds(): void
    {
        $mentor = $this->makeMentor();

        $this->actingAs($mentor)
             ->post(route('mentor.gigs.store'), $this->validGigPayload())
             ->assertSessionHasNoErrors();
    }

    /** @test */
    public function updating_gig_with_new_cover_image_deletes_old_image(): void
    {
        $mentor   = $this->makeMentor();
        $oldFile  = UploadedFile::fake()->image('old.jpg', 800, 600);
        $newFile  = UploadedFile::fake()->image('new.jpg', 800, 600);

        // Create gig with original image
        $this->actingAs($mentor)
             ->post(route('mentor.gigs.store'), $this->validGigPayload([
                 'cover_image' => $oldFile,
             ]));

        $gig = Gig::where('mentor_id', $mentor->id)->first();
        $this->assertNotNull($gig->cover_image);
        $oldPath = $gig->cover_image;

        Storage::disk('public')->assertExists($oldPath);

        // Update gig with new image
        $this->actingAs($mentor)
             ->patch(route('mentor.gigs.update', $gig), $this->validGigPayload([
                 'cover_image' => $newFile,
             ]));

        // Old image should be deleted
        Storage::disk('public')->assertMissing($oldPath);

        // New image should exist
        $updatedGig = $gig->fresh();
        $this->assertNotNull($updatedGig->cover_image);
        Storage::disk('public')->assertExists($updatedGig->cover_image);
    }

    /** @test */
    public function uploading_non_image_as_gig_cover_fails_validation(): void
    {
        $mentor     = $this->makeMentor();
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($mentor)
                         ->post(route('mentor.gigs.store'), $this->validGigPayload([
                             'cover_image' => $invalidFile,
                         ]));

        $response->assertSessionHasErrors(['cover_image']);
    }

    /** @test */
    public function cover_image_max_size_is_enforced(): void
    {
        $mentor      = $this->makeMentor();
        $oversizeFile = UploadedFile::fake()->image('huge.jpg')->size(3000); // 3MB, typically max is 2MB

        $response = $this->actingAs($mentor)
                         ->post(route('mentor.gigs.store'), $this->validGigPayload([
                             'cover_image' => $oversizeFile,
                         ]));

        // Should fail with file size validation error
        $response->assertSessionHasErrors(['cover_image']);
    }

    // ─── PDF Upload for Lessons ───────────────────────────────────────────────

    /** @test */
    public function lesson_pdf_is_stored_to_public_disk(): void
    {
        $mentor       = $this->makeMentor();
        $relationship = \App\Models\MentorshipRelationship::factory()->accepted()->between($mentor, User::factory()->freelancer()->create())->create();
        $course       = \App\Models\Course::factory()->forRelationship($relationship)->create();
        $module       = \App\Models\CourseModule::factory()->forCourse($course)->create();

        $pdfFile = UploadedFile::fake()->create('notes.pdf', 500, 'application/pdf');

        $this->actingAs($mentor)
             ->post(route('mentor.lms.lessons.store', $module), [
                 'title'      => 'Test Lesson',
                 'content'    => 'Lesson content here.',
                 'sort_order' => 1,
                 'pdf_path'   => $pdfFile,
             ])
             ->assertSessionHasNoErrors();

        Storage::disk('public')->assertExists('lesson-pdfs/' . $pdfFile->hashName());
    }

    /** @test */
    public function updating_lesson_with_new_pdf_deletes_old_pdf(): void
    {
        $mentor       = $this->makeMentor();
        $relationship = \App\Models\MentorshipRelationship::factory()->accepted()->between($mentor, User::factory()->freelancer()->create())->create();
        $course       = \App\Models\Course::factory()->forRelationship($relationship)->create();
        $module       = \App\Models\CourseModule::factory()->forCourse($course)->create();

        $oldPdf = UploadedFile::fake()->create('old-notes.pdf', 200, 'application/pdf');
        $newPdf = UploadedFile::fake()->create('new-notes.pdf', 300, 'application/pdf');

        // Create lesson with old PDF
        $this->actingAs($mentor)
             ->post(route('mentor.lms.lessons.store', $module), [
                 'title'      => 'PDF Lesson',
                 'content'    => 'Some content.',
                 'sort_order' => 1,
                 'pdf_path'   => $oldPdf,
             ]);

        $lesson  = Lesson::where('module_id', $module->id)->first();
        $oldPath = $lesson->pdf_path;
        Storage::disk('public')->assertExists($oldPath);

        // Update with new PDF
        $this->actingAs($mentor)
             ->patch(route('mentor.lms.lessons.update', $lesson), [
                 'title'      => 'PDF Lesson Updated',
                 'content'    => 'Updated content.',
                 'sort_order' => 1,
                 'pdf_path'   => $newPdf,
             ]);

        // Old PDF deleted
        Storage::disk('public')->assertMissing($oldPath);

        // New PDF exists
        $updatedLesson = $lesson->fresh();
        $this->assertNotNull($updatedLesson->pdf_path);
        Storage::disk('public')->assertExists($updatedLesson->pdf_path);
    }

    /** @test */
    public function deleting_lesson_pdf_removes_file_and_clears_pdf_path(): void
    {
        $mentor       = $this->makeMentor();
        $relationship = \App\Models\MentorshipRelationship::factory()->accepted()->between($mentor, User::factory()->freelancer()->create())->create();
        $course       = \App\Models\Course::factory()->forRelationship($relationship)->create();
        $module       = \App\Models\CourseModule::factory()->forCourse($course)->create();
        $pdfFile      = UploadedFile::fake()->create('to-delete.pdf', 100, 'application/pdf');

        $this->actingAs($mentor)
             ->post(route('mentor.lms.lessons.store', $module), [
                 'title'      => 'Lesson With PDF',
                 'content'    => 'Content.',
                 'sort_order' => 1,
                 'pdf_path'   => $pdfFile,
             ]);

        $lesson  = Lesson::where('module_id', $module->id)->first();
        $pdfPath = $lesson->pdf_path;
        Storage::disk('public')->assertExists($pdfPath);

        // Delete the PDF only
        $this->actingAs($mentor)
             ->delete(route('mentor.lms.lessons.pdf.destroy', $lesson))
             ->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($pdfPath);
        $this->assertNull($lesson->fresh()->pdf_path);
    }

    /** @test */
    public function uploading_non_pdf_as_lesson_pdf_fails_validation(): void
    {
        $mentor       = $this->makeMentor();
        $relationship = \App\Models\MentorshipRelationship::factory()->accepted()->between($mentor, User::factory()->freelancer()->create())->create();
        $course       = \App\Models\Course::factory()->forRelationship($relationship)->create();
        $module       = \App\Models\CourseModule::factory()->forCourse($course)->create();

        $imageFile = UploadedFile::fake()->image('cover.jpg');

        $response = $this->actingAs($mentor)
                         ->post(route('mentor.lms.lessons.store', $module), [
                             'title'      => 'Bad PDF Lesson',
                             'content'    => 'Content.',
                             'sort_order' => 1,
                             'pdf_path'   => $imageFile,
                         ]);

        $response->assertSessionHasErrors(['pdf_path']);
    }
}
