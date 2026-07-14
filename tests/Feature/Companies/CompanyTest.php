<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the companies index page can be rendered', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('companies.index'));

    $response->assertOk();
});

test('companies can be created', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('companies.store'), [
            'name' => 'Test Company',
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('companies', [
        'name' => 'Test Company',
    ]);
});

test('company slug uses next available suffix', function () {
    $user = User::factory()->create();

    Company::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    Company::factory()->create(['name' => 'Acme One', 'slug' => 'acme-1']);
    Company::factory()->create(['name' => 'Acme Ten', 'slug' => 'acme-10']);

    $this
        ->actingAs($user)
        ->post(route('companies.store'), [
            'name' => 'Acme',
        ]);

    $this->assertDatabaseHas('companies', [
        'name' => 'Acme',
        'slug' => 'acme-11',
    ]);
});

test('the company edit page can be rendered', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('companies.edit', $company));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('companies/edit')
            ->where('company.slug', $company->slug)
            ->where('canDelete', true),
        );
});

test('companies can be updated', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create(['name' => 'Original Name']);

    $response = $this
        ->actingAs($user)
        ->patch(route('companies.update', $company), [
            'name' => 'Updated Name',
        ]);

    $response->assertRedirect(route('companies.edit', $company->fresh()));

    $this->assertDatabaseHas('companies', [
        'id' => $company->id,
        'name' => 'Updated Name',
    ]);
});

test('companies can be deleted', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('companies.destroy', $company), [
            'name' => $company->name,
        ]);

    $response->assertRedirect();

    $this->assertSoftDeleted('companies', [
        'id' => $company->id,
    ]);
});

test('company deletion requires name confirmation', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('companies.destroy', $company), [
            'name' => 'Wrong Name',
        ]);

    $response->assertSessionHasErrors('name');

    $this->assertDatabaseHas('companies', [
        'id' => $company->id,
        'deleted_at' => null,
    ]);
});

test('deleting current company switches to alphabetically first remaining company', function () {
    $user = User::factory()->create(['name' => 'Mike']);

    $zuluCompany = Company::factory()->create(['name' => 'Zulu Company']);
    Company::factory()->create(['name' => 'Alpha Company']);
    Company::factory()->create(['name' => 'Beta Company']);

    $user->update(['current_company_id' => $zuluCompany->id]);

    $response = $this
        ->actingAs($user)
        ->delete(route('companies.destroy', $zuluCompany), [
            'name' => $zuluCompany->name,
        ]);

    $response->assertRedirect();

    $this->assertSoftDeleted('companies', [
        'id' => $zuluCompany->id,
    ]);

    expect($user->fresh()->currentCompany->name)->toEqual('Alpha Company');
});

test('deleting non current company leaves current company unchanged', function () {
    $user = User::factory()->create();
    $currentCompany = $user->currentCompany;
    $company = Company::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('companies.destroy', $company), [
            'name' => $company->name,
        ]);

    $response->assertRedirect();

    $this->assertSoftDeleted('companies', [
        'id' => $company->id,
    ]);

    expect($user->fresh()->current_company_id)->toEqual($currentCompany->id);
});

test('the last remaining company cannot be deleted', function () {
    $user = User::factory()->create();
    $company = $user->currentCompany;

    $response = $this
        ->actingAs($user)
        ->delete(route('companies.destroy', $company), [
            'name' => $company->name,
        ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('companies', [
        'id' => $company->id,
        'deleted_at' => null,
    ]);
});

test('users can switch companies', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('companies.switch', $company));

    $response->assertRedirect();

    expect($user->fresh()->current_company_id)->toEqual($company->id);
});

test('guests cannot access companies', function () {
    $response = $this->get(route('companies.index'));

    $response->assertRedirect(route('login'));
});
