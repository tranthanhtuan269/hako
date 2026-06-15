@extends('layouts.app')

@section('title', 'Create Account')
@section('meta_robots', 'noindex, nofollow')

@section('content')
<div class="container" style="max-width:400px;padding:3rem 1rem;">
    <h1 style="margin-bottom:.5rem;">Create Account</h1>
    <p style="color:var(--muted);margin-bottom:1.5rem;">Join {{ config('site.name') }} to save your favorite deals.</p>
    @if(session('info'))
        <div class="alert alert-success">{{ session('info') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">
            <ul style="margin:0;padding-left:1.25rem;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="form-group">
            <label for="name">Full name</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus autocomplete="name">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autocomplete="email">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required autocomplete="new-password">
        </div>
        <div class="form-group">
            <label for="password_confirmation">Confirm password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:1rem;padding:.75rem;">Create Account</button>
    </form>
    <p style="margin-top:1.25rem;text-align:center;color:var(--muted);">
        Already have an account?
        <a href="{{ route('login') }}">Sign in</a>
    </p>
</div>
@endsection
