<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\OwnerNotification;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    /**
     * Get all contact messages (admin)
     */
    public function index(Request $request)
    {
        $query = ContactMessage::with(['user', 'repliedByUser']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        } else {
            $query->notArchived();
        }

        // Filter by subject
        if ($request->has('subject') && $request->subject !== 'all') {
            $query->where('subject', $request->subject);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $messages = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($messages);
    }

    /**
     * Submit a contact message (mobile app)
     */
    public function store(Request $request)
    {
        \Log::info('ContactMessage::store - Request received', [
            'user_id' => auth()->id(),
            'data' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'nullable|string|max:50',
                'subject' => 'required|string|max:255',
                'message' => 'required|string|max:5000',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('ContactMessage::store - Validation failed', [
                'errors' => $e->errors(),
                'input' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Τα δεδομένα δεν είναι έγκυρα',
                'errors' => $e->errors(),
            ], 422);
        }

        // Attach user_id if authenticated
        if (auth()->check()) {
            $validated['user_id'] = auth()->id();
        }

        try {
            $contactMessage = ContactMessage::create($validated);

            // Notify admins
            $this->notifyAdmins($contactMessage);

            \Log::info('ContactMessage::store - Message created successfully', [
                'message_id' => $contactMessage->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Το μήνυμά σας στάλθηκε επιτυχώς',
                'data' => $contactMessage,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('ContactMessage::store - Failed to create message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Δεν ήταν δυνατή η αποστολή του μηνύματος',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a single contact message
     */
    public function show(ContactMessage $contactMessage)
    {
        $contactMessage->load(['user', 'repliedByUser']);

        // Mark as read if unread
        if ($contactMessage->status === ContactMessage::STATUS_UNREAD) {
            $contactMessage->update(['status' => ContactMessage::STATUS_READ]);
        }

        return response()->json($contactMessage);
    }

    /**
     * Update contact message (admin notes, status)
     */
    public function update(Request $request, ContactMessage $contactMessage)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:unread,read,replied,archived',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $contactMessage->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Το μήνυμα ενημερώθηκε',
            'data' => $contactMessage,
        ]);
    }

    /**
     * Reply to a contact message
     */
    public function reply(Request $request, ContactMessage $contactMessage)
    {
        $validated = $request->validate([
            'reply' => 'required|string|max:5000',
        ]);

        $contactMessage->update([
            'reply' => $validated['reply'],
            'status' => ContactMessage::STATUS_REPLIED,
            'replied_at' => now(),
            'replied_by' => auth()->id(),
        ]);

        // TODO: Send email to user with reply

        return response()->json([
            'success' => true,
            'message' => 'Η απάντηση στάλθηκε',
            'data' => $contactMessage->fresh(['user', 'repliedByUser']),
        ]);
    }

    /**
     * Archive a contact message
     */
    public function archive(ContactMessage $contactMessage)
    {
        $contactMessage->update(['status' => ContactMessage::STATUS_ARCHIVED]);

        return response()->json([
            'success' => true,
            'message' => 'Το μήνυμα αρχειοθετήθηκε',
        ]);
    }

    /**
     * Delete a contact message
     */
    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Το μήνυμα διαγράφηκε',
        ]);
    }

    /**
     * Get statistics
     */
    public function stats()
    {
        return response()->json([
            'total' => ContactMessage::count(),
            'unread' => ContactMessage::where('status', ContactMessage::STATUS_UNREAD)->count(),
            'read' => ContactMessage::where('status', ContactMessage::STATUS_READ)->count(),
            'replied' => ContactMessage::where('status', ContactMessage::STATUS_REPLIED)->count(),
            'archived' => ContactMessage::where('status', ContactMessage::STATUS_ARCHIVED)->count(),
            'today' => ContactMessage::whereDate('created_at', today())->count(),
            'this_week' => ContactMessage::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ]);
    }

    /**
     * Notify admins about new contact message
     */
    private function notifyAdmins(ContactMessage $message)
    {
        OwnerNotification::create([
            'title' => 'Νέο Μήνυμα Επικοινωνίας',
            'message' => sprintf(
                "Νέο μήνυμα από %s\n\nΘέμα: %s\n\nΜήνυμα: %s",
                $message->name,
                $message->subject,
                \Illuminate\Support\Str::limit($message->message, 200)
            ),
            'type' => 'contact_message',
            'priority' => 'medium',
            'user_id' => null, // All admins
            'related_model_type' => 'ContactMessage',
            'related_model_id' => $message->id,
            'metadata' => json_encode([
                'contact_message_id' => $message->id,
                'name' => $message->name,
                'email' => $message->email,
                'phone' => $message->phone,
                'subject' => $message->subject,
            ]),
        ]);
    }
}
