<?php

namespace App\Http\Controllers;

use App\Support\MobileView;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** "Desktop version / Mobile version" switch: remembers the choice in the rn_view cookie. */
class ViewModeController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate(['mode' => ['required', Rule::in(MobileView::MODES)]]);

        return back()->withCookie(cookie()->forever(MobileView::COOKIE, $data['mode']));
    }
}
