@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Edit Patient" />

    <div
        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        @include('master-data.patients._form', [
            'action' => route('patients.update', $patient),
            'method' => 'PUT',
            'patient' => $patient,
            'submitLabel' => 'Update Patient',
        ])
    </div>
@endsection