<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $users = User::select(['id', 'name', 'email'])->get();

        return Inertia::render('loyalty-dashboard', [
            'users' => $users,
        ]);
    }
}
