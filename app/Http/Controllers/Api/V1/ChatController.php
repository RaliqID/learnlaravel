<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function conversations(Request $request)
    {
        $userId = $request->user()->id;

        $conversations = Conversation::where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId)
            ->with(['userOne', 'userTwo', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $conversations->map(function ($conv) use ($userId) {
                $other = $conv->user_one_id === $userId ? $conv->userTwo : $conv->userOne;
                return [
                    'id' => $conv->id,
                    'other_user' => [
                        'id' => $other->id,
                        'username' => $other->username,
                        'display_name' => $other->display_name,
                        'avatar' => $other->avatar,
                    ],
                    'last_message' => $conv->latestMessage ? [
                        'body' => $conv->latestMessage->body,
                        'sender_id' => $conv->latestMessage->sender_id,
                        'created_at' => $conv->latestMessage->created_at->toIso8601String(),
                        'read_at' => $conv->latestMessage->read_at,
                    ] : null,
                    'last_message_at' => $conv->last_message_at?->toIso8601String(),
                ];
            }),
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function messages(Request $request, Conversation $conversation)
    {
        $userId = $request->user()->id;

        if (!$conversation->involves($userId)) {
            abort(403, 'Not part of this conversation');
        }

        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark messages as read
        $conversation->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'status' => 'success',
            'data' => $messages->map(fn($m) => [
                'id' => $m->id,
                'body' => $m->body,
                'sender_id' => $m->sender_id,
                'sender' => [
                    'username' => $m->sender->username,
                    'display_name' => $m->sender->display_name,
                    'avatar' => $m->sender->avatar,
                ],
                'created_at' => $m->created_at->toIso8601String(),
                'read_at' => $m->read_at?->toIso8601String(),
            ]),
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'body' => 'required|string|max:5000',
        ]);

        $senderId = $request->user()->id;
        $recipientId = $request->recipient_id;

        if ($senderId == $recipientId) {
            throw ValidationException::withMessages(['recipient_id' => 'Cannot message yourself']);
        }

        $conversation = Conversation::between($senderId, $recipientId);

        $message = DB::transaction(function () use ($conversation, $senderId, $request) {
            $message = $conversation->messages()->create([
                'sender_id' => $senderId,
                'body' => $request->body,
            ]);

            $conversation->update(['last_message_at' => now()]);

            return $message;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Message sent',
            'data' => [
                'id' => $message->id,
                'body' => $message->body,
                'sender_id' => $message->sender_id,
                'created_at' => $message->created_at->toIso8601String(),
                'conversation_id' => $conversation->id,
            ],
            'meta' => ['timestamp' => now()->toIso8601String()],
        ], 201);
    }

    public function startConversation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $userId = $request->user()->id;
        $otherId = $request->user_id;

        if ($userId == $otherId) {
            throw ValidationException::withMessages(['user_id' => 'Cannot start with yourself']);
        }

        $conversation = Conversation::between($userId, $otherId);

        return response()->json([
            'status' => 'success',
            'data' => [
                'conversation_id' => $conversation->id,
            ],
            'meta' => ['timestamp' => now()->toIso8601String()],
        ]);
    }
}