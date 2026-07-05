@extends('layouts.admin')

@section('title', 'Account')

@section('content')
<h1 style="margin-bottom:.35rem;">Account</h1>
<p class="form-hint" style="margin-bottom:1.5rem;">Manage your admin login and profile.</p>

<div class="account-section">
    <h2>Profile</h2>
    <form action="{{ route('admin.account.profile') }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255">
            @error('name')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255">
            @error('email')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="btn btn-primary">Save profile</button>
    </form>
</div>

<div class="account-section">
    <h2>Change password</h2>
    <form action="{{ route('admin.account.password') }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="current_password">Current password</label>
            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
            @error('current_password')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label for="password">New password</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">
            @error('password')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="form-group">
            <label for="password_confirmation">Confirm new password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary">Update password</button>
    </form>
</div>
@endsection

@push('styles')
<style>
.account-section {
    background: #fff;
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 8px;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
    max-width: 32rem;
}
.account-section h2 {
    margin: 0 0 1rem;
    font-size: 1.05rem;
}
</style>
@endpush
