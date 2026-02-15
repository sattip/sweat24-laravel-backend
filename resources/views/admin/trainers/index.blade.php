@extends('layouts.admin')

@section('title', 'Trainer Management')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Trainer Management</h1>
        <button onclick="openCreateModal()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
            <i class="fas fa-plus mr-2"></i>Add New Trainer
        </button>
    </div>
    
    <!-- Trainers Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trainer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specialties</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contract</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($trainers as $trainer)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                    <i class="fas fa-user-tie text-indigo-600"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">{{ $trainer->name }}</div>
                                <div class="text-sm text-gray-500">{{ $trainer->email ?? 'No email' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-1">
                            @foreach($trainer->specialties as $specialty)
                                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">{{ $specialty }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900">{{ ucfirst($trainer->contract_type) }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">€{{ number_format($trainer->hourly_rate, 2) }}/hr</div>
                        @if($trainer->commission_rate)
                            <div class="text-xs text-gray-500">{{ $trainer->commission_rate * 100 }}% commission</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $trainer->status === 'active' ? 'bg-green-100 text-green-800' : 
                               ($trainer->status === 'vacation' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                            {{ ucfirst($trainer->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $trainer->completed_sessions }} sessions</div>
                        <div class="text-xs text-gray-500">€{{ number_format($trainer->total_revenue, 2) }} revenue</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button onclick="viewTrainer({{ $trainer->id }})" class="text-blue-600 hover:text-blue-900 mr-3">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="editTrainer({{ $trainer->id }})" class="text-indigo-600 hover:text-indigo-900 mr-3">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteTrainer({{ $trainer->id }})" class="text-red-600 hover:text-red-900">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        No trainers found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Trainer Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Edit Trainer</h3>
            <form id="editTrainerForm">
                @csrf
                <input type="hidden" id="edit_trainer_id">
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input type="text" id="edit_name" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" id="edit_email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                        <input type="tel" id="edit_phone" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="edit_status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="vacation">On Vacation</option>
                        </select>
                    </div>
                    
                    <div class="mb-4 col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Specialties (comma separated)</label>
                        <input type="text" id="edit_specialties" name="specialties" placeholder="Yoga, Pilates, Crossfit" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contract Type</label>
                        <select id="edit_contract_type" name="contract_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="hourly">Hourly</option>
                            <option value="salary">Salary</option>
                            <option value="commission">Commission</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hourly Rate (€)</label>
                        <input type="number" id="edit_hourly_rate" name="hourly_rate" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Monthly Bonus (€)</label>
                        <input type="number" id="edit_monthly_bonus" name="monthly_bonus" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Commission Rate (%)</label>
                        <input type="number" id="edit_commission_rate" name="commission_rate" step="0.01" min="0" max="100" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
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

<!-- Create Trainer Modal -->
<div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Add New Trainer</h3>
            <form id="createTrainerForm">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input type="text" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                        <input type="tel" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Join Date</label>
                        <input type="date" name="join_date" value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4 col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Specialties (comma separated)</label>
                        <input type="text" name="specialties" placeholder="Yoga, Pilates, Crossfit" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contract Type</label>
                        <select name="contract_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="hourly">Hourly</option>
                            <option value="salary">Salary</option>
                            <option value="commission">Commission</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hourly Rate (€)</label>
                        <input type="number" name="hourly_rate" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Monthly Bonus (€)</label>
                        <input type="number" name="monthly_bonus" step="0.01" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Commission Rate (%)</label>
                        <input type="number" name="commission_rate" step="0.01" min="0" max="100" value="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="flex justify-end gap-4 mt-6">
                    <button type="button" onclick="closeCreateModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition">Create Trainer</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // Edit Trainer
    function editTrainer(id) {
        fetch(`/api/v1/instructors/${id}`, {
            headers: {
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_trainer_id').value = data.id;
            document.getElementById('edit_name').value = data.name || '';
            document.getElementById('edit_email').value = data.email || '';
            document.getElementById('edit_phone').value = data.phone || '';
            document.getElementById('edit_status').value = data.status || 'active';
            document.getElementById('edit_specialties').value = data.specialties ? data.specialties.join(', ') : '';
            document.getElementById('edit_contract_type').value = data.contract_type || 'hourly';
            document.getElementById('edit_hourly_rate').value = data.hourly_rate || '';
            document.getElementById('edit_monthly_bonus').value = data.monthly_bonus || 0;
            document.getElementById('edit_commission_rate').value = data.commission_rate ? data.commission_rate * 100 : 0;
            
            document.getElementById('editModal').classList.remove('hidden');
        });
    }
    
    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editTrainerForm').reset();
    }
    
    // Submit Edit Form
    document.getElementById('editTrainerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const trainerId = document.getElementById('edit_trainer_id').value;
        const formData = new FormData(this);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() !== '') {
                if (key === 'specialties') {
                    data[key] = value.split(',').map(s => s.trim());
                } else if (key === 'commission_rate') {
                    data[key] = parseFloat(value) / 100;
                } else {
                    data[key] = value;
                }
            }
        }
        
        fetch(`/api/v1/instructors/${trainerId}`, {
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
                alert('Trainer updated successfully!');
                closeEditModal();
                location.reload();
            }
        })
        .catch(error => {
            alert('Error updating trainer: ' + error.message);
        });
    });
    
    // Create Trainer
    function openCreateModal() {
        document.getElementById('createModal').classList.remove('hidden');
    }
    
    function closeCreateModal() {
        document.getElementById('createModal').classList.add('hidden');
        document.getElementById('createTrainerForm').reset();
    }
    
    document.getElementById('createTrainerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() !== '') {
                if (key === 'specialties') {
                    data[key] = value.split(',').map(s => s.trim());
                } else if (key === 'commission_rate') {
                    data[key] = parseFloat(value) / 100;
                } else {
                    data[key] = value;
                }
            }
        }
        
        // Set default values
        data.status = 'active';
        data.total_revenue = 0;
        data.completed_sessions = 0;
        
        fetch('/api/v1/instructors', {
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
                alert('Trainer created successfully!');
                closeCreateModal();
                location.reload();
            }
        })
        .catch(error => {
            alert('Error creating trainer: ' + error.message);
        });
    });
    
    // View Trainer
    function viewTrainer(id) {
        window.location.href = `/admin/trainers/${id}`;
    }
    
    // Delete Trainer
    function deleteTrainer(id) {
        if (confirm('Are you sure you want to delete this trainer? This action cannot be undone.')) {
            fetch(`/api/v1/instructors/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]')?.content || ''
                }
            })
            .then(response => response.json())
            .then(result => {
                alert('Trainer deleted successfully!');
                location.reload();
            })
            .catch(error => {
                alert('Error deleting trainer: ' + error.message);
            });
        }
    }
</script>
@endsection