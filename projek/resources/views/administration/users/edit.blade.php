@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Edit User" />

    <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        @include('administration.users._form', [
            'action' => route('users.update', $user),
            'method' => 'PUT',
            'submitLabel' => 'Update User',
            'user' => $user,
            'roles' => $roles,
        ])
    </div>
@endsection