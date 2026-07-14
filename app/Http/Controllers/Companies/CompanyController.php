<?php

declare(strict_types=1);

namespace App\Http\Controllers\Companies;

use App\Actions\Companies\CreateCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\Companies\DeleteCompanyRequest;
use App\Http\Requests\Companies\SaveCompanyRequest;
use App\Models\Company;
use App\Support\Money;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class CompanyController extends Controller
{
    /**
     * Display a listing of the user's companies.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('companies/index', [
            'companies' => $user->toUserCompanies(includeCurrent: true),
        ]);
    }

    /**
     * Store a newly created company.
     */
    public function store(SaveCompanyRequest $request, CreateCompany $createCompany): RedirectResponse
    {
        $company = $createCompany->handle($request->user(), $request->validated('name'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company created.')]);

        return to_route('companies.edit', ['company' => $company->slug]);
    }

    /**
     * Show the company edit page.
     */
    public function edit(Request $request, Company $company): Response
    {
        return Inertia::render('companies/edit', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'timezone' => $company->timezone,
                'currency' => $company->currency,
            ],
            'timezones' => DateTimeZone::listIdentifiers(),
            'currencies' => collect(Money::CURRENCIES)->map(fn (string $symbol, string $code) => [
                'code' => $code,
                'symbol' => trim($symbol),
            ])->values(),
            'canDelete' => Gate::allows('delete', $company),
        ]);
    }

    /**
     * Update the specified company.
     */
    public function update(SaveCompanyRequest $request, Company $company): RedirectResponse
    {
        $company = DB::transaction(function () use ($request, $company) {
            $company = Company::whereKey($company->id)->lockForUpdate()->firstOrFail();

            $company->update(['name' => $request->validated('name')]);

            return $company;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company updated.')]);

        return to_route('companies.edit', ['company' => $company->slug]);
    }

    /**
     * Update the company preferences.
     */
    public function updatePreferences(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'timezone' => ['required', 'timezone:all'],
            'currency' => ['required', Rule::in(Money::codes())],
        ]);

        $company->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Preferences updated.')]);

        return back();
    }

    /**
     * Switch the user's current company.
     */
    public function switch(Request $request, Company $company): RedirectResponse
    {
        $request->user()->switchCompany($company);

        return back();
    }

    /**
     * Delete the specified company.
     */
    public function destroy(DeleteCompanyRequest $request, Company $company): RedirectResponse
    {
        $user = $request->user();
        $fallbackCompany = $user->isCurrentCompany($company)
            ? $user->fallbackCompany($company)
            : null;

        DB::transaction(function () use ($company) {
            $company->delete();
        });

        if ($fallbackCompany) {
            $user->switchCompany($fallbackCompany);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company deleted.')]);

        return to_route('companies.index');
    }
}
