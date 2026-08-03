@extends('layouts.admin')

@section('title', 'Yeni Kullanıcı')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kullanıcılar</p>
        <h1 class="page-title">Yeni Kullanıcı</h1>
        <p class="page-subtitle">Kimlik, iletişim ve hesap bilgilerini girin.</p>
    </div>
    <x-back-button :href="route('kullanicilar.index')" />
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

<form method="POST" action="{{ route('kullanicilar.store') }}" class="kullanici-form" id="kullanici-create-form">
    @csrf
    @include('kullanicilar._form', ['kullanici' => null])

    <div class="form-footer">
        <x-back-button :href="route('kullanicilar.index')" icon="close">İptal</x-back-button>
        <x-cta-button type="submit" icon="save">Kullanıcıyı Kaydet</x-cta-button>
    </div>
</form>
@endsection
