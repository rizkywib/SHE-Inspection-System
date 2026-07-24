@extends('layouts.app')

@section('title', 'Tambah Permit Matrix')
@section('nav-permit-matrix', 'active')

@section('content')
    @include('pages.permit_matrix._form', ['mode' => 'create', 'inspectionId' => null])
@endsection
