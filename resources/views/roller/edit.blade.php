@extends('layouts.admin')

@section('title', $rol->ad.' — Düzenle')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Roller</p>
        <h1 class="page-title">{{ $rol->ad }}</h1>
        <p class="page-subtitle">Rol bilgilerini ve yetkilerini güncelleyin.</p>
    </div>
    <div class="flex items-center gap-2">
        <x-back-button href="{{ route('roller.show', $rol) }}">Detaya Dön</x-back-button>
    </div>
</div>

<form method="POST" action="{{ route('roller.update', $rol) }}">
    @csrf
    @method('PUT')
    @include('roller._form')
    <div class="form-actions">
        <x-cta-button type="submit" icon="save">Kaydet</x-cta-button>
    </div>
</form>
@endsection
