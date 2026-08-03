@extends('layouts.admin')

@section('title', 'Yeni Eğitmen')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kullanıcılar</p>
        <h1 class="page-title">Yeni Eğitmen</h1>
        <p class="page-subtitle">Kimlik, iletişim ve hesap bilgilerini girin.</p>
    </div>
    <x-back-button :href="route('egitmenler.index')" />
</div>

@if ($errors->any())
    <div class="alert alert-error mb-4">
        <strong>Formda hatalar var.</strong>
        <ul class="mt-2 list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('egitmenler.store') }}" class="egitmen-form" id="egitmen-create-form">
    @csrf
    @include('egitmenler._form', ['egitmen' => null])

    <div class="form-footer">
        <x-back-button :href="route('egitmenler.index')" icon="close">İptal</x-back-button>
        <x-cta-button type="submit" icon="save">Eğitmeni Kaydet</x-cta-button>
    </div>
</form>
@endsection
