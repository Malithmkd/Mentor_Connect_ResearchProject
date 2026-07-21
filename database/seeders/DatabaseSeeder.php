<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Gig;
use App\Models\MentorProfile;
use App\Models\Review;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * DatabaseSeeder
 * Seeds the application with sample data for testing.
 * Creates: admin, mentors with gigs, freelancers with bookings, skills, reviews.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSkills();
        $this->seedAdmin();
        $this->seedMentors();
        $this->seedFreelancers();
        $this->seedBookingsAndReviews();
    }

    private function seedSkills(): void
    {
        $skills = [
            ['name' => 'Laravel', 'category' => 'backend'],
            ['name' => 'React', 'category' => 'frontend'],
            ['name' => 'Vue.js', 'category' => 'frontend'],
            ['name' => 'Node.js', 'category' => 'backend'],
            ['name' => 'Python', 'category' => 'backend'],
            ['name' => 'AWS', 'category' => 'devops'],
            ['name' => 'Docker', 'category' => 'devops'],
            ['name' => 'System Design', 'category' => 'architecture'],
            ['name' => 'UI/UX Design', 'category' => 'design'],
            ['name' => 'DevOps', 'category' => 'devops'],
            ['name' => 'Machine Learning', 'category' => 'data'],
            ['name' => 'Data Science', 'category' => 'data'],
            ['name' => 'TypeScript', 'category' => 'frontend'],
            ['name' => 'Go', 'category' => 'backend'],
            ['name' => 'Career Growth', 'category' => 'career'],
            ['name' => 'Interview Prep', 'category' => 'career'],
        ];

        foreach ($skills as $skill) {
            Skill::create([
                'name' => $skill['name'],
                'slug' => Str::slug($skill['name']),
                'category' => $skill['category'],
                'is_active' => true,
            ]);
        }

        $this->command->info('Seeded ' . count($skills) . ' skills.');
    }

    private function seedAdmin(): void
    {
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@mentorconnect.test',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->command->info('Seeded admin user (admin@mentorconnect.test / password)');
    }

    private function seedMentors(): void
    {
        $mentors = [
            [
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'email' => 'sarah@example.com',
                'headline' => 'Senior Architect @ Google',
                'about' => '15+ years building scalable distributed systems. Former tech lead at Google Cloud.',
                'skills' => ['Laravel', 'System Design', 'AWS', 'Docker'],
                'gigs' => [
                    [
                        'title' => 'System Design Deep Dive',
                        'description' => 'Learn to design scalable systems from scratch. We cover load balancing, caching strategies, database sharding, and microservices architecture.',
                        'what_to_expect' => 'Whiteboard session with real-world examples. Leave with a framework for approaching any system design interview.',
                        'price' => 150.00,
                        'duration_minutes' => 90,
                        'level' => 'advanced',
                    ],
                    [
                        'title' => 'Laravel Architecture Review',
                        'description' => 'Get expert feedback on your Laravel application architecture. Identify bottlenecks and improve code quality.',
                        'price' => 120.00,
                        'duration_minutes' => 60,
                        'level' => 'intermediate',
                    ],
                ],
            ],
            [
                'first_name' => 'Michael',
                'last_name' => 'Ross',
                'email' => 'michael@example.com',
                'headline' => 'Lead Developer @ Stripe',
                'about' => 'Full-stack expert specializing in payment systems and API design.',
                'skills' => ['Node.js', 'TypeScript', 'System Design', 'React'],
                'gigs' => [
                    [
                        'title' => 'API Design Masterclass',
                        'description' => 'Design RESTful and GraphQL APIs that scale. Learn best practices for authentication, rate limiting, and versioning.',
                        'price' => 100.00,
                        'duration_minutes' => 75,
                        'level' => 'intermediate',
                    ],
                ],
            ],
            [
                'first_name' => 'Emily',
                'last_name' => 'Chen',
                'email' => 'emily@example.com',
                'headline' => 'Design Lead @ Figma',
                'about' => 'Product designer with expertise in design systems and user research.',
                'skills' => ['UI/UX Design', 'Career Growth'],
                'gigs' => [
                    [
                        'title' => 'Portfolio Review & Feedback',
                        'description' => 'Get actionable feedback on your design portfolio. Learn how to present your work effectively to land your dream job.',
                        'price' => 80.00,
                        'duration_minutes' => 60,
                        'level' => 'all_levels',
                    ],
                ],
            ],
            [
                'first_name' => 'David',
                'last_name' => 'Park',
                'email' => 'david@example.com',
                'headline' => 'ML Engineer @ OpenAI',
                'about' => 'Machine learning researcher and practitioner. Former PhD from Stanford.',
                'skills' => ['Python', 'Machine Learning', 'Data Science'],
                'gigs' => [
                    [
                        'title' => 'ML Career Transition Guide',
                        'description' => 'Transition into machine learning engineering. We cover the skills you need, project ideas, and interview preparation.',
                        'price' => 120.00,
                        'duration_minutes' => 75,
                        'level' => 'beginner',
                    ],
                ],
            ],
        ];

        foreach ($mentors as $data) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'role' => UserRole::MENTOR,
                'email_verified_at' => now(),
            ]);

            MentorProfile::create([
                'user_id' => $user->id,
                'headline' => $data['headline'],
                'about' => $data['about'],
                'verification_status' => 'verified',
                'verified_at' => now(),
                'years_experience' => rand(5, 20),
            ]);

            $skillIds = Skill::whereIn('name', $data['skills'])->pluck('id');

            foreach ($data['gigs'] as $gigData) {
                $gig = Gig::create([
                    'mentor_id' => $user->id,
                    'title' => $gigData['title'],
                    'slug' => Str::slug($gigData['title']) . '-' . uniqid(),
                    'description' => $gigData['description'],
                    'what_to_expect' => $gigData['what_to_expect'] ?? null,
                    'price' => $gigData['price'],
                    'duration_minutes' => $gigData['duration_minutes'],
                    'experience_level' => $gigData['level'],
                    'status' => 'published',
                ]);

                $gig->skills()->attach($skillIds->random(min(3, $skillIds->count())));
            }
        }

        $this->command->info('Seeded ' . count($mentors) . ' mentors with gigs.');
    }

    private function seedFreelancers(): void
    {
        $freelancers = [
            ['first_name' => 'Alex', 'last_name' => 'Rivera', 'email' => 'alex@example.com'],
            ['first_name' => 'Jordan', 'last_name' => 'Lee', 'email' => 'jordan@example.com'],
            ['first_name' => 'Casey', 'last_name' => 'Morgan', 'email' => 'casey@example.com'],
        ];

        foreach ($freelancers as $data) {
            User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'role' => UserRole::FREELANCER,
                'email_verified_at' => now(),
            ]);
        }

        $this->command->info('Seeded ' . count($freelancers) . ' freelancers.');
    }

    private function seedBookingsAndReviews(): void
    {
        $freelancer = User::where('email', 'alex@example.com')->first();
        $gigs = Gig::published()->get();

        if (!$freelancer || $gigs->isEmpty()) return;

        // Create a completed booking with review
        $booking = Booking::create([
            'freelancer_id' => $freelancer->id,
            'mentor_id' => $gigs->first()->mentor_id,
            'gig_id' => $gigs->first()->id,
            'status' => 'reviewed',
            'requested_at' => now()->subDays(7),
            'responded_at' => now()->subDays(6),
            'scheduled_at' => now()->subDays(5),
            'completed_at' => now()->subDays(4),
            'price_paid' => $gigs->first()->price,
        ]);

        Review::create([
            'booking_id' => $booking->id,
            'reviewer_id' => $freelancer->id,
            'reviewee_id' => $gigs->first()->mentor_id,
            'freelancer_id' => $freelancer->id,
            'mentor_id' => $gigs->first()->mentor_id,
            'gig_id' => $gigs->first()->id,
            'rating' => 5,
            'comment' => 'Excellent session! Sarah broke down complex system design concepts into digestible pieces. Highly recommend for anyone preparing for senior engineering interviews.',
            'is_public' => true,
        ]);

        // Update mentor profile & user rating
        $profile = $gigs->first()->mentor->mentorProfile;
        if ($profile) {
            $profile->update([
                'average_rating' => 5.0,
                'total_reviews' => 1,
                'total_sessions' => 1,
            ]);
        }
        $gigs->first()->mentor->update([
            'average_rating' => 5.0,
            'total_reviews' => 1,
        ]);

        // Seed mutual review from mentor back to freelancer
        Review::create([
            'booking_id' => $booking->id,
            'reviewer_id' => $gigs->first()->mentor_id,
            'reviewee_id' => $freelancer->id,
            'freelancer_id' => $freelancer->id,
            'mentor_id' => $gigs->first()->mentor_id,
            'gig_id' => $gigs->first()->id,
            'rating' => 5,
            'comment' => 'Alex was fantastic to mentor! Came well-prepared with thoughtful questions and grasped complex system design trade-offs quickly. Highly recommend working with Alex!',
            'is_public' => true,
        ]);

        $freelancer->update([
            'average_rating' => 5.0,
            'total_reviews' => 1,
        ]);

        // Create a pending booking
        Booking::create([
            'freelancer_id' => $freelancer->id,
            'mentor_id' => $gigs->skip(1)->first()?->mentor_id ?? $gigs->first()->mentor_id,
            'gig_id' => $gigs->skip(1)->first()?->id ?? $gigs->first()->id,
            'status' => 'requested',
            'requested_at' => now()->subHours(2),
            'price_paid' => $gigs->skip(1)->first()?->price ?? $gigs->first()->price,
            'freelancer_note' => 'Looking forward to learning about API design patterns!',
        ]);

        $this->command->info('Seeded bookings and reviews.');
        $this->command->info('');
        $this->command->info('=== LOGIN CREDENTIALS ===');
        $this->command->info('Admin:    admin@mentorconnect.test / password');
        $this->command->info('Mentor:   sarah@example.com / password');
        $this->command->info('Freelancer: alex@example.com / password');
        $this->command->info('========================');
    }
}
