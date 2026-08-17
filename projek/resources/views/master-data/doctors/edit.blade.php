@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Edit Doctor" />

    <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        @include('master-data.doctors._form', [
            'action' => route('doctors.update', $doctor),
            'method' => 'PUT',
            'submitLabel' => 'Update Doctor',
            'doctor' => $doctor,
        ])
    </div>
@endsection