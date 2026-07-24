@extends('layouts.app')

@section('title', 'Edit Permit Matrix')
@section('nav-permit-matrix', 'active')

@section('content')
    @include('pages.permit_matrix._form', ['mode' => 'edit', 'inspectionId' => $inspectionId])
@endsection
