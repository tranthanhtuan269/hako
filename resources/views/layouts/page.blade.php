@extends('layouts.app')

@section('content')
<div class="page-hero">
    <div class="container">
        <h1>@yield('page_title')</h1>
        @hasSection('page_subtitle')
            <p class="page-subtitle">@yield('page_subtitle')</p>
        @endif
        @hasSection('page_meta')
            <p class="page-meta">@yield('page_meta')</p>
        @endif
    </div>
</div>
<div class="container page-body">
    <div class="legal-content">
        @yield('page_content')
    </div>
</div>
@endsection
