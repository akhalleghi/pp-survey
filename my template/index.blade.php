<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سامانه نظرسنجی شرکت پاریز پیشرو صنعت توسعه</title>
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">
</head>
<body class="bg-gray-50 font-vazir">
    <!-- Navigation Header -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-200 shadow-sm">
        <div class="container-max section-padding">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center space-x-4 space-x-reverse">
                    <div class="flex items-center">
                        <svg class="w-10 h-10 text-primary" viewBox="0 0 40 40" fill="currentColor">
                            <circle cx="20" cy="20" r="18" fill="#D81921"/>
                            <path d="M12 20h16M20 12v16" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="20" cy="20" r="3" fill="white"/>
                        </svg>
                        <div class="mr-3">
                            <h1 class="text-xl font-bold text-gray-900">سامانه نظرسنجی شرکت پاریز پیشرو صنعت توسعه</h1>
                            <p class="text-xs text-gray-500">سیستم مدیریت نظرسنجی</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-8 space-x-reverse">
                    <a href="#home" class="nav-link active">صفحه اصلی</a>
                    <a href="#surveys" class="nav-link">نظرسنجی‌های اخیر</a>
                    <a href="#about" class="nav-link">درباره ما</a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex items-center space-x-4 space-x-reverse">
                    <button class="md:hidden p-2 rounded-lg hover:bg-gray-100" onclick="toggleMobileMenu()">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div id="mobileMenu" class="hidden md:hidden py-4 border-t border-gray-200">
                <div class="flex flex-col space-y-3">
                    <a href="#home" class="nav-link active py-2">صفحه اصلی</a>
                    <a href="#surveys" class="nav-link py-2">نظرسنجی‌های اخیر</a>
                    <a href="#about" class="nav-link py-2">درباره ما</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="relative min-h-screen flex items-center justify-center overflow-hidden">
        <!-- Background with Gradient -->
        <div class="absolute inset-0 hero-gradient"></div>
        
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                <defs>
                    <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                        <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100" height="100" fill="url(#grid)" />
            </svg>
        </div>

        <!-- Hero Content -->
        <div class="relative z-10 container-max section-padding py-20">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left Content -->
                <div class="text-center lg:text-right">
                    <div class="slide-in-right">
                        <h1 class="text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                            سامانه نظرسنجی شرکت پاریز پیشرو صنعت توسعه
                        </h1>
                        <div class="w-24 h-1 bg-white/80 mx-auto lg:mx-0 mb-6"></div>
                    </div>
                    
                    <div class="slide-in-left">
                        <p class="text-xl lg:text-2xl text-white/90 mb-8 leading-relaxed">
                            سیستم مدیریت نظرسنجی داخلی برای پرسنل شرکت
                        </p>
                        <p class="text-lg text-white/80 mb-10 leading-relaxed">
                            صدای شما مهم است. نظرات و پیشنهادات شما در تصمیم‌گیری‌های شرکت نقش کلیدی دارد. با شرکت در نظرسنجی‌ها، به بهبود محیط کار و فرآیندهای سازمانی کمک کنید.
                        </p>
                    </div>

                    <div class="fade-in-up flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        <a href="#surveys" class="btn-primary bg-white text-primary hover:bg-gray-100 px-8 py-4 text-lg font-semibold rounded-xl shadow-lg transform hover:scale-105 transition-all duration-300">
                            <svg class="w-6 h-6 inline-block ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            شرکت در نظرسنجی‌ها
                        </a>
                    </div>
                </div>

                <!-- Right Content - Floating Cards -->
                <div class="relative">
                    <div class="grid grid-cols-2 gap-6">
                        <!-- Survey Stats Card -->
                        <div class="floating-card bg-white/95 backdrop-blur-sm rounded-2xl p-6 shadow-xl">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <span class="status-active">فعال</span>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">۱۲</h3>
                            <p class="text-gray-600">نظرسنجی فعال</p>
                        </div>

                        <!-- Participation Card -->
                        <div class="floating-card bg-white/95 backdrop-blur-sm rounded-2xl p-6 shadow-xl" style="animation-delay: 1s;">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <span class="status-badge bg-green-100 text-green-800">۸۵٪</span>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-2">۲۴۷</h3>
                            <p class="text-gray-600">مشارکت کننده</p>
                        </div>

                        <!-- Recent Survey Card -->
                        <div class="floating-card bg-white/95 backdrop-blur-sm rounded-2xl p-6 shadow-xl col-span-2" style="animation-delay: 2s;">
                            <div class="flex items-center space-x-4 space-x-reverse">
                                <img src="https://images.unsplash.com/photo-1662018105935-96c150612e13" 
                                     alt="تصویر محیط کار مدرن با کامپیوترها و فضای باز" 
                                     class="w-16 h-16 rounded-xl object-cover"
                                     onerror="this.src='https://images.pexels.com/photos/3184291/pexels-photo-3184291.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'; this.onerror=null;">
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900 mb-1">نظرسنجی رضایت از محیط کار</h4>
                                    <p class="text-sm text-gray-600">آخرین نظرسنجی منتشر شده</p>
                                    <div class="flex items-center mt-2">
                                        <span class="text-xs text-gray-500">۱۴۰۳/۰۸/۱۵</span>
                                        <span class="mx-2 text-gray-300">•</span>
                                        <span class="text-xs text-primary">۱۲۳ پاسخ</span>
                                    </div>
                                </div>
                                <div class="pulse-animation">
                                    <svg class="w-6 h-6 text-primary" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 z-10">
            <div class="flex flex-col items-center text-white/80">
                <p class="text-sm mb-2">برای ادامه اسکرول کنید</p>
                <div class="w-6 h-10 border-2 border-white/50 rounded-full flex justify-center">
                    <div class="w-1 h-3 bg-white/70 rounded-full mt-2 animate-bounce"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Surveys Section -->
    <section id="surveys" class="py-20 bg-white">
        <div class="container-max section-padding">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">نظرسنجی‌های اخیر</h2>
                <p class="text-lg text-gray-600">سه نظرسنجی جدید منتشر شده</p>
            </div>

            <div class="grid lg:grid-cols-3 gap-8">
                @if(isset($recentSurveys) && count($recentSurveys) > 0)
                    @foreach($recentSurveys->take(3) as $survey)
                        <div class="survey-card survey-card-hover">
                            <div class="relative">
                                <!-- Survey Thumbnail -->
                                <div class="survey-thumbnail h-48 rounded-xl mb-6 flex items-center justify-center relative overflow-hidden">
                                    <img src="{{ $survey->image ?? 'https://images.unsplash.com/photo-1662018105935-96c150612e13' }}" 
                                         alt="{{ $survey->title }}" 
                                         class="w-full h-full object-cover rounded-xl"
                                         onerror="this.src='https://images.pexels.com/photos/3184291/pexels-photo-3184291.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'; this.onerror=null;">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent rounded-xl"></div>
                                    <div class="absolute top-4 right-4">
                                        <span class="status-active">فعال</span>
                                    </div>
                                </div>

                                <!-- Survey Content -->
                                <div class="space-y-4">
                                    <h3 class="text-xl font-bold text-gray-900 leading-tight">{{ $survey->title }}</h3>
                                    
                                    <p class="text-gray-600 leading-relaxed">
                                        {{ $survey->description ?? 'نظرات شما در بهبود سیستم‌های سازمانی نقش کلیدی دارد.' }}
                                    </p>

                                    <!-- Survey Stats -->
                                    <div class="flex items-center justify-between text-sm">
                                        <div class="flex items-center space-x-4 space-x-reverse">
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 text-gray-400 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span class="text-gray-600">{{ $survey->duration ?? '۵' }} دقیقه</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="text-primary font-semibold">{{ $survey->responses_count ?? 0 }} مشارکت</span>
                                        </div>
                                    </div>

                                    <!-- Action Button -->
                                    <a href="{{ $survey->link ?? '#' }}" class="btn-primary w-full py-4 text-lg font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 block text-center">
                                        <svg class="w-6 h-6 inline-block ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                        شرکت در نظرسنجی
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <!-- Default Surveys if no data -->
                    <!-- Survey 1 -->
                    <div class="survey-card survey-card-hover">
                        <div class="relative">
                            <div class="survey-thumbnail h-48 rounded-xl mb-6 flex items-center justify-center relative overflow-hidden">
                                <img src="https://images.unsplash.com/photo-1609959914470-d50dd6e5850d" 
                                     alt="نظرسنجی رضایت از محیط کار" 
                                     class="w-full h-full object-cover rounded-xl"
                                     onerror="this.src='https://images.pexels.com/photos/3184291/pexels-photo-3184291.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'; this.onerror=null;">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent rounded-xl"></div>
                                <div class="absolute top-4 right-4">
                                    <span class="status-badge bg-red-100 text-red-800 font-semibold">فوری</span>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <h3 class="text-xl font-bold text-gray-900 leading-tight">نظرسنجی رضایت از محیط کار</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    نظرات شما درباره کیفیت محیط کار، امکانات، و شرایط کاری برای بهبود فضای سازمانی ضروری است.
                                </p>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center space-x-4 space-x-reverse">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-gray-400 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="text-gray-600">۵ دقیقه</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-primary font-semibold">۱۲۳ مشارکت</span>
                                    </div>
                                </div>
                                <a href="#" class="btn-primary w-full py-4 text-lg font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 block text-center">
                                    <svg class="w-6 h-6 inline-block ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    شرکت در نظرسنجی
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Survey 2 -->
                    <div class="survey-card survey-card-hover">
                        <div class="relative">
                            <div class="survey-thumbnail h-48 rounded-xl mb-6 flex items-center justify-center relative overflow-hidden">
                                <img src="https://images.unsplash.com/photo-1690191793785-2607c27d5c20" 
                                     alt="ارزیابی برنامه‌های آموزشی" 
                                     class="w-full h-full object-cover rounded-xl"
                                     onerror="this.src='https://images.pexels.com/photos/3184291/pexels-photo-3184291.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'; this.onerror=null;">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent rounded-xl"></div>
                                <div class="absolute top-4 right-4">
                                    <span class="status-active">فعال</span>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <h3 class="text-xl font-bold text-gray-900 leading-tight">ارزیابی برنامه‌های آموزشی</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    نظرات شما درباره کیفیت و مؤثر بودن دوره‌های آموزشی برگزار شده در شرکت.
                                </p>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center space-x-4 space-x-reverse">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-gray-400 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="text-gray-600">۸ دقیقه</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-primary font-semibold">۸۹ مشارکت</span>
                                    </div>
                                </div>
                                <a href="#" class="btn-primary w-full py-4 text-lg font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 block text-center">
                                    <svg class="w-6 h-6 inline-block ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    شرکت در نظرسنجی
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Survey 3 -->
                    <div class="survey-card survey-card-hover">
                        <div class="relative">
                            <div class="survey-thumbnail h-48 rounded-xl mb-6 flex items-center justify-center relative overflow-hidden">
                                <img src="https://images.unsplash.com/photo-1608197280556-22abc656c779" 
                                     alt="ارزیابی سیستم‌های IT" 
                                     class="w-full h-full object-cover rounded-xl"
                                     onerror="this.src='https://images.pexels.com/photos/3184291/pexels-photo-3184291.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'; this.onerror=null;">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent rounded-xl"></div>
                                <div class="absolute top-4 right-4">
                                    <span class="status-active">فعال</span>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <h3 class="text-xl font-bold text-gray-900 leading-tight">ارزیابی سیستم‌های IT</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    نظرات شما درباره کارایی سیستم‌های فناوری اطلاعات و نیازهای تکنولوژیکی شرکت.
                                </p>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center space-x-4 space-x-reverse">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-gray-400 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="text-gray-600">۱۰ دقیقه</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-primary font-semibold">۶۷ مشارکت</span>
                                    </div>
                                </div>
                                <a href="#" class="btn-primary w-full py-4 text-lg font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 block text-center">
                                    <svg class="w-6 h-6 inline-block ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    شرکت در نظرسنجی
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section id="about" class="py-16 bg-white">
        <div class="container-max section-padding">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="fade-in-up">
                    <h2 class="text-4xl font-bold text-gray-900 mb-6">تیم IT پاریز پیشرو صنعت توسعه</h2>
                    <p class="text-lg text-gray-600 mb-6 leading-relaxed">
                        تیم فناوری اطلاعات ما با بیش از ۱۰ سال تجربه در توسعه سیستم‌های سازمانی، این پلتفرم را با استفاده از جدیدترین تکنولوژی‌ها و بهترین شیوه‌های امنیتی طراحی و پیاده‌سازی کرده است.
                    </p>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-primary rounded-full ml-3"></div>
                            <span class="text-gray-700">طراحی و توسعه کامل داخلی</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-primary rounded-full ml-3"></div>
                            <span class="text-gray-700">پیاده‌سازی استانداردهای امنیتی بین‌المللی</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-primary rounded-full ml-3"></div>
                            <span class="text-gray-700">پشتیبانی و نگهداری مداوم</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-primary rounded-full ml-3"></div>
                            <span class="text-gray-700">بهینه‌سازی برای شبکه سازمانی</span>
                        </div>
                    </div>
                </div>
                
                <div class="floating-element">
                    <img src="https://images.unsplash.com/photo-1542744095-fcf48d80b0fd" 
                         alt="تیم IT در حال کار روی پروژه‌های فناوری در محیط مدرن دفتر کار" 
                         class="w-full h-96 object-cover rounded-2xl shadow-xl"
                         onerror="this.src='https://images.pexels.com/photos/3184291/pexels-photo-3184291.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'; this.onerror=null;">
                </div>
            </div>
        </div>
    </section>

    <!-- Technology Stack -->
    <section class="py-16 bg-gray-50">
        <div class="container-max section-padding">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">تکنولوژی‌های استفاده شده</h2>
                <p class="text-lg text-gray-600">مجموعه‌ای از پیشرفته‌ترین تکنولوژی‌های وب</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Frontend Technologies -->
                <div class="tech-card card fade-in-up">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-orange-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-orange-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M1.5 0h21l-1.91 21.563L11.977 24l-8.564-2.438L1.5 0zm7.031 9.75l-.232-2.718 10.059.003.23-2.622L5.412 4.41l.698 8.01h9.126l-.326 3.426-2.91.804-2.955-.81-.188-2.11H6.248l.33 4.171L12 19.351l5.379-1.443.744-8.157H8.531z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">HTML5</h3>
                        <p class="text-sm text-gray-600">ساختار معنایی و دسترسی‌پذیر</p>
                    </div>
                </div>

                <div class="tech-card card fade-in-up">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M1.5 0h21l-1.91 21.563L11.977 24l-8.564-2.438L1.5 0zm17.09 4.413L5.41 4.41l.213 2.622 10.125.002-.255 2.716h-6.64l.24 2.573h6.182l-.366 3.523-2.91.804-2.956-.81-.188-2.11h-2.61l.29 3.855L12 19.288l5.373-1.53L18.59 4.414z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">CSS3</h3>
                        <p class="text-sm text-gray-600">طراحی مدرن و ریسپانسیو</p>
                    </div>
                </div>

                <div class="tech-card card fade-in-up">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-yellow-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-yellow-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M0 0h24v24H0V0zm22.034 18.276c-.175-1.095-.888-2.015-3.003-2.873-.736-.345-1.554-.585-1.797-1.14-.091-.33-.105-.51-.046-.705.15-.646.915-.84 1.515-.66.39.12.75.42.976.9 1.034-.676 1.034-.676 1.755-1.125-.27-.42-.404-.601-.586-.78-.63-.705-1.469-1.065-2.834-1.034l-.705.089c-.676.165-1.32.525-1.71 1.005-1.14 1.291-.811 3.541.569 4.471 1.365 1.02 3.361 1.244 3.616 2.205.24 1.17-.87 1.545-1.966 1.41-.811-.18-1.26-.586-1.755-1.336l-1.83 1.051c.21.48.45.689.81 1.109 1.74 1.756 6.09 1.666 6.871-1.004.029-.09.24-.705.074-1.65l.046.067zm-8.983-7.245h-2.248c0 1.938-.009 3.864-.009 5.805 0 1.232.063 2.363-.138 2.711-.33.689-1.18.601-1.566.48-.396-.196-.597-.466-.83-.855-.063-.105-.11-.196-.127-.196l-1.825 1.125c.305.63.75 1.172 1.324 1.517.855.51 2.004.675 3.207.405.783-.226 1.458-.691 1.811-1.411.51-.93.402-2.07.397-3.346.012-2.054 0-4.109 0-6.179l.004-.056z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">JavaScript</h3>
                        <p class="text-sm text-gray-600">تعاملات پیشرفته کاربری</p>
                    </div>
                </div>

                <div class="tech-card card fade-in-up">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-cyan-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-cyan-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.001,4.8c-3.2,0-5.2,1.6-6,4.8c1.2-1.6,2.6-2.2,4.2-1.8c0.913,0.228,1.565,0.89,2.288,1.624 C13.666,10.618,15.027,12,18.001,12c3.2,0,5.2-1.6,6-4.8c-1.2,1.6-2.6,2.2-4.2,1.8c-0.913-0.228-1.565-0.89-2.288-1.624 C16.337,6.182,14.976,4.8,12.001,4.8z M6.001,12c-3.2,0-5.2,1.6-6,4.8c1.2-1.6,2.6-2.2,4.2-1.8c0.913,0.228,1.565,0.89,2.288,1.624 C7.666,17.818,9.027,19.2,12.001,19.2c3.2,0,5.2-1.6,6-4.8c-1.2,1.6-2.6,2.2-4.2,1.8c-0.913-0.228-1.565-0.89-2.288-1.624 C10.337,13.382,8.976,12,6.001,12z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Tailwind CSS</h3>
                        <p class="text-sm text-gray-600">فریمورک CSS مدرن</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container-max section-padding">
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 text-primary ml-3" viewBox="0 0 40 40" fill="currentColor">
                            <circle cx="20" cy="20" r="18" fill="#D81921"/>
                            <path d="M12 20h16M20 12v16" stroke="white" stroke-width="2" stroke-linecap="round"/>
                            <circle cx="20" cy="20" r="3" fill="white"/>
                        </svg>
                        <div>
                            <h3 class="text-lg font-bold">سامانه نظرسنجی شرکت پاریز پیشرو صنعت توسعه</h3>
                            <p class="text-sm text-gray-400">سیستم مدیریت نظرسنجی</p>
                        </div>
                    </div>
                    <p class="text-gray-400 leading-relaxed">
                        سیستم مدیریت نظرسنجی داخلی شرکت پاریز پیشرو صنعت توسعه، طراحی شده برای تقویت ارتباط و مشارکت کارکنان.
                    </p>
                </div>

                <div>
                    <h4 class="text-lg font-semibold mb-4">دسترسی سریع</h4>
                    <ul class="space-y-2">
                        <li><a href="#home" class="text-gray-400 hover:text-white transition-colors">صفحه اصلی</a></li>
                        <li><a href="#surveys" class="text-gray-400 hover:text-white transition-colors">نظرسنجی‌های اخیر</a></li>
                        <li><a href="#about" class="text-gray-400 hover:text-white transition-colors">درباره ما</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-lg font-semibold mb-4">اطلاعات تماس</h4>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-primary ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h4M9 7h6m-6 4h6m-6 4h6"></path>
                            </svg>
                            <span class="text-gray-400">شرکت پاریز پیشرو صنعت توسعه</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-primary ml-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span class="text-gray-400">it-department@pariz.com</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 text-center">
                <p class="text-gray-400">
                    © ۲۰۲۵ شرکت پاریز پیشرو صنعت توسعه. تمامی حقوق محفوظ است.
                </p>
                <p class="text-sm text-gray-500 mt-2">
                    طراحی و توسعه توسط تیم IT شرکت
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            mobileMenu.classList.toggle('hidden');
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    // Close mobile menu if open
                    const mobileMenu = document.getElementById('mobileMenu');
                    if (!mobileMenu.classList.contains('hidden')) {
                        mobileMenu.classList.add('hidden');
                    }
                }
            });
        });

        // Add scroll effect to navigation
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('nav');
            if (window.scrollY > 100) {
                nav.classList.add('bg-white/98');
                nav.classList.remove('bg-white/95');
            } else {
                nav.classList.add('bg-white/95');
                nav.classList.remove('bg-white/98');
            }
        });
    </script>
</body>
</html>
