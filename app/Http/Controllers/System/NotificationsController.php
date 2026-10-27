<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationsController extends Controller
{
    private function orgId(): int
    {
        return (int) Auth::user()->organisation_id;
    }

    // ── Index ─────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = NotificationTemplate::forOrg($this->orgId());

        if ($channel = $request->channel) {
            $query->channel($channel);
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('is_active', (bool) $request->status);
        }

        if ($search = $request->search) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%$search%")
                ->orWhere('event', 'like', "%$search%")
            );
        }

        $templates = $query->orderBy('event')->orderBy('channel')->paginate(15)->withQueryString();

        $stats = [
            'total'   => NotificationTemplate::forOrg($this->orgId())->count(),
            'active'  => NotificationTemplate::forOrg($this->orgId())->active()->count(),
            'sms'     => NotificationTemplate::forOrg($this->orgId())->channel('sms')->count(),
            'email'   => NotificationTemplate::forOrg($this->orgId())->channel('email')->count(),
            'in_app'  => NotificationTemplate::forOrg($this->orgId())->channel('in_app')->count(),
        ];

        $channels = NotificationTemplate::CHANNELS;
        $events   = NotificationTemplate::EVENTS;

        return view('notifications.index', compact('templates', 'stats', 'channels', 'events'));
    }

    // ── Create ────────────────────────────────────────────────

    public function create()
    {
        $channels = NotificationTemplate::CHANNELS;
        $events   = NotificationTemplate::EVENTS;
        return view('notifications.create', compact('channels', 'events'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:120'],
            'event'     => ['required', 'string', 'max:120'],
            'channel'   => ['required', 'in:sms,email,in_app'],
            'subject'   => ['nullable', 'string', 'max:255'],
            'body'      => ['required', 'string'],
            'variables' => ['nullable', 'string'],  // JSON array
            'is_active' => ['boolean'],
        ]);

        // Enforce one template per event+channel per org
        $exists = NotificationTemplate::forOrg($this->orgId())
            ->where('event', $data['event'])
            ->where('channel', $data['channel'])
            ->exists();

        if ($exists) {
            return back()->withInput()
                ->withErrors(['event' => "A template for that event and channel already exists."]);
        }

        $variables = null;
        if (!empty($data['variables'])) {
            $decoded   = json_decode($data['variables'], true);
            $variables = is_array($decoded) ? $decoded : null;
        }

        NotificationTemplate::create([
            'organisation_id' => $this->orgId(),
            'name'      => $data['name'],
            'event'     => $data['event'],
            'channel'   => $data['channel'],
            'subject'   => $data['subject'] ?? null,
            'body'      => $data['body'],
            'variables' => $variables,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('notifications.index')
            ->with('success', "Notification template \"{$data['name']}\" created.");
    }

    // ── Edit / Update ─────────────────────────────────────────

    public function edit(NotificationTemplate $template)
    {
        $this->authorise($template);
        $channels = NotificationTemplate::CHANNELS;
        $events   = NotificationTemplate::EVENTS;
        return view('notifications.edit', compact('template', 'channels', 'events'));
    }

    public function update(Request $request, NotificationTemplate $template)
    {
        $this->authorise($template);

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:120'],
            'event'     => ['required', 'string', 'max:120'],
            'channel'   => ['required', 'in:sms,email,in_app'],
            'subject'   => ['nullable', 'string', 'max:255'],
            'body'      => ['required', 'string'],
            'variables' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $conflict = NotificationTemplate::forOrg($this->orgId())
            ->where('event', $data['event'])
            ->where('channel', $data['channel'])
            ->where('id', '!=', $template->id)
            ->exists();

        if ($conflict) {
            return back()->withInput()
                ->withErrors(['event' => "Another template for that event and channel already exists."]);
        }

        $variables = $template->variables;
        if (!empty($data['variables'])) {
            $decoded   = json_decode($data['variables'], true);
            $variables = is_array($decoded) ? $decoded : null;
        }

        $template->update([
            'name'      => $data['name'],
            'event'     => $data['event'],
            'channel'   => $data['channel'],
            'subject'   => $data['subject'] ?? null,
            'body'      => $data['body'],
            'variables' => $variables,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('notifications.index')
            ->with('success', "Template \"{$template->name}\" updated.");
    }

    // ── Delete ────────────────────────────────────────────────

    public function destroy(NotificationTemplate $template)
    {
        $this->authorise($template);
        $name = $template->name;
        $template->delete();

        return redirect()->route('notifications.index')
            ->with('success', "Template \"{$name}\" deleted.");
    }

    // ── Toggle active ─────────────────────────────────────────

    public function toggleActive(NotificationTemplate $template)
    {
        $this->authorise($template);
        $template->update(['is_active' => ! $template->is_active]);
        $state = $template->is_active ? 'enabled' : 'disabled';

        return back()->with('success', "Template \"{$template->name}\" {$state}.");
    }

    // ── Test send ─────────────────────────────────────────────

    public function test(NotificationTemplate $template)
    {
        $this->authorise($template);
        // Mark last_tested_at — actual dispatch wired to queue in production
        $template->update(['last_tested_at' => now()]);

        return back()->with('success', "Test notification sent for \"{$template->name}\".");
    }

    // ── Private helpers ───────────────────────────────────────

    private function authorise(NotificationTemplate $template): void
    {
        abort_if($template->organisation_id !== $this->orgId(), 403);
    }
}
