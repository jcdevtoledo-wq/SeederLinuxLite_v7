<?php
// painel/dashboard.php - Painel Administrativo Refatorado
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) header('Location: /login');
$csrf_token = $_SESSION['csrf_token'] ?? '';
$username = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel — SeederLinux Lite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .sidebar-responsive { transition: transform 0.3s ease; }
        .sidebar-open { transform: translateX(0); }
        .sidebar-closed { transform: translateX(-100%); }
        .fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .skeleton { background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%); background-size: 200% 100%; animation: loading 1.5s infinite; }
        @keyframes loading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        .nav-item.active { @apply bg-blue-50 text-blue-600 border-l-4 border-blue-600; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex">

    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-3"></div>

    <!-- Mobile Menu Button -->
    <button id="mobile-menu-btn" class="fixed top-4 left-4 z-40 md:hidden bg-white p-2 rounded-lg shadow-md border border-gray-200">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white shadow-lg flex flex-col min-h-screen fixed top-0 left-0 z-30 md:relative md:z-0 md:translate-x-0 sidebar-responsive sidebar-closed md:sidebar-open">
        <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-700 rounded-lg flex items-center justify-center shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </div>
                <div>
                    <span class="font-bold text-gray-900 text-sm block">SeederLinux Lite</span>
                    <span class="text-xs text-gray-500">Admin Panel</span>
                </div>
            </div>
        </div>

        <nav class="flex-1 px-3 py-5 space-y-2 overflow-y-auto">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider px-3 mb-3">Menu Principal</p>
            <button onclick="showSection('dashboard')" class="nav-item active w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Dashboard</span>
            </button>
            <button onclick="showSection('organizations')" class="nav-item w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span>Organizações</span>
            </button>
            <button onclick="showSection('variables')" class="nav-item w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v10a2 2 0 002 2h5M16 6h5a2 2 0 012 2v10a2 2 0 01-2 2h-5m-4-6h8m-8-4h8"/>
                </svg>
                <span>Variáveis</span>
            </button>
            <button onclick="showSection('bundle-generator')" class="nav-item w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Gerar Bundle</span>
            </button>
            <button onclick="showSection('inventory')" class="nav-item w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10h2m-2 0a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2m3 4H9"/>
                </svg>
                <span>Inventário</span>
            </button>
            <button onclick="showSection('logs')" class="nav-item w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Logs</span>
            </button>
            <button onclick="showSection('settings')" class="nav-item w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Configurações</span>
            </button>
        </nav>

        <div class="px-4 py-4 border-t border-gray-200 bg-gray-50">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                    <?= strtoupper(substr($username, 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate"><?= htmlspecialchars($username) ?></p>
                    <p class="text-xs text-gray-500">Administrador</p>
                </div>
            </div>
            <a href="/logout" class="w-full flex items-center justify-center gap-2 text-red-600 hover:text-red-700 font-medium text-sm py-2 px-3 rounded-lg hover:bg-red-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Sair
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 md:ml-0 min-h-screen">
        <header class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 flex items-center justify-between sticky top-0 z-20 shadow-sm">
            <div>
                <h1 id="page-title" class="text-xl sm:text-2xl font-bold text-gray-900">Dashboard</h1>
                <p id="page-subtitle" class="text-xs sm:text-sm text-gray-500 mt-0.5">Visão geral do sistema</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 text-xs font-semibold px-3 py-1.5 rounded-full border border-green-200">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    Online
                </span>
            </div>
        </header>

        <!-- Dashboard Section -->
        <section id="section-dashboard" class="p-4 sm:p-6 fade-in">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Total de OMs</p>
                            <p id="stat-orgs" class="text-3xl font-bold text-blue-600 mt-2">—</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Bundles Gerados</p>
                            <p id="stat-bundles" class="text-3xl font-bold text-green-600 mt-2">—</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Máquinas Ativas</p>
                            <p id="stat-machines" class="text-3xl font-bold text-purple-600 mt-2">—</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10h2m-2 0a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2m3 4H9"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 font-medium">Atividades (24h)</p>
                            <p id="stat-activities" class="text-3xl font-bold text-orange-600 mt-2">—</p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Atividades Recentes
                </h3>
                <div id="recent-activities-table" class="text-gray-400 text-sm">Carregando...</div>
            </div>
        </section>

        <!-- Organizations Section -->
        <section id="section-organizations" class="p-4 sm:p-6 hidden fade-in">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Organizações Militares</h2>
                    <p class="text-sm text-gray-500 mt-1">Gerenciar OMs, variáveis e configurações</p>
                </div>
                <button onclick="openNewOmModal()" class="bg-blue-600 text-white font-semibold px-5 py-2.5 rounded-lg hover:bg-blue-700 transition-all flex items-center gap-2 shadow-md hover:shadow-lg w-full sm:w-auto justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nova OM
                </button>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-5 py-3 text-xs font-bold text-gray-600 uppercase">Nome</th>
                            <th class="text-left px-5 py-3 text-xs font-bold text-gray-600 uppercase">Sigla</th>
                            <th class="text-left px-5 py-3 text-xs font-bold text-gray-600 uppercase">Domínio</th>
                            <th class="text-right px-5 py-3 text-xs font-bold text-gray-600 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="orgs-table-body" class="divide-y divide-gray-100">
                        <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">Carregando...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Variables Section -->
        <section id="section-variables" class="p-4 sm:p-6 hidden fade-in">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Editar Variáveis</h2>
                <p class="text-sm text-gray-500 mt-1">Configure placeholders para cada organização</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">Selecione a Organização</label>
                <select id="var-org-select" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Carregando...</option>
                </select>
            </div>

            <div id="variables-form-container" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <p class="text-gray-400 text-sm">Selecione uma organização para editar suas variáveis</p>
            </div>
        </section>

        <!-- Bundle Generator Section -->
        <section id="section-bundle-generator" class="p-4 sm:p-6 hidden fade-in">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Gerar Bundle</h2>
                <p class="text-sm text-gray-500 mt-1">Crie um arquivo .sh personalizado para uma OM</p>
            </div>

            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <form id="bundle-form" class="space-y-5">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Organização *</label>
                                <select id="bundle-org-select" name="organization_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Selecione uma OM...</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Scripts a Incluir</label>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                        <input type="checkbox" name="scripts" value="core_domain" checked class="w-4 h-4 text-blue-600 rounded">
                                        <span class="text-sm font-medium text-gray-700">core_domain.sh</span>
                                        <span class="text-xs text-gray-500">(AD)</span>
                                    </label>
                                    <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                        <input type="checkbox" name="scripts" value="core_network" checked class="w-4 h-4 text-blue-600 rounded">
                                        <span class="text-sm font-medium text-gray-700">core_network.sh</span>
                                        <span class="text-xs text-gray-500">(Proxy/Impressão)</span>
                                    </label>
                                    <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                        <input type="checkbox" name="scripts" value="core_inventory" checked class="w-4 h-4 text-blue-600 rounded">
                                        <span class="text-sm font-medium text-gray-700">core_inventory.sh</span>
                                        <span class="text-xs text-gray-500">(OCS)</span>
                                    </label>
                                    <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                                        <input type="checkbox" name="scripts" value="core_branding" checked class="w-4 h-4 text-blue-600 rounded">
                                        <span class="text-sm font-medium text-gray-700">core_branding.sh</span>
                                        <span class="text-xs text-gray-500">(Visual)</span>
                                    </label>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-gray-200 flex gap-3">
                                <button type="button" onclick="previewBundle()" class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2.5 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Preview
                                </button>
                                <button type="submit" class="flex-1 bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 transition-all flex items-center justify-center gap-2 shadow-md">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span id="submit-btn-text">Gerar Bundle</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Info -->
                <div class="bg-blue-50 rounded-xl border border-blue-200 p-6 h-fit">
                    <h4 class="font-bold text-blue-900 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Como Funciona
                    </h4>
                    <ul class="text-sm text-blue-900 space-y-2">
                        <li class="flex gap-2">
                            <span class="font-bold">1.</span>
                            <span>Selecione a OM</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="font-bold">2.</span>
                            <span>Escolha os scripts</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="font-bold">3.</span>
                            <span>Visualize o resultado</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="font-bold">4.</span>
                            <span>Baixe o arquivo .sh</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="font-bold">5.</span>
                            <span>Execute nas estações</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Inventory Section -->
        <section id="section-inventory" class="p-4 sm:p-6 hidden fade-in">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Inventário de Máquinas</h2>
                <p class="text-sm text-gray-500 mt-1">Máquinas provisionadas e seus dados de hardware</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-5 py-3 text-xs font-bold text-gray-600 uppercase">Hostname</th>
                            <th class="text-left px-5 py-3 text-xs font-bold text-gray-600 uppercase">IP</th>
                            <th class="text-left px-5 py-3 text-xs font-bold text-gray-600 uppercase">OM</th>
                            <th class="text-left px-5 py-3 text-xs font-bold text-gray-600 uppercase">CPU</th>
                            <th class="text-left px-5 py-3 text-xs font-bold text-gray-600 uppercase">RAM</th>
                            <th class="text-left px-5 py-3 text-xs font-bold text-gray-600 uppercase">Disco</th>
                            <th class="text-left px-5 py-3 text-xs font-bold text-gray-600 uppercase">Agente</th>
                            <th class="text-left px-5 py-3 text-xs font-bold text-gray-600 uppercase">Último Check-in</th>
                        </tr>
                    </thead>
                    <tbody id="inventory-table-body" class="divide-y divide-gray-100">
                        <tr><td colspan="8" class="px-5 py-8 text-center text-gray-400">Carregando...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Logs Section -->
        <section id="section-logs" class="p-4 sm:p-6 hidden fade-in">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Logs de Atividade</h2>
                <p class="text-sm text-gray-500 mt-1">Histórico de ações do sistema</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-5 py-3 text-xs font-bold text-gray-600 uppercase">Data/Hora</th>
                            <th class="text-left px-5 py-3 text-xs font-bold text-gray-600 uppercase">Usuário</th>
                            <th class="text-left px-5 py-3 text-xs font-bold text-gray-600 uppercase">Ação</th>
                            <th class="text-left px-5 py-3 text-xs font-bold text-gray-600 uppercase">Detalhes</th>
                            <th class="text-left px-5 py-3 text-xs font-bold text-gray-600 uppercase">IP</th>
                        </tr>
                    </thead>
                    <tbody id="logs-table-body" class="divide-y divide-gray-100">
                        <tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">Carregando...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Settings Section -->
        <section id="section-settings" class="p-4 sm:p-6 hidden fade-in">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Configurações do Sistema</h2>
                <p class="text-sm text-gray-500 mt-1">Parâmetros globais</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <form id="settings-form" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <div id="settings-grid" class="space-y-5">
                        <p class="text-gray-400 text-sm">Carregando...</p>
                    </div>
                    <div class="flex justify-end pt-4 border-t border-gray-200">
                        <button type="submit" class="bg-blue-600 text-white font-semibold py-2.5 px-6 rounded-lg hover:bg-blue-700 transition-all flex items-center gap-2 shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span id="settings-submit-text">Salvar Configurações</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <!-- Modal: Nova OM -->
    <div id="modal-new-om" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-bold text-gray-900 text-lg">Nova Organização</h3>
                <button onclick="closeNewOmModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="form-new-om" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nome Completo *</label>
                    <input type="text" id="om-name" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ex: Comando Aéreo Regional">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Sigla *</label>
                    <input type="text" id="om-acronym" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ex: COMARA">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Domínio AD</label>
                    <input type="text" id="om-domain" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ex: comara.intraer">
                </div>
                <div id="om-form-error" class="hidden text-sm text-red-600 bg-red-50 px-4 py-2.5 rounded-lg border border-red-200"></div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeNewOmModal()" class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2.5 rounded-lg hover:bg-gray-50 transition-colors">Cancelar</button>
                    <button type="submit" class="flex-1 bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 transition-colors">Criar OM</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const CSRF_TOKEN = '<?= htmlspecialchars($csrf_token) ?>';

        // Toast Notification System
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            const bgColor = {
                'success': 'bg-green-50 border-green-200 text-green-800',
                'error': 'bg-red-50 border-red-200 text-red-800',
                'info': 'bg-blue-50 border-blue-200 text-blue-800',
                'warning': 'bg-yellow-50 border-yellow-200 text-yellow-800'
            }[type] || 'bg-blue-50 border-blue-200 text-blue-800';
            
            const icon = {
                'success': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
                'error': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
                'info': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                'warning': '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 0v2m0-2v-2m0 0V9m0 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
            }[type] || '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

            toast.className = `border rounded-lg p-4 flex items-center gap-3 shadow-lg animate-fade-in ${bgColor}`;
            toast.innerHTML = `${icon}<span class="text-sm font-medium">${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }

        // Mobile Menu
        document.getElementById('mobile-menu-btn').addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('sidebar-closed');
            sidebar.classList.toggle('sidebar-open');
        });

        // Section Navigation
        function showSection(name) {
            document.querySelectorAll('section[id^="section-"]').forEach(s => s.classList.add('hidden'));
            document.getElementById('section-' + name).classList.remove('hidden');
            document.querySelectorAll('.nav-item').forEach(a => a.classList.remove('active'));
            event.target.closest('.nav-item').classList.add('active');

            const titles = {
                dashboard: ['Dashboard', 'Visão geral do sistema'],
                organizations: ['Organizações (OMs)', 'Gerenciar organizações militares'],
                variables: ['Editar Variáveis', 'Configure placeholders por OM'],
                'bundle-generator': ['Gerar Bundle', 'Crie arquivos .sh personalizados'],
                inventory: ['Inventário de Máquinas', 'Máquinas provisionadas'],
                logs: ['Logs de Atividade', 'Histórico de ações'],
                settings: ['Configurações', 'Parâmetros do sistema']
            };
            document.getElementById('page-title').textContent = titles[name][0];
            document.getElementById('page-subtitle').textContent = titles[name][1];

            if (name === 'dashboard') loadDashboard();
            else if (name === 'organizations') loadOrganizations();
            else if (name === 'variables') loadVariablesForm();
            else if (name === 'bundle-generator') loadBundleForm();
            else if (name === 'inventory') loadInventory();
            else if (name === 'logs') loadLogs();
            else if (name === 'settings') loadSettings();
        }

        async function loadDashboard() {
            const orgs = await apiFetch('/api/organizations.php');
            document.getElementById('stat-orgs').textContent = orgs.length || 0;
            
            const inventory = await apiFetch('/api/machine_inventory.php');
            document.getElementById('stat-machines').textContent = inventory.length || 0;

            const logs = await apiFetch('/api/activity_log.php?limit=10');
            document.getElementById('stat-activities').textContent = logs.length || 0;

            const tbody = document.getElementById('recent-activities-table');
            if (logs.length === 0) {
                tbody.innerHTML = '<p class="text-gray-400 text-sm">Nenhuma atividade registrada.</p>';
                return;
            }
            tbody.innerHTML = `<table class="w-full text-sm"><thead class="bg-gray-50 border-b"><tr><th class="text-left px-4 py-2 text-xs font-bold text-gray-600">Data/Hora</th><th class="text-left px-4 py-2 text-xs font-bold text-gray-600">Usuário</th><th class="text-left px-4 py-2 text-xs font-bold text-gray-600">Ação</th></tr></thead><tbody class="divide-y divide-gray-100">${logs.slice(0, 5).map(l => `<tr class="hover:bg-gray-50"><td class="px-4 py-2 text-gray-500 text-xs">${new Date(l.timestamp).toLocaleString('pt-BR')}</td><td class="px-4 py-2 font-medium text-gray-800">${escHtml(l.username || 'Sistema')}</td><td class="px-4 py-2 text-gray-700">${escHtml(l.action)}</td></tr>`).join('')}</tbody></table>`;
        }

        async function loadOrganizations() {
            const orgs = await apiFetch('/api/organizations.php');
            const tbody = document.getElementById('orgs-table-body');
            if (orgs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">Nenhuma organização cadastrada. <a href="#" onclick="openNewOmModal()" class="text-blue-600 font-semibold">Criar primeira OM</a></td></tr>';
                return;
            }
            tbody.innerHTML = orgs.map(o => `<tr class="hover:bg-gray-50 transition-colors"><td class="px-5 py-3 font-semibold text-gray-900">${escHtml(o.name)}</td><td class="px-5 py-3"><span class="bg-blue-100 text-blue-700 text-xs font-mono font-bold px-2.5 py-1 rounded">${escHtml(o.acronym)}</span></td><td class="px-5 py-3 text-gray-500 text-sm">${escHtml(o.domain || '—')}</td><td class="px-5 py-3 text-right"><button class="text-blue-600 hover:text-blue-800 text-xs font-semibold hover:underline">Gerenciar</button></td></tr>`).join('');
        }

        async function loadVariablesForm() {
            const orgs = await apiFetch('/api/organizations.php');
            const select = document.getElementById('var-org-select');
            select.innerHTML = '<option value="">Selecione uma OM...</option>' + orgs.map(o => `<option value="${o.id}">${escHtml(o.name)} (${escHtml(o.acronym)})</option>`).join('');
            select.addEventListener('change', loadVariablesForOrg);
        }

        async function loadVariablesForOrg() {
            const orgId = document.getElementById('var-org-select').value;
            if (!orgId) return;
            const variables = await apiFetch(`/api/variables.php?organization_id=${orgId}`);
            const container = document.getElementById('variables-form-container');
            if (variables.length === 0) {
                container.innerHTML = '<p class="text-gray-400 text-sm">Nenhuma variável para esta OM.</p>';
                return;
            }
            container.innerHTML = `<form id="variables-edit-form" class="space-y-4">${variables.map(v => `<div><label class="block text-sm font-semibold text-gray-700 mb-1.5">${escHtml(v.variable_key)}</label><input type="text" name="${escHtml(v.variable_key)}" value="${escHtml(v.variable_value || '')}" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"><p class="text-xs text-gray-500 mt-1">${escHtml(v.description || '')}</p></div>`).join('')}<div class="flex gap-3 pt-4 border-t border-gray-200"><button type="button" onclick="showSection('variables')" class="flex-1 border border-gray-300 text-gray-700 font-semibold py-2.5 rounded-lg hover:bg-gray-50">Cancelar</button><button type="submit" class="flex-1 bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700">Salvar Variáveis</button></div></form>`;
            document.getElementById('variables-edit-form').addEventListener('submit', saveVariables);
        }

        async function saveVariables(e) {
            e.preventDefault();
            const orgId = document.getElementById('var-org-select').value;
            const formData = new FormData(e.target);
            for (const [key, value] of formData.entries()) {
                await apiFetch('/api/variables.php', 'POST', { csrf_token: CSRF_TOKEN, organization_id: orgId, variable_key: key, variable_value: value });
            }
            showToast('Variáveis salvas com sucesso!', 'success');
            loadVariablesForOrg();
        }

        async function loadBundleForm() {
            const orgs = await apiFetch('/api/organizations.php');
            const select = document.getElementById('bundle-org-select');
            select.innerHTML = '<option value="">Selecione uma OM...</option>' + orgs.map(o => `<option value="${o.id}">${escHtml(o.name)} (${escHtml(o.acronym)})</option>`).join('');
        }

        async function previewBundle() {
            showToast('Preview em desenvolvimento...', 'info');
        }

        document.getElementById('bundle-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/></svg><span>Gerando...</span>';
            
            const formData = new FormData(e.target);
            const res = await apiFetch('/api/generate-bundle.php', 'POST', Object.fromEntries(formData));
            
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span id="submit-btn-text">Gerar Bundle</span>';
            
            if (res.error) {
                showToast(res.error, 'error');
            } else {
                showToast('Bundle gerado com sucesso!', 'success');
                window.location.href = `/api/bundle.php?id=${res.bundle_id}`;
            }
        });

        async function loadInventory() {
            const inventory = await apiFetch('/api/machine_inventory.php');
            const tbody = document.getElementById('inventory-table-body');
            if (inventory.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-5 py-8 text-center text-gray-400">Nenhuma máquina registrada.</td></tr>';
                return;
            }
            tbody.innerHTML = inventory.map(m => `<tr class="hover:bg-gray-50"><td class="px-5 py-3 font-semibold text-gray-900">${escHtml(m.hostname)}</td><td class="px-5 py-3 text-gray-500 text-sm font-mono">${escHtml(m.ip_address)}</td><td class="px-5 py-3"><span class="bg-blue-100 text-blue-700 text-xs font-mono font-bold px-2.5 py-1 rounded">${escHtml(m.organization_acronym || 'N/A')}</span></td><td class="px-5 py-3 text-gray-500 text-sm">${escHtml(m.cpu_info)}</td><td class="px-5 py-3 text-gray-500 text-sm">${m.ram_gb} GB</td><td class="px-5 py-3 text-gray-500 text-sm">${m.disk_gb} GB</td><td class="px-5 py-3 text-gray-500 text-sm">${escHtml(m.agent_version)}</td><td class="px-5 py-3 text-xs text-gray-500">${new Date(m.last_checkin).toLocaleString('pt-BR')}</td></tr>`).join('');
        }

        async function loadLogs() {
            const logs = await apiFetch('/api/activity_log.php?limit=100');
            const tbody = document.getElementById('logs-table-body');
            if (logs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">Nenhum log registrado.</td></tr>';
                return;
            }
            tbody.innerHTML = logs.map(l => `<tr class="hover:bg-gray-50"><td class="px-5 py-3 text-xs text-gray-500">${new Date(l.timestamp).toLocaleString('pt-BR')}</td><td class="px-5 py-3 font-semibold text-gray-900">${escHtml(l.username || 'Sistema')}</td><td class="px-5 py-3"><span class="bg-blue-100 text-blue-700 text-xs font-bold px-2.5 py-1 rounded">${escHtml(l.action)}</span></td><td class="px-5 py-3 text-gray-500 text-sm max-w-xs truncate" title="${escHtml(l.details || '')}">${escHtml(l.details || '—')}</td><td class="px-5 py-3 text-gray-500 text-xs font-mono">${escHtml(l.ip_address)}</td></tr>`).join('');
        }

        async function loadSettings() {
            const settings = await apiFetch('/api/settings.php');
            const grid = document.getElementById('settings-grid');
            grid.innerHTML = settings.map(s => `<div><label class="block text-sm font-semibold text-gray-700 mb-1.5">${escHtml(s.setting_key)}</label><input type="text" name="${escHtml(s.setting_key)}" value="${escHtml(s.setting_value || '')}" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"><p class="text-xs text-gray-500 mt-1">${escHtml(s.description || '')}</p></div>`).join('');
        }

        document.getElementById('form-new-om').addEventListener('submit', async (e) => {
            e.preventDefault();
            const res = await apiFetch('/api/organizations.php', 'POST', {
                csrf_token: CSRF_TOKEN,
                name: document.getElementById('om-name').value.trim(),
                acronym: document.getElementById('om-acronym').value.trim().toUpperCase(),
                domain: document.getElementById('om-domain').value.trim()
            });

            if (res.error) {
                document.getElementById('om-form-error').textContent = res.error;
                document.getElementById('om-form-error').classList.remove('hidden');
            } else {
                closeNewOmModal();
                showToast('Organização criada com sucesso!', 'success');
                loadOrganizations();
                showSection('organizations');
            }
        });

        document.getElementById('settings-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/></svg><span>Salvando...</span>';
            
            const inputs = document.querySelectorAll('#settings-grid input[name]');
            let errors = 0;
            for (const input of inputs) {
                const res = await apiFetch('/api/settings.php', 'POST', {
                    csrf_token: CSRF_TOKEN,
                    setting_key: input.name,
                    setting_value: input.value.trim()
                });
                if (res.error) errors++;
            }

            btn.disabled = false;
            btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span id="settings-submit-text">Salvar Configurações</span>';
            
            if (errors === 0) {
                showToast('Configurações salvas com sucesso!', 'success');
            } else {
                showToast('Erro ao salvar algumas configurações.', 'error');
            }
        });

        function openNewOmModal() { document.getElementById('modal-new-om').classList.remove('hidden'); }
        function closeNewOmModal() { document.getElementById('modal-new-om').classList.add('hidden'); document.getElementById('form-new-om').reset(); document.getElementById('om-form-error').classList.add('hidden'); }

        async function apiFetch(url, method = 'GET', body = null) {
            const opts = { method, headers: { 'Content-Type': 'application/json' } };
            if (body) opts.body = JSON.stringify(body);
            try {
                const res = await fetch(url, opts);
                return await res.json();
            } catch (e) {
                return { error: 'Erro de comunicação com o servidor.' };
            }
        }

        function escHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadDashboard();
        });
    </script>
</body>
</html>
