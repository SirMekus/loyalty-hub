<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $users = User::with(['achievements', 'orders'])->get()->map(fn (User $user) => [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ]);
        // dd($users);
        return Inertia::render('loyalty-dashboard', [
            'users' => $users,
        ]);
    }
}
