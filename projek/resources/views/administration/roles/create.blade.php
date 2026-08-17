@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Add Role" />

    <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        @include('administration.roles._form', [
            'action' => route('roles.store'),
            'method' => 'POST',
            'submitLabel' => 'Save Role',
            'role' => null,
            'permissions' => $permissions,
        ])
    </div>
@endsection