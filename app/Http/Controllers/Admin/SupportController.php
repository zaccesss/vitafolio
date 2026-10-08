<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** the support queue: every ticket, newest activity first, filtered by status and category */
class SupportController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), SupportTicket::STATUSES, true) ? (string) $request->query('status') : 'open';
        $category = in_array($request->query('category'), SupportTicket::CATEGORIES, true) ? (string) $request->query('category') : '';
        $tickets = SupportTicket::query()
            ->where('status', $status)
            ->when($category !== '', fn ($q) => $q->where('category', $category))
            ->withCount('messages')
            ->latest('last_activity_at')
            ->paginate(25)->withQueryString();
        $counts = SupportTicket::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.support', compact('tickets', 'counts', 'status', 'category'));
    }

    public function status(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(SupportTicket::STATUSES)]]);
        $ticket->update(['status' => $data['status'], 'last_activity_at' => now()]);

        return back()->with('status', __(':ref is now :status.', ['ref' => $ticket->reference(), 'status' => SupportTicket::statusLabels()[$data['status']]]));
    }
}
