@extends('layouts.admin')

@section('title', 'Booking Management')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Booking Management</h1>
        <button onclick="openCreateModal()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
            <i class="fas fa-plus mr-2"></i>New Booking
        </button>
    </div>
    
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('admin.bookings.index') }}" class="flex gap-4">
            <div class="flex-1">
                <input type="date" name="date" value="{{ request('date', date('Y-m-d')) }}" 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <select name="status" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Status</option>
                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="no_show" {{ request('status') == 'no_show' ? 'selected' : '' }}>No Show</option>
            </select>
            <input type="text" name="instructor" value="{{ request('instructor') }}" 
                placeholder="Instructor name..." 
                class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
        </form>
    </div>
    
    <!-- Bookings Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instructor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($bookings as $booking)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $booking->customer_name }}</div>
                            <div class="text-sm text-gray-500">{{ $booking->customer_email }}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $booking->class_name }}</div>
                            <div class="text-sm text-gray-500">{{ $booking->type }}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($booking->date)->setTimezone(config('app.timezone'))->format('M d, Y') }}</div>
                        <div class="text-sm text-gray-500">{{ $booking->time }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $booking->instructor }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $booking->status === 'confirmed' ? 'bg-blue-100 text-blue-800' : 
                               ($booking->status === 'completed' ? 'bg-green-100 text-green-800' : 
                               ($booking->status === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                            {{ ucfirst($booking->status) }}
                        </span>
                        @if($booking->attended)
                            <span class="ml-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Attended
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button onclick="editBooking({{ $booking->id }})" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        @if($booking->status === 'confirmed')
                            <button onclick="checkInBooking({{ $booking->id }})" class="text-green-600 hover:text-green-900 mr-3" title="Check In">
                                <i class="fas fa-check-circle"></i>
                            </button>
                            <button onclick="cancelBooking({{ $booking->id }})" class="text-yellow-600 hover:text-yellow-900 mr-3" title="Cancel">
                                <i class="fas fa-ban"></i>
                            </button>
                        @endif
                        <button onclick="deleteBooking({{ $booking->id }})" class="text-red-600 hover:text-red-900" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        No bookings found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <!-- Pagination -->
        @if($bookings->hasPages())
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
            {{ $bookings->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Edit Booking Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Edit Booking</h3>
            <form id="editBookingForm">
                @csrf
                <input type="hidden" id="edit_booking_id">
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Customer Name</label>
                        <input type="text" id="edit_customer_name" name="customer_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Customer Email</label>
                        <input type="email" id="edit_customer_email" name="customer_email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Class Name</label>
                        <input type="text" id="edit_class_name" name="class_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <input type="text" id="edit_type" name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Instructor</label>
                        <input type="text" id="edit_instructor" name="instructor" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                        <input type="text" id="edit_location" name="location" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                        <input type="date" id="edit_date" name="date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Time</label>
                        <input type="time" id="edit_time" name="time" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="edit_status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="confirmed">Confirmed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="completed">Completed</option>
                            <option value="no_show">No Show</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Attended</label>
                        <select id="edit_attended" name="attended" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    
                    <div class="mb-4 col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cancellation Reason</label>
                        <textarea id="edit_cancellation_reason" name="cancellation_reason" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end gap-4 mt-6">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // Edit Booking
    function editBooking(id) {
        fetch(`/api/v1/bookings/${id}`, {
            headers: {
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_booking_id').value = data.id;
            document.getElementById('edit_customer_name').value = data.customer_name || '';
            document.getElementById('edit_customer_email').value = data.customer_email || '';
            document.getElementById('edit_class_name').value = data.class_name || '';
            document.getElementById('edit_type').value = data.type || '';
            document.getElementById('edit_instructor').value = data.instructor || '';
            document.getElementById('edit_location').value = data.location || '';
            document.getElementById('edit_date').value = data.date || '';
            document.getElementById('edit_time').value = data.time || '';
            document.getElementById('edit_status').value = data.status || 'confirmed';
            document.getElementById('edit_attended').value = data.attended ? '1' : '0';
            document.getElementById('edit_cancellation_reason').value = data.cancellation_reason || '';
            
            document.getElementById('editModal').classList.remove('hidden');
        });
    }
    
    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editBookingForm').reset();
    }
    
    // Submit Edit Form
    document.getElementById('editBookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const bookingId = document.getElementById('edit_booking_id').value;
        const formData = new FormData(this);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() !== '') {
                if (key === 'attended') {
                    data[key] = value === '1';
                } else {
                    data[key] = value;
                }
            }
        }
        
        fetch(`/api/v1/bookings/${bookingId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content || ''
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.id) {
                alert('Booking updated successfully!');
                closeEditModal();
                location.reload();
            }
        })
        .catch(error => {
            alert('Error updating booking: ' + error.message);
        });
    });
    
    // Check In Booking
    function checkInBooking(id) {
        if (confirm('Mark this booking as attended?')) {
            fetch(`/api/v1/bookings/${id}/check-in`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content || '',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(result => {
                alert('Booking checked in successfully!');
                location.reload();
            })
            .catch(error => {
                alert('Error checking in booking: ' + error.message);
            });
        }
    }
    
    // Cancel Booking
    function cancelBooking(id) {
        const reason = prompt('Please provide a cancellation reason (optional):');
        
        fetch(`/api/v1/bookings/${id}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content || '',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ cancellation_reason: reason })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert(result.message);
                location.reload();
            }
        })
        .catch(error => {
            alert('Error cancelling booking: ' + error.message);
        });
    }
    
    // Delete Booking
    function deleteBooking(id) {
        if (confirm('Are you sure you want to delete this booking? This action cannot be undone.')) {
            fetch(`/api/v1/bookings/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content || ''
                }
            })
            .then(response => response.json())
            .then(result => {
                alert('Booking deleted successfully!');
                location.reload();
            })
            .catch(error => {
                alert('Error deleting booking: ' + error.message);
            });
        }
    }
    
    // Create Booking (placeholder)
    function openCreateModal() {
        alert('Create booking functionality will be implemented with class selection');
    }
</script>
@endsection