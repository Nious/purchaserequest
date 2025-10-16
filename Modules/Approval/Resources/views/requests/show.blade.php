@extends('layouts.app')
@section('title','Approval Request #'.$req->id)
@section('content')
<div class="container-fluid">
    @include('utils.alerts')
    <div class="card mb-3">
        <div class="card-header">
            Request #{{ $req->id }} — {{ $req->type->approval_name ?? '' }} — {{ number_format($req->amount,0) }}
        </div>
        <div class="card-body">
            <p><b>Doc:</b> {{ class_basename($req->requestable_type) }}#{{ $req->requestable_id }}</p>
            <p><b>Current Level:</b> {{ $req->current_level }}</p>
            <p><b>Status:</b> {{ $req->status }}</p>
            <hr>
            <h6>Logs</h6>
            <ul>
            @foreach($req->logs as $log)
                <li>[{{ $log->created_at }}] (L{{ $log->level }}) {{ $log->action }} by {{ optional(\App\Models\User::find($log->user_id))->name ?? $log->user_id }} — {{ $log->comment }}</li>
            @endforeach
            </ul>

            @php $isApprover = in_array(auth()->id(), (array)$approverIds->toArray()); @endphp

            @if($req->status == 'pending' && $isApprover)
                <form method="POST" action="{{ route('approval_requests.approve',$req->id) }}" style="display:inline">
                    @csrf
                    <input name="comment" class="form-control mb-2" placeholder="Comment (optional)">
                    <button class="btn btn-success">Approve</button>
                </form>

                <form method="POST" action="{{ route('approval_requests.reject',$req->id) }}" style="display:inline">
                    @csrf
                    <input name="comment" class="form-control mb-2" placeholder="Reason (optional)">
                    <button class="btn btn-danger">Reject</button>
                </form>
            @endif
        </div>
    </div>

    <a href="{{ route('approval_requests.index') }}" class="btn btn-secondary">Back</a>
</div>
@endsection
