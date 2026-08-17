@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Add Patient" />

    <div
        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        @include('master-data.patients._form', [
            'action' => route('patients.store'),
            'method' => 'POST',
            'patient' => null,
            'submitLabel' => 'Save Patient',
        ])
    </div>
@endsection