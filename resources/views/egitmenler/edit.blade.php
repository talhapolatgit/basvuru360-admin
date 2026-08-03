@extends('layouts.admin')

@section('title', 'Eğitmen Düzenle')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kullanıcılar</p>
        <h1 class="page-title">Eğitmen Düzenle</h1>
        <p class="page-subtitle">{{ $egitmen->tam_adi }} — bilgilerini güncelleyin.</p>
    </div>
    <x-back-button :href="route('egitmenler.show', $egitmen)" />
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

<form method="POST" action="{{ route('egitmenler.update', $egitmen) }}" class="egitmen-form" id="egitmen-edit-form">
    @csrf
    @method('PUT')
    @include('egitmenler._form', ['egitmen' => $egitmen])

    <div class="form-footer">
        <x-back-button :href="route('egitmenler.show', $egitmen)" icon="close">İptal</x-back-button>
        <x-cta-button type="submit" icon="save">Değişiklikleri Kaydet</x-cta-button>
    </div>
</form>
@endsection
