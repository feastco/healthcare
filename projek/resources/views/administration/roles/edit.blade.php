@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Edit Role" />

    <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        @include('administration.roles._form', [
            'action' => route('roles.update', $role),
            'method' => 'PUT',
            'submitLabel' => 'Update Role',
            'role' => $role,
            'permissions' => $permissions,
        ])
    </div>
@endsection