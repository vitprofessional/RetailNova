<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;
use Alert;

class AdminUserManagementController extends Controller
{
    public function index()
    {
        $actor = auth()->guard('admin')->user();
        if (!$actor) {
            abort(403);
        }
        $query = AdminUser::query()->orderBy('created_at','desc');
        if ($actor->role === 'gm') {
            $query->where('businessId', $actor->businessId);
        } elseif ($actor->role === 'storemanager') {
            // Store Manager: can manage only Till Users within their business
            $query->where('businessId', $actor->businessId);
        }
        // Filters: role, businessId, q (name/email)
        $viewableRoles = $this->viewableRoles($actor->role);
        // Always restrict listing to roles not higher than actor
        if (!empty($viewableRoles)) {
            $query->whereIn('role', $viewableRoles);
        }
        $role = request('role');
        if ($role && in_array($role, $viewableRoles)) {
            $query->where('role', $role);
        }
        $businessId = request('businessId');
        if ($businessId && ($actor->role !== 'gm')) {
            $query->where('businessId', $businessId);
        }
        $q = request('q');
        if ($q) {
            $query->where(function($w) use ($q){
                $w->where('fullName','like','%'.$q.'%')
                  ->orWhere('mail','like','%'.$q.'%');
            });
        }
        $users = $query->paginate(20)->appends(request()->query());

        $allowedRoles = $this->allowedCreatableRoles($actor->role);
        $businesses = [];
        if (in_array($actor->role, ['superadmin','admin'])) {
            $businesses = \App\Models\BusinessSetup::select('id','businessName')->orderBy('businessName')->get();
        } elseif ($actor->role === 'gm' || $actor->role === 'storemanager') {
            // GM/Store Manager: restrict to their own business
            $businesses = \App\Models\BusinessSetup::where('id', $actor->businessId)->select('id','businessName')->get();
        }
        // Map business IDs to names for table display
        $businessIds = collect($users->items())->pluck('businessId')->filter()->unique()->values();
        $businessMap = \App\Models\BusinessSetup::whereIn('id', $businessIds)->pluck('businessName','id');
        return view('admin.users.index', compact('users','allowedRoles','actor','viewableRoles','businesses','businessMap'));
    }

    public function store(Request $request)
    {
        $actor = auth()->guard('admin')->user();
        if (!$actor) abort(403);

        $allowed = $this->allowedCreatableRoles($actor->role);
        $request->validate([
            'fullName' => 'required|string|max:255',
            'mail' => 'required|email|unique:admin_users,mail',
            'role' => 'required|in:'.implode(',', $allowed),
            'password' => 'required|string|min:6',
            'businessId' => 'nullable|integer',
        ]);

        $role = $request->role;
        // Business scoping rules
        if (in_array($role, ['gm','storemanager','salesmanager'])) {
            // GM/StoreManager must have a businessId
            $bizId = $request->businessId ?: null;
            if ($actor->role === 'gm') {
                // GM may assign business to Store Manager but only their own business
                if (!$bizId) {
                    $bizId = $actor->businessId;
                }
                if ((string)$bizId !== (string)$actor->businessId) {
                    Alert::error('Error','GM can only assign their own business to Store Manager');
                    return back()->withInput();
                }
            } elseif ($actor->role === 'storemanager') {
                // Store Manager creates Sales Managers within own business only
                $bizId = $actor->businessId;
            }
            if (empty($bizId)) {
                Alert::error('Error','Business is required for selected role');
                return back()->withInput();
            }
            $request->merge(['businessId' => $bizId]);
        } else {
            // admin/superadmin can be server-wide
            // if actor is admin, they cannot create admin/superadmin per allowed list already
        }

        $user = new AdminUser();
        $user->fullName = $request->fullName;
        $user->sureName = $request->sureName;
        $user->storeName = $request->storeName;
        $user->mail = $request->mail;
        $user->contactNumber = $request->contactNumber;
        $user->businessId = $request->businessId;
        $user->role = $role;
        $user->password = Hash::make($request->password);
        $user->save();

        Alert::success('Success','User created');
        return back();
    }

    public function edit($id)
    {
        $actor = auth()->guard('admin')->user();
        if (!$actor) abort(403);

        $user = AdminUser::findOrFail($id);
        $allowed = $this->allowedCreatableRoles($actor->role);
        // Only allow editing users within actor's permissible roles
        if (!in_array($user->role, $allowed) && $actor->role !== 'superadmin') {
            abort(403);
        }
        // GM can only edit within their business
        if (in_array($actor->role, ['gm','storemanager']) && $user->businessId != $actor->businessId) {
            abort(403);
        }
        $businesses = [];
        if (in_array($actor->role, ['superadmin','admin'])) {
            $businesses = \App\Models\BusinessSetup::select('id','businessName')->orderBy('businessName')->get();
        } elseif (in_array($actor->role, ['gm','storemanager'])) {
            $businesses = \App\Models\BusinessSetup::where('id',$actor->businessId)->select('id','businessName')->get();
        }
        return view('admin.users.edit', compact('user','businesses'));
    }

    public function update(Request $request, $id)
    {
        $actor = auth()->guard('admin')->user();
        if (!$actor) abort(403);

        $user = AdminUser::findOrFail($id);
        $allowed = $this->allowedCreatableRoles($actor->role);
        // Prevent editing users outside permissible scope
        if (!in_array($user->role, $allowed) && $actor->role !== 'superadmin') {
            abort(403);
        }
        if (in_array($actor->role, ['gm','storemanager']) && $user->businessId != $actor->businessId) {
            abort(403);
        }
        $request->validate([
            'fullName' => 'required|string|max:255',
            'mail' => 'required|email|unique:admin_users,mail,' . $user->id,
            'role' => 'required|in:'.implode(',', $allowed),
            'password' => 'nullable|string|min:6',
            'businessId' => 'nullable|integer',
        ]);

        $role = $request->role;
        // Business scoping on update
        if (in_array($role, ['gm','storemanager','salesmanager'])) {
            $bizId = $request->businessId ?: null;
            if ($actor->role === 'gm') {
                if (!$bizId) {
                    $bizId = $actor->businessId;
                }
                if ((string)$bizId !== (string)$actor->businessId) {
                    Alert::error('Error','GM can only assign their own business to Store Manager');
                    return back()->withInput();
                }
            } elseif ($actor->role === 'storemanager') {
                $bizId = $actor->businessId;
            }
            if (empty($bizId)) {
                Alert::error('Error','Business is required for selected role');
                return back()->withInput();
            }
            $request->merge(['businessId' => $bizId]);
        }

        $user->fullName = $request->fullName;
        $user->sureName = $request->sureName;
        $user->storeName = $request->storeName;
        $user->mail = $request->mail;
        $user->contactNumber = $request->contactNumber;
        $user->businessId = $request->businessId;
        $user->role = $role;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        Alert::success('Updated','User updated');
        // Redirect intelligently based on actor
        return redirect()->route($this->routeBase($actor).'.users.index');
    }

    public function destroy($id)
    {
        $actor = auth()->guard('admin')->user();
        if (!$actor) abort(403);

        $user = AdminUser::findOrFail($id);
        $allowed = $this->allowedCreatableRoles($actor->role);
        if (!in_array($user->role, $allowed) && $actor->role !== 'superadmin') {
            abort(403);
        }
        if (in_array($actor->role, ['gm','storemanager']) && $user->businessId != $actor->businessId) {
            abort(403);
        }
        if ($user->id === $actor->id) {
            Alert::error('Error','You cannot delete your own account');
            return back();
        }
        $user->delete();
        Alert::success('Deleted','User deleted');
        return back();
    }

    public function bulkDelete(Request $request)
    {
        $actor = auth()->guard('admin')->user();
        if (!$actor) abort(403);

        $ids = collect($request->input('ids', []))
            ->map(fn($v) => (int)$v)
            ->filter(fn($v) => $v > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            Alert::error('Error','No users selected for deletion');
            return back();
        }

        $allowedRoles = $this->allowedCreatableRoles($actor->role);
        $users = AdminUser::whereIn('id', $ids)->get();
        $deletable = $users->filter(function($u) use ($actor, $allowedRoles){
            if ($u->id === $actor->id) return false; // cannot delete self
            if ($actor->role === 'superadmin') return true; // superadmin can delete any
            if (!in_array($u->role, $allowedRoles)) return false; // cannot delete higher roles
            if (in_array($actor->role, ['gm','storemanager']) && $u->businessId != $actor->businessId) return false; // GM/Store Manager restricted to own business
            return true;
        });

        if ($deletable->isEmpty()) {
            Alert::error('Error','No permitted users to delete');
            return back();
        }

        AdminUser::whereIn('id', $deletable->pluck('id'))->delete();
        Alert::success('Deleted', sprintf('Deleted %d user(s)', $deletable->count()));
        return back();
    }

    protected function allowedCreatableRoles(string $actorRole): array
    {
        switch ($actorRole) {
            case 'superadmin':
                return ['superadmin','admin','gm','storemanager','salesmanager'];
            case 'admin':
                return ['gm','storemanager','salesmanager'];
            case 'gm':
                return ['storemanager','salesmanager'];
            case 'storemanager':
                return ['salesmanager'];
            default:
                return [];
        }
    }

    protected function viewableRoles(string $actorRole): array
    {
        switch ($actorRole) {
            case 'superadmin':
                return ['superadmin','admin','gm','storemanager','salesmanager'];
            case 'admin':
                return ['gm','storemanager','salesmanager'];
            case 'gm':
                return ['storemanager','salesmanager'];
            case 'storemanager':
                return ['salesmanager'];
            default:
                return [];
        }
    }

    protected function routeBase($actor): string
    {
        return ($actor && $actor->role === 'superadmin') ? 'admin.super' : 'admin.manage';
    }
}
