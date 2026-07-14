<?php

declare(strict_types=1);

use App\Actions\Categories\CreateCategory;
use App\Actions\Companies\CreateCompany;
use App\Actions\Transactions\CreateTransaction;
use App\Enums\CategoryKind;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Company;
use App\Models\User;

function categoryCompany(User $user): Company
{
    return app(CreateCompany::class)->handle($user, 'Acme Studio');
}

test('a category can be created with a sub-category', function () {
    $user = User::factory()->create();
    $company = categoryCompany($user);

    $this->actingAs($user)->post(route('categories.store', ['current_company' => $company->slug]), [
        'name' => 'Software',
        'kind' => CategoryKind::Expense->value,
    ])->assertRedirect();

    $parent = $company->categories()->where('name', 'Software')->firstOrFail();

    $this->actingAs($user)->post(route('categories.store', ['current_company' => $company->slug]), [
        'name' => 'Licenses',
        'kind' => CategoryKind::Expense->value,
        'parent_id' => $parent->id,
    ])->assertRedirect();

    $child = $company->categories()->where('name', 'Licenses')->firstOrFail();

    expect($child->parent_id)->toBe($parent->id)
        ->and($child->kind->value)->toBe('expense');
});

test('categories cannot nest more than two levels', function () {
    $user = User::factory()->create();
    $company = categoryCompany($user);

    $parent = app(CreateCategory::class)->handle($company, 'Level 1', CategoryKind::Expense);
    $child = app(CreateCategory::class)->handle($company, 'Level 2', CategoryKind::Expense, $parent);

    $this->actingAs($user)->post(route('categories.store', ['current_company' => $company->slug]), [
        'name' => 'Level 3',
        'kind' => CategoryKind::Expense->value,
        'parent_id' => $child->id,
    ])->assertSessionHasErrors('parent_id');
});

test('a category with ledger activity cannot be deleted but can be archived', function () {
    $user = User::factory()->create();
    $company = categoryCompany($user);

    $category = app(CreateCategory::class)->handle($company, 'Hosting Bills', CategoryKind::Expense);
    $wallet = $company->wallets()->firstOrFail();

    app(CreateTransaction::class)->handle($company, TransactionType::Expense, $wallet, 30_000, now(), $category, 'Server payment');

    $this->actingAs($user)
        ->delete(route('categories.destroy', ['current_company' => $company->slug, 'category' => $category->id]))
        ->assertRedirect();

    expect(Category::find($category->id))->not->toBeNull();

    $this->actingAs($user)
        ->patch(route('categories.archive', ['current_company' => $company->slug, 'category' => $category->id]))
        ->assertRedirect();

    expect($category->refresh()->isArchived())->toBeTrue();
});

test('an unused category can be deleted', function () {
    $user = User::factory()->create();
    $company = categoryCompany($user);

    $category = app(CreateCategory::class)->handle($company, 'Unused', CategoryKind::Income);

    $this->actingAs($user)
        ->delete(route('categories.destroy', ['current_company' => $company->slug, 'category' => $category->id]))
        ->assertRedirect();

    expect(Category::find($category->id))->toBeNull();
});
