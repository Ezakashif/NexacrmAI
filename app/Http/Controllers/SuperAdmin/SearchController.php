<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SuperAdmin\PlatformSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(
        protected PlatformSearchService $search,
    ) {}

    public function index(Request $request): View
    {
        $term = (string) ($request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ])['q'] ?? '');

        $results = $this->search->search($term, 25);

        return view('superadmin.search.index', [
            'term' => $term,
            'companies' => $results['companies'],
            'users' => $results['users'],
        ]);
    }

    public function suggest(Request $request): JsonResponse
    {
        $term = (string) ($request->validate([
            'q' => ['nullable', 'string', 'max:255'],
        ])['q'] ?? '');

        $results = $this->search->search($term, 6);

        return response()->json([
            'companies' => $results['companies']->map(fn ($company) => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'email' => $company->email,
                'url' => route('superadmin.companies.show', $company),
            ]),
            'users' => $results['users']->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company' => $user->company?->name,
                'is_super_admin' => $user->is_super_admin,
                'url' => $user->company
                    ? route('superadmin.companies.show', $user->company)
                    : route('superadmin.super-admins.index'),
            ]),
        ]);
    }
}
