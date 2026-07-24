@extends('layouts.app')
@section('title', 'Edit Safety Talk')
@section('nav-safety-talk-trainings', 'active')
@section('content')
    @include('pages.safety_talk_trainings._form', ['mode' => 'edit', 'trainingId' => $trainingId])
@endsection
