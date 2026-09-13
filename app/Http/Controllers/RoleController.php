<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Services\PermissionRegistry;
use App\Services\RoleAssignmentGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function __construct(
        protected RoleAssignmentGuard $roleAssignmentGuard,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Role::class);

        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $term = $filters['search'];

                $query->where(function ($builder) use ($term) {
                    $builder->where('name', 'like', "%{$term}%")
                        ->orWhere('slug', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('roles.index', [
            'roles' => $roles,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Role::class);

        return view('roles.create', [
            'modulePermissions' => app(PermissionRegistry::class)->groupedForUi(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Role::class);

        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('roles', 'slug')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'description' => 'nullable|string|max:1000',
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $permissionIds = $this->roleAssignmentGuard->filterAssignablePermissions(
            $request->user(),
            $validated['permissions'] ?? [],
        );

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        $role->permissions()->sync($permissionIds);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Role created successfully.',
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'description' => $role->description,
                ],
            ], 201);
        }

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $this->authorize('update', $role);

        $role->load('permissions');

        return view('roles.edit', [
            'role' => $role,
            'modulePermissions' => app(PermissionRegistry::class)->groupedForUi(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $this->authorize('update', $role);

        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('roles', 'slug')
                    ->where(fn ($query) => $query->where('company_id', $companyId))
                    ->ignore($role->id),
            ],
            'description' => 'nullable|string|max:1000',
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        if ($role->isProtected()) {
            $validated['slug'] = $role->slug;
        }

        $role->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
        ]);

        // System Admin role permissions are managed by the platform synchronizer.
        if (! ($role->isProtected() && $role->slug === 'admin')) {
            $permissionIds = $this->roleAssignmentGuard->filterAssignablePermissions(
                $request->user(),
                $validated['permissions'] ?? [],
            );

            $role->permissions()->sync($permissionIds);
        }

        return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);

        if ($role->isProtected()) {
            return back()->withErrors(['error' => 'System roles cannot be deleted.']);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete a role that is assigned to users.']);
        }

        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
