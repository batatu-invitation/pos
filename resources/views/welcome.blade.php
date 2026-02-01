<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartPOS Elite - Standar Baru Manajemen Bisnis</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Outfit:wght@100;300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #050505;
            --accent-blue: #2D5BFF;
            --glass-white: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body { 
            font-family: 'Outfit', sans-serif; 
            background-color: var(--bg-dark);
            color: #ffffff;
        }

        .font-space { font-family: 'Space Grotesk', sans-serif; }

        .glass-card {
            background: var(--glass-white);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 32px;
        }

        .gradient-border {
            position: relative;
            border-radius: 32px;
            background: linear-gradient(135deg, #2D5BFF, #9D50BB);
            padding: 1px;
        }

        .gradient-inner {
            background: var(--bg-dark);
            border-radius: 31px;
        }

        .hero-text {
            font-size: clamp(3rem, 10vw, 8rem);
            line-height: 0.9;
            letter-spacing: -0.04em;
        }

        .bento-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-template-rows: repeat(2, 300px);
            gap: 24px;
        }

        .noise {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            pointer-events: none;
            opacity: 0.05;
            z-index: 9999;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E");
        }

        .glow {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(45, 91, 255, 0.15) 0%, transparent 70%);
            z-index: -1;
            pointer-events: none;
        }

        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        .animate-marquee {
            animation: marquee 30s linear infinite;
        }
    </style>
</head>
<body class="overflow-x-hidden">
    <div class="noise"></div>
    <div class="glow top-[-10%] right-[-10%]"></div>
    <div class="glow bottom-[10%] left-[-10%]"></div>

    <!-- Nav -->
    <nav class="fixed w-full z-[100] px-6 py-6">
        <div class="max-w-7xl mx-auto glass-card px-8 py-4 flex justify-between items-center border-white/5">
            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-blue-600 rounded-full"></div>
                <span class="text-xl font-extrabold tracking-tighter uppercase font-space">Smart<span class="text-blue-500">POS</span></span>
            </div>
            <div class="hidden md:flex space-x-8 text-sm font-semibold uppercase tracking-widest text-white/60">
                <a href="#" class="hover:text-white transition">Fitur</a>
                <a href="#" class="hover:text-white transition">Ekosistem</a>
                <a href="#" class="hover:text-white transition">Mitra</a>
                <a href="#" class="hover:text-white transition">Harga</a>
            </div>
            <button class="bg-white text-black px-6 py-2.5 rounded-full text-sm font-bold hover:bg-blue-600 hover:text-white transition">
                HUBUNGI KAMI
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative pt-60 pb-32 px-6">
        <div class="max-w-[1400px] mx-auto">
            <div class="mb-12">
                <span class="inline-block border border-white/20 px-4 py-1 rounded-full text-xs font-bold tracking-widest uppercase mb-6 text-white/40">
                    Versi 4.0 Kini Tersedia
                </span>
                <h1 class="hero-text font-bold uppercase">
                    Kendali Mutlak <br>
                    <span class="text-blue-600">Bisnis Anda.</span>
                </h1>
            </div>
            
            <div class="flex flex-col md:flex-row justify-between items-end gap-12">
                <p class="max-w-xl text-xl text-white/50 leading-relaxed font-light">
                    Sistem manajemen point-of-sales paling presisi untuk pengusaha yang tidak ingin berkompromi dengan detail. Dirancang untuk kecepatan, keamanan, dan skalabilitas tanpa batas.
                </p>
                <div class="flex space-x-4">
                    <button class="bg-blue-600 text-white w-20 h-20 rounded-full flex items-center justify-center hover:scale-110 transition shadow-2xl shadow-blue-600/20">
                        <i class="fas fa-arrow-right text-xl"></i>
                    </button>
                    <div class="text-left">
                        <p class="text-sm font-bold uppercase tracking-widest mb-1">Coba Demo</p>
                        <p class="text-xs text-white/40">Tanpa Kartu Kredit</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Visual Showcase (Bento Grid) -->
    <section class="py-20 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-4 md:grid-rows-2 gap-6 h-auto md:h-[700px]">
                <!-- Item 1: Large Dashboard -->
                <div class="md:col-span-2 md:row-span-2 glass-card p-10 flex flex-col justify-between overflow-hidden relative group">
                    <div class="relative z-10">
                        <h3 class="text-3xl font-bold mb-4 font-space">Analisis <br>Penjualan Real-Time</h3>
                        <p class="text-white/40 max-w-xs leading-relaxed">Pantau setiap rupiah yang masuk dengan presisi milidetik. Laporan otomatis langsung ke email Anda.</p>
                    </div>
                    <div class="mt-8 bg-white/5 rounded-2xl h-64 w-full border border-white/10 p-6 flex items-end gap-3 group-hover:bg-white/10 transition">
                        <div class="bg-blue-600 w-full h-1/2 rounded-t-lg"></div>
                        <div class="bg-blue-400 w-full h-3/4 rounded-t-lg"></div>
                        <div class="bg-blue-700 w-full h-full rounded-t-lg"></div>
                        <div class="bg-blue-500 w-full h-2/3 rounded-t-lg"></div>
                        <div class="bg-blue-600 w-full h-4/5 rounded-t-lg"></div>
                    </div>
                </div>

                <!-- Item 2: Inventory -->
                <div class="md:col-span-2 glass-card p-10 flex justify-between items-center group">
                    <div>
                        <h3 class="text-2xl font-bold mb-2 font-space">Manajemen Stok</h3>
                        <p class="text-white/40 text-sm">Otomasi rantai pasok Anda tanpa ribet.</p>
                    </div>
                    <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center text-3xl group-hover:scale-110 transition">
                        <i class="fas fa-boxes-stacked"></i>
                    </div>
                </div>

                <!-- Item 3: Security -->
                <div class="glass-card p-10 flex flex-col justify-center items-center text-center group">
                    <i class="fas fa-shield-halved text-4xl text-blue-500 mb-6 group-hover:scale-125 transition"></i>
                    <h4 class="font-bold font-space uppercase">Enkripsi <br>End-to-End</h4>
                </div>

                <!-- Item 4: Multi Branch -->
                <div class="glass-card p-10 flex flex-col justify-center items-center text-center group">
                    <i class="fas fa-network-wired text-4xl text-blue-500 mb-6 group-hover:scale-125 transition"></i>
                    <h4 class="font-bold font-space uppercase">Multi <br>Cabang</h4>
                </div>
            </div>
        </div>
    </section>

    <!-- Marquee Text -->
    <div class="py-20 bg-blue-600 overflow-hidden whitespace-nowrap border-y border-white/20">
        <div class="animate-marquee inline-block text-8xl font-black uppercase tracking-tighter">
            SOLUSI RETAIL &bull; SOLUSI F&B &bull; SOLUSI JASA &bull; MANAJEMEN INVENTARIS &bull; LAPORAN PAJAK &bull; SOLUSI RETAIL &bull; SOLUSI F&B &bull; 
        </div>
    </div>

    <!-- Deep Tech Features -->
    <section class="py-32 px-6">
        <div class="max-w-7xl mx-auto grid md:grid-cols-2 gap-32 items-center">
            <div>
                <h2 class="text-5xl font-extrabold font-space uppercase leading-tight mb-12">
                    Dibuat untuk <br><span class="text-white/30">Kebutuhan yang</span> <br>Sangat Kompleks.
                </h2>
                <div class="space-y-12">
                    <div class="border-l-4 border-blue-600 pl-8">
                        <h4 class="text-xl font-bold mb-4 uppercase">Sinkronisasi Offline</h4>
                        <p class="text-white/50 leading-relaxed">Internet mati? Tidak masalah. Sistem tetap mencatat transaksi dan akan menyinkronkan data secara otomatis saat koneksi kembali normal.</p>
                    </div>
                    <div class="border-l-4 border-white/10 pl-8 hover:border-blue-600 transition">
                        <h4 class="text-xl font-bold mb-4 uppercase">Integrasi Hardware</h4>
                        <p class="text-white/50 leading-relaxed">Kompatibel dengan printer thermal, barcode scanner, laci kasir, dan timbangan digital manapun.</p>
                    </div>
                    <div class="border-l-4 border-white/10 pl-8 hover:border-blue-600 transition">
                        <h4 class="text-xl font-bold mb-4 uppercase">Loyalty Program</h4>
                        <p class="text-white/50 leading-relaxed">Kelola poin member, diskon khusus, dan promo berkala untuk menjaga retensi pelanggan Anda.</p>
                    </div>
                </div>
            </div>
            <div class="relative">
                <div class="gradient-border">
                    <div class="gradient-inner p-4">
                        <div class="rounded-3xl overflow-hidden bg-zinc-900 border border-white/5 p-12">
                            <div class="flex items-center space-x-2 mb-10">
                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                            </div>
                            <div class="space-y-6">
                                <div class="h-4 bg-white/5 rounded w-3/4"></div>
                                <div class="h-4 bg-white/5 rounded w-full"></div>
                                <div class="h-4 bg-white/5 rounded w-5/6"></div>
                                <div class="h-20 bg-blue-600/20 border border-blue-600/30 rounded w-full flex items-center justify-center text-blue-400 font-mono text-xs">
                                    [ SISTEM STATUS: OPTIMAL ]
                                </div>
                                <div class="h-4 bg-white/5 rounded w-2/3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing (No-Nonsense) -->
    <section class="py-32 px-6">
        <div class="max-w-4xl mx-auto">
            <div class="glass-card p-16 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-12 opacity-10 text-9xl">
                    <i class="fas fa-tag"></i>
                </div>
                <div class="relative z-10 text-center">
                    <h2 class="text-4xl font-bold mb-8 uppercase font-space tracking-tight">Investasi Tanpa Embun</h2>
                    <div class="text-8xl font-black mb-6 tracking-tighter">Rp 2jt<span class="text-2xl text-white/30 tracking-normal font-normal">/tahun</span></div>
                    <p class="text-white/50 mb-12 max-w-sm mx-auto">Satu harga untuk semua fitur. Tanpa biaya tambahan, tanpa batas outlet, tanpa batas transaksi.</p>
                    <button class="bg-blue-600 text-white px-12 py-5 rounded-full font-bold text-lg hover:bg-blue-700 transition shadow-2xl shadow-blue-600/30 active:scale-95">
                        AKTIFKAN SEKARANG
                    </button>
                    <p class="mt-8 text-xs text-white/20 uppercase font-bold tracking-[0.3em]">Jaminan Uang Kembali 30 Hari</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Elite -->
    <footer class="py-20 px-6 border-t border-white/10">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-start md:items-center gap-12">
            <div>
                <div class="flex items-center space-x-2 mb-6">
                    <div class="w-6 h-6 bg-blue-600 rounded-full"></div>
                    <span class="text-xl font-extrabold tracking-tighter uppercase font-space">SmartPOS</span>
                </div>
                <p class="text-white/30 text-sm max-w-xs leading-relaxed">Mendefinisikan ulang cara pengusaha mengelola aset dan pendapatan mereka sejak 2018.</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-16">
                <div>
                    <h5 class="text-xs font-bold uppercase tracking-widest text-white/20 mb-6">Produk</h5>
                    <ul class="space-y-3 text-sm font-semibold">
                        <li><a href="#" class="hover:text-blue-500">Fitur</a></li>
                        <li><a href="#" class="hover:text-blue-500">Integrasi</a></li>
                        <li><a href="#" class="hover:text-blue-500">Keamanan</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="text-xs font-bold uppercase tracking-widest text-white/20 mb-6">Legal</h5>
                    <ul class="space-y-3 text-sm font-semibold">
                        <li><a href="#" class="hover:text-blue-600">Privasi</a></li>
                        <li><a href="#" class="hover:text-blue-600">Ketentuan</a></li>
                        <li><a href="#" class="hover:text-blue-600">SLA</a></li>
                    </ul>
                </div>
                <div class="col-span-2 md:col-span-1">
                    <h5 class="text-xs font-bold uppercase tracking-widest text-white/20 mb-6">Regional</h5>
                    <p class="text-sm font-bold">Jakarta, Indonesia</p>
                    <p class="text-sm text-white/40">Gedung Pusat Bisnis, Lt. 24</p>
                </div>
            </div>
        </div>
        <div class="max-w-7xl mx-auto mt-20 pt-10 border-t border-white/5 flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-white/20">
            <p>&copy; 2024 SMARTPOS ELITE. ALL RIGHTS RESERVED.</p>
            <div class="flex space-x-6">
                <a href="#">Instagram</a>
                <a href="#">Twitter</a>
                <a href="#">LinkedIn</a>
            </div>
        </div>
    </footer>

</body>
</html>