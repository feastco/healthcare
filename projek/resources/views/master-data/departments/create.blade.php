@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Add Department" />

    <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        @include('master-data.departments._form', [
            'action' => route('departments.store'),
            'method' => 'POST',
            'submitLabel' => 'Save Department',
        ])
    </div>
@endsection