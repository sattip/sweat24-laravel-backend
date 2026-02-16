@extends('layouts.admin')

@section('title', 'Bulk Operation Details')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Bulk Operation Details</h1>
        <div class="flex gap-4">
            <a href="{{ route('admin.packages.bulk.history') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to History
            </a>
        </div>
    </div>

    <!-- Operation Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Type</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $operation->getTypeLabel() }}</p>
                </div>
                <i class="fas fa-cog text-2xl text-blue-500"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Status</p>
                    <p class="text-lg font-semibold {{ $operation->getStatusColorClass() }}">{{ $operation->getStatusLabel() }}</p>
                </div>
                <i class="fas fa-info-circle text-2xl {{ $operation->getStatusColorClass() }}"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Progress</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $operation->progress_percentage }}%</p>
                </div>
                <i class="fas fa-chart-pie text-2xl text-green-500"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Duration</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $operation->duration ?? 'N/A' }}</p>
                </div>
                <i class="fas fa-clock text-2xl text-purple-500"></i>
            </div>
        </div>
    </div>

    <!-- Detailed Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Target Count</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $operation->target_count }}</p>
                </div>
                <i class="fas fa-bullseye text-3xl text-blue-500"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Successful</p>
                    <p class="text-2xl font-bold text-green-600">{{ $operation->successful_count }}</p>
                </div>
                <i class="fas fa-check-circle text-3xl text-green-500"></i>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Failed</p>
                    <p class="text-2xl font-bold text-red-600">{{ $operation->failed_count }}</p>
                </div>
                <i class="fas fa-times-circle text-3xl text-red-500"></i>
            </div>
        </div>
    </div>

    <!-- Operation Details -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- General Information -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">General Information</h3>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Performed By:</span>
                    <span class="text-sm font-medium text-gray-900">{{ $operation->performedBy->name ?? 'Unknown' }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Started At:</span>
                    <span class="text-sm font-medium text-gray-900">
                        {{ $operation->started_at ? $operation->started_at->format('M d, Y H:i') : 'N/A' }}
                    </span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Completed At:</span>
                    <span class="text-sm font-medium text-gray-900">
                        {{ $operation->completed_at ? $operation->completed_at->format('M d, Y H:i') : 'N/A' }}
                    </span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Success Rate:</span>
                    <span class="text-sm font-medium text-gray-900">{{ $operation->success_rate }}%</span>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Progress Breakdown</h3>
            
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Successful</span>
                        <span class="text-sm font-medium text-gray-700">{{ $operation->successful_count }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: {{ $operation->target_count > 0 ? ($operation->successful_count / $operation->target_count * 100) : 0 }}%"></div>
                    </div>
                </div>
                
                <div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Failed</span>
                        <span class="text-sm font-medium text-gray-700">{{ $operation->failed_count }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-red-600 h-2 rounded-full" style="width: {{ $operation->target_count > 0 ? ($operation->failed_count / $operation->target_count * 100) : 0 }}%"></div>
                    </div>
                </div>
                
                @php
                    $remaining = $operation->target_count - $operation->successful_count - $operation->failed_count;
                @endphp
                @if($remaining > 0)
                    <div>
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Remaining</span>
                            <span class="text-sm font-medium text-gray-700">{{ $remaining }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gray-400 h-2 rounded-full" style="width: {{ $remaining / $operation->target_count * 100 }}%"></div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Filters Used -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Filters Applied</h3>
        
        @php
            $filters = $operation->getFormattedFilters();
        @endphp
        
        @if(count($filters) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($filters as $label => $value)
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">{{ $label }}:</span>
                        <span class="text-sm font-medium text-gray-900">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-sm">No filters were applied</p>
        @endif
    </div>

    <!-- Operation Parameters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Operation Parameters</h3>
        
        @php
            $operationData = $operation->getFormattedOperationData();
        @endphp
        
        @if(count($operationData) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($operationData as $label => $value)
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">{{ $label }}:</span>
                        <span class="text-sm font-medium text-gray-900">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-sm">No operation parameters</p>
        @endif
    </div>

    <!-- Errors (if any) -->
    @if($operation->hasErrors() && $operation->errors)
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-red-600 mb-4">Errors</h3>
            
            <div class="space-y-3">
                @foreach($operation->errors as $error)
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-red-800">Package ID: {{ $error['package_id'] ?? 'Unknown' }}</p>
                                @if(isset($error['user_name']))
                                    <p class="text-sm text-red-600">User: {{ $error['user_name'] }}</p>
                                @endif
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-red-600">{{ $error['error'] ?? 'Unknown error' }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

@endsection