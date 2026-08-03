@extends('layouts.admin')

@section('title', 'Kurs Düzenle')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <p class="page-eyebrow">Kurs Yönetimi</p>
        <h1 class="page-title">Kurs Düzenle</h1>
        <p class="page-subtitle">#{{ $kurs->kurs_no }} — kurs bilgilerini güncelleyin.</p>
    </div>
    <x-back-button :href="route('kurslar.index')" />
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

<form method="POST" action="{{ route('kurslar.update', $kurs) }}" class="kurs-form" id="kurs-edit-form">
    @csrf
    @method('PUT')
    @include('kurslar._form', ['kurs' => $kurs])

    <div class="form-footer">
        <x-back-button :href="route('kurslar.index')" icon="close">İptal</x-back-button>
        <x-cta-button type="submit" icon="save">Değişiklikleri Kaydet</x-cta-button>
    </div>
</form>
@endsection

@push('scripts')
    @include('kurslar._form_scripts')
@endpush
