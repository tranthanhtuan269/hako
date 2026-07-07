@foreach($projects as $project)
    @include('admin.affiliate-signups._row', ['project' => $project])
@endforeach
