@extends('layouts.admin')

@section('title', 'Yeni Rol')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Roller</p>
        <h1 class="page-title">Yeni Rol</h1>
        <p class="page-subtitle">Yeni bir rol oluşturun ve yetkilerini seçin.</p>
    </div>
    <div class="flex items-center gap-2">
        <x-back-button href="{{ route('roller.index') }}">Rollere Dön</x-back-button>
    </div>
</div>

<form method="POST" action="{{ route('roller.store') }}">
    @csrf
    @include('roller._form')
    <div class="form-actions">
        <x-cta-button type="submit" icon="save">Kaydet</x-cta-button>
    </div>
</form>
@endsection
