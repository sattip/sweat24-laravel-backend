<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!$this->canAccessTasks($request->user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Task::with(['creator:id,name,email', 'assignee:id,name,email']);

        if ($request->has('status') && $request->status) {
            $query->byStatus($request->status);
        }
        if ($request->has('priority') && $request->priority) {
            $query->byPriority($request->priority);
        }
        if ($request->has('assigned_to') && $request->assigned_to) {
            $query->assignedTo($request->assigned_to);
        }
        if ($request->has('created_by') && $request->created_by) {
            $query->createdBy($request->created_by);
        }
        if ($request->has('overdue') && $request->boolean('overdue')) {
            $query->overdue();
        }
        if ($request->has('due_within') && $request->due_within) {
            $query->dueWithin((int) $request->due_within);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSorts = ['created_at', 'deadline', 'priority', 'status', 'name'];
        if (in_array($sortBy, $allowedSorts)) {
            if ($sortBy === 'priority') {
                // Custom priority sorting (urgent > high > medium > low) - SQLite compatible
                $query->orderByRaw("
                    CASE priority 
                        WHEN 'urgent' THEN 1 
                        WHEN 'high' THEN 2 
                        WHEN 'medium' THEN 3 
                        WHEN 'low' THEN 4 
                        ELSE 5 
                    END " . ($sortOrder === 'desc' ? 'ASC' : 'DESC')
                );
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
        }

        $perPage = min($request->get('per_page', 15), 100);
        $tasks = $query->paginate($perPage);

        return response()->json(['success' => true, 'data' => $tasks]);
    }

    public function store(Request $request): JsonResponse
    {
        // Check permissions
        if (!$this->canAccessTasks($request->user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Parse deadline BEFORE validation
        $requestData = $request->all();
        if (isset($requestData['deadline'])) {
            $requestData['deadline'] = $this->parseLocalDateTime($requestData['deadline']);
        }

        $validatedData = Validator::make($requestData, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'priority' => 'required|in:low,medium,high,urgent',
            'deadline' => 'nullable|string',  // Accept as string to avoid timezone conversion
            'assigned_to' => 'required|exists:users,id'
        ])->validate();

        // Verify assignee is admin or trainer
        $assignee = User::find($validatedData['assigned_to']);
        if (!$assignee || !in_array($assignee->role, ['admin', 'trainer'])) {
            return response()->json(['error' => 'Tasks can only be assigned to admin or trainer users'], 422);
        }

        // Create task
        $task = Task::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
            'priority' => $validatedData['priority'],
            'deadline' => $validatedData['deadline'],
            'assigned_to' => $validatedData['assigned_to'],
            'created_by' => $request->user()->id,
            'status' => 'pending'
        ]);

        $task->load(['creator:id,name,email', 'assignee:id,name,email']);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => $task
        ], 201);
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        if (!$this->canAccessTasks($request->user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $task->load(['creator:id,name,email', 'assignee:id,name,email']);
        return response()->json(['success' => true, 'data' => $task]);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        // Check permissions
        if (!$this->canAccessTasks($request->user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Parse deadline BEFORE validation
        $requestData = $request->all();
        if (isset($requestData['deadline'])) {
            $requestData['deadline'] = $this->parseLocalDateTime($requestData['deadline']);
        }

        $validatedData = Validator::make($requestData, [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'priority' => 'sometimes|required|in:low,medium,high,urgent',
            'deadline' => 'nullable|string',  // Accept as string to avoid timezone conversion
            'assigned_to' => 'sometimes|required|exists:users,id',
            'status' => 'sometimes|required|in:pending,in_progress,completed,cancelled'
        ])->validate();

        // If assigned_to is being updated, verify user is admin or trainer
        if (isset($validatedData['assigned_to'])) {
            $assignee = User::find($validatedData['assigned_to']);
            if (!$assignee || !in_array($assignee->role, ['admin', 'trainer'])) {
                return response()->json(['error' => 'Tasks can only be assigned to admin or trainer users'], 422);
            }
        }

        $task->update($validatedData);
        $task->load(['creator:id,name,email', 'assignee:id,name,email']);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data' => $task
        ]);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        if (!$this->canAccessTasks($request->user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $task->delete();
        return response()->json(['success' => true, 'message' => 'Task deleted successfully']);
    }

    public function stats(Request $request): JsonResponse
    {
        // Check if user has permission to view tasks (admin or trainer only)
        if (!$this->canAccessTasks($request->user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get today's date as string (no timezone conversion)
        $today = now()->format('Y-m-d');

        $stats = [
            'total_tasks' => Task::count(),
            'pending_tasks' => Task::byStatus('pending')->count(),
            'in_progress_tasks' => Task::byStatus('in_progress')->count(),
            'completed_tasks' => Task::byStatus('completed')->count(),
            'cancelled_tasks' => Task::byStatus('cancelled')->count(),
            
            // Fixed: Count overdue tasks using string date comparison
            'overdue_tasks' => Task::where('deadline', '<', $today . ' 23:59:59')
                                  ->whereNotIn('status', ['completed', 'cancelled'])
                                  ->count(),
            
            // Fixed: Count due today tasks using string date comparison
            'due_today' => Task::where('deadline', 'LIKE', $today . '%')
                              ->whereNotIn('status', ['completed', 'cancelled'])
                              ->count(),
            
            // Fixed: Count due this week tasks
            'due_this_week' => Task::whereBetween('deadline', [
                                  $today . ' 00:00:00',
                                  now()->addDays(7)->format('Y-m-d') . ' 23:59:59'
                              ])
                              ->whereNotIn('status', ['completed', 'cancelled'])
                              ->count(),
            
            'priority_breakdown' => [
                'urgent' => Task::byPriority('urgent')->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'high' => Task::byPriority('high')->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'medium' => Task::byPriority('medium')->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'low' => Task::byPriority('low')->whereNotIn('status', ['completed', 'cancelled'])->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function getAssignableUsers(Request $request): JsonResponse
    {
        if (!$this->canAccessTasks($request->user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only admin and trainer users can be assigned tasks
        $users = User::select('id', 'name', 'email', 'role')
                    ->whereIn('role', ['admin', 'trainer'])
                    ->where('status', 'active')
                    ->orderBy('name')
                    ->get();

        return response()->json(['success' => true, 'data' => $users]);
    }

    /**
     * Get pending assigned tasks for the current user (for notifications)
     */
    public function getMyPendingTasks(Request $request): JsonResponse
    {
        // Check if user has permission to view tasks (admin or trainer only)
        if (!$this->canAccessTasks($request->user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = $request->user();

        // Get pending tasks assigned to the current user
        $pendingTasks = Task::with(['creator:id,name,email'])
                          ->where('assigned_to', $user->id)
                          ->whereNotIn('status', ['completed', 'cancelled'])
                          ->orderBy('deadline', 'asc')
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $pendingTasks
        ]);
    }

    public function markCompleted(Request $request, Task $task): JsonResponse
    {
        if (!$this->canAccessTasks($request->user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $task->update([
            'status' => 'completed',
            'completion_date' => Carbon::today()
        ]);

        $task->load(['creator:id,name,email', 'assignee:id,name,email']);

        return response()->json([
            'success' => true,
            'message' => 'Task marked as completed',
            'data' => $task
        ]);
    }

    /**
     * Get tasks assigned to the current user for popup notifications
     */
    public function getMyTasks(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tasks = Task::with(['creator:id,name,email'])
                    ->where('assigned_to', $user->id)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->orderByRaw("
                        CASE priority 
                            WHEN 'urgent' THEN 1 
                            WHEN 'high' THEN 2 
                            WHEN 'medium' THEN 3 
                            WHEN 'low' THEN 4 
                            ELSE 5 
                        END
                    ")
                    ->orderBy('deadline', 'asc')
                    ->get();

        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }

    /**
     * Get high priority tasks for popup notifications
     * Returns tasks that should trigger popups based on priority and acknowledgment
     */
    public function getHighPriorityTasks(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tasks = Task::with(['creator:id,name,email'])
                    ->where('assigned_to', $user->id)
                    ->whereIn('priority', ['high', 'urgent'])
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->orderByRaw("
                        CASE priority 
                            WHEN 'urgent' THEN 1 
                            WHEN 'high' THEN 2 
                            ELSE 3 
                        END
                    ")
                    ->orderBy('deadline', 'asc')
                    ->get()
                    ->map(function ($task) {
                        // Add popup logic flags
                        $task->should_show_popup = $this->shouldShowTaskPopup($task);
                        $task->popup_frequency = $this->getPopupFrequency($task);
                        return $task;
                    });

        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }

    /**
     * Determine if a task should show a popup notification
     */
    private function shouldShowTaskPopup(Task $task): bool
    {
        // Always show popup for urgent tasks
        if ($task->priority === 'urgent') {
            return true;
        }

        // For high priority tasks, show popup if:
        // 1. Task is new (created today)
        // 2. Task is overdue
        // 3. User hasn't acknowledged it in the last 24 hours
        if ($task->priority === 'high') {
            $today = now()->format('Y-m-d');
            
            // New task created today
            if ($task->creation_date === $today) {
                return true;
            }
            
            // Overdue task
            if ($task->deadline && $task->deadline < $today . ' 23:59:59') {
                return true;
            }
            
            // Not acknowledged in last 24 hours
            if ($task->updated_at->diffInHours(now()) > 24) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get popup frequency for different priorities
     */
    private function getPopupFrequency(Task $task): string
    {
        switch ($task->priority) {
            case 'urgent':
                return 'every_login'; // Show every time user logs in
            case 'high':
                return 'daily'; // Show once per day
            default:
                return 'once'; // Show only once
        }
    }

    /**
     * Mark a task as acknowledged by the user (to reduce popup frequency)
     */
    public function acknowledgeTask(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Only the assigned user can acknowledge the task
        if ($task->assigned_to !== $user->id) {
            return response()->json(['error' => 'You can only acknowledge tasks assigned to you'], 403);
        }

        // For now, we'll use a simple approach - update the task's updated_at timestamp
        // This can be used to track when the user last saw the task
        $task->touch();

        return response()->json([
            'success' => true,
            'message' => 'Task acknowledged'
        ]);
    }

    private function canAccessTasks(User $user): bool
    {
        return $user && ($user->isAdmin() || $user->isTrainer());
    }

    /**
     * Parse datetime and force local timezone - return as string to prevent conversion
     */
    private function parseLocalDateTime($dateTimeString)
    {
        if (!$dateTimeString) {
            return null;
        }
        
        // Parse as local time and ensure it stays local
        $date = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateTimeString, config('app.timezone'));
        
        // Return as string to prevent further timezone conversion
        return $date->format('Y-m-d H:i:s');
    }
}
