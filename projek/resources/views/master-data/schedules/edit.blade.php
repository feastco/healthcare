@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Edit Schedule" />

    <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        @include('master-data.schedules._form', [
            'action' => route('schedules.update', $schedule),
            'method' => 'PUT',
            'submitLabel' => 'Update Schedule',
            'schedule' => $schedule,
        ])
    </div>
@endsection