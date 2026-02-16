@extends('layouts.admin')

@section('title', 'Class Management')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Class Management</h1>
        <button onclick="openCreateModal()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
            <i class="fas fa-plus mr-2"></i>Add New Class
        </button>
    </div>
    
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('admin.classes.index') }}" class="flex gap-4">
            <div class="flex-1">
                <input type="date" name="date" value="{{ request('date', date('Y-m-d')) }}" 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <select name="instructor" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Instructors</option>
                @foreach($instructors as $instructor)
                    <option value="{{ $instructor->id }}" {{ request('instructor') == $instructor->id ? 'selected' : '' }}>
                        {{ $instructor->name }}
                    </option>
                @endforeach
            </select>
            <select name="status" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
        </form>
    </div>
    
    <!-- Classes Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instructor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($classes as $class)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $class->name }}</div>
                            <div class="text-sm text-gray-500">{{ $class->type }}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $class->instructor->name ?? 'No Instructor' }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($class->date)->format('M d, Y') }}</div>
                        <div class="text-sm text-gray-500">{{ $class->time }} ({{ $class->duration }} min)</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $class->location }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            {{ $class->current_participants }} / {{ $class->max_participants }}
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                            <div class="bg-blue-600 h-2 rounded-full" 
                                style="width: {{ $class->max_participants > 0 ? ($class->current_participants / $class->max_participants * 100) : 0 }}%">
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $class->status === 'active' ? 'bg-green-100 text-green-800' : 
                               ($class->status === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                            {{ ucfirst($class->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button onclick="viewClass({{ $class->id }})" class="text-blue-600 hover:text-blue-900 mr-3" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="editClass({{ $class->id }})" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        @if($class->status === 'active')
                            <button onclick="cancelClass({{ $class->id }})" class="text-yellow-600 hover:text-yellow-900 mr-3" title="Cancel">
                                <i class="fas fa-ban"></i>
                            </button>
                        @endif
                        <button onclick="deleteClass({{ $class->id }})" class="text-red-600 hover:text-red-900" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        No classes found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Class Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Edit Class</h3>
            <form id="editClassForm">
                @csrf
                <input type="hidden" id="edit_class_id">
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Class Name</label>
                        <input type="text" id="edit_name" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <input type="text" id="edit_type" name="type" placeholder="e.g., Yoga, Pilates, CrossFit" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Instructor</label>
                        <select id="edit_instructor_id" name="instructor_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">Select Instructor</option>
                            @foreach($instructors as $instructor)
                                <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                        <input type="text" id="edit_location" name="location" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Duration (minutes)</label>
                        <input type="number" id="edit_duration" name="duration" min="15" step="15" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Max Participants</label>
                        <input type="number" id="edit_max_participants" name="max_participants" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="edit_status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="active">Active</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    
                    <div class="mb-4 col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="edit_description" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
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

<!-- Create Class Modal -->
<div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Add New Class</h3>
            <form id="createClassForm">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Class Name</label>
                        <input type="text" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <input type="text" name="type" placeholder="e.g., Yoga, Pilates, CrossFit" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Instructor</label>
                        <select name="instructor_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">Select Instructor</option>
                            @foreach($instructors as $instructor)
                                <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                        <input type="text" name="location" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                        <input type="date" name="date" min="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Time</label>
                        <input type="time" name="time" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Duration (minutes)</label>
                        <input type="number" name="duration" min="15" step="15" value="60" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Max Participants</label>
                        <input type="number" name="max_participants" min="1" value="20" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4 col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end gap-4 mt-6">
                    <button type="button" onclick="closeCreateModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition">Create Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // Edit Class
    function editClass(id) {
        fetch(`/api/v1/classes/${id}`, {
            headers: {
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_class_id').value = data.id;
            document.getElementById('edit_name').value = data.name || '';
            document.getElementById('edit_type').value = data.type || '';
            document.getElementById('edit_instructor_id').value = data.instructor_id || '';
            document.getElementById('edit_location').value = data.location || '';
            document.getElementById('edit_date').value = data.date || '';
            document.getElementById('edit_time').value = data.time || '';
            document.getElementById('edit_duration').value = data.duration || '';
            document.getElementById('edit_max_participants').value = data.max_participants || '';
            document.getElementById('edit_status').value = data.status || 'active';
            document.getElementById('edit_description').value = data.description || '';
            
            document.getElementById('editModal').classList.remove('hidden');
        });
    }
    
    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editClassForm').reset();
    }
    
    // Submit Edit Form
    document.getElementById('editClassForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const classId = document.getElementById('edit_class_id').value;
        const formData = new FormData(this);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() !== '') {
                data[key] = value;
            }
        }
        
        fetch(`/api/v1/classes/${classId}`, {
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
                alert('Class updated successfully!');
                closeEditModal();
                location.reload();
            }
        })
        .catch(error => {
            alert('Error updating class: ' + error.message);
        });
    });
    
    // Create Class
    function openCreateModal() {
        document.getElementById('createModal').classList.remove('hidden');
    }
    
    function closeCreateModal() {
        document.getElementById('createModal').classList.add('hidden');
        document.getElementById('createClassForm').reset();
    }
    
    document.getElementById('createClassForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() !== '') {
                data[key] = value;
            }
        }
        
        fetch('/api/v1/classes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content || ''
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.id) {
                alert('Class created successfully!');
                closeCreateModal();
                location.reload();
            }
        })
        .catch(error => {
            alert('Error creating class: ' + error.message);
        });
    });
    
    // View Class
    function viewClass(id) {
        window.location.href = `/admin/classes/${id}`;
    }
    
    // Cancel Class
    function cancelClass(id) {
        if (confirm('Are you sure you want to cancel this class?')) {
            fetch(`/api/v1/classes/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content || ''
                },
                body: JSON.stringify({ status: 'cancelled' })
            })
            .then(response => response.json())
            .then(result => {
                alert('Class cancelled successfully!');
                location.reload();
            })
            .catch(error => {
                alert('Error cancelling class: ' + error.message);
            });
        }
    }
    
    // Delete Class
    function deleteClass(id) {
        if (confirm('Are you sure you want to delete this class? This action cannot be undone.')) {
            fetch(`/api/v1/classes/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content || ''
                }
            })
            .then(response => response.json())
            .then(result => {
                alert('Class deleted successfully!');
                location.reload();
            })
            .catch(error => {
                alert('Error deleting class: ' + error.message);
            });
        }
    }
</script>
@endsection