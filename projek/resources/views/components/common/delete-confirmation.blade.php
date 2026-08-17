@props([
    'action' => null,
    'label' => 'Delete',
    'title' => 'Delete Confirmation',
    'message' => 'Apakah Anda yakin ingin menghapus data ini?',
])

<div x-data>
    <button type="button" @click="$refs.dialog.showModal()"
        class="inline-flex items-center gap-2 rounded-lg bg-red-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-red-600">
        {{ $label }}
    </button>

    <dialog x-ref="dialog" aria-labelledby="delete-confirmation-title"
        class="m-auto w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
        <h2 id="delete-confirmation-title" class="text-lg font-semibold text-gray-800 dark:text-white">{{ $title }}</h2>
        <p class="mt-2 text-theme-sm text-gray-500 dark:text-gray-400">{{ $message }}</p>

        <div class="mt-6 flex items-center justify-end gap-3">
            <button type="button" @click="$refs.dialog.close()"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2.5 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                Cancel
            </button>
            <form action="{{ $action }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-red-500 px-4 py-2.5 text-theme-sm font-medium text-white hover:bg-red-600">
                    {{ $label }}
                </button>
            </form>
        </div>
    </dialog>
</div>