@extends('layouts.app')

@section('title', 'Edit Department')

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('approval_types.index') }}">Approval Types</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-7">
                @include('utils.alerts')
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('approval_types.update', $approvalType->id) }}" method="POST">
                            @csrf
                            @method('patch')
                            <div class="form-group">
                                <label class="font-weight-bold" for="approval_code">Approval Code <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" name="approval_code" required value="{{ $approvalType->approval_code }}">
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold" for="appproval_name">Approval Name <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" name="approval_name" required value="{{ $approvalType->approval_name }}">
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Update <i class="bi bi-check"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection