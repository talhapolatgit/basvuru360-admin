@extends('layouts.admin')

@section('title', 'Kişi Düzenle')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kişiler</p>
        <h1 class="page-title">Kişi Düzenle</h1>
        <p class="page-subtitle">{{ $kisi->tam_adi }} — bilgilerini güncelleyin.</p>
    </div>
    <x-back-button :href="route('kisiler.show', $kisi)" />
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

<form method="POST" action="{{ route('kisiler.update', $kisi) }}" class="kisi-form" id="kisi-edit-form">
    @csrf
    @method('PUT')
    @include('kisiler._form', ['kisi' => $kisi])

    <div class="form-footer">
        <x-back-button :href="route('kisiler.show', $kisi)" icon="close">İptal</x-back-button>
        <x-cta-button type="submit" icon="save">Değişiklikleri Kaydet</x-cta-button>
    </div>
</form>
@endsection
