@extends('layouts.admin')

@section('title', 'Yeni Kişi')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kişiler</p>
        <h1 class="page-title">Yeni Kişi</h1>
        <p class="page-subtitle">Kimlik ve iletişim bilgilerini girin.</p>
    </div>
    <x-back-button :href="route('kisiler.index')" />
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

<form method="POST" action="{{ route('kisiler.store') }}" class="kisi-form" id="kisi-create-form">
    @csrf
    @include('kisiler._form', ['kisi' => null])

    <div class="form-footer">
        <x-back-button :href="route('kisiler.index')" icon="close">İptal</x-back-button>
        <x-cta-button type="submit" icon="save">Kişiyi Kaydet</x-cta-button>
    </div>
</form>
@endsection
