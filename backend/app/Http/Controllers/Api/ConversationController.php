<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\AddonOffer;
use App\Models\Conversation;
use App\Services\ExternalDealingFilter;
use Illuminate\Http\Request;

class ConversationController extends ApiController
{
    public function __construct(private readonly ExternalDealingFilter $filter) {}

    /** GET /api/conversations — conversations the user takes part in (buyer or seller). */
    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->with(['buyer', 'store', 'messages'])
            ->where('buyer_id', $user->id)
            ->orWhereHas('store', fn ($q) => $q->where('user_id', $user->id))
            ->latest('id')
            ->get();

        return ConversationResource::collection($conversations);
    }

    /** GET /api/conversations/{conversation}/messages */
    public function messages(Request $request, Conversation $conversation)
    {
        $this->assertParticipant($request, $conversation);

        return MessageResource::collection(
            $conversation->messages()->with('addonOffer')->orderBy('id')->get()
        );
    }

    /** POST /api/conversations/{conversation}/messages */
    public function postMessage(Request $request, Conversation $conversation)
    {
        $this->assertParticipant($request, $conversation);
        $data = $request->validate(['body' => ['required', 'string']]);

        // payfirst chat is locked until the buyer pays. SPEC §4.8.
        if ($conversation->locked) {
            return $this->fail('المحادثة مقفلة — الدفع أولًا لفتح التواصل مع المتجر');
        }

        $inspection = $this->filter->inspect($data['body']);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'type' => 'text',
            'body' => $data['body'],
            'flagged' => $inspection['flagged'],
        ]);

        $created = [$message];

        // Flagged messages are stored but followed by a system warning. SPEC §4.10.
        if ($inspection['flagged']) {
            $created[] = $conversation->messages()->create([
                'sender_id' => null,
                'type' => 'system',
                'body' => $inspection['warning'],
                'flagged' => false,
            ]);
        }

        return $this->ok([
            'messages' => MessageResource::collection(collect($created)),
        ], 201);
    }

    /** POST /api/conversations/{conversation}/addon-offers — seller proposes an addition. */
    public function addonOffer(Request $request, Conversation $conversation)
    {
        abort_unless(
            $conversation->store->user_id === $request->user()->id,
            403,
            'الإضافات تُرسل من المتجر فقط'
        );

        $data = $request->validate([
            'name' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'type' => 'addon_offer',
            'body' => 'عرض إضافة: '.$data['name'].' — '.$data['price'].' ر.س',
            'flagged' => false,
        ]);

        $offer = AddonOffer::create([
            'message_id' => $message->id,
            'order_id' => $conversation->order_id,
            'name' => $data['name'],
            'price' => round((float) $data['price'], 2),
            'status' => 'pending',
        ]);

        return $this->ok([
            'message' => new MessageResource($message->load('addonOffer')),
            'addon_offer' => ['id' => $offer->id, 'name' => $offer->name, 'price' => (float) $offer->price],
        ], 201);
    }

    private function assertParticipant(Request $request, Conversation $conversation): void
    {
        $user = $request->user();
        $isBuyer = $conversation->buyer_id === $user->id;
        $isSeller = $conversation->store->user_id === $user->id;
        abort_unless($isBuyer || $isSeller, 403);
    }
}
