@extends('layouts.app')

@section('title', 'Master Budget Report')

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Reports</li>
        <li class="breadcrumb-item active">Master Budget Report</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">
        <livewire:reports.master-budget-report :departments="\Modules\Department\Entities\Departments::all()"/>
    </div>
@endsection
