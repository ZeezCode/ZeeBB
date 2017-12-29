<?php

namespace App\Http\Controllers;

use App\Post;
use App\Thread;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => 'show']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'message' => 'required',
            'thread' => 'required',
        ]);

        $thread = Thread::find($request->input('thread'));
        if ($thread == null)
            return redirect('/')->with('error', 'Invalid thread specified');

        $post = new Post();
        $post->thread_id = $thread->id;
        $post->forum_id = $thread->forum_id;
        $post->user_id = auth()->user()->id;
        $post->user_name = auth()->user()->name;
        $post->subject = 'Re: ' . $thread->subject;
        $post->message = $request->input('message');
        $post->save();

        return redirect('/thread/' . $thread->id)->with('success', 'Your post has successfully been added to the thread!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function edit(Post $post)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Post $post)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {
        //
    }
}