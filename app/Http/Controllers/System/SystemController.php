<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SystemController extends Controller
{
    private function orgId(): int
    {
        return (int) Auth::user()->organisation_id;
    }

    // ── Overview ──────────────────────────────────────────────

    public function index()
    {
        $orgId = $this->orgId();

        $settings = SystemSetting::forOrg($orgId)
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group');

        $stats = [
            'total'  => SystemSetting::forOrg($orgId)->count(),
            'active' => SystemSetting::forOrg($orgId)->active()->count(),
            'groups' => SystemSetting::forOrg($orgId)->distinct('group')->count('group'),
        ];

        return view('system.index', compact('settings', 'stats'));
    }

    // ── Create ────────────────────────────────────────────────

    public function create()
    {
        $groups = $this->existingGroups();
        return view('system.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'group'       => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/'],
            'key'         => ['required', 'string', 'max:120', 'regex:/^[a-z0-9_]+$/'],
            'value'       => ['nullable', 'string'],
            'type'        => ['required', 'in:string,boolean,integer,json,select'],
            'label'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'options'     => ['nullable', 'string'],  // JSON string from textarea
            'is_public'   => ['boolean'],
            'is_active'   => ['boolean'],
            'sort_order'  => ['integer', 'min:0'],
        ]);

        // Ensure unique group+key per org
        $exists = SystemSetting::forOrg($this->orgId())
            ->where('group', $data['group'])
            ->where('key', $data['key'])
            ->exists();

        if ($exists) {
            return back()->withInput()
                ->withErrors(['key' => "A setting with key \"{$data['key']}\" already exists in group \"{$data['group']}\"."]); 
        }

        $options = null;
        if (!empty($data['options'])) {
            $decoded = json_decode($data['options'], true);
            $options = is_array($decoded) ? $decoded : null;
        }

        SystemSetting::create([
            'organisation_id' => $this->orgId(),
            'group'       => $data['group'],
            'key'         => $data['key'],
            'value'       => $data['value'],
            'type'        => $data['type'],
            'label'       => $data['label'],
            'description' => $data['description'] ?? null,
            'options'     => $options,
            'is_public'   => $request->boolean('is_public'),
            'is_active'   => $request->boolean('is_active', true),
            'sort_order'  => $data['sort_order'] ?? 0,
        ]);

        return redirect()->route('system.index')
            ->with('success', "Setting \"{$data['label']}\" created.");
    }

    // ── Edit / Update ─────────────────────────────────────────

    public function edit(SystemSetting $setting)
    {
        $this->authorise($setting);
        $groups = $this->existingGroups();
        return view('system.edit', compact('setting', 'groups'));
    }

    public function update(Request $request, SystemSetting $setting)
    {
        $this->authorise($setting);

        $data = $request->validate([
            'group'       => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/'],
            'key'         => ['required', 'string', 'max:120', 'regex:/^[a-z0-9_]+$/'],
            'value'       => ['nullable', 'string'],
            'type'        => ['required', 'in:string,boolean,integer,json,select'],
            'label'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'options'     => ['nullable', 'string'],
            'is_public'   => ['boolean'],
            'is_active'   => ['boolean'],
            'sort_order'  => ['integer', 'min:0'],
        ]);

        // Check uniqueness excluding self
        $conflict = SystemSetting::forOrg($this->orgId())
            ->where('group', $data['group'])
            ->where('key', $data['key'])
            ->where('id', '!=', $setting->id)
            ->exists();

        if ($conflict) {
            return back()->withInput()
                ->withErrors(['key' => "Another setting with that group + key combination already exists."]);
        }

        $options = $setting->options;
        if (array_key_exists('options', $data) && $data['options'] !== null) {
            $decoded = json_decode($data['options'], true);
            $options = is_array($decoded) ? $decoded : null;
        }

        $setting->update([
            'group'       => $data['group'],
            'key'         => $data['key'],
            'value'       => $data['value'],
            'type'        => $data['type'],
            'label'       => $data['label'],
            'description' => $data['description'] ?? null,
            'options'     => $options,
            'is_public'   => $request->boolean('is_public'),
            'is_active'   => $request->boolean('is_active'),
            'sort_order'  => $data['sort_order'] ?? 0,
        ]);

        return redirect()->route('system.index')
            ->with('success', "Setting \"{$setting->label}\" updated.");
    }

    // ── Delete ────────────────────────────────────────────────

    public function destroy(SystemSetting $setting)
    {
        $this->authorise($setting);
        $label = $setting->label;
        $setting->delete();

        return redirect()->route('system.index')
            ->with('success', "Setting \"{$label}\" deleted.");
    }

    // ── Toggle active ─────────────────────────────────────────

    public function toggleActive(SystemSetting $setting)
    {
        $this->authorise($setting);
        $setting->update(['is_active' => ! $setting->is_active]);

        return back()->with('success', "Setting \"{$setting->label}\" " . ($setting->is_active ? 'enabled' : 'disabled') . '.');
    }

    // ── Private helpers ───────────────────────────────────────

    private function authorise(SystemSetting $setting): void
    {
        abort_if($setting->organisation_id !== $this->orgId(), 403);
    }

    private function existingGroups(): array
    {
        return SystemSetting::forOrg($this->orgId())
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->toArray();
    }
}
