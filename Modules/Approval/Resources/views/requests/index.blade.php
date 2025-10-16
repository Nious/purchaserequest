@extends('layouts.app')
@section('title','Approval Requests')
@section('content')
<div class="container-fluid">
    @include('utils.alerts')
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead><tr><th>#</th><th>Doc</th><th>Type</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                @foreach($list as $r)
                    <tr>
                        <td>{{ $r->id }}</td>
                        <td>{{ class_basename($r->requestable_type) }}#{{ $r->requestable_id }}</td>
                        <td>{{ $r->type->approval_name ?? '-' }}</td>
                        <td>{{ number_format($r->amount,0) }}</td>
                        <td>{{ $r->status }}</td>
                        <td><a href="{{ route('approval_requests.show',$r->id) }}" class="btn btn-sm btn-primary">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $list->links() }}
        </div>
    </div>
</div>
@endsection
