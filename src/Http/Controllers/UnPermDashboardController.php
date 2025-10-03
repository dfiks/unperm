<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Http\Controllers;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UnPermDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'actions' => Action::count(),
            'roles' => Role::count(),
            'groups' => Group::count(),
        ];

        return view('unperm::dashboard', compact('stats'));
    }

    public function actions()
    {
        return view('unperm::actions.index');
    }

    public function roles()
    {
        return view('unperm::roles.index');
    }

    public function groups()
    {
        return view('unperm::groups.index');
    }

    public function users()
    {
        return view('unperm::users.index');
    }

    public function resources()
    {
        return view('unperm::resources.index');
    }
}

