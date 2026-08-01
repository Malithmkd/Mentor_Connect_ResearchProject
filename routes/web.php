<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\GigManagementController;
use App\Http\Controllers\Admin\RegistrationApprovalController;
use App\Http\Controllers\Admin\SkillManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Freelancer\DashboardController as FreelancerDashboardController;
use App\Http\Controllers\GigController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Lms\CourseController;
use App\Http\Controllers\Lms\FreelancerLmsController;
use App\Http\Controllers\Lms\ModuleLessonController;
use App\Http\Controllers\Lms\MentorshipRelationshipController;
use App\Http\Controllers\Mentor\DashboardController as MentorDashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SkillOnboardingController;
use Illuminate\Support\Facades\Route;

/**
 * Web Routes
 * Laravel 11 — clean, role-based routing with middleware chains
 */

/* ═══ Public Routes ═══ */
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/mentors', [GigController::class, 'index'])->name('gigs.index');
Route::get('/mentors/{slug}', [GigController::class, 'show'])->name('gigs.show');

/* ═══ Auth Routes (Guest Only) ═══ */
Route::middleware('guest')->group(function () {
    // Registration
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    // Login
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    // Password Reset
    Route::get('/forgot-password', [PasswordResetController::class, 'forgotShow'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotStore'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetShow'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetStore'])->name('password.store');
});

/* ═══ Auth Routes (Authenticated) ═══ */
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Account approval status pages (shown to non-approved users — no auth needed)
    Route::get('/account/pending',  fn() => view('auth.pending-approval'))->name('approval.pending');
    Route::get('/account/rejected', fn() => view('auth.rejected'))->name('approval.rejected');

    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])->name('profile.avatar');
    Route::get('/users/{user}/profile', [ProfileController::class, 'view'])->name('users.profile');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.count');

    /* ─── Freelancer Routes ─── */
    Route::middleware('role:freelancer')->group(function () {
        Route::get('/dashboard', [FreelancerDashboardController::class, 'index'])->name('freelancer.dashboard');

        // Bookings
        Route::get('/bookings', [BookingController::class, 'freelancerIndex'])->name('freelancer.bookings.index');
        Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('freelancer.bookings.show');

        // Create booking
        Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    });

    /* ─── Mentor Routes ─── */
    Route::middleware('role:mentor')->group(function () {
        Route::get('/mentor/dashboard', [MentorDashboardController::class, 'index'])->name('mentor.dashboard');

        // Bookings management
        Route::get('/mentor/bookings', [BookingController::class, 'mentorIndex'])->name('mentor.bookings.index');
        Route::get('/mentor/bookings/{booking}', [BookingController::class, 'show'])->name('mentor.bookings.show');

        // Gig CRUD
        Route::get('/mentor/gigs', [GigController::class, 'mentorIndex'])->name('mentor.gigs.index');
        Route::get('/mentor/gigs/create', [GigController::class, 'create'])->name('mentor.gigs.create');
        Route::post('/mentor/gigs', [GigController::class, 'store'])->name('mentor.gigs.store');
        Route::get('/mentor/gigs/{gig}/edit', [GigController::class, 'edit'])->name('mentor.gigs.edit');
        Route::patch('/mentor/gigs/{gig}', [GigController::class, 'update'])->name('mentor.gigs.update');
        Route::delete('/mentor/gigs/{gig}', [GigController::class, 'destroy'])->name('mentor.gigs.destroy');
        Route::post('/mentor/gigs/{id}/restore', [GigController::class, 'restore'])->name('mentor.gigs.restore');
    });

    /* ─── Booking Status & Review Management (Mentor + Freelancer) ─── */
    Route::patch('/bookings/{booking}/status', [BookingController::class, 'updateStatus'])->name('bookings.status');
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/bookings/{booking}/review', [BookingController::class, 'review'])->name('bookings.review');

    /* ─── Admin Routes ─── */
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        // Registration approval queue
        Route::get('/admin/approvals',                          [RegistrationApprovalController::class, 'index'])->name('admin.approvals.index');
        Route::patch('/admin/approvals/{user}/approve',         [RegistrationApprovalController::class, 'approve'])->name('admin.approvals.approve');
        Route::patch('/admin/approvals/{user}/reject',          [RegistrationApprovalController::class, 'reject'])->name('admin.approvals.reject');
        Route::patch('/admin/approvals/{user}/reopen',          [RegistrationApprovalController::class, 'reopen'])->name('admin.approvals.reopen');

        // User management — Mentors
        Route::get('/admin/mentors', [UserManagementController::class, 'mentors'])->name('admin.users.mentors');

        // User management — Freelancers
        Route::get('/admin/freelancers', [UserManagementController::class, 'freelancers'])->name('admin.users.freelancers');

        // Individual user profile
        Route::get('/admin/users/{user}', [UserManagementController::class, 'show'])->name('admin.users.show');

        // Toggle active / disabled
        Route::patch('/admin/users/{user}/toggle', [UserManagementController::class, 'toggleStatus'])->name('admin.users.toggle');

        // Permanently remove account
        Route::delete('/admin/users/{user}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');

        // Audit log
        Route::get('/admin/audit-log', [AuditLogController::class, 'index'])->name('admin.audit-log');

        // Gig management
        Route::get('/admin/gigs', [GigManagementController::class, 'index'])->name('admin.gigs.index');

        // Skill management (CRUD)
        Route::resource('/admin/skills', SkillManagementController::class)->names([
            'index'   => 'admin.skills.index',
            'create'  => 'admin.skills.create',
            'store'   => 'admin.skills.store',
            'edit'    => 'admin.skills.edit',
            'update'  => 'admin.skills.update',
            'destroy' => 'admin.skills.destroy',
        ]);
    });

    /* ─── LMS: Freelancer ─── */
    Route::middleware('role:freelancer')->prefix('lms')->name('lms.')->group(function () {
        // Send a long-term request from a completed booking
        Route::post('relationships', [MentorshipRelationshipController::class, 'requestLongTerm'])
            ->name('relationships.request');

        // Renew an existing accepted mentorship
        Route::post('relationships/{relationship}/renew', [MentorshipRelationshipController::class, 'renew'])
            ->name('relationships.renew');

        // Enrolled courses dashboard
        Route::get('', [FreelancerLmsController::class, 'index'])->name('index');

        // Course overview + progress
        Route::get('courses/{enrollment}', [FreelancerLmsController::class, 'showCourse'])->name('course');

        // Lesson viewer
        Route::get('courses/{enrollment}/lessons/{lesson}', [FreelancerLmsController::class, 'showLesson'])->name('lesson');

        // Mark lesson complete
        Route::post('courses/{enrollment}/lessons/{lesson}/complete', [FreelancerLmsController::class, 'completeLesson'])->name('lesson.complete');

        // Progress analytics
        Route::get('courses/{enrollment}/progress', [FreelancerLmsController::class, 'showProgress'])->name('progress');
    });

    /* ─── LMS: Mentor ─── */
    Route::middleware('role:mentor')->prefix('mentor/lms')->name('mentor.lms.')->group(function () {
        // Relationships list + accept/decline
        Route::get('relationships', [MentorshipRelationshipController::class, 'index'])
            ->name('relationships.index');
        Route::patch('relationships/{relationship}/accept', [MentorshipRelationshipController::class, 'accept'])
            ->name('relationships.accept');
        Route::patch('relationships/{relationship}/decline', [MentorshipRelationshipController::class, 'decline'])
            ->name('relationships.decline');

        // Courses
        Route::get('relationships/{relationship}/courses', [CourseController::class, 'index'])
            ->name('courses.index');
        Route::get('relationships/{relationship}/courses/create', [CourseController::class, 'create'])
            ->name('courses.create');
        Route::post('relationships/{relationship}/courses', [CourseController::class, 'store'])
            ->name('courses.store');
        Route::get('courses/{course}', [CourseController::class, 'show'])
            ->name('courses.show');
        Route::get('courses/{course}/edit', [CourseController::class, 'edit'])
            ->name('courses.edit');
        Route::patch('courses/{course}', [CourseController::class, 'update'])
            ->name('courses.update');
        Route::patch('courses/{course}/publish', [CourseController::class, 'publish'])
            ->name('courses.publish');
        Route::delete('courses/{course}', [CourseController::class, 'destroy'])
            ->name('courses.destroy');

        // Modules
        Route::post('courses/{course}/modules', [ModuleLessonController::class, 'storeModule'])
            ->name('modules.store');
        Route::patch('modules/{module}', [ModuleLessonController::class, 'updateModule'])
            ->name('modules.update');
        Route::delete('modules/{module}', [ModuleLessonController::class, 'destroyModule'])
            ->name('modules.destroy');

        // Lessons
        Route::post('modules/{module}/lessons', [ModuleLessonController::class, 'storeLesson'])
            ->name('lessons.store');
        Route::patch('lessons/{lesson}', [ModuleLessonController::class, 'updateLesson'])
            ->name('lessons.update');
        Route::delete('lessons/{lesson}', [ModuleLessonController::class, 'destroyLesson'])
            ->name('lessons.destroy');
    });
});

/* ═══ Skill Onboarding Routes ═══ */
Route::middleware('auth')->group(function () {
    Route::get('/onboarding/skills', [SkillOnboardingController::class, 'show'])
        ->name('onboarding.skills.show');
    Route::post('/onboarding/skills', [SkillOnboardingController::class, 'store'])
        ->name('onboarding.skills.store');
});
