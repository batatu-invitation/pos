<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth {{ ($currentTheme ?? 'system') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Setup - Inovasi Bung' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol"; }
        .glass { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .glass-card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .gradient-text { background: linear-gradient(90deg, #3b82f6, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        /* Custom Scrollbar for the options area */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05); 
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2); 
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3); 
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#4f46e5',
                        secondary: '#1e293b',
                    }
                }
            }
        }
    </script>
    <script>
        // SweetAlert Dark Mode
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            background: '#1e293b',
            color: '#fff'
        });

        window.addEventListener('notify', event => {
            Toast.fire({
                icon: event.detail.type,
                title: event.detail.message
            });
        });
    </script>
</head>
<body class="bg-gray-50 dark:bg-slate-950 text-gray-900 dark:text-slate-100 antialiased h-screen w-full overflow-hidden flex items-center justify-center relative" 
      x-data="{
          theme: '{{ $currentTheme ?? 'system' }}',
          setTheme(val) {
              this.theme = val;
              localStorage.setItem('theme', val);
              document.cookie = 'theme=' + val + '; path=/; max-age=31536000; SameSite=Lax';
              if (val === 'dark' || (val === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                  document.documentElement.classList.add('dark');
              } else {
                  document.documentElement.classList.remove('dark');
              }
          },
          init() {
              if (this.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                  document.documentElement.classList.add('dark');
              }
              window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                  if (this.theme === 'system') {
                      if (e.matches) document.documentElement.classList.add('dark');
                      else document.documentElement.classList.remove('dark');
                  }
              });
          }
      }" 
      x-init="init()">
    
    <!-- Background Glow Effects -->
    <div class="absolute -top-24 -left-24 w-96 h-96 bg-gradient-to-br from-blue-400/20 to-purple-400/20 blur-[120px] rounded-full pointer-events-none dark:from-blue-600/20 dark:to-purple-600/20"></div>
    <div class="absolute bottom-0 right-0 w-80 h-80 bg-gradient-to-br from-purple-400/20 to-pink-400/20 blur-[120px] rounded-full pointer-events-none dark:from-purple-600/20 dark:to-pink-600/20"></div>
    
    <!-- Theme Switcher -->
    <div class="absolute top-4 right-4 z-50" x-data="{
        open: false,
        currentTheme: localStorage.getItem('theme') || '{{ $currentTheme ?? 'system' }}',
        toggleTheme() {
            const themes = ['light', 'dark', 'system'];
            const currentIndex = themes.indexOf(this.currentTheme);
            const nextTheme = themes[(currentIndex + 1) % themes.length];
            this.currentTheme = nextTheme;
            localStorage.setItem('theme', nextTheme);
            document.cookie = 'theme=' + nextTheme + '; path=/; max-age=31536000; SameSite=Lax';
            
            if (nextTheme === 'dark' || (nextTheme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    }">
        <button @click="toggleTheme()"
            class="flex items-center space-x-2 text-gray-600 hover:text-indigo-600 transition-colors focus:outline-none p-3 rounded-xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-lg border border-gray-200/50 dark:border-gray-700/50">
            <template x-if="currentTheme === 'light'"><i class="fas fa-sun text-lg"></i></template>
            <template x-if="currentTheme === 'dark'"><i class="fas fa-moon text-lg"></i></template>
            <template x-if="currentTheme === 'system'"><i class="fas fa-desktop text-lg"></i></template>
            <span class="text-sm font-medium" x-text="currentTheme.charAt(0).toUpperCase() + currentTheme.slice(1)"></span>
        </button>
    </div>

    {{ $slot }}

</body>
</html>
