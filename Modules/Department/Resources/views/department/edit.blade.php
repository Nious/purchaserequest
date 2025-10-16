@extends('layouts.app')

@section('title', 'Edit Department')

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('departments.index') }}">Departments</a></li>
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
                        <form action="{{ route('departments.update', $department->id) }}" method="POST">
                            @csrf
                            @method('patch')
                            <div class="form-group">
                                <label class="font-weight-bold" for="department_code">Department Code <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" name="department_code" required value="{{ $department->department_code }}">
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold" for="department_name">Department Name <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" name="department_name" required value="{{ $department->department_name }}">
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