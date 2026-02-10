<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modern POS - Empower Your Business</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            900: '#312e81',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="font-sans antialiased text-gray-800 bg-white selection:bg-primary-500 selection:text-white">

    <!-- Navigation -->
    <nav class="fixed w-full z-50 bg-white/80 backdrop-blur-md border-b border-gray-100 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <!-- Logo -->
                <div class="flex items-center gap-2">
                    <div class="bg-gradient-to-br from-primary-600 to-purple-600 text-white p-2.5 rounded-xl shadow-lg shadow-primary-500/30">
                        <i class="fas fa-cash-register text-xl"></i>
                    </div>
                    <span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-gray-900 to-gray-600">
                        Modern POS
                    </span>
                </div>

                <!-- Auth Links -->
                <div class="flex items-center gap-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="font-semibold text-gray-600 hover:text-primary-600 transition-colors">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="font-medium text-gray-600 hover:text-primary-600 transition-colors">Log in</a>
                            
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="hidden sm:inline-flex items-center justify-center px-5 py-2.5 text-sm font-semibold text-white transition-all duration-200 bg-primary-600 border border-transparent rounded-xl hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-600 shadow-lg shadow-primary-600/30 hover:shadow-primary-600/40">
                                    Get Started
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="relative pt-32 pb-20 lg:pt-40 lg:pb-28 overflow-hidden">
        <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 rounded-full bg-primary-100 blur-3xl opacity-50"></div>
        <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-72 h-72 rounded-full bg-purple-100 blur-3xl opacity-50"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-3xl mx-auto">
                <div class="inline-flex items-center px-3 py-1 rounded-full bg-primary-50 text-primary-700 text-sm font-medium mb-6 border border-primary-100">
                    <span class="flex h-2 w-2 rounded-full bg-primary-600 mr-2 animate-pulse"></span>
                    v2.0 is now live
                </div>
                <h1 class="text-5xl md:text-6xl font-extrabold tracking-tight text-gray-900 mb-8 leading-tight">
                    Manage your business with <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-purple-600">Precision & Style</span>
                </h1>
                <p class="text-xl text-gray-500 mb-10 leading-relaxed">
                    The ultimate Point of Sale solution for modern retail and hospitality. Track inventory, manage sales, and analyze growth—all in one beautiful interface.
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-8 py-4 text-base font-bold text-white transition-all duration-200 bg-primary-600 border border-transparent rounded-2xl hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-600 shadow-xl shadow-primary-600/30 hover:scale-105">
                        <i class="fas fa-rocket mr-2"></i> Start Free Trial
                    </a>
                    <a href="#features" class="inline-flex items-center justify-center px-8 py-4 text-base font-bold text-gray-700 transition-all duration-200 bg-white border border-gray-200 rounded-2xl hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 shadow-sm hover:shadow-md">
                        <i class="fas fa-play-circle mr-2 text-primary-600"></i> Watch Demo
                    </a>
                </div>
            </div>
            
            <!-- Hero Image / Dashboard Preview -->
            <div class="mt-16 relative">
                <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent z-10 h-20 bottom-0"></div>
                <div class="rounded-3xl bg-gray-900 p-2 shadow-2xl ring-1 ring-gray-900/10">
                    <div class="rounded-2xl overflow-hidden bg-gray-800 aspect-[16/9] relative group">
                        <!-- Placeholder for dashboard image, using a gradient for now -->
                        <div class="absolute inset-0 bg-gradient-to-br from-gray-800 to-gray-900 flex items-center justify-center">
                            <div class="text-center">
                                <i class="fas fa-chart-pie text-6xl text-gray-700 mb-4 group-hover:text-primary-500 transition-colors duration-500"></i>
                                <p class="text-gray-500 font-medium">Dashboard Preview</p>
                            </div>
                        </div>
                        <!-- UI Elements Mockup -->
                        <div class="absolute top-0 left-0 right-0 h-12 bg-gray-800 border-b border-gray-700 flex items-center px-4 gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div id="features" class="py-24 bg-gray-50 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-base font-bold text-primary-600 uppercase tracking-wide">Powerful Features</h2>
                <p class="mt-2 text-3xl font-extrabold text-gray-900 sm:text-4xl">Everything you need to run your store</p>
                <p class="mt-4 text-lg text-gray-500">Streamline operations and boost productivity with our comprehensive suite of tools.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white rounded-3xl p-8 shadow-lg shadow-gray-200/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border border-gray-100">
                    <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 mb-6">
                        <i class="fas fa-desktop text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Visual POS</h3>
                    <p class="text-gray-500 leading-relaxed">
                        Intuitive point-of-sale interface designed for speed. Process transactions, handle returns, and manage customers effortlessly.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white rounded-3xl p-8 shadow-lg shadow-gray-200/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border border-gray-100">
                    <div class="w-14 h-14 bg-green-50 rounded-2xl flex items-center justify-center text-green-600 mb-6">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Real-time Analytics</h3>
                    <p class="text-gray-500 leading-relaxed">
                        Gain insights with dynamic dashboards. Track sales growth, top products, and financial health in real-time.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white rounded-3xl p-8 shadow-lg shadow-gray-200/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border border-gray-100">
                    <div class="w-14 h-14 bg-purple-50 rounded-2xl flex items-center justify-center text-purple-600 mb-6">
                        <i class="fas fa-boxes text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Smart Inventory</h3>
                    <p class="text-gray-500 leading-relaxed">
                        Keep track of stock levels, manage categories, and get low-stock alerts. Never run out of your best-sellers.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-white rounded-3xl p-8 shadow-lg shadow-gray-200/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border border-gray-100">
                    <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center text-orange-600 mb-6">
                        <i class="fas fa-file-invoice-dollar text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Financial Reports</h3>
                    <p class="text-gray-500 leading-relaxed">
                        Generate professional Profit & Loss, Balance Sheet, and Cash Flow statements. Export to PDF or Excel.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-white rounded-3xl p-8 shadow-lg shadow-gray-200/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border border-gray-100">
                    <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 mb-6">
                        <i class="fas fa-building text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Multi-Branch</h3>
                    <p class="text-gray-500 leading-relaxed">
                        Scale your business with ease. Manage multiple branches, warehouses, and staff from a single account.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-white rounded-3xl p-8 shadow-lg shadow-gray-200/50 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border border-gray-100">
                    <div class="w-14 h-14 bg-pink-50 rounded-2xl flex items-center justify-center text-pink-600 mb-6">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Team Management</h3>
                    <p class="text-gray-500 leading-relaxed">
                        Control access with role-based permissions (Admin, Manager, Cashier). Track employee performance.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div class="relative py-24 bg-gray-900 overflow-hidden">
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-r from-primary-900 to-purple-900 opacity-90"></div>
            <!-- Pattern -->
            <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 30px 30px;"></div>
        </div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-extrabold text-white sm:text-4xl mb-6">
                Ready to transform your business?
            </h2>
            <p class="text-xl text-gray-300 mb-10 max-w-2xl mx-auto">
                Join thousands of businesses that trust Modern POS for their daily operations. Start your journey today.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-4 text-base font-bold text-primary-900 transition-all duration-200 bg-white border border-transparent rounded-2xl hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white shadow-lg">
                    Create Account
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-8 py-4 text-base font-bold text-white transition-all duration-200 bg-transparent border border-gray-600 rounded-2xl hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-600">
                    Login Now
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-100 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <div class="col-span-1 md:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="bg-primary-600 text-white p-1.5 rounded-lg">
                            <i class="fas fa-cash-register"></i>
                        </div>
                        <span class="text-lg font-bold text-gray-900">Modern POS</span>
                    </div>
                    <p class="text-gray-500 text-sm leading-relaxed">
                        Empowering businesses with modern tools for a digital age. Simple, powerful, and reliable.
                    </p>
                </div>
                
                <div>
                    <h4 class="font-bold text-gray-900 mb-4">Product</h4>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li><a href="#" class="hover:text-primary-600 transition-colors">Features</a></li>
                        <li><a href="#" class="hover:text-primary-600 transition-colors">Pricing</a></li>
                        <li><a href="#" class="hover:text-primary-600 transition-colors">Integrations</a></li>
                        <li><a href="#" class="hover:text-primary-600 transition-colors">Updates</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold text-gray-900 mb-4">Company</h4>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li><a href="#" class="hover:text-primary-600 transition-colors">About Us</a></li>
                        <li><a href="#" class="hover:text-primary-600 transition-colors">Careers</a></li>
                        <li><a href="#" class="hover:text-primary-600 transition-colors">Blog</a></li>
                        <li><a href="#" class="hover:text-primary-600 transition-colors">Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-bold text-gray-900 mb-4">Support</h4>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li><a href="#" class="hover:text-primary-600 transition-colors">Help Center</a></li>
                        <li><a href="#" class="hover:text-primary-600 transition-colors">Documentation</a></li>
                        <li><a href="#" class="hover:text-primary-600 transition-colors">API Status</a></li>
                        <li><a href="#" class="hover:text-primary-600 transition-colors">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-100 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-sm text-gray-400">
                    &copy; {{ date('Y') }} Modern POS. All rights reserved.
                </p>
                <div class="flex gap-4">
                    <a href="#" class="text-gray-400 hover:text-primary-600 transition-colors"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-400 hover:text-primary-600 transition-colors"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-gray-400 hover:text-primary-600 transition-colors"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-gray-400 hover:text-primary-600 transition-colors"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>