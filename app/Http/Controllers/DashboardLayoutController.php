<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardLayoutController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'layout' => ['required', 'array'],
            'layout.order' => ['required', 'array'],
            'layout.order.*' => ['string'],
            'layout.visible' => ['required', 'array'],
            'layout.visible.*' => ['string'],
        ]);

        $request->user()->forceFill([
            'dashboard_layout' => $validated['layout'],
        ])->save();

        return back(status: 303);
    }
}
