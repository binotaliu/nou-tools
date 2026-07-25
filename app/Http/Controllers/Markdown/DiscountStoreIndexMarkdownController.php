<?php

namespace App\Http\Controllers\Markdown;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use NouTools\Domains\DiscountStores\Actions\ShowDiscountStorePage;
use NouTools\Domains\DiscountStores\DataTransferObjects\ShowDiscountStorePageData;

class DiscountStoreIndexMarkdownController extends Controller
{
    public function __invoke(
        ShowDiscountStorePage $showDiscountStorePage,
        ShowDiscountStorePageData $input,
    ): Response {
        $page = $showDiscountStorePage($input);

        return response()
            ->view('discount-stores.markdown.index', ['stores' => $page->stores])
            ->header('Content-Type', 'text/markdown; charset=utf-8');
    }
}
