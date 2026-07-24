@extends('layouts.app')
@section('title', 'New ES&EW Inspection')
@section('nav-es-ew-inspections', 'active')
@section('content')
    @include('pages.es_ew_inspections._form', ['mode' => 'create', 'inspectionId' => null])
@endsection
