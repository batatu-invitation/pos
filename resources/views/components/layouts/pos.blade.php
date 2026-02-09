<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ ($currentTheme ?? 'system') === 'dark' ? 'dark' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'POS - Modern POS' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Fix SweetAlert2 layout shift issues
        window.Swal = window.Swal.mixin({
            heightAuto: false,
            scrollbarPadding: false
        });
    </script>
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
</head>
<body class="bg-gray-50 h-screen overflow-hidden dark:bg-gray-900 dark:text-gray-100" 
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
    {{ $slot }}
    <script src="{{ asset('js/main.js') }}" defer></script>
    <script src="{{ asset('js/pos-devices.js') }}" defer></script>
    <script>
        document.addEventListener('livewire:navigated', () => {
            initSweetAlert();
        });

        document.addEventListener('DOMContentLoaded', () => {
            initSweetAlert();
        });

        function initSweetAlert() {
            window.addEventListener('notify', event => {
                const msg = Array.isArray(event.detail) ? event.detail[0] : event.detail;
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: msg,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
            });

            window.addEventListener('notify-error', event => {
                const msg = Array.isArray(event.detail) ? event.detail[0] : event.detail;
                 Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: msg,
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
            });
        }
    </script>
</body>
</html>
