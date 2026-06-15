@extends('layouts.app')

@section('title', 'Sign In')
@section('meta_robots', 'noindex, nofollow')

@section('content')
<div class="container" style="max-width:400px;padding:3rem 1rem;">
    <h1 style="margin-bottom:1.5rem;">Sign In</h1>
    @if($errors->any())
        <div class="alert alert-error">
            @foreach($errors->all() as $error){{ $error }}@endforeach
        </div>
    @endif
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-check">
            <input type="checkbox" name="remember" id="remember">
            <label for="remember">Remember me</label>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:1rem;padding:.75rem;">Sign In</button>
    </form>
    <p style="margin-top:1.25rem;text-align:center;color:var(--muted);">
        Don't have an account?
        <a href="{{ route('register') }}">Create one</a>
    </p>
</div>
@endsection
