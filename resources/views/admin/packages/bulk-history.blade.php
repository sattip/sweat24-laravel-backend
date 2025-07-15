@extends('layouts.admin')

@section('title', 'Bulk Operations History')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Bulk Operations History</h1>
        <div class="flex gap-4">
            <a href="{{ route('admin.packages.bulk.index') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Bulk Operations
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('admin.packages.bulk.history') }}" class="flex gap-4">
            <div class="flex-1">
                <select name="type" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="package_extension" {{ request('type') == 'package_extension' ? 'selected' : '' }}>Package Extension</option>
                    <option value="pricing_adjustment" {{ request('type') == 'pricing_adjustment' ? 'selected' : '' }}>Pricing Adjustment</option>
                </select>
            </div>
            <div class="flex-1">
                <select name="status" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="completed_with_errors" {{ request('status') == 'completed_with_errors' ? 'selected' : '' }}>Completed with Errors</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="flex-1">
                <input type="date" name="from_date" value="{{ request('from_date') }}" 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex-1">
                <input type="date" name="to_date" value="{{ request('to_date') }}" 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
            <a href="{{ route('admin.packages.bulk.history') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                <i class="fas fa-times mr-2"></i>Clear
            </a>
        </form>
    </div>

    <!-- Operations List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performed By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($operations as $operation)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $operation->getTypeLabel() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($operation->status === 'completed') bg-green-100 text-green-800
                                @elseif($operation->status === 'completed_with_errors') bg-yellow-100 text-yellow-800
                                @elseif($operation->status === 'failed') bg-red-100 text-red-800
                                @elseif($operation->status === 'cancelled') bg-gray-100 text-gray-800
                                @else bg-blue-100 text-blue-800
                                @endif">
                                {{ $operation->getStatusLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {{ $operation->successful_count }} / {{ $operation->target_count }}
                                @if($operation->target_count > 0)
                                    ({{ $operation->success_rate }}%)
                                @endif
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ $operation->success_rate }}%"></div>
                            </div>
                            @if($operation->failed_count > 0)
                                <div class="text-xs text-red-600 mt-1">{{ $operation->failed_count }} failed</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $operation->performedBy->name ?? 'Unknown' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $operation->created_at->format('M d, Y') }}</div>
                            <div class="text-sm text-gray-500">{{ $operation->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $operation->duration ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.packages.bulk.show-operation', $operation) }}" 
                                   class="text-blue-600 hover:text-blue-900" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($operation->isRunning())
                                    <button type="button" 
                                            class="text-red-600 hover:text-red-900 cancel-operation" 
                                            data-operation-id="{{ $operation->id }}" 
                                            title="Cancel Operation">
                                        <i class="fas fa-stop"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No operations found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $operations->links() }}
    </div>
</div>

<!-- Cancel Operation Modal -->
<div id="cancel-operation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Cancel Operation</h3>
            <button type="button" id="close-cancel-modal" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="mb-4">
            <p class="text-sm text-gray-600">Are you sure you want to cancel this operation? This action cannot be undone.</p>
        </div>
        
        <div class="flex justify-end space-x-2">
            <button type="button" id="cancel-cancel-operation" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                No, Keep Running
            </button>
            <button type="button" id="confirm-cancel-operation" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition">
                Yes, Cancel
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let operationToCancel = null;

    // Cancel operation buttons
    document.querySelectorAll('.cancel-operation').forEach(button => {
        button.addEventListener('click', function() {
            operationToCancel = this.dataset.operationId;
            document.getElementById('cancel-operation-modal').classList.remove('hidden');
            document.getElementById('cancel-operation-modal').classList.add('flex');
        });
    });

    // Close cancel modal
    document.getElementById('close-cancel-modal').addEventListener('click', function() {
        document.getElementById('cancel-operation-modal').classList.add('hidden');
        document.getElementById('cancel-operation-modal').classList.remove('flex');
        operationToCancel = null;
    });

    // Cancel cancel operation
    document.getElementById('cancel-cancel-operation').addEventListener('click', function() {
        document.getElementById('cancel-operation-modal').classList.add('hidden');
        document.getElementById('cancel-operation-modal').classList.remove('flex');
        operationToCancel = null;
    });

    // Confirm cancel operation
    document.getElementById('confirm-cancel-operation').addEventListener('click', function() {
        if (operationToCancel) {
            fetch(`{{ route('admin.packages.bulk.cancel-operation', '') }}/${operationToCancel}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while cancelling the operation.');
            });
        }
        
        document.getElementById('cancel-operation-modal').classList.add('hidden');
        document.getElementById('cancel-operation-modal').classList.remove('flex');
        operationToCancel = null;
    });
});
</script>
@endsection