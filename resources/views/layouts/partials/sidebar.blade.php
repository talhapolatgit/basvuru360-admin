<button
    type="button"
    id="sidebarOpenBtn"
    class="sidebar-open-btn"
    title="Menüyü göster"
    aria-label="Menüyü göster"
>
    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
</button>

<div id="sidebarBackdrop" class="sidebar-backdrop" hidden></div>

<aside id="adminSidebar" class="admin-sidebar">
  <div class="sidebar-header">
    <div class="sidebar-brand-icon">
      <x-brand-logo :size="22" />
    </div>
    <div class="sidebar-brand-text">
      <div class="sidebar-brand-title">Başvuru 360</div>
      <div class="sidebar-brand-subtitle">Yönetim Paneli</div>
    </div>
    <button
      type="button"
      id="sidebarHideBtn"
      class="sidebar-hide-btn"
      title="Menüyü gizle"
      aria-label="Menüyü gizle"
    >
      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
      </svg>
    </button>
  </div>

  <nav class="sidebar-nav">
  <div class="menu-section-label">Genel Bakış</div>
  <a href="{{ route('anasayfa') }}" class="menu-item {{ request()->routeIs('anasayfa') ? 'menu-item-active' : '' }}">
    <svg class="menu-item-icon {{ request()->routeIs('anasayfa') ? 'menu-item-icon-accent' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
    Anasayfa
  </a>
  @yetki('dashboard.goruntule')
  <a href="{{ route('dashboard') }}" class="menu-item {{ request()->routeIs('dashboard') ? 'menu-item-active' : '' }}">
    <svg class="menu-item-icon {{ request()->routeIs('dashboard') ? 'menu-item-icon-accent' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
    Dashboard
  </a>
  @endyetki

  <div class="menu-section-label">Modüller</div>

  @php
    $kursMenuAcik = request()->routeIs('kurslar.*', 'basvurular.*', 'merkezler.*', 'alanlar.*', 'branslar.*', 'sabit-tanimlar.index', 'sabit-tanimlar.kurs-tipleri.*', 'sabit-tanimlar.evrak-tipleri.*', 'sabit-tanimlar.basvuru-durumlari.*', 'sabit-tanimlar.basari-durumlari.*', 'sabit-tanimlar.iptal-gerekceleri.*', 'sabit-tanimlar.kurumlar.*', 'sabit-tanimlar.sertifika-ayarlari.*', 'sabit-tanimlar.diger-ayarlar.*');
    $kursMenuGoster = auth()->user()?->hasAnyYetki(['kurs.goruntule', 'basvuru.goruntule', 'merkez.goruntule', 'alan.goruntule', 'brans.goruntule', 'sabit.goruntule']);
  @endphp
  @if ($kursMenuGoster)
  <div class="menu-dropdown" data-menu-dropdown>
    <button
      type="button"
      class="menu-item menu-dropdown-toggle w-full justify-between {{ $kursMenuAcik ? 'menu-item-active dropdown-open' : '' }}"
      data-menu-dropdown-toggle
      aria-expanded="{{ $kursMenuAcik ? 'true' : 'false' }}"
    >
      <span class="flex items-center gap-3.5">
        <svg class="menu-item-icon {{ $kursMenuAcik ? 'menu-item-icon-accent' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        Kurs Yönetimi
      </span>
      <svg class="menu-arrow h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </button>
    <div class="submenu {{ $kursMenuAcik ? 'open' : '' }}" data-menu-dropdown-content>
      @yetki('kurs.goruntule')
      <a href="{{ route('kurslar.index') }}" class="submenu-item {{ request()->routeIs('kurslar.index', 'kurslar.export', 'kurslar.show', 'kurslar.edit', 'kurslar.update') ? 'is-active' : '' }}">
        Kurslar
      </a>
      @endyetki
      @yetki('basvuru.goruntule')
      <a href="{{ route('basvurular.index') }}" class="submenu-item {{ request()->routeIs('basvurular.index', 'basvurular.export') ? 'is-active' : '' }}">
        Kurs Başvuruları
      </a>
      @endyetki
      @yetki('basvuru.olustur')
      <a href="{{ route('basvurular.create') }}" class="submenu-item {{ request()->routeIs('basvurular.create', 'basvurular.store') ? 'is-active' : '' }}">
        Yeni Başvuru
      </a>
      @endyetki
      @yetki('merkez.goruntule')
      <a href="{{ route('merkezler.index') }}" class="submenu-item {{ request()->routeIs('merkezler.*') ? 'is-active' : '' }}">
        Merkezler
      </a>
      @endyetki
      @yetki('alan.goruntule')
      <a href="{{ route('alanlar.index') }}" class="submenu-item {{ request()->routeIs('alanlar.*') ? 'is-active' : '' }}">
        Alanlar
      </a>
      @endyetki
      @yetki('brans.goruntule')
      <a href="{{ route('branslar.index') }}" class="submenu-item {{ request()->routeIs('branslar.*') ? 'is-active' : '' }}">
        Branşlar
      </a>
      @endyetki
      @yetki('sabit.goruntule')
      <a href="{{ route('sabit-tanimlar.index') }}" class="submenu-item {{ request()->routeIs('sabit-tanimlar.index', 'sabit-tanimlar.kurs-tipleri.*', 'sabit-tanimlar.evrak-tipleri.*', 'sabit-tanimlar.basvuru-durumlari.*', 'sabit-tanimlar.basari-durumlari.*', 'sabit-tanimlar.iptal-gerekceleri.*', 'sabit-tanimlar.kurumlar.*', 'sabit-tanimlar.sertifika-ayarlari.*', 'sabit-tanimlar.diger-ayarlar.*') ? 'is-active' : '' }}">
        Sabit Tanımlar
      </a>
      @endyetki
    </div>
  </div>
  @endif

  @php
    $etkinlikMenuAcik = request()->routeIs('etkinlikler.*', 'etkinlik-basvurulari.*', 'etkinlik-sabit-tanimlar.*', 'sabit-tanimlar.etkinlik-tipleri.*', 'sabit-tanimlar.etkinlik-basvuru-durumlari.*', 'sabit-tanimlar.etkinlik-diger-ayarlar.*');
    $etkinlikMenuGoster = auth()->user()?->hasAnyYetki(['etkinlik.goruntule', 'etkinlik_basvuru.goruntule', 'sabit.goruntule']);
  @endphp
  @if ($etkinlikMenuGoster)
  <div class="menu-dropdown" data-menu-dropdown>
    <button
      type="button"
      class="menu-item menu-dropdown-toggle w-full justify-between {{ $etkinlikMenuAcik ? 'menu-item-active dropdown-open' : '' }}"
      data-menu-dropdown-toggle
      aria-expanded="{{ $etkinlikMenuAcik ? 'true' : 'false' }}"
    >
      <span class="flex items-center gap-3.5">
        <svg class="menu-item-icon {{ $etkinlikMenuAcik ? 'menu-item-icon-accent' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        Etkinlik Yönetimi
      </span>
      <svg class="menu-arrow h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </button>
    <div class="submenu {{ $etkinlikMenuAcik ? 'open' : '' }}" data-menu-dropdown-content>
      @yetki('etkinlik.goruntule')
      <a href="{{ route('etkinlikler.index') }}" class="submenu-item {{ request()->routeIs('etkinlikler.*') ? 'is-active' : '' }}">
        Etkinlikler
      </a>
      @endyetki
      @yetki('etkinlik_basvuru.goruntule')
      <a href="{{ route('etkinlik-basvurulari.index') }}" class="submenu-item {{ request()->routeIs('etkinlik-basvurulari.index', 'etkinlik-basvurulari.export') ? 'is-active' : '' }}">
        Etkinlik Başvuruları
      </a>
      @endyetki
      @yetki('etkinlik_basvuru.olustur')
      <a href="{{ route('etkinlik-basvurulari.create') }}" class="submenu-item {{ request()->routeIs('etkinlik-basvurulari.create', 'etkinlik-basvurulari.store') ? 'is-active' : '' }}">
        Yeni Başvuru
      </a>
      @endyetki
      @yetki('sabit.goruntule')
      <a href="{{ route('etkinlik-sabit-tanimlar.index') }}" class="submenu-item {{ request()->routeIs('etkinlik-sabit-tanimlar.*', 'sabit-tanimlar.etkinlik-tipleri.*', 'sabit-tanimlar.etkinlik-basvuru-durumlari.*', 'sabit-tanimlar.etkinlik-diger-ayarlar.*') ? 'is-active' : '' }}">
        Sabit Tanımlar
      </a>
      @endyetki
    </div>
  </div>
  @endif

  @yetki('takvim.goruntule')
  <a href="{{ route('takvim.index') }}" class="menu-item {{ request()->routeIs('takvim.*') ? 'menu-item-active' : '' }}">
    <svg class="menu-item-icon {{ request()->routeIs('takvim.*') ? 'menu-item-icon-accent' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    Takvim
  </a>
  @endyetki

  <div class="menu-section-label">Kullanıcılar</div>
  @yetki('egitmen.goruntule')
  <a href="{{ route('egitmenler.index') }}" class="menu-item {{ request()->routeIs('egitmenler.*') ? 'menu-item-active' : '' }}">
    <svg class="menu-item-icon {{ request()->routeIs('egitmenler.*') ? 'menu-item-icon-accent' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    Eğitmenler
  </a>
  @endyetki
  @yetki('kullanici.goruntule')
  <a href="{{ route('kullanicilar.index') }}" class="menu-item {{ request()->routeIs('kullanicilar.*') && ! request()->routeIs('kullanicilar.merkez-yetkileri.*') ? 'menu-item-active' : '' }}">
    <svg class="menu-item-icon {{ request()->routeIs('kullanicilar.*') && ! request()->routeIs('kullanicilar.merkez-yetkileri.*') ? 'menu-item-icon-accent' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
    Kullanıcılar
  </a>
  @endyetki
  @yetki('kisi.goruntule')
  <a href="{{ route('kisiler.index') }}" class="menu-item {{ request()->routeIs('kisiler.*') ? 'menu-item-active' : '' }}">
    <svg class="menu-item-icon {{ request()->routeIs('kisiler.*') ? 'menu-item-icon-accent' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
    Kişiler
  </a>
  @endyetki

  @php
    $sistemMenuGoster = auth()->user()?->hasAnyYetki(['kullanici.goruntule', 'rol.goruntule', 'log.goruntule', 'entegrasyon.goruntule', 'genel_ayar.goruntule', 'portal_ayar.goruntule']);
  @endphp
  @if ($sistemMenuGoster)
  <div class="menu-section-label">Sistem</div>
  @yetki('genel_ayar.goruntule')
  <a href="{{ route('genel-ayarlar.edit') }}" class="menu-item {{ request()->routeIs('genel-ayarlar.*') ? 'menu-item-active' : '' }}">
    <svg class="menu-item-icon {{ request()->routeIs('genel-ayarlar.*') ? 'menu-item-icon-accent' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    Genel Ayarlar
  </a>
  @endyetki
  @yetki('portal_ayar.goruntule')
  <a href="{{ route('portal-ayarlar.index') }}" class="menu-item {{ request()->routeIs('portal-ayarlar.*') ? 'menu-item-active' : '' }}">
    <svg class="menu-item-icon {{ request()->routeIs('portal-ayarlar.*') ? 'menu-item-icon-accent' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
    Portal Ayarları
  </a>
  @endyetki
  @yetki('log.goruntule')
  <a href="{{ route('loglar.index') }}" class="menu-item {{ request()->routeIs('loglar.*') ? 'menu-item-active' : '' }}">
    <svg class="menu-item-icon {{ request()->routeIs('loglar.*') ? 'menu-item-icon-accent' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    İşlem Kayıtları
  </a>
  @endyetki
  @yetki('entegrasyon.goruntule')
  <a href="{{ route('entegrasyonlar.index') }}" class="menu-item {{ request()->routeIs('entegrasyonlar.*') ? 'menu-item-active' : '' }}">
    <svg class="menu-item-icon {{ request()->routeIs('entegrasyonlar.*') ? 'menu-item-icon-accent' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
    Entegrasyonlar
  </a>
  @endyetki
  @yetki('kullanici.goruntule')
  <a href="{{ route('merkez-yetkileri.index') }}" class="menu-item {{ request()->routeIs('merkez-yetkileri.*', 'kullanicilar.merkez-yetkileri.*') ? 'menu-item-active' : '' }}">
    <svg class="menu-item-icon {{ request()->routeIs('merkez-yetkileri.*', 'kullanicilar.merkez-yetkileri.*') ? 'menu-item-icon-accent' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    Merkez Yetkilendirme
  </a>
  @endyetki
  @yetki('rol.goruntule')
  <a href="{{ route('roller.index') }}" class="menu-item {{ request()->routeIs('roller.*') ? 'menu-item-active' : '' }}">
    <svg class="menu-item-icon {{ request()->routeIs('roller.*') ? 'menu-item-icon-accent' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
    Roller
  </a>
  @endyetki
  @endif
  </nav>

  <div class="sidebar-footer">
    <div class="appearance-settings-panel" id="settingsPanel" hidden>
      <div class="settings-title">
        Görünüm Ayarları
        <button type="button" id="settingsPanelCloseBtn" class="settings-close-btn" title="Kapat" aria-label="Kapat">
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="settings-group">
        <span class="settings-label">Renk Teması</span>
        <div class="theme-selector">
          <button type="button" class="theme-btn active" data-theme-btn="default" style="background:#323247;" title="Varsayılan" aria-label="Varsayılan tema"></button>
          <button type="button" class="theme-btn" data-theme-btn="onyx" style="background:#000000;" title="Tam Siyah" aria-label="Tam siyah tema"></button>
          <button type="button" class="theme-btn" data-theme-btn="light" style="background:#ffffff; border-color:#ccc;" title="Aydınlık" aria-label="Aydınlık tema"></button>
          <button type="button" class="theme-btn" data-theme-btn="bordo" style="background:#7d062b;" title="Bordo" aria-label="Bordo tema"></button>
          <button type="button" class="theme-btn" data-theme-btn="kucukcekmece" style="background:#23408f;" title="Küçükçekmece" aria-label="Küçükçekmece tema"></button>
        </div>
      </div>

      <div class="settings-group">
        <span class="settings-label">Yazı Boyutu: <span id="fontSizeDisplay">14px</span></span>
        <input type="range" class="range-slider" id="fontSlider" min="12" max="18" step="1" value="14">
      </div>

      <div class="settings-reset-row">
        <button type="button" id="appearanceResetBtn" class="settings-reset-btn">Sıfırla</button>
      </div>
    </div>

    <div class="sidebar-footer-inner">
          <a href="{{ route('profil.edit') }}" class="user-info user-info-link {{ request()->routeIs('profil.*') ? 'user-info-active' : '' }}" title="Profilim">
            <div class="user-name">{{ auth()->user()?->tam_adi }}</div>
            <div class="user-role">{{ auth()->user()?->roller_ozeti }}</div>
          </a>
      <div class="footer-actions">
        <button type="button" id="appearanceToggleBtn" class="icon-btn" title="Görünüm Ayarları" aria-label="Görünüm Ayarları">
          <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z"/>
            <circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/>
            <circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/>
            <circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/>
            <circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/>
          </svg>
        </button>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="icon-btn icon-btn-logout" title="Çıkış" aria-label="Çıkış">
            <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          </button>
        </form>
      </div>
    </div>
  </div>
</aside>
