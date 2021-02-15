<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShortLinkController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        $shortLinks = auth()->user()->short_links;

        return view('links.index', compact('shortLinks'));
    }

    public function create(ShortLink $link)
    {
        return view('links.create', compact('link'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $link = auth()->user()->short_links()->create($this->validateRequest());

        return redirect($link->linkspath())->with('success', 'The link was created successfully!');
    }

    public function edit(ShortLink $link)
    {
        return view('links.edit', compact('link'));
    }

    public function update(ShortLink $link)
    {
        $this->authorize('update', $link);

        $link->update($this->validateRequest());

        return redirect($link->linkspath())->with('success', 'The link was updated successfully!');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function resolve($code)
    {
        if (!$shortLink = ShortLink::where('code', $code)->first()) {
            abort('404');
        }

        return redirect($shortLink->link);
    }

    public function delete(ShortLink $link)
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        if (auth()->user()->isNot($link->owner)) {
            abort(403);
        }

        DB::table('short_links')->where('id', '=', $link->id)->delete();

        return redirect('/links')->with('success', 'The link was deleted successfully!');
    }

    protected function validateRequest(): array
    {
        return request()->validate([
            'link' => 'required|url',
            'code' => 'required|unique:short_links|reserved',
        ]);
    }
}
