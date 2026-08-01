<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DiscountStore;
use Coderflex\LaravelTurnstile\Rules\TurnstileCheck;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use NouTools\Domains\DiscountStores\Actions\SubmitStoreComment;
use NouTools\Domains\DiscountStores\DataTransferObjects\SubmitStoreCommentDTO;

final class DiscountStoreCommentController extends Controller
{
    public function store(
        Request $request,
        DiscountStore $store,
        SubmitStoreComment $submitStoreComment,
    ): RedirectResponse {
        $validated = $request->validate([
            'nickname' => ['required', 'string', 'max:100'],
            'content' => ['required', 'string', 'max:1000'],
            'cf-turnstile-response' => ['required', new TurnstileCheck],
        ]);

        $dto = new SubmitStoreCommentDTO(
            nickname: $validated['nickname'],
            content: $validated['content'],
        );

        $submitStoreComment($store, $dto, $request);

        return redirect()
            ->route('discount-stores.show', $store)
            ->with('success', '留言已送出，將在確認通過後顯示。');
    }
}
