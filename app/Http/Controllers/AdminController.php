<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

use App\User;
use App\Group;
use App\Forum;
use App\Post;
use App\Thread;

class AdminController extends Controller
{
    public function __construct() {
        $this->middleware('auth');
        $this->middleware('staff');
    }

    public function index() {
        return view('pages.admin.home');
    }

    public function userIndex() {
        $users = User::orderBy('name', 'ASC')->paginate(20);

        return view('pages.admin.users.index')->with('users', $users);
    }

    public function userCreate() {
        $rawGroups = Group::all();
        $groups = [];
        foreach ($rawGroups as $group)
            $groups[$group->id] = $group->name;

        return view('pages.admin.users.create')->with('groups', $groups);
    }

    public function userStore(Request $request) {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'group' => 'required|integer',
            'password' => 'required',
            'password2' => 'required|same:password',
        ]);

        $group = Group::find($request->input('group'));
        if ($group == null) { //If specified group does not exist
            return back()->withInput()->with('error', 'An illegal group was specified!');
        }

        if ($request->input('password') !== $request->input('password2')) { //If passwords don't match
            return back()->withInput()->with('error', 'The provided passwords did not match!');
        }

        $user = new User;
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));
        $user->group_id = $request->input('group');
        $user->remember_token = null;
        $user->save();

        return redirect('/admin/users')->with('success', 'You have successfully created a user with ID: ' . $user->id);
    }

    public function userEdit($id) {
        $user = User::find($id);

        if ($user == null)
            return redirect('/admin/users')->with('error', 'The specified user does not exist!');

        $rawGroups = Group::all();
        $groups = [];
        foreach ($rawGroups as $group)
            $groups[$group->id] = $group->name;

        $data = [
            'user' => $user,
            'groups' => $groups,
        ];

        return view('pages.admin.users.edit')->with($data);
    }

    public function userUpdate(Request $request, $id) {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'group' => 'required|integer',
            'password2' => 'required_with:password|same:password',
        ]);

        $user = User::find($id);
        if ($user == null)
            return redirect('/admin')->with('error', 'The specified user does not exist!');

        if ($request->input('name') != $user->name) { //If name changed
            //Update all references to old name
            Forum::where('last_poster_id', '=', $user->id)
                ->update(['last_poster_name' => $request->input('name')]);

            Post::where('user_id', '=', $user->id)
                ->update(['user_name' => $request->input('name')]);


            Thread::where('user_id', '=', $user->id)
                ->update(['user_name' => $request->input('name')]);


            Thread::where('last_poster_id', '=', $user->id)
                ->update(['last_poster_name' => $request->input('name')]);
        }

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->group_id = $request->input('group');
        if ($request->input('password') != null) {
            $user->password = Hash::make($request->input('password'));
        }
        $user->save();

        return redirect('/admin/users/edit/' . $user->id)->with('success', 'Your changes have been saved!');
    }

    public function userDestroy($id) {
        User::destroy($id);

        return redirect('/admin/users/')->with('success', 'You have successfully deleted user ID: ' . $id);
    }

    public function groupIndex() {
        $groups = Group::orderBy('power_level', 'DESC')->paginate(20);

        return view('pages.admin.groups.index')->with('groups', $groups);
    }

    public function groupCreate() {
        return view('pages.admin.groups.create');
    }

    public function groupStore(Request $request) {
        $this->validate($request, [
            'name' => 'required|unique:groups',
            'power_level' => 'required|integer|min:0',
            'is_staff_group' => 'sometimes|required|integer|min:0|max:1',
            'is_banned_group' => 'sometimes|required|integer|min:0|max:1',
        ]);

        $group = new Group;
        $group->name = $request->input('name');
        $group->power_level = $request->input('power_level');
        $group->is_staff_group = ($request->input('is_staff_group') ? true : false);
        $group->is_banned_group = ($request->input('is_banned_group') ? true : false);
        $group->save();

        return redirect('/admin/groups')->with('success', 'You have successfully created a group with ID: ' . $group->id);
    }

    public function groupEdit($id) {
        $group = Group::find($id);
        if ($group == null)
            return redirect('/admin/groups')->with('error', 'The specified group does not exist!');

        return view('pages.admin.groups.edit')->with('group', $group);
    }

    public function groupUpdate(Request $request, $id) {
        $group = Group::find($id);
        if ($group == null) {
            return redirect('/admin/groups')->with('error', 'The specified group does not exist!');
        }

        $this->validate($request, [
            'name' => 'required|unique:groups,name,' . $id,
            'power_level' => 'required|integer|min:0',
            'is_staff_group' => 'sometimes|required|integer|min:0|max:1',
            'is_banned_group' => 'sometimes|required|integer|min:0|max:1',
        ]);

        $group->name = $request->input('name');
        $group->power_level = $request->input('power_level');
        $group->is_staff_group = ($request->input('is_staff_group') ? true : false);
        $group->is_banned_group = ($request->input('is_banned_group') ? true : false);
        $group->save();

        return redirect('/admin/groups/edit/' . $id)->with('success', 'Your changes have been saved!');

    }

    public function groupDestroy($id) {
        $group = Group::find($id);
        $count = $group->users()->count();
        if ($count > 0) {
            return back()->withInput()->with('error', 'This group can not be deleted as ' . $count . ' user(s) are currently assigned to it. Move those users to delete the group.');
        }

        $group->delete();

        return redirect('/admin/groups/')->with('success', 'You have successfully deleted group ID: ' . $id);
    }
}
