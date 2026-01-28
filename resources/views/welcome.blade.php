<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inovasi Bung - Evolusi POS Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .gradient-text { background: linear-gradient(90deg, #3b82f6, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 antialiased overflow-x-hidden">

    <!-- Section 1: Navigation -->
    <nav class="fixed w-full z-50 glass top-0 px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center font-bold text-xl">B</div>
                <span class="text-xl font-extrabold tracking-tight">INNOVASI<span class="text-blue-500">BUNG</span></span>
            </div>
            <div class="hidden md:flex gap-8 text-sm font-medium">
                <a href="#fitur" class="hover:text-blue-400 transition">Fitur</a>
                <a href="#keunggulan" class="hover:text-blue-400 transition">Keunggulan</a>
                <a href="#testimoni" class="hover:text-blue-400 transition">Testimoni</a>
                <a href="#harga" class="hover:text-blue-400 transition">Harga</a>
            </div>
            <button class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded-full text-sm font-semibold transition shadow-lg shadow-blue-500/20">Coba Gratis</button>
        </div>
    </nav>

    <!-- Section 2: Hero Section -->
    <section class="relative pt-32 pb-20 px-6 min-h-screen flex items-center overflow-hidden">
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-blue-600/20 blur-[120px] rounded-full"></div>
        <div class="absolute top-1/2 -right-24 w-80 h-80 bg-purple-600/20 blur-[120px] rounded-full"></div>

        <div class="max-w-7xl mx-auto grid lg:grid-cols-2 gap-12 items-center relative z-10">
            <div>
                <span class="inline-block px-4 py-1.5 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-bold mb-6 tracking-widest uppercase italic">The New Era of Retail</span>
                <h1 class="text-5xl lg:text-7xl font-extrabold mb-6 leading-tight">
                    Sebuah Perubahan <span class="gradient-text italic">Dalam Inovasi</span> Untuk Bisnis Anda.
                </h1>
                <p class="text-slate-400 text-lg mb-8 max-w-lg leading-relaxed">
                    Ubah cara Anda mengelola toko dengan sistem POS tercanggih. Lebih dari sekadar kasir, ini adalah revolusi efisiensi operasional.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <button class="bg-white text-black px-8 py-4 rounded-xl font-bold flex items-center justify-center gap-2 hover:bg-slate-200 transition">
                        Mulai Sekarang <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                    <button class="glass px-8 py-4 rounded-xl font-bold border border-slate-700 hover:border-slate-500 transition">Lihat Demo</button>
                </div>
            </div>
            <div class="relative group">
                <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl blur opacity-25 group-hover:opacity-50 transition duration-1000"></div>
                <img src="https://images.unsplash.com/photo-1556742049-13da736c0a47?auto=format&fit=crop&q=80&w=1000" alt="Dashboard POS" class="relative rounded-2xl border border-slate-800 shadow-2xl transition duration-500 group-hover:scale-[1.01]">
            </div>
        </div>
    </section>

    <!-- Section 3: Stat Overview -->
    <section class="py-12 px-6 border-y border-slate-800 bg-slate-900/50">
        <div class="max-w-7xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div><h3 class="text-3xl font-bold mb-1">50k+</h3><p class="text-slate-400 text-sm">Merchant Aktif</p></div>
            <div><h3 class="text-3xl font-bold mb-1">99.9%</h3><p class="text-slate-400 text-sm">Uptime System</p></div>
            <div><h3 class="text-3xl font-bold mb-1">200+</h3><p class="text-slate-400 text-sm">Integrasi API</p></div>
            <div><h3 class="text-3xl font-bold mb-1">Rp 12T+</h3><p class="text-slate-400 text-sm">Transaksi Tahunan</p></div>
        </div>
    </section>

    <!-- Section 4: Intro Keunggulan -->
    <section id="fitur" class="py-24 px-6 max-w-7xl mx-auto text-center">
        <h2 class="text-4xl font-bold mb-4">Mengapa Inovasi Bung Berbeda?</h2>
        <p class="text-slate-400 max-w-2xl mx-auto mb-16 italic">"Perubahan bukanlah ancaman, melainkan peluang yang dikemas dalam barisan kode kami."</p>
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Feature Card 1 -->
            <div class="p-8 rounded-3xl glass text-left border-b-4 border-blue-500 group hover:translate-y-[-8px] transition duration-300">
                <div class="w-14 h-14 bg-blue-500/20 rounded-2xl flex items-center justify-center mb-6 text-blue-500 text-2xl group-hover:bg-blue-500 group-hover:text-white transition">
                    <i class="fa-solid fa-bolt-lightning"></i>
                </div>
                <h4 class="text-xl font-bold mb-3 italic">Kilat & Akurat</h4>
                <p class="text-slate-400 text-sm leading-relaxed">Proses transaksi hanya dalam 2 detik. Kurangi antrean panjang dan tingkatkan kepuasan pelanggan secara instan.</p>
            </div>
            <!-- Feature Card 2 -->
            <div class="p-8 rounded-3xl glass text-left border-b-4 border-purple-500 group hover:translate-y-[-8px] transition duration-300">
                <div class="w-14 h-14 bg-purple-500/20 rounded-2xl flex items-center justify-center mb-6 text-purple-500 text-2xl group-hover:bg-purple-500 group-hover:text-white transition">
                    <i class="fa-solid fa-cloud"></i>
                </div>
                <h4 class="text-xl font-bold mb-3 italic">Cloud Sinkronisasi</h4>
                <p class="text-slate-400 text-sm leading-relaxed">Akses data bisnis Anda dari mana saja, kapan saja. Pantau performa cabang meskipun Anda sedang berlibur.</p>
            </div>
            <!-- Feature Card 3 -->
            <div class="p-8 rounded-3xl glass text-left border-b-4 border-green-500 group hover:translate-y-[-8px] transition duration-300">
                <div class="w-14 h-14 bg-green-500/20 rounded-2xl flex items-center justify-center mb-6 text-green-500 text-2xl group-hover:bg-green-500 group-hover:text-white transition">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h4 class="text-xl font-bold mb-3 italic">Keamanan Berlapis</h4>
                <p class="text-slate-400 text-sm leading-relaxed">Enkripsi tingkat bank untuk setiap transaksi. Data inventaris dan keuangan Anda aman bersama kami.</p>
            </div>
        </div>
    </section>

    <!-- Section 5: Visual Feature Showcase 1 -->
    <section class="py-20 px-6 max-w-7xl mx-auto">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div class="order-2 lg:order-1">
                <img src="https://images.unsplash.com/photo-1556740734-7f9a2b7a0f42?auto=format&fit=crop&q=80&w=800" alt="Laporan Keuangan" class="rounded-3xl shadow-2xl border border-slate-800">
            </div>
            <div class="order-1 lg:order-2">
                <h2 class="text-4xl font-extrabold mb-6 leading-tight italic">Analisis Data Yang <br><span class="text-blue-500 underline decoration-2 underline-offset-8">Mengubah Perspektif.</span></h2>
                <p class="text-slate-400 mb-8 leading-relaxed">Jangan menebak-nebak lagi. Gunakan AI Dashboard kami untuk memprediksi stok barang yang akan habis dan jam-jam sibuk toko Anda.</p>
                <ul class="space-y-4">
                    <li class="flex items-center gap-3 text-sm font-medium"><i class="fa-solid fa-check text-green-500"></i> Laporan Laba Rugi Real-time</li>
                    <li class="flex items-center gap-3 text-sm font-medium"><i class="fa-solid fa-check text-green-500"></i> Heatmap Penjualan Produk</li>
                    <li class="flex items-center gap-3 text-sm font-medium"><i class="fa-solid fa-check text-green-500"></i> Export Data ke Berbagai Format</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Section 6: Image Grid - Multiverse of Use Cases -->
    <section class="py-24 bg-slate-900/30 overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 text-center mb-16">
            <h2 class="text-3xl font-bold mb-4 italic italic">Satu Aplikasi Untuk Segala Bidang</h2>
        </div>
        <div class="flex gap-6 animate-scroll whitespace-nowrap overflow-x-auto pb-8 px-6 no-scrollbar">
            <div class="min-w-[300px] h-[400px] rounded-2xl overflow-hidden relative group shrink-0">
                <img src="https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&q=80&w=500" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex items-end p-6">
                    <span class="text-xl font-bold uppercase italic tracking-tighter">Coffee Shop</span>
                </div>
            </div>
            <div class="min-w-[300px] h-[400px] rounded-2xl overflow-hidden relative group shrink-0">
                <img src="https://images.unsplash.com/photo-1595152772835-219674b2a8a6?auto=format&fit=crop&q=80&w=500" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex items-end p-6">
                    <span class="text-xl font-bold uppercase italic tracking-tighter">Barbershop</span>
                </div>
            </div>
            <div class="min-w-[300px] h-[400px] rounded-2xl overflow-hidden relative group shrink-0">
                <img src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&q=80&w=500" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex items-end p-6">
                    <span class="text-xl font-bold uppercase italic tracking-tighter">Retail Fashion</span>
                </div>
            </div>
            <div class="min-w-[300px] h-[400px] rounded-2xl overflow-hidden relative group shrink-0">
                <img src="https://images.unsplash.com/photo-1550989460-0adf9ea622e2?auto=format&fit=crop&q=80&w=500" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex items-end p-6">
                    <span class="text-xl font-bold uppercase italic tracking-tighter">Supermarket</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 7-12: Fitur List (Compact Grid) -->
    <section id="keunggulan" class="py-24 px-6 max-w-7xl mx-auto">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-extrabold italic mb-4 uppercase tracking-widest">Mastering Efficiency</h2>
            <div class="h-1 w-24 bg-blue-600 mx-auto"></div>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="p-6 glass rounded-2xl hover:bg-white/5 transition border-l-2 border-blue-500">
                <i class="fa-solid fa-box mb-4 text-blue-500 text-2xl"></i>
                <h5 class="font-bold mb-2">Manajemen Stok Otomatis</h5>
                <p class="text-sm text-slate-400">Update stok otomatis tiap kali ada penjualan, cegah kekurangan barang.</p>
            </div>
            <div class="p-6 glass rounded-2xl hover:bg-white/5 transition border-l-2 border-purple-500">
                <i class="fa-solid fa-users mb-4 text-purple-500 text-2xl"></i>
                <h5 class="font-bold mb-2">CRM Terintegrasi</h5>
                <p class="text-sm text-slate-400">Simpan database pelanggan dan buat program loyalitas untuk mereka.</p>
            </div>
            <div class="p-6 glass rounded-2xl hover:bg-white/5 transition border-l-2 border-green-500">
                <i class="fa-solid fa-receipt mb-4 text-green-500 text-2xl"></i>
                <h5 class="font-bold mb-2">Struk Digital (E-Receipt)</h5>
                <p class="text-sm text-slate-400">Kirim struk lewat WhatsApp atau Email, hemat kertas dan ramah lingkungan.</p>
            </div>
            <div class="p-6 glass rounded-2xl hover:bg-white/5 transition border-l-2 border-red-500">
                <i class="fa-solid fa-tags mb-4 text-red-500 text-2xl"></i>
                <h5 class="font-bold mb-2">Promo & Diskon Fleksibel</h5>
                <p class="text-sm text-slate-400">Atur diskon bertingkat atau promo buy 1 get 1 dengan mudah.</p>
            </div>
            <div class="p-6 glass rounded-2xl hover:bg-white/5 transition border-l-2 border-yellow-500">
                <i class="fa-solid fa-tablet-screen-button mb-4 text-yellow-500 text-2xl"></i>
                <h5 class="font-bold mb-2">UI/UX Intuitif</h5>
                <p class="text-sm text-slate-400">Desain yang mudah dipelajari bahkan oleh karyawan baru dalam 15 menit.</p>
            </div>
            <div class="p-6 glass rounded-2xl hover:bg-white/5 transition border-l-2 border-pink-500">
                <i class="fa-solid fa-wallet mb-4 text-pink-500 text-2xl"></i>
                <h5 class="font-bold mb-2">Multi-Payment Support</h5>
                <p class="text-sm text-slate-400">Terima QRIS, Kartu Kredit, Debit, hingga Dompet Digital dengan satu alat.</p>
            </div>
        </div>
    </section>

    <!-- Section 13: Big Image CTA -->
    <section class="relative py-20 overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="bg-gradient-to-br from-blue-700 to-blue-900 rounded-[3rem] p-12 lg:p-20 overflow-hidden relative">
                <div class="lg:w-1/2">
                    <h2 class="text-4xl lg:text-5xl font-bold mb-8 italic">Mulai Revolusi <br>Bisnis Hari Ini.</h2>
                    <p class="text-blue-100 mb-10 text-lg">Ribuan pengusaha telah bermigrasi ke Inovasi Bung. Jangan biarkan teknologi lama menghambat pertumbuhan Anda.</p>
                    <button class="bg-white text-blue-900 px-10 py-4 rounded-2xl font-black uppercase tracking-wider hover:bg-slate-100 transition shadow-xl">Join The Tribe</button>
                </div>
                <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&q=80&w=800" class="absolute right-0 top-0 h-full w-1/2 object-cover hidden lg:block mask-image-linear" alt="Bisnis Berkembang">
            </div>
        </div>
    </section>

    <!-- Section 14: Testimoni -->
    <section id="testimoni" class="py-24 px-6 max-w-7xl mx-auto">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-4xl font-bold mb-6 italic">Cerita Perubahan <br><span class="text-blue-500">Dibalik Layar.</span></h2>
                <div class="glass p-8 rounded-3xl mb-6 relative">
                    <i class="fa-solid fa-quote-left text-4xl text-blue-500/20 absolute top-4 left-4"></i>
                    <p class="text-lg italic leading-relaxed mb-6">"Dulu pencatatan stok adalah mimpi buruk bagi saya. Sejak pakai Inovasi Bung, saya punya waktu lebih banyak untuk keluarga dan stok selalu aman."</p>
                    <div class="flex items-center gap-4">
                        <img src="https://i.pravatar.cc/150?u=bung1" class="w-12 h-12 rounded-full border-2 border-blue-500">
                        <div>
                            <p class="font-bold">Bung Andre</p>
                            <p class="text-xs text-slate-400 uppercase tracking-widest">Owner Coffee Bung</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=300" class="rounded-2xl grayscale hover:grayscale-0 transition duration-500 aspect-square object-cover">
                <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=300" class="rounded-2xl grayscale hover:grayscale-0 transition duration-500 aspect-square object-cover">
                <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=300" class="rounded-2xl grayscale hover:grayscale-0 transition duration-500 aspect-square object-cover">
                <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=300" class="rounded-2xl grayscale hover:grayscale-0 transition duration-500 aspect-square object-cover">
            </div>
        </div>
    </section>

    <!-- Section 15: Pricing -->
    <section id="harga" class="py-24 px-6 bg-slate-900/50">
        <div class="max-w-7xl mx-auto text-center mb-16">
            <h2 class="text-4xl font-bold mb-4">Investasi Untuk Masa Depan</h2>
            <p class="text-slate-400">Tanpa biaya tersembunyi. Pilih paket yang sesuai skala bisnis Anda.</p>
        </div>
        <div class="max-w-4xl mx-auto grid md:grid-cols-2 gap-8">
            <!-- Basic -->
            <div class="p-10 glass rounded-[2rem] border-t-8 border-slate-700">
                <h4 class="text-xl font-bold mb-2">Startup Bung</h4>
                <p class="text-3xl font-black mb-6">Rp 149k <span class="text-sm font-normal text-slate-400">/bulan</span></p>
                <ul class="space-y-4 mb-10 text-sm">
                    <li><i class="fa-solid fa-check text-blue-500 mr-2"></i> 1 Outlet & 2 User</li>
                    <li><i class="fa-solid fa-check text-blue-500 mr-2"></i> Manajemen Inventaris Dasar</li>
                    <li><i class="fa-solid fa-check text-blue-500 mr-2"></i> Laporan Harian Otomatis</li>
                    <li class="opacity-40"><i class="fa-solid fa-xmark mr-2"></i> Integrasi Akuntansi</li>
                </ul>
                <button class="w-full py-4 glass border-slate-700 rounded-xl font-bold hover:bg-white hover:text-black transition">Pilih Paket</button>
            </div>
            <!-- Pro -->
            <div class="p-10 bg-blue-600 rounded-[2rem] relative shadow-2xl shadow-blue-500/20 transform md:scale-105">
                <div class="absolute -top-4 right-8 bg-white text-blue-600 text-[10px] font-black px-3 py-1 rounded-full uppercase italic">Terpopuler</div>
                <h4 class="text-xl font-bold mb-2">Business Bung</h4>
                <p class="text-3xl font-black mb-6">Rp 399k <span class="text-sm font-normal text-blue-200">/bulan</span></p>
                <ul class="space-y-4 mb-10 text-sm text-blue-50">
                    <li><i class="fa-solid fa-check mr-2"></i> Unlimited Outlet & User</li>
                    <li><i class="fa-solid fa-check mr-2"></i> Advanced AI Analytics</li>
                    <li><i class="fa-solid fa-check mr-2"></i> Integrasi Marketplace</li>
                    <li><i class="fa-solid fa-check mr-2"></i> Support Prioritas 24/7</li>
                </ul>
                <button class="w-full py-4 bg-white text-blue-600 rounded-xl font-black hover:bg-slate-100 transition shadow-lg">Gas Sekarang!</button>
            </div>
        </div>
    </section>

    <!-- Section 16: FAQ (Brief) -->
    <section class="py-20 px-6 max-w-3xl mx-auto">
        <h2 class="text-3xl font-bold mb-10 text-center italic">Pertanyaan Umum</h2>
        <div class="space-y-4">
            <details class="group glass p-4 rounded-xl cursor-pointer">
                <summary class="font-bold flex justify-between items-center list-none">
                    Apakah bisa digunakan offline?
                    <i class="fa-solid fa-chevron-down group-open:rotate-180 transition"></i>
                </summary>
                <p class="text-sm text-slate-400 mt-4 leading-relaxed">Tentu! Inovasi Bung tetap berfungsi saat internet mati. Data akan otomatis disinkronkan saat koneksi kembali stabil.</p>
            </details>
            <details class="group glass p-4 rounded-xl cursor-pointer">
                <summary class="font-bold flex justify-between items-center list-none">
                    Bagaimana dengan migrasi data lama?
                    <i class="fa-solid fa-chevron-down group-open:rotate-180 transition"></i>
                </summary>
                <p class="text-sm text-slate-400 mt-4 leading-relaxed">Tim kami akan membantu proses migrasi data dari sistem lama Anda ke Inovasi Bung secara gratis dalam 24 jam.</p>
            </details>
        </div>
    </section>

    <!-- Section 17-19: Support & Ecosystem -->
    <section class="py-24 px-6 border-t border-slate-900 overflow-hidden relative">
        <div class="max-w-7xl mx-auto text-center">
            <h2 class="text-2xl font-bold mb-12 opacity-50 uppercase tracking-[0.3em]">Dipercaya Oleh Ekosistem Digital</h2>
            <div class="flex flex-wrap justify-center gap-12 opacity-30 grayscale items-center">
                <img src="https://upload.wikimedia.org/wikipedia/commons/7/72/Gojek_logo_2019.svg" class="h-8" alt="Gojek">
                <img src="https://upload.wikimedia.org/wikipedia/commons/1/12/Grab_logo.svg" class="h-8" alt="Grab">
                <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Shopee_logo.svg" class="h-10" alt="Shopee">
                <img src="https://upload.wikimedia.org/wikipedia/commons/a/ab/Logo_Dana.svg" class="h-8" alt="Dana">
                <img src="https://upload.wikimedia.org/wikipedia/commons/e/eb/Logo_ovo.svg" class="h-6" alt="OVO">
            </div>
        </div>
    </section>

    <!-- Section 20: Footer -->
    <footer class="bg-black py-20 px-6 border-t border-slate-800">
        <div class="max-w-7xl mx-auto grid md:grid-cols-4 gap-12">
            <div class="md:col-span-2">
                <div class="flex items-center gap-2 mb-6">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center font-bold">B</div>
                    <span class="text-xl font-black">INOVASI<span class="text-blue-500">BUNG</span></span>
                </div>
                <p class="text-slate-500 text-sm max-w-sm mb-6 leading-relaxed">
                    Membangun jembatan digital untuk UMKM Indonesia menuju level global.
                    Inovasi bukanlah pilihan, melainkan sebuah perubahan yang harus dijalani.
                </p>
                <div class="flex gap-4">
                    <a href="#" class="w-10 h-10 rounded-full glass flex items-center justify-center hover:bg-blue-600 transition"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="w-10 h-10 rounded-full glass flex items-center justify-center hover:bg-blue-600 transition"><i class="fa-brands fa-tiktok"></i></a>
                    <a href="#" class="w-10 h-10 rounded-full glass flex items-center justify-center hover:bg-blue-600 transition"><i class="fa-brands fa-linkedin-in"></i></a>
                </div>
            </div>
            <div>
                <h6 class="font-bold mb-6 text-blue-500 uppercase tracking-widest text-xs">Produk</h6>
                <ul class="space-y-4 text-sm text-slate-400">
                    <li><a href="#" class="hover:text-white transition">Point of Sale</a></li>
                    <li><a href="#" class="hover:text-white transition">Inventaris</a></li>
                    <li><a href="#" class="hover:text-white transition">Manajemen Staf</a></li>
                    <li><a href="#" class="hover:text-white transition">Loyalty Program</a></li>
                </ul>
            </div>
            <div>
                <h6 class="font-bold mb-6 text-blue-500 uppercase tracking-widest text-xs">Perusahaan</h6>
                <ul class="space-y-4 text-sm text-slate-400">
                    <li><a href="#" class="hover:text-white transition">Tentang Kami</a></li>
                    <li><a href="#" class="hover:text-white transition">Karir</a></li>
                    <li><a href="#" class="hover:text-white transition">Kontak</a></li>
                    <li><a href="#" class="hover:text-white transition">Blog Inovasi</a></li>
                </ul>
            </div>
        </div>
        <div class="max-w-7xl mx-auto mt-20 pt-8 border-t border-slate-900 flex flex-col md:row justify-between text-[10px] text-slate-600 uppercase tracking-widest gap-4">
            <p>&copy; 2024 Inovasi Bung. All rights reserved.</p>
            <div class="flex gap-6">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </div>
        </div>
    </footer>

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .mask-image-linear { mask-image: linear-gradient(to left, black 80%, transparent); }
    </style>

</body>
</html>
