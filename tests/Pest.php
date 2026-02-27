<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a user who owns an organization.
 *
 * @return array{user: \App\Models\User, organization: \App\Models\Organization}
 */
function createOwnerWithOrganization(): array
{
    $user = \App\Models\User::factory()->create();
    $organization = \App\Models\Organization::factory()->create();
    $organization->users()->attach($user, ['role' => \App\Enums\OrganizationRole::Owner->value]);

    return ['user' => $user, 'organization' => $organization];
}

/**
 * Create a user who owns an organization, and optionally an event within it.
 *
 * @param  array<string, mixed>  $eventAttributes
 * @return array{user: \App\Models\User, organization: \App\Models\Organization, event: \App\Models\Event}
 */
function createOwnerWithEvent(array $eventAttributes = []): array
{
    $user = \App\Models\User::factory()->create();
    $organization = \App\Models\Organization::factory()->create();
    $organization->users()->attach($user, ['role' => \App\Enums\OrganizationRole::Owner->value]);
    $event = \App\Models\Event::factory()->create(array_merge(
        ['organization_id' => $organization->id],
        $eventAttributes,
    ));

    return ['user' => $user, 'organization' => $organization, 'event' => $event];
}

/**
 * Create a scorekeeper user attached to an organization with an event.
 *
 * @param  array<string, mixed>  $eventAttributes
 * @return array{user: \App\Models\User, organization: \App\Models\Organization, event: \App\Models\Event}
 */
function createScorekeeperWithEvent(array $eventAttributes = []): array
{
    $user = \App\Models\User::factory()->create();
    $organization = \App\Models\Organization::factory()->create();
    $organization->users()->attach($user, ['role' => \App\Enums\OrganizationRole::Scorekeeper->value]);
    $event = \App\Models\Event::factory()->create(array_merge(
        ['organization_id' => $organization->id],
        $eventAttributes,
    ));

    return ['user' => $user, 'organization' => $organization, 'event' => $event];
}
