@extends('layouts.app')

@section('title')
    Edit User
@endsection

@section('content')
    @if(isset($ban))
        <div class="alert alert-warning">
            <p>
                <strong>This user is currently banned!</strong><br />
                Reason: {{$ban->reason}}<br />
                Length: {{getBanLengths()['' . $ban->length]}}<br />
                Banned: {{$ban->created_at}}
            </p>
        </div>
    @endif

    <a href="/admin/users/" class="btn btn-default">Back</a>
    <h1>Edit User: {{$user->name}}</h1>
    {!! Form::open(['action' => ['AdminController@userUpdate', $user->id], 'method' => 'PUT']) !!}
        <div class="row">
            <div class="form-group col-md-6">
                {{Form::label('name', 'Name')}}
                {{Form::text('name', $user->name, ['class' => 'form-control', 'placeholder' => 'Username'])}}
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-6">
                {{Form::label('email', 'Email')}}
                {{Form::text('email', $user->email, ['class' => 'form-control', 'placeholder' => 'Email'])}}
            </div>
            <div class="form-group col-md-6">
                {{Form::label('group', 'User Group')}}
                {{Form::select('group', $groups, $user->group_id, ['class' => 'form-control'])}}
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-6">
                {{Form::label('password', 'Password (Only specify if changing)')}}
                {{Form::password('password', ['class' => 'form-control', 'placeholder' => 'Password'])}}
            </div>
            <div class="form-group col-md-6">
                {{Form::label('password2', 'Password (Confirm)')}}
                {{Form::password('password2', ['class' => 'form-control', 'placeholder' => 'Password'])}}
            </div>
        </div>
        {{Form::submit('Update', ['class' => 'btn btn-primary pull-left'])}}
    {!! Form::close() !!}

    @if(userHasPermission('pl_ban'))
        @if(isUserBanned($user))
            <a href="/admin/users/unban/{{$user->id}}" class="btn btn-success pull-left" style="margin-left: 15px;">Unban</a>
        @else
            <a href="/admin/users/ban/{{$user->id}}" class="btn btn-danger pull-left" style="margin-left: 15px;">Ban</a>
        @endif
    @endif

    @if(userHasPermission('pl_delete_user'))
        {!! Form::open(['action' => ['AdminController@userDestroy', $user->id], 'method' => 'DELETE']) !!}
            {{Form::submit('Delete', ['class' => 'btn btn-warning pull-right', 'style' => 'margin-left: 15px;'])}}
        {!! Form::close() !!}
    @endif
@endsection
