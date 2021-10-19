<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;

class ShortLinkController extends Controller
{
    /**
     * Display the table with all short links.
     *
     * @return Application|Factory|View
     */
    public function show()
    {
        $shortLinks = auth()->user()->short_links;

        return view('links.index', compact('shortLinks'));
    }

    /**
     * Resolves a short link request.
     *
     * @param  string  $code
     * @return RedirectResponse
     */
    public function resolve(string $code): RedirectResponse
    {
        if (!$shortLink = ShortLink::where('code', $code)->first()) {
            abort('404');
        }

        $shortLink->increment('views');

        return redirect()->to($shortLink->link);
    }

    /**
     * Renders the create form.
     *
     * @param  ShortLink  $link
     * @return Application|Factory|View
     */
    public function create(ShortLink $link)
    {
        $text = 'Create Link';

        return view('links.create', compact('link', 'text'));
    }

    /**
     * Renders the edit form.
     *
     * @param  ShortLink  $link
     * @return Application|Factory|View
     */
    public function edit(ShortLink $link)
    {
        $text = 'Update Link';

        return view('links.edit', compact('link', 'text'));
    }

    /**
     * Store new short link entry.
     *
     * @param  ShortLink  $link
     */
    public function store(ShortLink $link)
    {
        $link = auth()->user()->short_links()->create(['link' => $link->link, 'code' => $link->code]);
    }

    /**
     * Update existing short link entry.
     *
     * @param  ShortLink  $link
     * @throws AuthorizationException
     */
    public function update(ShortLink $link)
    {
        $this->authorize('update', $link);

        $link->update();
    }

    /**
     * Delete short link entry.
     *
     * @param  ShortLink  $link
     * @return Application|RedirectResponse|Redirector
     */
    public function delete(ShortLink $link)
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        if (auth()->user()->isNot($link->owner)) {
            abort(403);
        }

        DB::table('short_links')->where('id', '=', $link->id)->delete();

        session()->flash('success_message', 'Link successfully deleted.');

        return redirect()->to('/links');
    }
}
