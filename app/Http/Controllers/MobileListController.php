<?php

namespace App\Http\Controllers;

use App\Services\MobileLists\MobileLists;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only JSON for the mobile list pages' "load more" (UI-2): the next PER_PAGE cards of a
 * list, with the same filters, business scope and permissions as the page itself.
 */
class MobileListController extends Controller
{
    public function page(Request $request, string $list, MobileLists $lists): JsonResponse
    {
        $mobileList = $lists->get($list);
        $filters = $mobileList->filters($request);
        $page = $mobileList->page($filters, max(1, (int) $request->query('page', 1)));

        return response()->json([
            'list' => $mobileList->key(),
            'page' => $page['page'],
            'next_page' => $page['next_page'],
            'next_url' => $page['next_page'] ? $mobileList->endpoint($filters + ['page' => $page['next_page']]) : null,
            'total' => $page['total'],
            'shown' => $page['shown'],
            'items' => $page['cards'],
            'html' => view('mobile.lists.cards', ['cards' => $page['cards']])->render(),
        ]);
    }
}
