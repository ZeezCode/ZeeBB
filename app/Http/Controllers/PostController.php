<?php

namespace App\Http\Controllers;

use App\Post;
use App\Thread;
use App\Forum;
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

        $user = auth()->user();

        $post = new Post;
        $post->thread_id = $thread->id;
        $post->forum_id = $thread->forum_id;
        $post->user_id = $user->id;
        $post->user_name = $user->name;
        $post->subject = 'Re: ' . $thread->subject;
        $post->message = $request->input('message');
        $post->save();

        $thread->last_poster_id = $user->id;
        $thread->last_poster_name = $user->name;
        $thread->save();

        $forum = $thread->forum;
        $forum->last_poster_id = $user->id;
        $forum->last_poster_name = $user->name;
        $forum->last_post_id = $post->id;
        $forum->save();

        $cat = $forum->parent;
        $cat->last_poster_id = $user->id;
        $cat->last_poster_name = $user->name;
        $cat->last_post_id = $post->id;
        $cat->save();

        return redirect('/thread/' . $thread->id)->with('success', 'Your post has successfully been added to the thread!');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $post = Post::find($id);
        if ($post == null)
            return redirect('/')->with('error', 'The requested post was not found!');

        return redirect('/thread/' . $post->thread->id . '#post' . $id); //Redirect to thread containing desired post
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $post = Post::find($id);
        if ($post == null) {
            return redirect('/')->with('error', 'The specified post does not exist!');
        }

        if ($post->user_id != auth()->id() && !auth()->user()->group->is_staff_group) {
            return redirect('/')->with('error', 'You do not have permission to edit this post!');
        }

        return view('pages.posts.edit')->with('post', $post);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $post = Post::find($id);
        if ($post == null) { //If post doesn't exist
            return redirect('/')->with('error', 'The specified post does not exist!');
        }
        if ($post->user_id != auth()->id() && !auth()->user()->group->is_staff_group) { //If user isn't post's creator or staff
            return redirect('/')->with('error', 'You do not have permission to edit this post!');
        }

        $this->validate($request, [
            'message' => 'required',
        ]);

        $post->message = $request->input('message');
        $post->save();
        return redirect('/thread/' . $post->thread_id)->with('success', 'Your post has been successfully updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->group->is_staff_group) {
            return back()->withInput()->with('error', 'You do not have permission to delete this post!');
        }

        $post = Post::find($id);
        if ($post == null) {
            return redirect('/')->with('error', 'The specified post does not exist!');
        }

        if ($post->thread->first_post_id == $post->id) {
            return back()->withInput()->with('error', 'The primary post in a thread can not be deleted!');
        }

        $post->delete();
        $forum = $post->forum;

        $forumLastPost = Post::where('forum_id', $forum->id)
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get();
        foreach($forumLastPost as $newLastPost) { //Will only loop once
            $newUser = $newLastPost->user;
            $forum->last_poster_id = $newUser->id;
            $forum->last_poster_name = $newUser->name;
            $forum->last_post_id = $newLastPost->id;
            $forum->save();

            $cat = $forum->parent;
            $cat->last_poster_id = $newUser->id;
            $cat->last_poster_name = $newUser->name;
            $cat->last_post_id = $newLastPost->id;
            $cat->save();
        }

        $thread = $post->thread;
        $threadLastPost = $thread->posts()->orderBy('id', 'DESC')->limit(1)->get();
        $newUser = $threadLastPost[0]->user;
        $thread->last_poster_id = $newUser->id;
        $thread->last_poster_name = $newUser->name;
        $thread->save();

        return redirect('/thread/' . $post->thread->id)->with('success', 'The post has been successfully deleted!');
    }
}
