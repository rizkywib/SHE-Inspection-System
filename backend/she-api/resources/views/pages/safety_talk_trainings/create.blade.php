@extends('layouts.app')
@section('title', 'Tambah Safety Talk')
@section('nav-safety-talk-trainings', 'active')
@section('content')
    @include('pages.safety_talk_trainings._form', ['mode' => 'create', 'trainingId' => null])
@endsection
