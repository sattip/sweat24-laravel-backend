@extends('layouts.admin')

@section('title', 'Referral Analytics')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Referral Analytics</h1>
        <p class="text-gray-600 mt-2">Track how members found your gym and referral performance</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-500 rounded-full p-3">
                    <i class="fas fa-users text-white"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Referrals</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $totalReferrals }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-500 rounded-full p-3">
                    <i class="fas fa-check-circle text-white"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Validated</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $validatedReferrals }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-purple-500 rounded-full p-3">
                    <i class="fas fa-share-alt text-white"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Social Media</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $socialMediaRegistrations }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-orange-500 rounded-full p-3">
                    <i class="fas fa-percentage text-white"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Conversion Rate</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $conversionRate }}%</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Source Distribution -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Registration Sources</h2>
            <div class="space-y-3">
                @foreach($sourceStats as $source)
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-2 h-2 rounded-full 
                            {{ in_array($source->found_us_via, ['facebook', 'instagram']) ? 'bg-blue-500' : 
                               (in_array($source->found_us_via, ['friend', 'member']) ? 'bg-green-500' : 
                               ($source->found_us_via === 'google' ? 'bg-yellow-500' : 'bg-gray-500')) }} mr-3">
                        </div>
                        <span class="text-sm font-medium text-gray-700">{{ $source->display_name }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-sm text-gray-600 mr-2">{{ $source->total }}</span>
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $totalMembers > 0 ? ($source->total / $totalMembers) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Top Referrers -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Top Referrers</h2>
            <div class="space-y-3">
                @forelse($topReferrers as $index => $referrer)
                <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded">
                    <div class="flex items-center">
                        <span class="text-lg font-bold text-gray-400 mr-3">#{{ $index + 1 }}</span>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $referrer->name }}</p>
                            <p class="text-xs text-gray-500">{{ $referrer->email }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-green-600">{{ $referrer->referred_users_count }} referrals</p>
                        <p class="text-xs text-gray-500">{{ $referrer->validated_referrals_count }} validated</p>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-500 text-center py-4">No referrers yet</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Referrals Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Recent Registrations</h2>
        </div>
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referrer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($recentRegistrations as $member)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $member->name }}</div>
                        <div class="text-xs text-gray-500">{{ $member->email }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($member->found_us_via)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ in_array($member->found_us_via, ['facebook', 'instagram']) ? 'bg-blue-100 text-blue-800' : 
                                   (in_array($member->found_us_via, ['friend', 'member']) ? 'bg-green-100 text-green-800' : 
                                   'bg-gray-100 text-gray-800') }}">
                                {{ $member->how_found_us_display }}
                            </span>
                        @else
                            <span class="text-sm text-gray-400">Not specified</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($member->referrer)
                            <div class="text-sm text-gray-900">{{ $member->referrer->name }}</div>
                            @if($member->referral_validated)
                                <span class="text-xs text-green-600"><i class="fas fa-check-circle"></i> Validated</span>
                            @else
                                <span class="text-xs text-orange-600"><i class="fas fa-clock"></i> Pending</span>
                            @endif
                        @elseif($member->referral_code_or_name)
                            <span class="text-sm text-gray-500">{{ $member->referral_code_or_name }}</span>
                        @else
                            <span class="text-sm text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $member->status === 'active' ? 'bg-green-100 text-green-800' : 
                               ($member->status === 'inactive' ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800') }}">
                            {{ ucfirst($member->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $member->created_at->format('M d, Y') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                        No recent registrations found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($recentRegistrations->hasPages())
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
            {{ $recentRegistrations->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Optional: Add charts or additional interactivity here
</script>
@endsection