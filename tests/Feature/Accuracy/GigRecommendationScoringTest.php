<?php

namespace Tests\Feature\Accuracy;

use App\Models\Gig;
use App\Models\MentorProfile;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Gig Recommendation Scoring Accuracy Tests.
 *
 * Verifies that the scalar-subquery recommendation engine orders gigs by:
 * 1. match_count DESC (number of overlapping skills with the current user)
 * 2. created_at DESC (as tiebreaker)
 *
 * Uses known, controlled user skill vectors and gig skill sets to
 * verify the sort order precisely.
 */
class GigRecommendationScoringTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeMentor(): User
    {
        $mentor = User::factory()->mentor()->approved()->create();
        MentorProfile::factory()->verified()->create(['user_id' => $mentor->id]);
        return $mentor;
    }

    private function makeSkills(array $names): array
    {
        return array_map(fn ($name) => Skill::create([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name . '-' . uniqid()),
        ]), $names);
    }

    /**
     * Build a recommendation query matching the application's scalar-subquery approach.
     * This replicates what FreelancerDashboardController / GigController does.
     */
    private function recommendedGigsQuery(User $freelancer): \Illuminate\Support\Collection
    {
        $userSkillIds = $freelancer->skills->pluck('id')->toArray();

        if (empty($userSkillIds)) {
            return Gig::published()
                ->with(['mentor', 'skills'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $safeSkilIds = array_map('intval', $userSkillIds);

        return Gig::published()
            ->selectRaw('gigs.*, (
                SELECT COUNT(*)
                FROM gig_skill gs
                INNER JOIN skill_user su ON gs.skill_id = su.skill_id
                WHERE gs.gig_id = gigs.id
                  AND su.user_id = ?
            ) AS match_count', [$freelancer->id])
            ->with(['mentor', 'skills'])
            ->orderByRaw('match_count DESC')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // ─── Core Sorting Tests ───────────────────────────────────────────────────

    /** @test */
    public function gigs_are_sorted_by_skill_overlap_count_descending(): void
    {
        $freelancer = User::factory()->freelancer()->approved()->create();
        $mentor     = $this->makeMentor();

        [$php, $laravel, $vue, $react, $docker] = $this->makeSkills([
            'PHP', 'Laravel', 'Vue', 'React', 'Docker',
        ]);

        // Freelancer knows: PHP, Laravel, Vue
        $freelancer->skills()->attach([$php->id, $laravel->id, $vue->id]);

        // Gig A: matches 3 of freelancer's skills (PHP, Laravel, Vue)
        $gigA = Gig::factory()->published()->forMentor($mentor)->create(['title' => 'Gig A']);
        $gigA->skills()->attach([$php->id, $laravel->id, $vue->id]);

        // Gig B: matches 1 skill (PHP)
        $gigB = Gig::factory()->published()->forMentor($mentor)->create(['title' => 'Gig B']);
        $gigB->skills()->attach([$php->id]);

        // Gig C: matches 2 skills (PHP, Vue)
        $gigC = Gig::factory()->published()->forMentor($mentor)->create(['title' => 'Gig C']);
        $gigC->skills()->attach([$php->id, $vue->id]);

        // Gig D: matches 0 skills (Docker, React)
        $gigD = Gig::factory()->published()->forMentor($mentor)->create(['title' => 'Gig D']);
        $gigD->skills()->attach([$docker->id, $react->id]);

        $results = $this->recommendedGigsQuery($freelancer);

        $ids = $results->pluck('id')->toArray();

        $posA = array_search($gigA->id, $ids);
        $posB = array_search($gigB->id, $ids);
        $posC = array_search($gigC->id, $ids);
        $posD = array_search($gigD->id, $ids);

        // Gig A (3 matches) must come before Gig C (2 matches)
        $this->assertLessThan($posC, $posA, 'Gig A (3 matches) should rank above Gig C (2 matches)');
        // Gig C (2 matches) must come before Gig B (1 match)
        $this->assertLessThan($posB, $posC, 'Gig C (2 matches) should rank above Gig B (1 match)');
        // Gig B (1 match) must come before Gig D (0 matches)
        $this->assertLessThan($posD, $posB, 'Gig B (1 match) should rank above Gig D (0 matches)');
    }

    /** @test */
    public function gigs_with_equal_match_count_are_sorted_by_created_at_desc(): void
    {
        $freelancer = User::factory()->freelancer()->approved()->create();
        $mentor     = $this->makeMentor();

        [$php, $laravel] = $this->makeSkills(['PHP-tiebreak', 'Laravel-tiebreak']);

        $freelancer->skills()->attach([$php->id, $laravel->id]);

        // Gig X: created first, 2 matches
        $gigX = Gig::factory()->published()->forMentor($mentor)->create([
            'title'      => 'Gig X',
            'created_at' => now()->subHours(2),
        ]);
        $gigX->skills()->attach([$php->id, $laravel->id]);

        // Gig Y: created later, 2 matches (same count)
        $gigY = Gig::factory()->published()->forMentor($mentor)->create([
            'title'      => 'Gig Y',
            'created_at' => now()->subHour(),
        ]);
        $gigY->skills()->attach([$php->id, $laravel->id]);

        $results = $this->recommendedGigsQuery($freelancer);
        $ids     = $results->pluck('id')->toArray();

        $posX = array_search($gigX->id, $ids);
        $posY = array_search($gigY->id, $ids);

        // Both have same match_count → newer (Gig Y) should come first
        $this->assertLessThan($posX, $posY, 'Newer gig with same match count should rank first');
    }

    /** @test */
    public function gigs_with_zero_matching_skills_appear_last(): void
    {
        $freelancer = User::factory()->freelancer()->approved()->create();
        $mentor     = $this->makeMentor();

        [$php, $docker] = $this->makeSkills(['PHP-zero', 'Docker-zero']);

        $freelancer->skills()->attach([$php->id]);

        // Gig with matching skill
        $matched = Gig::factory()->published()->forMentor($mentor)->create(['title' => 'Matched']);
        $matched->skills()->attach([$php->id]);

        // Gig with no matching skills
        $unmatched = Gig::factory()->published()->forMentor($mentor)->create(['title' => 'Unmatched']);
        $unmatched->skills()->attach([$docker->id]);

        $results = $this->recommendedGigsQuery($freelancer);
        $ids     = $results->pluck('id')->toArray();

        $posMatched   = array_search($matched->id, $ids);
        $posUnmatched = array_search($unmatched->id, $ids);

        $this->assertLessThan($posUnmatched, $posMatched,
            'Gig with matching skills must appear before gig with no matches');
    }

    /** @test */
    public function recommendation_query_only_returns_published_gigs(): void
    {
        $freelancer = User::factory()->freelancer()->approved()->create();
        $mentor     = $this->makeMentor();

        [$php] = $this->makeSkills(['PHP-status']);
        $freelancer->skills()->attach([$php->id]);

        $published = Gig::factory()->published()->forMentor($mentor)->create(['title' => 'Published']);
        $published->skills()->attach([$php->id]);

        $draft = Gig::factory()->draft()->forMentor($mentor)->create(['title' => 'Draft']);
        $draft->skills()->attach([$php->id]);

        $results = $this->recommendedGigsQuery($freelancer);

        $this->assertTrue($results->contains('id', $published->id));
        $this->assertFalse($results->contains('id', $draft->id));
    }

    /** @test */
    public function match_count_is_correctly_computed_via_scalar_subquery(): void
    {
        $freelancer = User::factory()->freelancer()->approved()->create();
        $mentor     = $this->makeMentor();

        [$s1, $s2, $s3, $s4] = $this->makeSkills(['Skill1', 'Skill2', 'Skill3', 'Skill4']);

        $freelancer->skills()->attach([$s1->id, $s2->id, $s3->id]);

        $gig = Gig::factory()->published()->forMentor($mentor)->create();
        $gig->skills()->attach([$s1->id, $s2->id, $s4->id]); // $s4 not in freelancer's skills

        $result = $this->recommendedGigsQuery($freelancer)
            ->firstWhere('id', $gig->id);

        // 2 overlapping skills ($s1, $s2)
        $this->assertSame(2, (int) $result->match_count);
    }

    /** @test */
    public function freelancer_with_no_skills_gets_all_published_gigs_by_date(): void
    {
        $freelancer = User::factory()->freelancer()->approved()->create();
        // No skills attached
        $mentor = $this->makeMentor();

        $gig1 = Gig::factory()->published()->forMentor($mentor)->create(['created_at' => now()->subDay()]);
        $gig2 = Gig::factory()->published()->forMentor($mentor)->create(['created_at' => now()]);

        $results = $this->recommendedGigsQuery($freelancer);

        // Should contain both, sorted by created_at desc
        $this->assertTrue($results->contains('id', $gig1->id));
        $this->assertTrue($results->contains('id', $gig2->id));

        $ids  = $results->pluck('id')->toArray();
        $this->assertLessThan(array_search($gig1->id, $ids), array_search($gig2->id, $ids) === false
            ? PHP_INT_MAX
            : array_search($gig1->id, $ids)); // gig2 is newer, should come first
    }
}
