@extends('layouts.admin')

@section('title', 'Kullanıcı Düzenle')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kullanıcılar</p>
        <h1 class="page-title">Kullanıcı Düzenle</h1>
        <p class="page-subtitle">{{ $kullanici->tam_adi }} — bilgilerini güncelleyin.</p>
    </div>
    <x-back-button :href="route('kullanicilar.show', $kullanici)" />
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

<form method="POST" action="{{ route('kullanicilar.update', $kullanici) }}" class="kullanici-form" id="kullanici-edit-form">
    @csrf
    @method('PUT')
    @include('kullanicilar._form', ['kullanici' => $kullanici])

    <div class="form-footer">
        <x-back-button :href="route('kullanicilar.show', $kullanici)" icon="close">İptal</x-back-button>
        <x-cta-button type="submit" icon="save">Değişiklikleri Kaydet</x-cta-button>
    </div>
</form>
@endsection
