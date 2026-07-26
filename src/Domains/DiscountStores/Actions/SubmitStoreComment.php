<?php

declare(strict_types=1);

namespace NouTools\Domains\DiscountStores\Actions;

use App\Models\DiscountStore;
use App\Models\DiscountStoreComment;
use App\Notifications\NewDiscountStoreComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use NouTools\Domains\DiscountStores\DataTransferObjects\SubmitStoreCommentDTO;

final readonly class SubmitStoreComment
{
    public function __invoke(DiscountStore $store, SubmitStoreCommentDTO $data, Request $request): DiscountStoreComment
    {
        $comment = new DiscountStoreComment;
        $comment->store_id = $store->id;
        $comment->nickname = $data->nickname;
        $comment->content = $data->content;
        $comment->is_approved = false;
        $comment->saveOrFail();

        Notification::route('discord-webhook', config('services.discord.webhooks.new_comment'))
            ->notifyNow(new NewDiscountStoreComment($store, $comment));

        return $comment;
    }
}
