<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:150',
            'last_name' => 'required|string|max:150',
            'email' => 'required|string|email|max:254',
            'phone' => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $contactMessage = ContactMessage::create($request->all());

        // Send notification email to admin (optional)
        try {
            // Mail::to(config('mail.admin_email'))->send(new ContactMessageReceived($contactMessage));
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('Failed to send contact notification email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Message sent successfully. We will get back to you soon.',
            'data' => $contactMessage
        ], 201);
    }

    public function subscribe(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|string|email|max:254|unique:newsletter_subscriptions,email',
        'name' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $subscription = NewsletterSubscription::create([
        'email' => $request->email,
        'name' => $request->name,
        'subscribed_at' => now(),
        'is_active' => true,
    ]);

    // Create a contact message for the admin
    $contactMessage = ContactMessage::create([
        'first_name' => $request->name ? explode(' ', $request->name)[0] : 'Newsletter',
        'last_name' => $request->name && count(explode(' ', $request->name)) > 1 ? explode(' ', $request->name)[1] : 'Subscriber',
        'email' => $request->email,
        'subject' => 'Newsletter Subscription Request',
        'message' => 'User subscribed to newsletter: ' . $request->email . ($request->name ? ' (Name: ' . $request->name . ')' : ''),
        'is_read' => false,
        'type' => 'newsletter' // Add type
    ]);

    return response()->json([
        'message' => 'Successfully subscribed to newsletter',
        'data' => $subscription
    ], 201);
}

    public function unsubscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|exists:newsletter_subscriptions,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subscription = NewsletterSubscription::where('email', $request->email)->first();
        $subscription->update([
            'is_active' => false,
            'unsubscribed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Successfully unsubscribed from newsletter'
        ]);
    }

    public function index(Request $request)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = ContactMessage::query();

        // Filters
        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ILIKE', '%' . $search . '%')
                  ->orWhere('last_name', 'ILIKE', '%' . $search . '%')
                  ->orWhere('email', 'ILIKE', '%' . $search . '%')
                  ->orWhere('subject', 'ILIKE', '%' . $search . '%');
            });
        }

        $messages = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($messages);
    }

    public function show(ContactMessage $message)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Mark as read
        if (!$message->is_read) {
            $message->update(['is_read' => true]);
        }

        return response()->json(['data' => $message]);
    }

    public function update(Request $request, ContactMessage $message)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'is_read' => 'boolean',
            'reply_message' => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = $request->only(['is_read', 'reply_message']);

        if ($request->has('reply_message') && $request->reply_message) {
            $updateData['replied_at'] = now();
            $updateData['replied_by'] = auth()->id();
        }

        $message->update($updateData);

        return response()->json([
            'message' => 'Contact message updated successfully',
            'data' => $message
        ]);
    }

    public function destroy(ContactMessage $message)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message->delete();
        return response()->json(['message' => 'Contact message deleted successfully']);
    }

    public function markAsRead(ContactMessage $message)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message->update(['is_read' => true]);

        return response()->json([
            'message' => 'Message marked as read',
            'data' => $message
        ]);
    }

    public function newsletterSubscribers(Request $request)
    {
        // Check authorization
        if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = NewsletterSubscription::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'ILIKE', '%' . $search . '%')
                  ->orWhere('name', 'ILIKE', '%' . $search . '%');
            });
        }

        $subscribers = $query->latest('subscribed_at')->paginate($request->get('per_page', 20));

        return response()->json($subscribers);
    }

    public function export(Request $request)
{
    // Check authorization
    if (!auth()->check() || (!auth()->user()->is_staff && !auth()->user()->is_superuser)) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $query = ContactMessage::query();

    // Apply filters
    if ($request->has('type') && $request->type !== 'all') {
        $query->where('type', $request->type);
    }

    if ($request->has('is_read')) {
        $query->where('is_read', $request->boolean('is_read'));
    }

    if ($request->has('replied') && $request->boolean('replied')) {
        $query->whereNotNull('replied_at');
    }

    if ($request->has('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('first_name', 'ILIKE', '%' . $search . '%')
              ->orWhere('last_name', 'ILIKE', '%' . $search . '%')
              ->orWhere('email', 'ILIKE', '%' . $search . '%')
              ->orWhere('subject', 'ILIKE', '%' . $search . '%');
        });
    }

    $messages = $query->latest()->get();

    $csvFileName = 'messages_export_' . date('Y-m-d') . '.csv';
    $headers = [
        'Content-Type' => 'text/csv; charset=utf-8',
        'Content-Disposition' => 'attachment; filename="' . $csvFileName . '"',
    ];

    $handle = fopen('php://output', 'w');
    fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

    // Add CSV headers
    fputcsv($handle, [
        'ID', 'النوع', 'الاسم الأول', 'الاسم الأخير', 'البريد الإلكتروني', 
        'رقم الهاتف', 'الموضوع', 'الرسالة', 'تم القراءة', 'تم الرد',
        'تاريخ الرد', 'تاريخ الإنشاء'
    ]);

    // Add data rows
    foreach ($messages as $message) {
        fputcsv($handle, [
            $message->id,
            $message->type === 'contact' ? 'اتصال' : 'نشرة إخبارية',
            $message->first_name,
            $message->last_name,
            $message->email,
            $message->phone,
            $message->subject,
            $message->message,
            $message->is_read ? 'نعم' : 'لا',
            $message->replied_at ? 'نعم' : 'لا',
            $message->replied_at ? $message->replied_at->format('Y-m-d H:i:s') : '',
            $message->created_at->format('Y-m-d H:i:s')
        ]);
    }

    fclose($handle);

    return response()->make('', 200, $headers);
}
}