<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user
                    ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'branch_id' => $user->branch_id,
                        'role' => $user->role
                            ? [
                                'id' => $user->role->id,
                                'name' => $user->role->name,
                                'code' => $user->role->code,
                                'permissions' => $user->role->permissions ?? [],
                            ]
                            : null,
                        'permissions' => $user->role?->permissions ?? [],
                    ]
                    : null,
            ],
        ]);
    }
}