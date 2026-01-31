<?php
require_once 'AppLogic.php';
$app = new AppLogic();
$settings = $app->getSettings();
$appTitle = $settings['app_title'] ?? 'Kişisel Araç Kütüphanem';
$appIcon = $settings['app_icon'] ?? 'https://img.icons8.com/dusk/64/000000/console.png';
?>
<!DOCTYPE html>
<html lang="tr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($appTitle); ?></title>
    <link rel="icon" href="<?php echo htmlspecialchars($appIcon); ?>" type="image/x-icon" />
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            main: '#0F172A',   // Main background (Slate 900-ish)
                            sidebar: '#161E2E', // Sidebar background
                            card: '#1E293B',    // Card background (Slate 800)
                            cardHover: '#334155',
                            input: '#1E293B',
                            border: '#334155'
                        },
                        accent: {
                            blue: '#3B82F6',
                            green: '#10B981'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>
<body class="bg-dark-main text-gray-300 min-h-screen flex overflow-hidden" x-data="app()">

    <!-- Sidebar -->
    <aside class="w-64 bg-dark-main border-r border-dark-border flex-shrink-0 flex flex-col hidden md:flex">
        <!-- Logo Area -->
        <div class="h-16 flex items-center px-6 border-b border-dark-border/50">
             <template x-if="settings.logo_url">
                <img :src="settings.logo_url" alt="Logo" class="h-8 w-8 mr-3 rounded-md">
            </template>
            <span class="font-bold text-white tracking-wide" x-text="settings.app_title || 'Araç Kutusu'"></span>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-8">
            
            <!-- Main Group -->
            <div>
                 <div class="px-3 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Menü</div>
                 <a href="#" @click.prevent="switchTab('dashboard', '')" 
                   :class="{'bg-dark-card text-white': activeTab === 'dashboard' && currentCategory === '', 'text-gray-400 hover:text-gray-200': activeTab !== 'dashboard' || currentCategory !== ''}"
                   class="flex items-center px-3 py-2 rounded-lg transition-colors group">
                    <span class="w-8 flex items-center justify-center"><i class="fas fa-th-large group-hover:text-accent-blue transition-colors"></i></span>
                    <span class="font-medium">Tüm Araçlar</span>
                </a>
                <a href="#" @click.prevent="switchTab('add')" 
                   :class="{'bg-dark-card text-white': activeTab === 'add', 'text-gray-400 hover:text-gray-200': activeTab !== 'add'}"
                   class="flex items-center px-3 py-2 rounded-lg transition-colors mt-1 group">
                     <span class="w-8 flex items-center justify-center"><i class="fas fa-plus-circle group-hover:text-accent-green transition-colors"></i></span>
                    <span class="font-medium">Yeni Ekle</span>
                </a>
            </div>

            <!-- Dynamic Categories -->
            <div x-show="categories.length > 0">
                <div class="px-3 mb-2 text-xs font-semibold text-gray-500 uppercase tracking-wider flex justify-between items-center">
                    <span>Kategoriler</span>
                </div>
                <template x-for="cat in categories" :key="cat.id">
                    <div class="mb-1">
                        <!-- Main Category -->
                        <div class="flex items-center group">
                            <button @click="toggleCategory(cat.name)" class="text-gray-500 hover:text-white mr-1 p-1 transition-colors">
                                <i class="fas fa-chevron-right text-xs transition-transform" :class="{'rotate-90': openCategory === cat.name}"></i>
                            </button>
                            <a href="#" @click.prevent="switchTab('dashboard', cat.name)"
                               :class="{'text-white': currentCategory === cat.name && !currentSubCategory, 'text-gray-400 hover:text-gray-200': currentCategory !== cat.name || currentSubCategory}"
                               class="flex-1 flex items-center px-2 py-2 rounded-lg transition-colors text-sm font-medium">
                                <span x-text="cat.name"></span>
                            </a>
                        </div>
                        
                        <!-- Sub Categories -->
                        <div x-show="openCategory === cat.name" x-transition class="pl-6 space-y-0.5 mt-0.5 border-l border-dark-border ml-3">
                            <template x-for="sub in cat.subcategories" :key="sub.id">
                                <a href="#" @click.prevent="switchTab('dashboard', cat.name, sub.name)"
                                   :class="{'text-accent-blue font-medium': currentSubCategory === sub.name, 'text-gray-500 hover:text-gray-300': currentSubCategory !== sub.name}"
                                   class="block px-2 py-1.5 rounded transition-colors text-xs flex items-center">
                                    <span x-text="sub.name"></span>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </nav>

        <!-- Settings (Bottom) -->
        <div class="p-4 border-t border-dark-border/50">
            <a href="#" @click.prevent="switchTab('settings')" 
               :class="{'bg-dark-card text-white': activeTab === 'settings', 'text-gray-400 hover:text-gray-200': activeTab !== 'settings'}"
               class="flex items-center px-3 py-3 rounded-lg transition-colors group">
                <span class="w-8 flex items-center justify-center"><i class="fas fa-cog group-hover:text-yellow-500 transition-colors"></i></span>
                <span class="font-medium">Ayarlar</span>
            </a>
        </div>
    </aside>

    <!-- Main Section -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- Top Header -->
        <header class="h-16 bg-dark-main border-b border-dark-border/50 flex items-center justify-between px-8 z-20">
            <!-- Mobile Toggle -->
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-gray-400 hover:text-white mr-4">
                <i class="fas fa-bars text-xl"></i>
            </button>

            <!-- Page Title -->
            <h2 class="text-xl font-semibold text-white hidden md:block" x-text="getHeaderTitle()"></h2>

            <!-- Right Actions -->
            <div class="flex items-center space-x-4 flex-1 md:flex-none justify-end">
                <!-- Search Bar -->
                <div class="relative w-full md:w-64">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm"></i>
                    <input type="text" x-model="searchQuery" @input.debounce="filterTools()" 
                           placeholder="Araçlarda ara..." 
                           class="w-full bg-dark-input border border-dark-border text-sm rounded-lg pl-10 pr-4 py-2 text-gray-300 focus:outline-none focus:border-accent-blue focus:ring-1 focus:ring-accent-blue transition-all placeholder-gray-600">
                </div>
                
                <!-- Notification Bell (Visual) -->
                <button class="w-8 h-8 rounded-full bg-dark-card flex items-center justify-center text-gray-400 hover:text-white hover:bg-dark-border transition relative">
                    <i class="far fa-bell"></i>
                    <span class="absolute top-2 right-2.5 w-1.5 h-1.5 bg-red-500 rounded-full border border-dark-card"></span>
                </button>
                
                <!-- User Profile (Visual) -->
                 <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 p-0.5 cursor-pointer">
                    <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix" alt="User" class="rounded-full bg-dark-main">
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <main class="flex-1 overflow-y-auto p-4 md:p-8 relative">
            
            <!-- Dashboard (Cards) -->
            <div x-show="activeTab === 'dashboard'" x-transition.opacity.duration.300ms>
                
                <!-- Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <template x-for="tool in filteredTools" :key="tool.id">
                        <!-- Card -->
                        <div class="bg-dark-card rounded-xl border border-dark-border flex overflow-hidden hover:border-gray-500 transition-all duration-300 group relative min-h-[180px]">
                            
                            <!-- Edit/Delete Dropdown (Absolute Top Right) -->
                             <div class="absolute top-2 right-2 z-10 opacity-0 group-hover:opacity-100 transition-opacity" x-data="{ open: false }">
                                <button @click="open = !open" @click.away="open = false" class="text-gray-500 hover:text-white p-1 rounded hover:bg-dark-border bg-dark-card/80 backdrop-blur">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div x-show="open" class="absolute right-0 mt-2 w-32 bg-dark-main rounded-lg shadow-xl py-1 border border-dark-border" x-cloak>
                                    <button @click="editTool(tool)" class="block w-full text-left px-4 py-2 text-xs text-blue-400 hover:bg-dark-card border-b border-dark-border">
                                        <i class="fas fa-edit mr-2"></i> Düzenle
                                    </button>
                                    <button @click="deleteTool(tool.id)" class="block w-full text-left px-4 py-2 text-xs text-red-400 hover:bg-dark-card">
                                        <i class="fas fa-trash-alt mr-2"></i> Sil
                                    </button>
                                </div>
                            </div>

                            <!-- Left Column: Icon & Stats -->
                            <div class="w-32 bg-dark-main/30 flex flex-col items-center justify-center p-4 border-r border-dark-border flex-shrink-0 text-center">
                                <!-- Big Circle Icon -->
                                <div class="w-20 h-20 rounded-full bg-white flex items-center justify-center overflow-hidden mb-3 shadow-lg shadow-black/20 p-2">
                                     <img :src="tool.icon" @error="$el.src = 'https://img.icons8.com/color/48/000000/image.png'" class="w-full h-full object-contain">
                                </div>
                                <!-- Stats Text -->
                                <div class="text-xs font-semibold text-gray-500 mt-auto">
                                    <div class="flex items-center justify-center space-x-1">
                                        <span x-text="formatNumber(tool.clicks)"></span>
                                        <span>tıkla</span>
                                    </div>
                                    <div class="flex items-center justify-center space-x-1 text-yellow-500">
                                        <span x-text="tool.rating"></span>
                                        <span>puan</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Info -->
                            <div class="flex-1 p-5 flex flex-col">
                                <a :href="tool.url" target="_blank" @click="incrementClick(tool.id)" class="hover:text-accent-blue transition-colors">
                                    <h3 class="font-bold text-white text-lg uppercase tracking-wide leading-tight mb-1" x-text="tool.title"></h3>
                                </a>
                                <p class="text-[10px] uppercase font-bold text-gray-500 tracking-wider mb-3" x-text="tool.category"></p>
                                
                                <p class="text-gray-400 text-sm leading-relaxed mb-4 line-clamp-3 text-sm" x-text="tool.description"></p>

                                <div class="mt-auto pt-2 text-xs text-gray-500 font-medium">
                                    <span x-text="(tool.tags || []).join(', ')"></span>
                                </div>
                            </div>

                        </div>
                    </template>
                    
                    <!-- New Item Placeholder -->
                    <div @click="switchTab('add')" class="bg-dark-card/50 rounded-xl border border-dashed border-dark-border flex items-center justify-center text-gray-500 hover:text-white hover:border-gray-400 hover:bg-dark-card transition cursor-pointer min-h-[180px]">
                        <div class="text-center">
                            <i class="fas fa-plus text-3xl mb-2 block"></i>
                            <span class="font-medium text-sm">Yeni Ekle</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Tool Form -->
            <div x-show="activeTab === 'add'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" x-cloak>
                <div class="max-w-3xl mx-auto">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-white">Yeni Araç Ekle</h2>
                        <p class="text-gray-400 text-sm mt-1">AI yardımıyla koleksiyonunuza yeni bir parça ekleyin.</p>
                    </div>

                    <!-- AI Input -->
                    <div class="bg-gradient-to-r from-blue-900/20 to-purple-900/20 border border-blue-500/30 rounded-xl p-6 mb-8 relative overflow-hidden">
                        <div class="relative z-10">
                            <label class="block text-sm font-medium text-blue-200 mb-2">Hızlı Ekleme (AI)</label>
                            <div class="flex gap-3">
                                <input type="text" x-model="aiQuery" @input.debounce.800ms="analyzeUrl"
                                    class="flex-1 bg-dark-main border border-dark-border rounded-lg px-4 py-3 text-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:outline-none placeholder-gray-600"
                                    placeholder="Örn: 'Vite', 'https://laravel.com'...">
                                <button @click="analyzeUrl" :disabled="loadingAI"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition flex items-center disabled:opacity-50 shadow-lg shadow-blue-500/20">
                                    <i class="fas fa-magic mr-2" :class="{'fa-spin': loadingAI}"></i>
                                    <span x-text="loadingAI ? 'Analiz...' : 'Otomatik Doldur'"></span>
                                </button>
                            </div>
                            <p class="text-xs text-blue-300/60 mt-2"><i class="fas fa-info-circle mr-1"></i> Gemini AI (Google) kullanılarak açıklamaları, ikonu ve etiketleri otomatik çeker.</p>
                            <p x-show="aiError" class="text-red-400 text-sm mt-2" x-text="aiError"></p>
                        </div>
                    </div>

                    <!-- Manual Form -->
                    <div class="bg-dark-card rounded-xl border border-dark-border p-6 md:p-8">
                         <form @submit.prevent="addTool" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1.5 uppercase tracking-wide">Başlık</label>
                                    <input type="text" x-model="newTool.title" required class="w-full bg-dark-main border border-dark-border rounded-lg px-4 py-2.5 text-white focus:border-blue-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1.5 uppercase tracking-wide">Kategori</label>
                                    <div class="space-y-3">
                                        <!-- Main Category -->
                                        <div class="flex gap-2">
                                            <select x-model="newTool.category" class="flex-1 bg-dark-main border border-dark-border rounded-lg px-4 py-2.5 text-white focus:border-blue-500 focus:outline-none appearance-none">
                                                <option value="" disabled>Ana Kategori Seç</option>
                                                <template x-for="cat in categories" :key="cat.id">
                                                    <option :value="cat.name" x-text="cat.name"></option>
                                                </template>
                                            </select>
                                            <button type="button" @click="promptAddCategory" class="px-3 bg-dark-sidebar border border-dark-border rounded-lg text-gray-400 hover:text-white hover:border-gray-500 transition" title="Yeni Kategori Ekle">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>

                                        <!-- Sub Category -->
                                        <div class="flex gap-2">
                                            <select x-model="newTool.subcategory" :disabled="!newTool.category" class="flex-1 bg-dark-main border border-dark-border rounded-lg px-4 py-2.5 text-white focus:border-blue-500 focus:outline-none appearance-none disabled:opacity-50">
                                                <option value="">Alt Kategori Seç (Opsiyonel)</option>
                                                <template x-for="sub in subcategoriesForSelected" :key="sub.id">
                                                    <option :value="sub.name" x-text="sub.name"></option>
                                                </template>
                                            </select>
                                            <button type="button" @click="promptAddSubCategory(categories.find(c => c.name === newTool.category))" :disabled="!newTool.category" class="px-3 bg-dark-sidebar border border-dark-border rounded-lg text-gray-400 hover:text-white hover:border-gray-500 transition disabled:opacity-50" title="Yeni Alt Kategori Ekle">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5 uppercase tracking-wide">URL Adresi</label>
                                <div class="flex">
                                    <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-dark-border bg-dark-sidebar text-gray-500 text-sm">https://</span>
                                    <input type="text" x-model="newTool.url" class="flex-1 min-w-0 block w-full bg-dark-main border border-dark-border rounded-r-lg px-4 py-2.5 text-blue-400 focus:border-blue-500 focus:outline-none">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-400 mb-1.5 uppercase tracking-wide">Açıklama</label>
                                <textarea x-model="newTool.description" rows="3" class="w-full bg-dark-main border border-dark-border rounded-lg px-4 py-2.5 text-gray-300 focus:border-blue-500 focus:outline-none"></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-medium text-gray-400 mb-1.5 uppercase tracking-wide">İkon URL</label>
                                    <div class="flex items-center gap-3">
                                         <div class="w-10 h-10 rounded bg-dark-main border border-dark-border flex items-center justify-center flex-shrink-0">
                                            <img :src="newTool.icon || 'https://img.icons8.com/color/48/000000/image.png'" class="w-8 h-8 object-contain">
                                         </div>
                                         <input type="text" x-model="newTool.icon" class="flex-1 bg-dark-main border border-dark-border rounded-lg px-4 py-2.5 text-gray-400 focus:border-blue-500 focus:outline-none text-sm">
                                    </div>
                                </div>
                                 <div class="flex gap-4">
                                     <div class="flex-1">
                                        <label class="block text-xs font-medium text-gray-400 mb-1.5 uppercase tracking-wide">Puan</label>
                                        <input type="number" step="0.1" max="5" x-model="newTool.rating" class="w-full bg-dark-main border border-dark-border rounded-lg px-4 py-2.5 text-white focus:border-blue-500 focus:outline-none">
                                     </div>
                                </div>
                            </div>
                            
                            <div>
                                 <label class="block text-xs font-medium text-gray-400 mb-1.5 uppercase tracking-wide">Etiketler</label>
                                 <input type="text" x-model="tempTags" @input="updateTags" placeholder="Örn: Ücretsiz, Open Source, Frontend" class="w-full bg-dark-main border border-dark-border rounded-lg px-4 py-2.5 text-white focus:border-blue-500 focus:outline-none">
                                 <div class="flex flex-wrap gap-2 mt-3">
                                     <template x-for="tag in newTool.tags" :key="tag">
                                        <span class="text-xs bg-dark-sidebar border border-dark-border text-gray-300 px-2 py-1 rounded inline-flex items-center">
                                            <span x-text="tag"></span>
                                            <button type="button" @click="removeTag(tag)" class="ml-1.5 text-gray-500 hover:text-red-400"><i class="fas fa-times"></i></button>
                                        </span>
                                     </template>
                                 </div>
                            </div>

                            <div class="pt-6 border-t border-dark-border flex justify-end gap-3">
                                <button type="button" @click="resetForm" class="px-4 py-2 text-sm text-gray-400 hover:text-white transition">Temizle</button>
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg shadow-lg shadow-blue-900/20 transition font-medium flex items-center">
                                    <i class="fas fa-save mr-2"></i> Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Settings Tab -->
            <div x-show="activeTab === 'settings'" x-transition x-cloak>
            <!-- Settings Tab -->
            <div x-show="activeTab === 'settings'" x-transition x-cloak>
                 <div class="max-w-4xl mx-auto" x-data="{ settingsTab: 'general' }">
                     
                     <!-- Settings Navigation -->
                     <div class="flex space-x-1 bg-dark-card p-1 rounded-xl mb-6 border border-dark-border inline-flex w-full md:w-auto">
                         <button @click="settingsTab = 'general'" :class="{'bg-dark-sidebar text-white shadow': settingsTab === 'general', 'text-gray-400 hover:text-gray-200': settingsTab !== 'general'}" class="flex-1 md:flex-none px-6 py-2.5 rounded-lg text-sm font-medium transition-all">Genel</button>
                         <button @click="settingsTab = 'categories'" :class="{'bg-dark-sidebar text-white shadow': settingsTab === 'categories', 'text-gray-400 hover:text-gray-200': settingsTab !== 'categories'}" class="flex-1 md:flex-none px-6 py-2.5 rounded-lg text-sm font-medium transition-all">Kategoriler</button>
                         <button @click="settingsTab = 'updates'" :class="{'bg-dark-sidebar text-white shadow': settingsTab === 'updates', 'text-gray-400 hover:text-gray-200': settingsTab !== 'updates'}" class="flex-1 md:flex-none px-6 py-2.5 rounded-lg text-sm font-medium transition-all">Güncelleme</button>
                     </div>

                     <!-- App Settings -->
                     <div x-show="settingsTab === 'general'" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="bg-dark-card p-8 rounded-xl border border-dark-border">
                        <h2 class="text-2xl font-bold text-white mb-6">Genel Ayarlar</h2>
                        
                        <form @submit.prevent="saveSettings" class="space-y-6">
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Uygulama Başlığı</label>
                                <input type="text" x-model="settings.app_title" class="w-full bg-dark-main border border-dark-border rounded-lg px-4 py-2.5 text-white focus:border-blue-500 focus:outline-none">
                            </div>

                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Logo URL (Başlık Yanı)</label>
                                <input type="text" x-model="settings.logo_url" class="w-full bg-dark-main border border-dark-border rounded-lg px-4 py-2.5 text-white focus:border-blue-500 focus:outline-none">
                            </div>

                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Uygulama Simgesi URL (Favicon)</label>
                                <div class="flex gap-2">
                                     <input type="text" x-model="settings.app_icon" placeholder="https://..." class="flex-1 bg-dark-main border border-dark-border rounded-lg px-4 py-2.5 text-white focus:border-blue-500 focus:outline-none">
                                     <div class="w-10 h-10 bg-dark-main rounded-lg border border-dark-border flex items-center justify-center">
                                         <img :src="settings.app_icon" x-show="settings.app_icon" class="w-6 h-6 object-contain" onerror="this.style.display='none'">
                                         <i class="fas fa-image text-gray-600" x-show="!settings.app_icon"></i>
                                     </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Tarayıcı sekmesinde görünecek simge.</p>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-400 mb-1">GitHub Proje Sayfası (Güncellemeler İçin)</label>
                                <input type="text" x-model="settings.github_repo_url" placeholder="https://github.com/kullanici/repo" class="w-full bg-dark-main border border-dark-border rounded-lg px-4 py-2.5 text-white focus:border-blue-500 focus:outline-none placeholder-gray-600">
                                <p class="text-xs text-gray-500 mt-1">Örn: https://github.com/Antigravity/Araclarim - Bu adresten güncellemeler çekilir.</p>
                            </div>

                            <div class="p-5 rounded-lg bg-dark-main border border-yellow-900/30">
                                <label class="block text-sm font-medium text-yellow-500 mb-1"><i class="fas fa-key mr-1"></i> Google Gemini API Key (Ücretsiz)</label>
                                <p class="text-xs text-gray-500 mb-3">
                                    Akıllı ekleme özelliği için gereklidir.
                                    <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-blue-400 hover:underline">Anahtar Al</a>
                                </p>
                                <input type="password" x-model="settings.openai_api_key" placeholder="AIzSy..." class="w-full bg-dark-card border border-dark-border rounded-lg px-4 py-2.5 text-white focus:border-yellow-500 focus:outline-none font-mono">
                            </div>

                            <div class="flex justify-end pt-4">
                                 <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg transition shadow-lg">
                                    <i class="fas fa-save mr-2"></i> Ayarları Kaydet
                                </button>
                            </div>
                            
                            <div x-show="settingsMessage" x-transition class="bg-green-900/20 border border-green-900 text-green-400 px-4 py-3 rounded-lg text-sm flex items-center mt-4">
                                <i class="fas fa-check-circle mr-2"></i> <span x-text="settingsMessage"></span>
                            </div>
                        </form>
                     </div>
                     
                     <!-- Category Manager -->
                     <div x-show="settingsTab === 'categories'" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="bg-dark-card p-8 rounded-xl border border-dark-border">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold text-white">Kategori Yönetimi</h2>
                            <button @click="promptAddCategory" class="text-sm bg-dark-sidebar border border-dark-border hover:bg-dark-main px-3 py-1.5 rounded text-white transition">
                                <i class="fas fa-plus mr-1"></i> Yeni Kategori
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <template x-for="(cat, index) in categories" :key="cat.id">
                                <div class="bg-dark-main border border-dark-border rounded-lg p-4 group">
                                    <!-- Main Cat Header -->
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-3 flex-1">
                                            <div class="flex flex-col gap-1">
                                                <button @click="moveCategory(index, -1)" :disabled="index === 0" class="text-gray-600 hover:text-white disabled:opacity-20"><i class="fas fa-chevron-up"></i></button>
                                                <button @click="moveCategory(index, 1)" :disabled="index === categories.length - 1" class="text-gray-600 hover:text-white disabled:opacity-20"><i class="fas fa-chevron-down"></i></button>
                                            </div>
                                            <input type="text" x-model="cat.name" @change="saveCategories" class="bg-transparent border-b border-transparent focus:border-blue-500 text-lg font-bold text-white focus:outline-none w-full">
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button @click="promptAddSubCategory(cat)" class="text-xs bg-dark-card hover:bg-blue-900/30 text-blue-400 px-2 py-1 rounded border border-dark-border">
                                                <i class="fas fa-plus mr-1"></i> Alt Kategori
                                            </button>
                                            <button @click="deleteCategory(index)" class="text-gray-600 hover:text-red-400 p-2"><i class="fas fa-trash-alt"></i></button>
                                        </div>
                                    </div>
                                    
                                    <!-- Sub Cats -->
                                    <div class="pl-8 space-y-2 border-l-2 border-dark-border/30 ml-2">
                                        <template x-for="(sub, subIndex) in cat.subcategories" :key="sub.id">
                                            <div class="flex items-center gap-2">
                                                <input type="text" x-model="sub.name" @change="saveCategories" class="bg-transparent border-b border-transparent focus:border-blue-500 text-sm text-gray-400 focus:text-white focus:outline-none flex-1">
                                                <button @click="deleteSubCategory(cat, subIndex)" class="text-gray-700 hover:text-red-400 text-xs"><i class="fas fa-times"></i></button>
                                            </div>
                                        </template>
                                        <div x-show="cat.subcategories.length === 0" class="text-xs text-gray-600 italic">Alt kategori yok</div>
                                    </div>
                                </div>
                            </template>
                        </div>
                     </div>
                     
                     <!-- Update System -->
                     <div x-show="settingsTab === 'updates'" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="bg-dark-card p-8 rounded-xl border border-dark-border">
                         <h2 class="text-2xl font-bold text-white mb-6">Güncelleme Merkezi</h2>
                         <div class="flex items-center justify-between bg-dark-main p-4 rounded-lg border border-dark-border">
                             <div>
                                 <p class="text-gray-400 text-sm">Mevcut Versiyon</p>
                                 <p class="text-xl font-mono text-white" x-text="systemInfo.version || 'Bilinmiyor'"></p>
                             </div>
                             <div>
                                 <button @click="checkUpdate" :disabled="checkingUpdate" class="bg-dark-sidebar hover:bg-dark-card border border-dark-border text-white px-4 py-2 rounded-lg transition text-sm flex items-center">
                                     <i class="fas fa-sync-alt mr-2" :class="{'fa-spin': checkingUpdate}"></i> Güncellemeleri Kontrol Et
                                 </button>
                             </div>
                         </div>
                         
                         <div x-show="updateStatus" class="mt-4 p-4 rounded-lg border" :class="updateStatus.has_update ? 'bg-blue-900/20 border-blue-800' : 'bg-green-900/20 border-green-800'">
                             <p x-text="updateStatus.message" class="text-sm" :class="updateStatus.has_update ? 'text-blue-300' : 'text-green-300'"></p>
                             <div x-show="updateStatus.has_update" class="mt-3">
                                 <button @click="performUpdate" :disabled="updating" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm font-medium transition">
                                     <i class="fas fa-download mr-2"></i> Şimdi Güncelle <span x-show="updating">(İşleniyor...)</span>
                                 </button>
                             </div>
                         </div>
                     </div>
                 </div>
            </div>

        </main>
        
        <!-- Mobile Menu Overlay -->
        <div x-show="mobileMenuOpen" @click="mobileMenuOpen = false" x-transition.opacity class="fixed inset-0 z-40 bg-black/80 md:hidden backdrop-blur-sm" x-cloak></div>
        <div x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="fixed inset-y-0 left-0 z-50 w-64 bg-dark-sidebar border-r border-dark-border md:hidden flex flex-col" x-cloak>
            <div class="h-16 flex items-center px-6 border-b border-dark-border/50 justify-between">
                <span class="font-bold text-white">Menü</span>
                <button @click="mobileMenuOpen = false" class="text-gray-400"><i class="fas fa-times"></i></button>
            </div>
             <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <a href="#" @click.prevent="switchTab('dashboard', ''); mobileMenuOpen = false" class="block px-3 py-2 rounded text-gray-300 hover:bg-dark-card hover:text-white">Tüm Araçlar</a>
                <a href="#" @click.prevent="switchTab('add'); mobileMenuOpen = false" class="block px-3 py-2 rounded text-gray-300 hover:bg-dark-card hover:text-white">Yeni Ekle</a>
                <div class="border-t border-dark-border my-2"></div>
                <template x-for="cat in categories" :key="cat.id">
                    <div>
                        <a href="#" @click.prevent="switchTab('dashboard', cat.name); mobileMenuOpen = false" class="block px-3 py-2 rounded text-gray-400 hover:bg-dark-card hover:text-white text-sm font-medium" x-text="cat.name"></a>
                        <!-- Optional: Quick links to subcategories on mobile could be added here -->
                    </div>
                </template>
                <div class="border-t border-dark-border my-2"></div>
                <a href="#" @click.prevent="switchTab('settings'); mobileMenuOpen = false" class="block px-3 py-2 rounded text-gray-300 hover:bg-dark-card hover:text-white">Ayarlar</a>
            </nav>
        </div>
    </div>

    <script>
        function app() {
            return {
                activeTab: 'dashboard',
                mobileMenuOpen: false,
                tools: [],
                filteredTools: [],
                categories: [],
                settings: {},
                currentCategory: '',
                currentSubCategory: '',
                openCategory: '', // For accordion state
                searchQuery: '',
                
                // Add Tool Form
                aiQuery: '',
                loadingAI: false,
                aiError: '',
                tempTags: '',
                newTool: {
                    title: '', description: '', url: '', category: 'Geliştirme', subcategory: '', tags: [], icon: '', rating: 4.8
                },
                
                settingsMessage: '',
                
                // System & Updates
                systemInfo: {},
                checkingUpdate: false,
                updateStatus: null,
                updating: false,

                init() {
                    this.fetchSettings();
                    this.fetchTools();
                    this.fetchCategories();
                    this.fetchSystemInfo();
                },
                
                switchTab(tab, category = null, subcategory = null) {
                    this.activeTab = tab;
                    // If clicking Main Category, clear subcategory
                    if (category !== null) {
                         this.currentCategory = category;
                         this.currentSubCategory = subcategory; // Valid or null
                         // Auto open accordion for this category
                         this.openCategory = category;
                    }
                    this.filterTools();
                    
                    if (window.innerWidth < 768) this.mobileMenuOpen = false;
                },
                
                toggleCategory(name) {
                    if (this.openCategory === name) {
                        this.openCategory = ''; // Close if same
                    } else {
                        this.openCategory = name; // Open new (others close auto)
                    }
                },
                
                getHeaderTitle() {
                    if (this.activeTab === 'add') return 'Araç Ekle';
                    if (this.activeTab === 'settings') return 'Ayarlar';
                    if (this.currentSubCategory) return this.currentSubCategory;
                    return this.currentCategory || 'Tüm Araçlar';
                },
                
                formatNumber(num) {
                    return num > 999 ? (num/1000).toFixed(1) + 'k' : num;
                },

                async fetchSettings() {
                    try {
                        const res = await fetch('api.php?action=get_settings');
                        this.settings = await res.json();
                    } catch(e) { console.error('Settings fetch error', e); }
                },

                async fetchTools() {
                    try {
                        const res = await fetch('api.php?action=get_tools');
                        this.tools = await res.json();
                        this.filterTools();
                    } catch(e) { console.error('Tools fetch error', e); }
                },

                async fetchCategories() {
                    try {
                        const res = await fetch('api.php?action=get_categories');
                        this.categories = await res.json();
                    } catch(e) { console.error('Categories fetch error', e); }
                },

                // No longer extracting from tools

                filterTools() {
                    this.filteredTools = this.tools.filter(tool => {
                        let matchesCategory = true;
                        
                        // Strict filtering
                        if (this.currentSubCategory) {
                             matchesCategory = tool.subcategory === this.currentSubCategory;
                        } else if (this.currentCategory) {
                             matchesCategory = tool.category === this.currentCategory;
                        }

                        const searchLower = this.searchQuery.toLowerCase();
                        const matchesSearch = tool.title.toLowerCase().includes(searchLower) || 
                                            tool.description.toLowerCase().includes(searchLower) ||
                                            (tool.tags && tool.tags.some(t => t.toLowerCase().includes(searchLower)));
                        return matchesCategory && matchesSearch;
                    });
                },

                // ... keep analyzeUrl ...
                async analyzeUrl() {
                    if (!this.aiQuery) return;
                    this.loadingAI = true;
                    this.aiError = '';
                    
                    try {
                        const res = await fetch('api.php?action=analyze_url', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({ query: this.aiQuery })
                        });
                        
                        const data = await res.json();
                        if (data.error) throw new Error(data.error);
                        
                        this.newTool = {
                            ...this.newTool,
                            ...data,
                            rating: data.rating_prediction || 4.5
                        };
                        
                        if(data.suggested_tags) this.newTool.tags = data.suggested_tags;
                        this.tempTags = this.newTool.tags.join(', ');

                    } catch (e) {
                        this.aiError = 'Hata: ' + e.message;
                    } finally {
                        this.loadingAI = false;
                    }
                },

                updateTags() {
                    this.newTool.tags = this.tempTags.split(',').map(t => t.trim()).filter(t => t);
                },
                
                removeTag(tagToRemove) {
                    this.newTool.tags = this.newTool.tags.filter(t => t !== tagToRemove);
                    this.tempTags = this.newTool.tags.join(', ');
                },
                
                // Helper for Add Tool Form
                get subcategoriesForSelected() {
                     const cat = this.categories.find(c => c.name === this.newTool.category);
                     return cat ? cat.subcategories : [];
                },

                async addTool() {
                    if (!this.newTool.url.startsWith('http')) {
                            this.newTool.url = 'https://' + this.newTool.url;
                     }

                    const action = this.newTool.id ? 'update_tool' : 'add_tool';
                    
                    const res = await fetch(`api.php?action=${action}`, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(this.newTool)
                    });
                    
                    if (res.ok) {
                        await this.fetchTools();
                        this.switchTab('dashboard', '');
                        this.resetForm();
                    }
                },
                
                editTool(tool) {
                    this.newTool = JSON.parse(JSON.stringify(tool)); // Deep copy
                    this.tempTags = (this.newTool.tags || []).join(', ');
                    this.switchTab('add');
                },

                // Category Management
                
                async saveCategories() {
                    // Sync current categories state to backend
                    await fetch('api.php?action=update_categories', {
                        method: 'POST',
                        body: JSON.stringify(this.categories)
                    });
                },
                
                moveCategory(index, direction) {
                    if (direction === -1 && index > 0) {
                        [this.categories[index], this.categories[index - 1]] = [this.categories[index - 1], this.categories[index]];
                    } else if (direction === 1 && index < this.categories.length - 1) {
                         [this.categories[index], this.categories[index + 1]] = [this.categories[index + 1], this.categories[index]];
                    }
                    this.saveCategories();
                },
                
                async deleteCategory(index) {
                    if(!confirm('Bu kategoriyi ve alt kategorilerini silmek istediğinize emin misiniz? (İçindeki araçlar silinmez, sadece kategorisiz kalır)')) return;
                    this.categories.splice(index, 1);
                    await this.saveCategories();
                },
                
                async deleteSubCategory(cat, index) {
                     if(!confirm('Alt kategoriyi silmek istediğinize emin misiniz?')) return;
                     cat.subcategories.splice(index, 1);
                     await this.saveCategories();
                },

                // Quick Add Categories (Only used in Settings now)
                async promptAddCategory() {
                    const name = prompt("Yeni Ana Kategori Adı:");
                    if(name) {
                        // We add correctly formatted object
                        const newCat = {
                            id: 'cat_' + Date.now(),
                            name: name,
                            subcategories: []
                        };
                        this.categories.push(newCat);
                        await this.saveCategories();
                        
                        // If we are in 'add' tab, auto-select this new category
                        if (this.activeTab === 'add') {
                            this.newTool.category = name;
                        }
                    }
                },

                async promptAddSubCategory(cat) {
                     const name = prompt(cat.name + " altına yeni Alt Kategori:");
                     if(name) {
                        cat.subcategories.push({
                            id: 'sub_' + Date.now(),
                            name: name
                        });
                        await this.saveCategories();

                        // If we are in 'add' tab, auto-select this new subcategory
                        if (this.activeTab === 'add') {
                            this.newTool.subcategory = name;
                        }
                     }
                },
                
                // System Updates
                
                async fetchSystemInfo() {
                    const res = await fetch('api.php?action=get_system_info');
                    this.systemInfo = await res.json();
                },
                
                async checkUpdate() {
                    this.checkingUpdate = true;
                    this.updateStatus = null;
                    try {
                        const res = await fetch('api.php?action=check_update');
                        const data = await res.json();
                        
                        if(data.error) throw new Error(data.error);
                        
                        if (data.has_update) {
                            this.updateStatus = { has_update: true, message: `Yeni güncelleme mevcut! (Versiyon: ${data.remote_hash})` };
                        } else {
                            this.updateStatus = { has_update: false, message: 'Sisteminiz güncel.' };
                        }
                    } catch (e) {
                         this.updateStatus = { has_update: false, message: 'Güncelleme kontrolü başarısız: ' + e.message };
                    } finally {
                        this.checkingUpdate = false;
                    }
                },
                
                async performUpdate() {
                    if(!confirm('Güncelleme indirilecek ve uygulanacak. Onaylıyor musunuz?')) return;
                    this.updating = true;
                    try {
                        const res = await fetch('api.php?action=perform_update', { method: 'POST' });
                        const data = await res.json();
                        if (data.status === 'success') {
                            alert('Güncelleme başarılı! Sayfa yenileniyor.');
                            location.reload();
                        } else {
                            alert('Güncelleme hatası: ' + data.message);
                        }
                    } catch(e) {
                        alert('Bir hata oluştu.');
                    } finally {
                        this.updating = false;
                    }
                },


            async deleteTool(id) {
                    if(!confirm('Bu aracı silmek istediğinize emin misiniz?')) return;
                    
                    const res = await fetch('api.php?action=delete_tool', {
                        method: 'POST',
                        body: JSON.stringify({id})
                    });
                    
                    if(res.ok) {
                        this.fetchTools();
                    }
                },

                async incrementClick(id) {
                    await fetch('api.php?action=click_tool', {
                        method: 'POST',
                        body: JSON.stringify({id})
                    });
                    // Optimistic update
                    const tool = this.tools.find(t => t.id === id);
                    if(tool) tool.clicks++;
                },

                async saveSettings() {
                    const res = await fetch('api.php?action=update_settings', {
                        method: 'POST',
                        body: JSON.stringify(this.settings)
                    });
                    
                    if (res.ok) {
                        this.settingsMessage = 'Ayarlar kaydedildi!';
                        setTimeout(() => this.settingsMessage = '', 3000);
                    }
                },
                
                resetForm() {
                    this.newTool = {
                        title: '', description: '', url: '', category: 'Geliştirme', subcategory: '', tags: [], icon: '', rating: 4.8
                    };
                    this.aiQuery = '';
                    this.tempTags = '';
                    this.aiError = '';
                }
            }
        }
    </script>
</body>
</html>
