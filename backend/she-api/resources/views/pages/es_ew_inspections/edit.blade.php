@extends('layouts.app')
@section('title', 'Edit ES&EW Inspection')
@section('nav-es-ew-inspections', 'active')
@section('content')
    @include('pages.es_ew_inspections._form', ['mode' => 'edit', 'inspectionId' => $inspectionId])
@endsection
