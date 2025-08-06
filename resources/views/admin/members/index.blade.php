@extends('layouts.admin')

@section('title', 'Member Management')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Member Management</h1>
        <button onclick="openCreateModal()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
            <i class="fas fa-plus mr-2"></i>Add New Member
        </button>
    </div>
    
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('admin.members.index') }}" class="flex gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" 
                    placeholder="Search by name, email, or phone..." 
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <select name="status" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
            </select>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                <i class="fas fa-search mr-2"></i>Filter
            </button>
            <a href="{{ route('admin.members.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                <i class="fas fa-times mr-2"></i>Clear
            </a>
        </form>
    </div>
    
    <!-- Members Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membership</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referral</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($members as $member)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                    <i class="fas fa-user text-gray-600"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">{{ $member->name }}</div>
                                <div class="text-sm text-gray-500">ID: {{ $member->id }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $member->email }}</div>
                        <div class="text-sm text-gray-500">{{ $member->phone ?? 'No phone' }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $member->membership_type ?? 'Standard' }}</div>
                        @if($member->packages->count() > 0)
                            <div class="text-sm text-gray-500">{{ $member->packages->count() }} active package(s)</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $member->status === 'active' ? 'bg-green-100 text-green-800' : 
                               ($member->status === 'inactive' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800') }}">
                            {{ ucfirst($member->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($member->found_us_via)
                            <div class="text-sm text-gray-900">{{ $member->how_found_us_display }}</div>
                            @if($member->referrer)
                                <div class="text-xs text-blue-600">By: {{ $member->referrer->name }}</div>
                            @elseif($member->social_platform)
                                <div class="text-xs text-gray-500">{{ ucfirst($member->social_platform) }}</div>
                            @endif
                        @else
                            <span class="text-sm text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $member->created_at->format('M d, Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button onclick="viewMember({{ $member->id }})" class="text-blue-600 hover:text-blue-900 mr-3">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button onclick="editMember({{ $member->id }})" class="text-indigo-600 hover:text-indigo-900 mr-3">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button onclick="deleteMember({{ $member->id }})" class="text-red-600 hover:text-red-900">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        No members found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <!-- Pagination -->
        @if($members->hasPages())
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
            {{ $members->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Edit Member Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Edit Member</h3>
            <form id="editMemberForm">
                @csrf
                <input type="hidden" id="edit_member_id">
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input type="text" id="edit_name" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" id="edit_email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
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
                            <option value="expired">Expired</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                        <input type="date" id="edit_date_of_birth" name="date_of_birth" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Membership Type</label>
                        <input type="text" id="edit_membership_type" name="membership_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4 col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                        <input type="text" id="edit_address" name="address" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Emergency Contact</label>
                        <input type="text" id="edit_emergency_contact" name="emergency_contact" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Emergency Phone</label>
                        <input type="tel" id="edit_emergency_phone" name="emergency_phone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4 col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Medical History</label>
                        <textarea id="edit_medical_history" name="medical_history" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div class="mb-4 col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                        <textarea id="edit_notes" name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
                
                <div class="border-t pt-4 mt-4">
                    <h4 class="text-md font-medium text-gray-700 mb-2">Change Password (Optional)</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <input type="password" id="edit_password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                            <input type="password" id="edit_password_confirmation" name="password_confirmation" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
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

<!-- View Member Modal -->
<div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Member Details</h3>
                <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="memberDetails" class="space-y-4">
                <!-- Member details will be loaded here -->
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // Edit Member
    function editMember(id) {
        fetch(`/api/v1/users/${id}`, {
            headers: {
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_member_id').value = data.id;
            document.getElementById('edit_name').value = data.name || '';
            document.getElementById('edit_email').value = data.email || '';
            document.getElementById('edit_phone').value = data.phone || '';
            document.getElementById('edit_status').value = data.status || 'active';
            document.getElementById('edit_date_of_birth').value = data.date_of_birth || '';
            document.getElementById('edit_membership_type').value = data.membership_type || '';
            document.getElementById('edit_address').value = data.address || '';
            document.getElementById('edit_emergency_contact').value = data.emergency_contact || '';
            document.getElementById('edit_emergency_phone').value = data.emergency_phone || '';
            document.getElementById('edit_medical_history').value = data.medical_history || '';
            document.getElementById('edit_notes').value = data.notes || '';
            
            document.getElementById('editModal').classList.remove('hidden');
        });
    }
    
    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editMemberForm').reset();
    }
    
    // Submit Edit Form
    document.getElementById('editMemberForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const memberId = document.getElementById('edit_member_id').value;
        const formData = new FormData(this);
        const data = {};
        
        // Only include fields that have values
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() !== '') {
                data[key] = value;
            }
        }
        
        fetch(`/api/v1/users/${memberId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]').content
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.id) {
                alert('Member updated successfully!');
                closeEditModal();
                location.reload();
            }
        })
        .catch(error => {
            alert('Error updating member: ' + error.message);
        });
    });
    
    // View Member
    function viewMember(id) {
        fetch(`/api/v1/users/${id}`, {
            headers: {
                'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            const detailsHtml = `
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-700">Personal Information</h4>
                        <dl class="mt-2 space-y-2">
                            <div><dt class="text-sm text-gray-500">Name:</dt><dd class="text-sm font-medium">${data.name}</dd></div>
                            <div><dt class="text-sm text-gray-500">Email:</dt><dd class="text-sm font-medium">${data.email}</dd></div>
                            <div><dt class="text-sm text-gray-500">Phone:</dt><dd class="text-sm font-medium">${data.phone || 'N/A'}</dd></div>
                            <div><dt class="text-sm text-gray-500">Date of Birth:</dt><dd class="text-sm font-medium">${data.date_of_birth || 'N/A'}</dd></div>
                            <div><dt class="text-sm text-gray-500">Address:</dt><dd class="text-sm font-medium">${data.address || 'N/A'}</dd></div>
                        </dl>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-700">Membership Information</h4>
                        <dl class="mt-2 space-y-2">
                            <div><dt class="text-sm text-gray-500">Status:</dt><dd class="text-sm font-medium">${data.status}</dd></div>
                            <div><dt class="text-sm text-gray-500">Membership Type:</dt><dd class="text-sm font-medium">${data.membership_type || 'Standard'}</dd></div>
                            <div><dt class="text-sm text-gray-500">Join Date:</dt><dd class="text-sm font-medium">${new Date(data.created_at).toLocaleDateString()}</dd></div>
                            <div><dt class="text-sm text-gray-500">Active Packages:</dt><dd class="text-sm font-medium">${data.packages ? data.packages.length : 0}</dd></div>
                        </dl>
                    </div>
                </div>
                
                ${data.packages && data.packages.length > 0 ? `
                    <div class="mt-6">
                        <h4 class="font-semibold text-gray-700 mb-2">Active Packages</h4>
                        <div class="space-y-2">
                            ${data.packages.map(pkg => `
                                <div class="bg-gray-50 p-3 rounded">
                                    <div class="flex justify-between">
                                        <span class="font-medium">${pkg.name}</span>
                                        <span class="text-sm text-gray-500">Expires: ${new Date(pkg.expiry_date).toLocaleDateString()}</span>
                                    </div>
                                    <div class="text-sm text-gray-600">Sessions: ${pkg.remaining_sessions}/${pkg.total_sessions}</div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
                
                ${data.emergency_contact || data.medical_history ? `
                    <div class="mt-6">
                        <h4 class="font-semibold text-gray-700 mb-2">Emergency & Medical Information</h4>
                        <dl class="space-y-2">
                            ${data.emergency_contact ? `<div><dt class="text-sm text-gray-500">Emergency Contact:</dt><dd class="text-sm font-medium">${data.emergency_contact} (${data.emergency_phone || 'No phone'})</dd></div>` : ''}
                            ${data.medical_history ? `<div><dt class="text-sm text-gray-500">Medical History:</dt><dd class="text-sm">${data.medical_history}</dd></div>` : ''}
                        </dl>
                    </div>
                ` : ''}
            `;
            
            document.getElementById('memberDetails').innerHTML = detailsHtml;
            document.getElementById('viewModal').classList.remove('hidden');
        });
    }
    
    function closeViewModal() {
        document.getElementById('viewModal').classList.add('hidden');
    }
    
    // Delete Member
    function deleteMember(id) {
        if (confirm('Are you sure you want to delete this member? This action cannot be undone.')) {
            fetch(`/api/v1/users/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + document.querySelector('meta[name="api-token"]').content
                }
            })
            .then(response => response.json())
            .then(result => {
                alert('Member deleted successfully!');
                location.reload();
            })
            .catch(error => {
                alert('Error deleting member: ' + error.message);
            });
        }
    }
    
    // Create Member (placeholder)
    function openCreateModal() {
        alert('Create member functionality will be implemented next');
    }
</script>
@endsection