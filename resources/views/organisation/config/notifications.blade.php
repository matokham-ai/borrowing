@extends('layouts.app')
@section('title', 'Notifications Configuration')

@section('content')

{{-- ══════════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════════ --}}
<div class="page-header">
    <div>
        <div class="eyebrow">ORGANISATION CONFIGURATION</div>
        <h1>Notifications Configuration</h1>
        <p>Configure SMS, email, and in-app notification settings.</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('organisation.index') }}" class="btn btn-light">
            <i class="bi bi-arrow-left"></i> Back to Overview
        </a>
    </div>
</div>

{{-- ══════════════════════════════════════════
     CONFIGURATION TABS
══════════════════════════════════════════ --}}
<div class="org-config-tabs-wrapper">
    <div class="org-config-tabs">
        <a href="{{ route('organisation.general') }}" class="org-config-tab">
            <i class="bi bi-gear"></i> General
        </a>
        <a href="{{ route('organisation.lending') }}" class="org-config-tab">
            <i class="bi bi-bank"></i> Lending
        </a>
        <a href="{{ route('organisation.payments') }}" class="org-config-tab">
            <i class="bi bi-credit-card"></i> Payments
        </a>
        <a href="{{ route('organisation.notifications') }}" class="org-config-tab active">
            <i class="bi bi-bell"></i> Notifications
        </a>
        <a href="{{ route('organisation.branding') }}" class="org-config-tab">
            <i class="bi bi-palette"></i> Branding
        </a>
    </div>
</div>

{{-- ══════════════════════════════════════════
     NOTIFICATIONS CONFIGURATION
══════════════════════════════════════════ --}}
<div class="config-form-container">
    <form action="{{ route('organisation.update.notifications') }}" method="POST">
        @csrf
        @method('PUT')

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="config-form-section">
            <h3>Delivery Channels</h3>
            <p class="config-form-subtitle">Choose how LendFlow communicates with borrowers and your team.</p>

            <div class="notification-toggle-list">
                <label class="notification-toggle-row">
                    <span><i class="bi bi-broadcast-pin"></i><span><strong>Notifications enabled</strong><small>Master switch for all organisation notifications.</small></span></span>
                    <input type="checkbox" name="notifications_enabled" value="1" class="form-check-input" @checked(old('notifications_enabled', $organisation->notifications_enabled ?? true))>
                </label>
                <label class="notification-toggle-row">
                    <span><i class="bi bi-chat-dots"></i><span><strong>SMS notifications</strong><small>Send repayment reminders and transaction updates by SMS.</small></span></span>
                    <input type="checkbox" name="sms_notifications_enabled" value="1" class="form-check-input" @checked(old('sms_notifications_enabled', $organisation->sms_notifications_enabled ?? true))>
                </label>
                <label class="notification-toggle-row">
                    <span><i class="bi bi-envelope"></i><span><strong>Email notifications</strong><small>Send statements, alerts, and account messages by email.</small></span></span>
                    <input type="checkbox" name="email_notifications_enabled" value="1" class="form-check-input" @checked(old('email_notifications_enabled', $organisation->email_notifications_enabled ?? true))>
                </label>
                <label class="notification-toggle-row">
                    <span><i class="bi bi-app-indicator"></i><span><strong>In-app notifications</strong><small>Show activity and workflow alerts inside LendFlow.</small></span></span>
                    <input type="checkbox" name="in_app_notifications_enabled" value="1" class="form-check-input" @checked(old('in_app_notifications_enabled', $organisation->in_app_notifications_enabled ?? true))>
                </label>
            </div>
        </div>

        <div class="config-form-section">
            <h3>Sender Details</h3>
            <p class="config-form-subtitle">Use consistent organisation details in outbound messages.</p>

            <div class="form-grid">
                <div class="form-group">
                    <label for="notification_sender_name">Sender Name</label>
                    <input id="notification_sender_name" name="notification_sender_name" class="form-control" maxlength="100" value="{{ old('notification_sender_name', $organisation->notification_sender_name) }}">
                    <small class="form-text">Shown as the sender identity where supported.</small>
                </div>
                <div class="form-group">
                    <label for="notification_reply_to_email">Reply-to Email</label>
                    <input id="notification_reply_to_email" name="notification_reply_to_email" type="email" class="form-control" value="{{ old('notification_reply_to_email', $organisation->notification_reply_to_email) }}">
                    <small class="form-text">Replies to notification emails will be directed here.</small>
                </div>
            </div>
        </div>

        <div class="config-form-actions">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Notification Settings</button>
            <a href="{{ route('organisation.index') }}" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>

@endsection
