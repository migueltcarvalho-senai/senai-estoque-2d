<?php
// Página principal protegida: exige sessão válida (senão redireciona para login.php)
require_once __DIR__ . '/auth.php';
try {
    $currentUser = requirePageUser();
} catch (PDOException $e) {
    error_log('[senai-estoque] Falha de conexão ao abrir index.php: ' . $e->getMessage());
    header('Location: login.php');
    exit;
}
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
$csrfToken = csrfToken();
$clientUser = [
    'id'          => $currentUser['id'],
    'username'    => $currentUser['username'],
    'displayName' => $currentUser['displayName'],
    'role'        => $currentUser['role'],
];
$roleClass = $currentUser['role'] === ROLE_ADMIN ? 'role-admin' : 'role-operador';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <script>
        // Usuário da sessão (somente para exibição/ocultar botões — o servidor valida tudo)
        window.APP_USER = <?= json_encode($clientUser, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <title>SENAI 2D — Gestão de Estoque & Endereçamento</title>
    <!-- Bibliotecas externas com versão fixa (+ SRI onde o CDN permite) -->
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js" integrity="sha384-ieG+IKD0d/ZPXyCBTMVAbqsQdns8QGJR/e26WMw7M4fkaI/rHcS/YIoi+ah9WGge" crossorigin="anonymous"></script>
    <script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js" integrity="sha384-EnyY0/GSHQGSxSgMwaIPzSESbqoOLSexfnSMN2AP+39Ckmn92stwABZynq1JyzdT" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        @keyframes pulseHighlight {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(227, 6, 19, 0.65); }
            50% { transform: scale(1.03); box-shadow: 0 0 0 8px rgba(227, 6, 19, 0); }
        }
        .slot-highlight {
            animation: pulseHighlight 1.5s infinite;
            z-index: 20;
            outline: 3px solid #E30613;
            outline-offset: 1px;
        }

        /* Elementos exclusivos do administrador (a API também bloqueia as ações) */
        body.role-operador .admin-only { display: none !important; }
    </style>
</head>
<body class="bg-[#F4F6F9] text-slate-800 font-sans antialiased min-h-screen flex flex-col <?= $roleClass ?>">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:p-2 focus:bg-white focus:text-[#0F294A] focus:font-bold focus:rounded-md">Pular para o conteúdo</a>

    <!-- Cabeçalho Institucional Azul-Marinho & Linha Vermelha -->
    <header class="bg-[#0F294A] text-white sticky top-0 z-30 shadow-md border-b-4 border-[#E30613]">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Linha Superior: Identidade Visual SENAI & Ações Globais -->
            <div class="flex items-center justify-between py-2.5 gap-3 border-b border-white/10">
                <!-- Identidade Visual SENAI -->
                <div class="flex items-center gap-3 min-w-0">
                    <div class="shrink-0 rounded-md overflow-hidden shadow-sm border border-white/15 bg-[#FF0000]" title="SENAI">
                        <svg viewBox="0 0 380 92" class="h-9 sm:h-10 w-auto block" role="img" aria-label="Logo SENAI">
                            <rect width="380" height="92" fill="#FF0000"/>
                            <rect x="0" y="15" width="20" height="3.8" fill="#FFFFFF"/>
                            <rect x="0" y="30.5" width="20" height="3.8" fill="#FFFFFF"/>
                            <rect x="0" y="46" width="20" height="3.8" fill="#FFFFFF"/>
                            <rect x="0" y="61.5" width="20" height="3.8" fill="#FFFFFF"/>
                            <rect x="0" y="77" width="20" height="3.8" fill="#FFFFFF"/>
                            <rect x="360" y="15" width="20" height="3.8" fill="#FFFFFF"/>
                            <rect x="360" y="30.5" width="20" height="3.8" fill="#FFFFFF"/>
                            <rect x="360" y="46" width="20" height="3.8" fill="#FFFFFF"/>
                            <rect x="360" y="61.5" width="20" height="3.8" fill="#FFFFFF"/>
                            <rect x="360" y="77" width="20" height="3.8" fill="#FFFFFF"/>
                            <g transform="translate(190, 48) skewX(-12) scale(1.06, 1)">
                                <text x="0" y="27" text-anchor="middle" fill="#FFFFFF" font-family="'Arial Black', 'Inter', Impact, sans-serif" font-weight="900" font-size="78" letter-spacing="2">SENAI</text>
                            </g>
                        </svg>
                    </div>
                    <div class="border-l border-white/20 pl-3 min-w-0">
                        <div class="flex items-center gap-2">
                            <h1 class="text-sm sm:text-base font-extrabold text-white tracking-tight truncate">Controle de Estoque e Validades</h1>
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-white/10 text-sky-200 border border-white/15 shrink-0">2D</span>
                        </div>
                        <p class="hidden md:block text-[11px] text-slate-300 truncate">Gestão de Insumos, Endereçamento Virtual & Monitoramento de Lotes</p>
                    </div>
                </div>

                <!-- Ações Globais -->
                <div class="flex items-center gap-2 shrink-0">
                    <button onclick="openImportSpreadsheetModal()" class="hidden md:inline-flex bg-[#183B66] hover:bg-[#1F497D] text-white border border-white/15 text-xs font-semibold px-3 py-2 rounded-lg transition-colors items-center gap-1.5">
                        <i data-lucide="file-spreadsheet" class="w-4 h-4 text-amber-300"></i>
                        <span>Importar Planilha</span>
                    </button>
                    <button onclick="openProductModal()" class="hidden sm:inline-flex bg-[#183B66] hover:bg-[#1F497D] text-white border border-white/15 text-xs font-semibold px-3 py-2 rounded-lg transition-colors items-center gap-1.5">
                        <i data-lucide="plus-circle" class="w-4 h-4 text-sky-300"></i>
                        <span>+ Produto</span>
                    </button>
                    <button onclick="openAllocateModal()" class="bg-[#E30613] hover:bg-[#C80510] text-white text-xs font-bold px-3.5 py-2 rounded-lg shadow-sm transition-colors flex items-center gap-1.5">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>+ Item</span>
                    </button>
                    <div class="hidden lg:flex items-center gap-1.5 pl-1.5 border-l border-white/15">
                        <button onclick="exportCSV()" title="Exportar Planilha CSV" class="p-2 rounded-lg bg-[#183B66] hover:bg-[#1F497D] border border-white/15 text-slate-200 hover:text-white transition-colors">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                        </button>
                        <button onclick="exportJSON()" title="Salvar Backup JSON" class="p-2 rounded-lg bg-[#183B66] hover:bg-[#1F497D] border border-white/15 text-slate-200 hover:text-white transition-colors">
                            <i data-lucide="save" class="w-3.5 h-3.5"></i>
                        </button>
                        <label for="header-import-json" title="Restaurar Backup JSON" class="admin-only p-2 rounded-lg bg-[#183B66] hover:bg-[#1F497D] border border-white/15 text-slate-200 hover:text-white transition-colors cursor-pointer">
                            <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                            <input type="file" id="header-import-json" aria-label="Restaurar arquivo de backup JSON" accept=".json" onchange="importJSON(event)" class="hidden">
                        </label>
                        <button onclick="confirmClearAllData()" title="Limpar Todos os Dados Salvos" class="admin-only bg-rose-950/70 hover:bg-[#E30613] text-rose-200 hover:text-white border border-rose-400/30 text-xs font-semibold px-2.5 py-2 rounded-lg transition-all flex items-center gap-1">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            <span class="hidden xl:inline">Limpar</span>
                        </button>
                    </div>

                    <!-- Menu do Usuário Logado -->
                    <div class="relative pl-1.5 border-l border-white/15" id="user-menu-wrap">
                        <button type="button" id="user-menu-btn" onclick="toggleUserMenu()" aria-haspopup="true" aria-expanded="false" aria-controls="user-menu"
                                class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-white/10 transition-colors">
                            <span id="user-avatar" class="w-8 h-8 rounded-full bg-[#E30613] text-white text-xs font-extrabold flex items-center justify-center shrink-0" aria-hidden="true">--</span>
                            <span class="hidden sm:flex flex-col items-start leading-tight min-w-0">
                                <span id="user-display-name" class="text-xs font-bold text-white truncate max-w-[140px]">—</span>
                                <span id="user-role-label" class="text-[10px] font-semibold uppercase tracking-wider text-sky-200">—</span>
                            </span>
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-300"></i>
                        </button>
                        <div id="user-menu" role="menu" class="hidden absolute right-0 mt-2 w-60 bg-white text-slate-800 rounded-xl shadow-xl border border-slate-200 py-1.5 z-40">
                            <div class="px-3.5 py-2 border-b border-slate-100">
                                <p id="user-menu-name" class="text-sm font-bold text-slate-900 truncate">—</p>
                                <p id="user-menu-username" class="text-xs text-slate-600 truncate">—</p>
                            </div>
                            <button type="button" role="menuitem" onclick="openChangePasswordModal()" class="w-full text-left px-3.5 py-2.5 text-sm font-medium hover:bg-slate-50 flex items-center gap-2">
                                <i data-lucide="key-round" class="w-4 h-4 text-[#0F294A]"></i> Alterar minha senha
                            </button>
                            <button type="button" role="menuitem" onclick="openUsersModal()" class="admin-only w-full text-left px-3.5 py-2.5 text-sm font-medium hover:bg-slate-50 flex items-center gap-2">
                                <i data-lucide="users" class="w-4 h-4 text-[#0F294A]"></i> Gerenciar usuários
                            </button>
                            <form method="post" action="logout.php" class="border-t border-slate-100 mt-1 pt-1">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" role="menuitem" class="w-full text-left px-3.5 py-2.5 text-sm font-semibold text-[#C80510] hover:bg-rose-50 flex items-center gap-2">
                                    <i data-lucide="log-out" class="w-4 h-4"></i> Sair
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Linha Inferior: Barra de Navegação que Ocupa 100% da Largura do Header -->
            <div class="py-2">
                <nav class="w-full bg-[#091B33] p-1 rounded-xl border border-white/10 grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                    <button onclick="switchTab('dashboard')" id="tab-dashboard" class="nav-btn w-full px-3 py-2.5 rounded-lg text-xs sm:text-sm font-bold transition-all flex items-center justify-center gap-2 bg-white text-[#0F294A] shadow-sm whitespace-nowrap">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i>
                        <span class="truncate">Resumo Geral</span>
                    </button>
                    <button onclick="switchTab('map')" id="tab-map" class="nav-btn w-full px-3 py-2.5 rounded-lg text-xs sm:text-sm font-semibold text-slate-300 hover:text-white hover:bg-white/10 transition-all flex items-center justify-center gap-2 whitespace-nowrap">
                        <i data-lucide="map" class="w-4 h-4 shrink-0"></i>
                        <span class="truncate">Mapa 2D (Virtual)</span>
                    </button>
                    <button onclick="switchTab('catalog')" id="tab-catalog" class="nav-btn w-full px-3 py-2.5 rounded-lg text-xs sm:text-sm font-semibold text-slate-300 hover:text-white hover:bg-white/10 transition-all flex items-center justify-center gap-2 whitespace-nowrap">
                        <i data-lucide="package" class="w-4 h-4 shrink-0"></i>
                        <span class="truncate">Produtos</span>
                    </button>
                    <button onclick="switchTab('history')" id="tab-history" class="nav-btn w-full px-3 py-2.5 rounded-lg text-xs sm:text-sm font-semibold text-slate-300 hover:text-white hover:bg-white/10 transition-all flex items-center justify-center gap-2 whitespace-nowrap">
                        <i data-lucide="history" class="w-4 h-4 shrink-0"></i>
                        <span class="truncate">Histórico</span>
                    </button>
                </nav>
            </div>
        </div>
    </header>

    <!-- Barra de Legenda de Validades + Status do Banco -->
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="font-extrabold uppercase tracking-wider text-slate-500 text-[11px] mr-1">Legenda:</span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md font-bold bg-[#DC2626] text-white shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-white"></span> Vencido
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md font-bold bg-[#D9530F] text-white shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-white"></span> Próx. 15 dias
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md font-bold bg-[#FACC15] text-slate-900 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-slate-900"></span> Próx. 30 dias
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md font-bold bg-[#16A34A] text-white shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-white"></span> Longe de vencer
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md font-bold bg-slate-500 text-white shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-white"></span> Sem validade
                </span>
            </div>

            <div class="flex flex-wrap items-center gap-3 text-xs">
                <span id="db-status-badge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                    <span id="db-status-dot" class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span id="db-status-text">Conectando ao banco...</span>
                </span>
                <span class="inline-flex items-center gap-1.5 text-slate-600 font-medium">
                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-[#0F294A]"></i>
                    <span>Referência: <strong id="header-ref-date" class="text-slate-900">—</strong></span>
                </span>
            </div>
        </div>
    </div>

    <!-- Conteúdo Principal -->
    <main id="main-content" class="flex-1 max-w-[1440px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">

        <!-- =====================================================================
             ABA 1: RESUMO GERAL (KPIs + SEGMENTADOR + TABELA DE VALIDADES)
             ===================================================================== -->
        <section id="view-dashboard" class="space-y-6">

            <!-- Faixa Superior de 6 Indicadores Interativos (Botões de Filtro) -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
                <button type="button" onclick="setSlicerStatus('all')" id="kpi-card-all" title="Filtrar: Todos os Produtos" class="kpi-filter-card bg-white rounded-xl border border-slate-200 border-t-4 border-t-[#0F294A] p-4 shadow-2xs hover:shadow-md hover:-translate-y-0.5 transition-all text-left group cursor-pointer focus:outline-none">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 block group-hover:text-[#0F294A] transition-colors">Total Unidades</span>
                        <i data-lucide="filter" class="w-3.5 h-3.5 text-slate-300 group-hover:text-[#0F294A] transition-colors"></i>
                    </div>
                    <p id="kpi-total-units" class="text-2xl sm:text-3xl font-extrabold text-[#0F294A] mt-1">0</p>
                    <span id="kpi-total-skus-sub" class="text-[11px] text-slate-500 mt-1 block">0 lotes • 0 produtos</span>
                </button>

                <button type="button" onclick="setSlicerStatus('expired')" id="kpi-card-expired" title="Filtrar: Produtos Vencidos" class="kpi-filter-card bg-white rounded-xl border border-slate-200 border-t-4 border-t-[#DC2626] p-4 shadow-2xs hover:shadow-md hover:-translate-y-0.5 transition-all text-left group cursor-pointer focus:outline-none">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 block group-hover:text-[#DC2626] transition-colors">Vencidos</span>
                        <i data-lucide="filter" class="w-3.5 h-3.5 text-slate-300 group-hover:text-[#DC2626] transition-colors"></i>
                    </div>
                    <p id="kpi-expired-count" class="text-2xl sm:text-3xl font-extrabold text-[#DC2626] mt-1">0</p>
                    <span class="text-[11px] text-slate-500 mt-1 block">Ação imediata</span>
                </button>

                <button type="button" onclick="setSlicerStatus('urgent')" id="kpi-card-urgent" title="Filtrar: Vencendo em até 15 Dias" class="kpi-filter-card bg-white rounded-xl border border-slate-200 border-t-4 border-t-[#D9530F] p-4 shadow-2xs hover:shadow-md hover:-translate-y-0.5 transition-all text-left group cursor-pointer focus:outline-none">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 block group-hover:text-[#D9530F] transition-colors">Até 15 Dias</span>
                        <i data-lucide="filter" class="w-3.5 h-3.5 text-slate-300 group-hover:text-[#D9530F] transition-colors"></i>
                    </div>
                    <p id="kpi-urgent-count" class="text-2xl sm:text-3xl font-extrabold text-[#D9530F] mt-1">0</p>
                    <span class="text-[11px] text-slate-500 mt-1 block">Prioridade alta</span>
                </button>

                <button type="button" onclick="setSlicerStatus('warning')" id="kpi-card-warning" title="Filtrar: Vencendo em até 30 Dias" class="kpi-filter-card bg-white rounded-xl border border-slate-200 border-t-4 border-t-[#FACC15] p-4 shadow-2xs hover:shadow-md hover:-translate-y-0.5 transition-all text-left group cursor-pointer focus:outline-none">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 block group-hover:text-amber-600 transition-colors">Até 30 Dias</span>
                        <i data-lucide="filter" class="w-3.5 h-3.5 text-slate-300 group-hover:text-amber-600 transition-colors"></i>
                    </div>
                    <p id="kpi-warning-count" class="text-2xl sm:text-3xl font-extrabold text-amber-600 mt-1">0</p>
                    <span class="text-[11px] text-slate-500 mt-1 block">Atenção</span>
                </button>

                <button type="button" onclick="setSlicerStatus('ok')" id="kpi-card-ok" title="Filtrar: Produtos Longe de Vencer" class="kpi-filter-card bg-white rounded-xl border border-slate-200 border-t-4 border-t-[#16A34A] p-4 shadow-2xs hover:shadow-md hover:-translate-y-0.5 transition-all text-left group cursor-pointer focus:outline-none">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 block group-hover:text-[#16A34A] transition-colors">Longe de Vencer</span>
                        <i data-lucide="filter" class="w-3.5 h-3.5 text-slate-300 group-hover:text-[#16A34A] transition-colors"></i>
                    </div>
                    <p id="kpi-ok-count" class="text-2xl sm:text-3xl font-extrabold text-[#16A34A] mt-1">0</p>
                    <span class="text-[11px] text-slate-500 mt-1 block">Estoque regular</span>
                </button>

                <button type="button" onclick="setSlicerStatus('low')" id="kpi-card-low" title="Filtrar: Itens em Falta ou a Repor" class="kpi-filter-card bg-white rounded-xl border border-slate-200 border-t-4 border-t-[#E30613] p-4 shadow-2xs hover:shadow-md hover:-translate-y-0.5 transition-all text-left group cursor-pointer focus:outline-none">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 block group-hover:text-[#E30613] transition-colors">Em Falta / Repor</span>
                        <i data-lucide="filter" class="w-3.5 h-3.5 text-slate-300 group-hover:text-[#E30613] transition-colors"></i>
                    </div>
                    <p id="kpi-low-count" class="text-2xl sm:text-3xl font-extrabold text-[#E30613] mt-1">0</p>
                    <span id="kpi-occupancy-sub" class="text-[11px] text-slate-500 mt-1 block">0% mapa (0/15)</span>
                </button>
            </div>

            <!-- Área Principal com Segmentador Lateral + Painel de Sub-abas -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

                <!-- Segmentador Lateral -->
                <aside class="lg:col-span-3 bg-white rounded-xl border border-slate-200 p-4 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                        <div class="flex items-center gap-2 text-[#0F294A]">
                            <i data-lucide="sliders-horizontal" class="w-4 h-4"></i>
                            <h2 class="text-xs font-extrabold uppercase tracking-wider">Segmentador</h2>
                        </div>
                        <button onclick="resetDashboardFilters()" class="text-[11px] font-bold text-[#E30613] hover:underline">Limpar</button>
                    </div>

                    <div>
                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">Área / Setor do Mapa</label>
                        <select id="slicer-zone" onchange="renderDashboard()" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0F294A]">
                            <option value="all">Todas as Posições</option>
                            <option value="M">Estante Madeira (M1–M3)</option>
                            <option value="G">Gaveteiro Baixo (G1)</option>
                            <option value="E">Estante Aço Esquerda (E1–E2)</option>
                            <option value="C">Módulo de Canto (C1)</option>
                            <option value="F">Estante Aço Fundo (F1–F2)</option>
                            <option value="I">Ilha Central (I1)</option>
                            <option value="P">Área de Palete (P1)</option>
                            <option value="D">Estante Aço Direita (D1–D3)</option>
                            <option value="A">Armário Fechado (A1)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">Categoria do Produto</label>
                        <select id="slicer-category" onchange="renderDashboard()" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0F294A]">
                            <option value="all">Todas as Categorias</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <span class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Status do Estoque</span>

                        <button type="button" id="slicer-btn-all" onclick="setSlicerStatus('all')" class="slicer-status-btn w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs border-2 border-[#0F294A] bg-[#0F294A]/5 text-[#0F294A] font-bold transition-all">
                            <span>Todos os Produtos</span>
                            <span id="slicer-count-all" class="px-2 py-0.5 rounded-full text-[11px] bg-slate-200 text-slate-800">0</span>
                        </button>

                        <button type="button" id="slicer-btn-ok" onclick="setSlicerStatus('ok')" class="slicer-status-btn w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-slate-50 transition-all">
                            <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-[#16A34A]"></span> Longe de vencer</span>
                            <span id="slicer-count-ok" class="px-2 py-0.5 rounded-full text-[11px] bg-emerald-100 text-emerald-800">0</span>
                        </button>

                        <button type="button" id="slicer-btn-warning" onclick="setSlicerStatus('warning')" class="slicer-status-btn w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-slate-50 transition-all">
                            <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-[#FACC15]"></span> Próx. 30 dias</span>
                            <span id="slicer-count-warning" class="px-2 py-0.5 rounded-full text-[11px] bg-yellow-100 text-yellow-900">0</span>
                        </button>

                        <button type="button" id="slicer-btn-urgent" onclick="setSlicerStatus('urgent')" class="slicer-status-btn w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-slate-50 transition-all">
                            <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-[#D9530F]"></span> Próx. 15 dias</span>
                            <span id="slicer-count-urgent" class="px-2 py-0.5 rounded-full text-[11px] bg-orange-100 text-orange-800">0</span>
                        </button>

                        <button type="button" id="slicer-btn-expired" onclick="setSlicerStatus('expired')" class="slicer-status-btn w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-slate-50 transition-all">
                            <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-[#DC2626]"></span> Vencidos</span>
                            <span id="slicer-count-expired" class="px-2 py-0.5 rounded-full text-[11px] bg-red-100 text-red-800">0</span>
                        </button>

                        <button type="button" id="slicer-btn-none" onclick="setSlicerStatus('none')" class="slicer-status-btn w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-slate-50 transition-all">
                            <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-slate-500"></span> Sem validade</span>
                            <span id="slicer-count-none" class="px-2 py-0.5 rounded-full text-[11px] bg-slate-200 text-slate-700">0</span>
                        </button>

                        <button type="button" id="slicer-btn-low" onclick="setSlicerStatus('low')" class="slicer-status-btn w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs border border-slate-200 bg-white text-slate-700 font-semibold hover:bg-slate-50 transition-all">
                            <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-[#0F294A]"></span> Em Falta / Repor</span>
                            <span id="slicer-count-low" class="px-2 py-0.5 rounded-full text-[11px] bg-slate-200 text-slate-800">0</span>
                        </button>
                    </div>
                </aside>

                <!-- Painel Principal do Resumo Geral -->
                <div class="lg:col-span-9 bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
                    <!-- Sub-abas Sublinhadas -->
                    <div class="px-5 border-b border-slate-200 flex flex-wrap items-center gap-6 bg-slate-50/50">
                        <button type="button" id="dash-subtab-overview" onclick="setDashboardSubView('overview')" class="dash-subtab py-3 text-xs sm:text-sm font-bold border-b-2 border-[#E30613] text-[#0F294A] flex items-center gap-2 transition-all -mb-px">
                            <span>Estoque & Validades</span>
                        </button>
                        <button type="button" id="dash-subtab-almox" onclick="setDashboardSubView('almox')" class="dash-subtab py-3 text-xs sm:text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-[#0F294A] flex items-center gap-2 transition-all -mb-px">
                            <span>Itens em Falta</span>
                            <span id="subtab-low-badge" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-[#E30613] text-white">0</span>
                        </button>
                        <button type="button" id="dash-subtab-expiry" onclick="setDashboardSubView('expiry')" class="dash-subtab py-3 text-xs sm:text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-[#0F294A] flex items-center gap-2 transition-all -mb-px">
                            <span>Radar de Validades (FEFO)</span>
                        </button>
                    </div>

                    <!-- SUB-VIEW 1: ESTOQUE & VALIDADES -->
                    <div id="dash-subview-overview" class="p-4 sm:p-5 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="relative flex-1">
                                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                                <input type="text" id="dash-search" oninput="renderDashboard()" placeholder="Buscar produto, SKU, posição ou validade..." class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg pl-10 pr-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F294A]">
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <select id="dash-sort" onchange="renderDashboard()" class="text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-[#0F294A]">
                                    <option value="expiry-asc">Validade (Mais próxima)</option>
                                    <option value="expiry-desc">Validade (Mais distante)</option>
                                    <option value="name-asc">Ordem Alfabética (A → Z)</option>
                                    <option value="name-desc">Ordem Alfabética (Z → A)</option>
                                    <option value="qty-desc">Quantidade (Maior → Menor)</option>
                                    <option value="qty-asc">Quantidade (Menor → Maior)</option>
                                </select>
                                <div class="inline-flex items-center bg-slate-100 p-1 rounded-lg border border-slate-200">
                                    <button type="button" id="btn-view-table" onclick="setDashboardViewMode('table')" class="px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 bg-white text-[#0F294A] shadow-2xs">
                                        <i data-lucide="table" class="w-3.5 h-3.5"></i>
                                        <span>Tabela</span>
                                    </button>
                                    <button type="button" id="btn-view-cards" onclick="setDashboardViewMode('cards')" class="px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 text-slate-600 hover:text-slate-900">
                                        <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i>
                                        <span>Cards</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tabela Principal -->
                        <div id="dash-overview-table-wrap" class="overflow-x-auto rounded-lg border border-slate-200">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-[#0F294A] text-white text-[11px] font-bold uppercase tracking-wider">
                                        <th class="py-3.5 px-4 text-left">Produto</th>
                                        <th class="py-3.5 px-4 text-left">Posição</th>
                                        <th class="py-3.5 px-4 text-center">Validade</th>
                                        <th class="py-3.5 px-4 text-center">Dias Restantes</th>
                                        <th class="py-3.5 px-4 text-center">Status</th>
                                        <th class="py-3.5 px-4 text-center">Quantidade</th>
                                        <th class="py-3.5 px-4 text-left">Observações</th>
                                        <th class="py-3.5 px-4 text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="dash-overview-tbody" class="divide-y divide-slate-200 text-xs"></tbody>
                            </table>
                        </div>

                        <!-- Visualização em Cards -->
                        <div id="dash-overview-cards-wrap" class="hidden grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3.5"></div>
                    </div>

                    <!-- SUB-VIEW 2: ITENS EM FALTA / ALMOXARIFADO -->
                    <div id="dash-subview-almox" class="hidden p-4 sm:p-5 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <button type="button" id="almox-pill-all" onclick="setAlmoxFilter('all')" class="almox-pill px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-900 text-white">
                                    Todos (<span id="almox-count-all">0</span>)
                                </button>
                                <button type="button" id="almox-pill-normal" onclick="setAlmoxFilter('normal')" class="almox-pill px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Regular (<span id="almox-count-normal">0</span>)
                                </button>
                                <button type="button" id="almox-pill-low" onclick="setAlmoxFilter('low')" class="almox-pill px-3 py-1.5 rounded-xl text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                    Reposição (<span id="almox-count-low">0</span>)
                                </button>
                                <button type="button" id="almox-pill-empty" onclick="setAlmoxFilter('empty')" class="almox-pill px-3 py-1.5 rounded-xl text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                    Em Falta (<span id="almox-count-empty">0</span>)
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="text" id="almox-search" oninput="renderDashboard()" placeholder="Filtrar item..." class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0F294A]">
                                <select id="almox-sort" onchange="renderDashboard()" class="text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-2 text-slate-700">
                                    <option value="name-asc">A → Z</option>
                                    <option value="qty-asc">Menor Saldo</option>
                                    <option value="qty-desc">Maior Saldo</option>
                                </select>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-[#0F294A] text-white text-[11px] font-bold uppercase tracking-wider">
                                        <th class="py-3 px-4">Produto</th>
                                        <th class="py-3 px-4">Localização</th>
                                        <th class="py-3 px-4">Estoque Atual</th>
                                        <th class="py-3 px-4">Estoque Mínimo</th>
                                        <th class="py-3 px-4">Situação</th>
                                        <th class="py-3 px-4 text-right">Ação</th>
                                    </tr>
                                </thead>
                                <tbody id="dash-almox-tbody" class="divide-y divide-slate-200 text-xs"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- SUB-VIEW 3: RADAR FEFO -->
                    <div id="dash-subview-expiry" class="hidden p-4 sm:p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="rounded-xl border border-slate-200 overflow-hidden">
                            <div class="bg-[#0F294A] text-white px-4 py-3 flex items-center justify-between">
                                <span class="text-xs font-extrabold uppercase tracking-wider">Prioridade de Saída (Mais Próximos)</span>
                                <span id="fefo-urgent-badge" class="text-[11px] font-bold px-2 py-0.5 rounded bg-[#E30613]">0 lotes</span>
                            </div>
                            <div id="fefo-nearest-list" class="divide-y divide-slate-100 max-h-[420px] overflow-y-auto"></div>
                        </div>

                        <div class="rounded-xl border border-slate-200 overflow-hidden">
                            <div class="bg-[#0F294A] text-white px-4 py-3 flex items-center justify-between">
                                <span class="text-xs font-extrabold uppercase tracking-wider">Maior Prazo (Mais Distantes)</span>
                                <span id="fefo-safe-badge" class="text-[11px] font-bold px-2 py-0.5 rounded bg-[#16A34A]">0 lotes</span>
                            </div>
                            <div id="fefo-farthest-list" class="divide-y divide-slate-100 max-h-[420px] overflow-y-auto"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- =====================================================================
             ABA 2: MAPA 2D INTERATIVO (PLANTA BAIXA VISTA DE CIMA)
             ===================================================================== -->
        <section id="view-map" class="hidden space-y-5">
            <!-- Barra de Pesquisa & Filtros do Mapa -->
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2.5 flex-1">
                    <div class="relative flex-1 min-w-[210px]">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="text" id="map-search" oninput="renderMap()" placeholder="Localizar posição (ex: M1, P1) ou produto no mapa..." class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg pl-10 pr-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F294A]">
                    </div>
                    <select id="map-category-filter" onchange="renderMap()" class="text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-slate-700">
                        <option value="all">Todas Categorias</option>
                    </select>
                    <select id="map-expiry-filter" onchange="renderMap()" class="text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-slate-700">
                        <option value="all">Todos os Status</option>
                        <option value="free">Posições Livres</option>
                        <option value="expired">Vencidos (Vermelho)</option>
                        <option value="urgent">Próx. 15 dias (Laranja)</option>
                        <option value="warning">Próx. 30 dias (Amarelo)</option>
                        <option value="ok">Longe de vencer (Verde)</option>
                        <option value="none">Sem validade (Cinza)</option>
                    </select>
                    <button id="btn-reset-map-filters" onclick="resetMapFilters()" class="hidden text-xs font-bold text-[#E30613] hover:underline px-2">
                        Limpar Filtro
                    </button>
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    <span class="text-xs font-semibold text-slate-500">Ocupação: <strong id="map-occupied-pill" class="text-[#0F294A]">0 / 15</strong></span>
                    <button onclick="openLayoutConfigModal()" class="admin-only px-3 py-2 rounded-lg text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 flex items-center gap-1.5">
                        <i data-lucide="settings-2" class="w-3.5 h-3.5"></i>
                        <span>+ Posição Extra</span>
                    </button>
                </div>
            </div>

            <!-- Planta Baixa 2D + Painel Inspetor Lateral -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                <!-- Planta Baixa 2D Vista de Cima -->
                <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 shadow-2xs">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-sm font-extrabold text-[#0F294A] uppercase tracking-wider">Planta Baixa 2D — Vista Superior do Almoxarifado</h2>
                            <p class="text-xs text-slate-500">Clique sobre qualquer estante, armário ou palete para consultar, alocar ou dar baixa.</p>
                        </div>
                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-md bg-slate-100 text-slate-600 border border-slate-200">Escala Esquemática</span>
                    </div>

                    <!-- Sala Delimitada (Planta Baixa) -->
                    <div class="relative bg-slate-100/90 border-4 border-slate-700 rounded-2xl p-4 sm:p-5 space-y-4">
                        <!-- Parede do Fundo -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pb-3 border-b-2 border-dashed border-slate-300">
                            <div class="flex-1">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 block mb-1.5">Parede do Fundo</span>
                                <div id="zone-back-slots" class="grid grid-cols-3 gap-2.5"></div>
                            </div>
                            <div class="sm:w-28 bg-slate-800 text-white rounded-xl p-2.5 text-center flex flex-col items-center justify-center shrink-0">
                                <i data-lucide="door-open" class="w-4 h-4 text-amber-300 mb-0.5"></i>
                                <span class="text-[10px] font-extrabold uppercase tracking-wider">Porta</span>
                            </div>
                        </div>

                        <!-- Corpo Central da Sala: Parede Esquerda | Centro | Parede Direita -->
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-stretch">
                            <!-- Parede Esquerda -->
                            <div class="md:col-span-4 bg-amber-50/60 border border-amber-200/80 rounded-xl p-2.5 space-y-2">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-900 block">Parede Esquerda (Madeira & Aço)</span>
                                <div id="zone-left-slots" class="space-y-2"></div>
                            </div>

                            <!-- Corredor & Ilha Central -->
                            <div class="md:col-span-4 flex flex-col justify-between bg-white/70 border border-slate-200 rounded-xl p-3 space-y-3">
                                <div class="text-center">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Corredor & Ilha Central</span>
                                </div>
                                <div id="zone-center-slots" class="space-y-3 my-auto"></div>
                                <div class="text-center pt-2 border-t border-dashed border-slate-200">
                                    <span class="text-[10px] font-semibold text-slate-400">Área de Circulação</span>
                                </div>
                            </div>

                            <!-- Parede Direita -->
                            <div class="md:col-span-4 bg-sky-50/60 border border-sky-200/80 rounded-xl p-2.5 space-y-2 flex flex-col justify-between">
                                <div class="space-y-2">
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-sky-900 block">Parede Direita (Aço & Armário)</span>
                                    <div id="zone-right-slots" class="space-y-2"></div>
                                </div>
                                <div class="mt-2 bg-sky-600 text-white rounded-lg py-1.5 px-3 text-center flex items-center justify-center gap-1.5">
                                    <i data-lucide="sun" class="w-3.5 h-3.5 text-yellow-200"></i>
                                    <span class="text-[10px] font-extrabold uppercase tracking-wider">Janela / Iluminação Natural</span>
                                </div>
                            </div>
                        </div>

                        <!-- Posições Extras (se cadastradas) -->
                        <div id="zone-custom-wrapper" class="hidden pt-3 border-t-2 border-dashed border-slate-300 space-y-2">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 block">Posições Adicionais</span>
                            <div id="zone-custom-slots" class="grid grid-cols-2 sm:grid-cols-4 gap-2.5"></div>
                        </div>
                    </div>
                </div>

                <!-- Painel Lateral Inspetor da Posição Selecionada -->
                <aside class="lg:col-span-4 bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs sticky top-24">
                    <div id="map-inspector"></div>
                </aside>
            </div>
        </section>

        <!-- =====================================================================
             ABA 3: CATÁLOGO DE PRODUTOS
             ===================================================================== -->
        <section id="view-catalog" class="hidden space-y-5">
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2.5 flex-1">
                    <div class="relative flex-1 min-w-[200px]">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                        <input type="text" id="catalog-search" oninput="renderCatalog()" placeholder="Buscar produto por nome ou SKU..." class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg pl-10 pr-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F294A]">
                    </div>
                    <select id="catalog-category-filter" onchange="renderCatalog()" class="text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-slate-700">
                        <option value="all">Todas as Categorias</option>
                    </select>
                    <select id="catalog-status-filter" onchange="renderCatalog()" class="text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-slate-700">
                        <option value="all">Todos os Status</option>
                        <option value="in-stock">Com Saldo</option>
                        <option value="out-of-stock">Sem Saldo (Em Falta)</option>
                        <option value="expired">Vencidos</option>
                        <option value="expiring-30">Vencem em até 30 dias</option>
                        <option value="ok">Longe de vencer (> 30d)</option>
                        <option value="none">Sem validade</option>
                    </select>
                    <select id="catalog-sort" onchange="renderCatalog()" class="text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-slate-700">
                        <option value="name-asc">Ordem Alfabética (A → Z)</option>
                        <option value="name-desc">Ordem Alfabética (Z → A)</option>
                        <option value="expiry-asc">Validade (Mais próxima)</option>
                        <option value="expiry-desc">Validade (Mais distante)</option>
                        <option value="qty-desc">Saldo (Maior → Menor)</option>
                        <option value="qty-asc">Saldo (Menor → Maior)</option>
                    </select>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span id="catalog-results-count" class="text-xs font-bold text-slate-500">0 produtos</span>
                    <button onclick="resetCatalogFilters()" class="text-xs font-semibold text-slate-600 hover:text-slate-900 px-2.5 py-2 rounded-lg bg-slate-100">Limpar</button>
                    <button onclick="openProductModal()" class="px-3.5 py-2 rounded-lg text-xs font-bold bg-[#0F294A] hover:bg-[#183B66] text-white">+ Novo Produto</button>
                </div>
            </div>

            <div id="catalog-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"></div>
        </section>

        <!-- =====================================================================
             ABA 4: HISTÓRICO DE MOVIMENTAÇÕES
             ===================================================================== -->
        <section id="view-history" class="hidden space-y-5">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-extrabold text-slate-900">Auditoria & Registro de Movimentações</h2>
                        <p class="text-xs text-slate-500">Todas as entradas, saídas, transferências e ajustes de validade registrados.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <select id="history-type-filter" onchange="renderHistory()" class="text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-700">
                            <option value="all">Todos os Tipos</option>
                            <option value="ENTRADA">Entradas</option>
                            <option value="SAIDA">Saídas / Baixas</option>
                            <option value="TRANSFERENCIA">Transferências</option>
                            <option value="VALIDADE">Ajustes de Validade</option>
                            <option value="CADASTRO">Cadastros de Produto</option>
                            <option value="EDICAO">Edições de Produto</option>
                            <option value="EXCLUSAO">Exclusões de Produto</option>
                            <option value="SISTEMA">Ações do Sistema</option>
                        </select>
                        <button onclick="clearHistoryLog()" class="admin-only text-xs font-semibold text-rose-600 hover:bg-rose-50 px-3 py-2 rounded-xl border border-rose-200">
                            Limpar Histórico
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto p-4">
                    <table class="w-full text-left border-collapse rounded-lg overflow-hidden">
                        <thead>
                            <tr class="bg-[#0F294A] text-white text-[11px] font-bold uppercase tracking-wider">
                                <th class="py-3 px-4">Data / Hora</th>
                                <th class="py-3 px-4">Operação</th>
                                <th class="py-3 px-4">Produto</th>
                                <th class="py-3 px-4">Posição</th>
                                <th class="py-3 px-4">Quantidade</th>
                                <th class="py-3 px-4">Observação</th>
                                <th class="py-3 px-4">Usuário</th>
                            </tr>
                        </thead>
                        <tbody id="history-tbody" class="divide-y divide-slate-100 text-xs"></tbody>
                    </table>
                </div>
            </div>
        </section>

    </main>

    <!-- =========================================================================
         MODAIS DO SISTEMA (SEM ALERT / CONFIRM NATIVO)
         ========================================================================= -->

    <!-- MODAL 1: CADASTRAR / EDITAR PRODUTO -->
    <div id="modal-product" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden max-h-[95vh] flex flex-col">

            <!-- Cabeçalho -->
            <div class="bg-[#0F294A] border-b-4 border-[#E30613] px-6 py-4 text-white flex items-center justify-between shrink-0">
                <div>
                    <h3 id="modal-product-title" class="text-sm font-extrabold leading-tight tracking-tight">Cadastrar Novo Produto</h3>
                    <p id="modal-product-subtitle" class="text-[11px] text-slate-300 mt-0.5">Preencha as informações do produto abaixo</p>
                </div>
                <button onclick="closeModal('modal-product')" class="p-1.5 rounded-lg text-white/70 hover:text-white hover:bg-white/10 transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form onsubmit="saveProductForm(event)" class="overflow-y-auto">
                <input type="hidden" id="prod-id">

                <!-- SEÇÃO 1: Identificação -->
                <div class="px-6 pt-5 pb-4 space-y-3 border-b border-slate-100">
                    <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Identificação</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Nome do Produto <span class="text-[#E30613]">*</span></label>
                            <input type="text" id="prod-name" required oninput="onProductNameInput()"
                                placeholder="Ex: Tomate Italiano, Arroz Branco 5 kg, Álcool 70%..."
                                class="w-full text-sm bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F294A] focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Código / SKU <span class="text-[#E30613]">*</span></label>
                            <input type="text" id="prod-sku" required
                                placeholder="Ex: LEG-001"
                                class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F294A] focus:border-transparent transition-all uppercase">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Categoria <span class="text-[#E30613]">*</span></label>
                            <select id="prod-category" required onchange="this.dataset.userEdited = '1'; onCategoryChange()"
                                class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F294A] focus:border-transparent transition-all">
                                <option value="">Selecione a categoria</option>
                                <optgroup label="Alimentos">
                                    <option value="Bebidas">Bebidas</option>
                                    <option value="Farinhas & Amidos">Farinhas & Amidos</option>
                                    <option value="Doces & Chocolates">Doces & Chocolates</option>
                                    <option value="Grãos & Cereais">Grãos & Cereais</option>
                                    <option value="Temperos & Condimentos">Temperos & Condimentos</option>
                                    <option value="Laticínios & Ovos">Laticínios & Ovos</option>
                                    <option value="Legumes & Verduras">Legumes & Verduras</option>
                                </optgroup>
                                <optgroup label="Insumos & Suprimentos">
                                    <option value="Descartáveis & Embalagens">Descartáveis & Embalagens</option>
                                    <option value="Higiene & Limpeza">Higiene & Limpeza</option>
                                    <option value="Material de Escritório">Material de Escritório</option>
                                </optgroup>
                                <option value="Geral">Geral (sem categoria específica)</option>
                                <option value="Outra">Outra (especificar abaixo)</option>
                            </select>
                            <p id="prod-category-auto" class="hidden text-[10px] text-slate-500 mt-1"></p>
                        </div>
                        <div id="prod-custom-category-wrap" class="sm:col-span-3 hidden">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Nome da Categoria <span class="text-[#E30613]">*</span></label>
                            <input type="text" id="prod-custom-category"
                                oninput="document.getElementById('prod-category').dataset.userEdited = '1'"
                                placeholder="Ex: Suprimentos de TI, Materiais de Limpeza Pesada..."
                                class="w-full text-xs bg-slate-50 border border-amber-300 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-500 transition-all">
                        </div>
                    </div>
                </div>

                <!-- SEÇÃO 2: Estoque & Medidas -->
                <div class="px-6 py-4 space-y-3 border-b border-slate-100 bg-slate-50/50">
                    <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Estoque & Medidas</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Unidade de Medida <span class="text-[#E30613]">*</span></label>
                            <select id="prod-unit" class="w-full text-xs bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F294A] transition-all">
                                <option value="un">Unidade (un)</option>
                                <option value="kg">Quilograma (kg)</option>
                                <option value="lt">Litro (lt)</option>
                                <option value="cx">Caixa (cx)</option>
                                <option value="fd">Fardo (fd)</option>
                                <option value="pct">Pacote (pct)</option>
                                <option value="maço">Maço</option>
                                <option value="dz">Dúzia (dz)</option>
                                <option value="bd">Bandeja (bd)</option>
                                <option value="sc">Saco (sc)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Estoque Mínimo</label>
                            <input type="number" id="prod-min-stock" min="0" value="5"
                                class="w-full text-xs bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F294A] transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Estoque Máximo</label>
                            <input type="number" id="prod-max-stock" min="0" placeholder="—"
                                class="w-full text-xs bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F294A] transition-all">
                        </div>
                    </div>
                </div>

                <!-- SEÇÃO 3: Validade & Armazenamento -->
                <div class="px-6 py-4 space-y-3 border-b border-slate-100">
                    <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Validade & Armazenamento</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Validade Padrão</label>
                            <input type="date" id="prod-default-expiry"
                                class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F294A] transition-all">
                            <span id="prod-expiry-preview" class="text-[10px] font-bold mt-0.5 block"></span>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Condição de Armazenamento</label>
                            <select id="prod-storage" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F294A] transition-all">
                                <option value="">Temperatura ambiente</option>
                                <option value="Refrigerado (2–8 °C)">Refrigerado (2–8 °C)</option>
                                <option value="Congelado (abaixo de -18 °C)">Congelado (abaixo de −18 °C)</option>
                                <option value="Local seco e arejado">Local seco e arejado</option>
                                <option value="Proteger da luz solar">Proteger da luz solar</option>
                                <option value="Inflamável — longe do calor">Inflamável — longe do calor</option>
                            </select>
                        </div>
                        <div class="sm:col-span-3 flex flex-wrap gap-2 pt-1">
                            <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 bg-white cursor-pointer text-xs font-semibold text-slate-700 hover:bg-slate-50 select-none transition-colors">
                                <input type="checkbox" id="prod-flag-perecivel" class="rounded text-[#E30613] focus:ring-[#E30613]">
                                Perecível
                            </label>
                            <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 bg-white cursor-pointer text-xs font-semibold text-slate-700 hover:bg-slate-50 select-none transition-colors">
                                <input type="checkbox" id="prod-flag-epi" class="rounded text-[#0F294A] focus:ring-[#0F294A]">
                                EPI / Segurança
                            </label>
                            <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 bg-white cursor-pointer text-xs font-semibold text-slate-700 hover:bg-slate-50 select-none transition-colors">
                                <input type="checkbox" id="prod-flag-controlado" class="rounded text-[#0F294A] focus:ring-[#0F294A]">
                                Uso Controlado
                            </label>
                        </div>
                    </div>
                </div>

                <!-- SEÇÃO 4: Observações -->
                <div class="px-6 py-4 space-y-3">
                    <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Observações</p>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Instruções de Uso / Notas Adicionais</label>
                        <textarea id="prod-observations" rows="2"
                            placeholder="Ex: Verificar lote antes de usar. Manter fora do alcance de crianças. Uso exclusivo da cozinha..."
                            class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F294A] resize-none transition-all"></textarea>
                    </div>
                </div>

                <!-- Rodapé com dica contextual + ações -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-3 shrink-0">
                    <div id="prod-category-hint" class="hidden text-[11px] text-slate-500 flex items-center gap-1.5 max-w-sm">
                        <i data-lucide="info" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i>
                        <span id="prod-category-hint-text"></span>
                    </div>
                    <div class="flex gap-2 ml-auto shrink-0">
                        <button type="button" onclick="closeModal('modal-product')"
                            class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 rounded-xl text-xs font-bold bg-[#0F294A] hover:bg-[#183B66] text-white shadow-sm transition-colors">
                            <span id="prod-submit-label">Salvar Produto</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: ALOCAR ITEM NO MAPA 2D -->
    <div id="modal-allocate" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden">
            <div class="bg-[#0F294A] border-b-2 border-[#E30613] px-5 py-4 text-white flex items-center justify-between">
                <h3 class="text-sm font-extrabold">Alocar / Adicionar Item no Estoque 2D</h3>
                <button onclick="closeModal('modal-allocate')" class="text-white/80 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form onsubmit="saveAllocateForm(event)" class="p-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Produto *</label>
                        <select id="alloc-product" onchange="onAllocateProductChange()" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500"></select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Posição no Mapa 2D *</label>
                        <select id="alloc-slot" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500"></select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Quantidade *</label>
                        <input type="number" id="alloc-qty" required min="1" value="1" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Data de Entrada *</label>
                        <input type="date" id="alloc-entry" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Data de Validade *</label>
                        <input type="date" id="alloc-expiry" oninput="updateAllocateExpiryPreview()" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <span id="alloc-expiry-preview" class="text-[10px] font-bold text-indigo-600 mt-1 block"></span>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Lote / Observação (Opcional)</label>
                        <input type="text" id="alloc-lot" placeholder="Ex: Lote 2026-A" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-allocate')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700">Cancelar</button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-[#E30613] hover:bg-[#C80510] text-white shadow-sm">Confirmar Alocação</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: DAR BAIXA (SAÍDA) -->
    <div id="modal-withdraw" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden">
            <div class="bg-[#0F294A] border-b-2 border-[#E30613] px-5 py-4 text-white flex items-center justify-between">
                <h3 class="text-sm font-extrabold">Registrar Saída / Baixa</h3>
                <button onclick="closeModal('modal-withdraw')" class="text-white/80 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form onsubmit="saveWithdrawForm(event)" class="p-5 space-y-4">
                <input type="hidden" id="withdraw-slot">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                    <p class="font-bold text-slate-900" id="withdraw-info-title">Produto</p>
                    <p class="text-slate-500 mt-0.5" id="withdraw-info-sub">Saldo atual: 0</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Quantidade a Retirar *</label>
                    <input type="number" id="withdraw-qty" required min="1" value="1" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-rose-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Motivo / Destino (Opcional)</label>
                    <input type="text" id="withdraw-note" placeholder="Ex: Consumo interno, reposição copa..." class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-rose-500">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-withdraw')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700">Cancelar</button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-sm">Confirmar Baixa</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: TRANSFERIR ENTRE POSIÇÕES -->
    <div id="modal-transfer" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden">
            <div class="bg-[#0F294A] border-b-2 border-[#E30613] px-5 py-4 text-white flex items-center justify-between">
                <h3 class="text-sm font-extrabold">Transferir de Posição no Mapa</h3>
                <button onclick="closeModal('modal-transfer')" class="text-white/80 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form onsubmit="saveTransferForm(event)" class="p-5 space-y-4">
                <input type="hidden" id="transfer-from-slot">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                    <p class="font-bold text-slate-900" id="transfer-info-title">Origem</p>
                    <p class="text-slate-500 mt-0.5" id="transfer-info-sub">Disponível: 0</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Posição de Destino *</label>
                    <select id="transfer-to-slot" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500"></select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Quantidade a Transferir *</label>
                    <input type="number" id="transfer-qty" required min="1" value="1" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-transfer')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700">Cancelar</button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm">Transferir</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 5: EDITAR DATA DE VALIDADE -->
    <div id="modal-edit-expiry" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-sm w-full shadow-2xl border border-slate-200 overflow-hidden">
            <div class="bg-[#0F294A] border-b-2 border-[#E30613] px-5 py-4 text-white flex items-center justify-between">
                <h3 class="text-sm font-extrabold">Atualizar Data de Validade</h3>
                <button onclick="closeModal('modal-edit-expiry')" class="text-white/80 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form onsubmit="saveEditExpiryForm(event)" class="p-5 space-y-4">
                <input type="hidden" id="edit-expiry-target-type">
                <input type="hidden" id="edit-expiry-target-id">
                <p id="edit-expiry-label" class="text-xs font-semibold text-slate-600">Selecione a nova data:</p>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Data de Vencimento *</label>
                    <input type="date" id="edit-expiry-date" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-edit-expiry')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700">Cancelar</button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-sm">Salvar Validade</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 6: CONFIGURAR POSIÇÕES EXTRAS DA PLANTA 2D -->
    <div id="modal-layout-config" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden">
            <div class="bg-[#0F294A] border-b-2 border-[#E30613] px-5 py-4 text-white flex items-center justify-between">
                <h3 class="text-sm font-extrabold">Configurar Posições Extras no Mapa 2D</h3>
                <button onclick="closeModal('modal-layout-config')" class="text-white/80 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-5 space-y-4">
                <p class="text-xs text-slate-600">As 15 posições originais do vídeo já estão mapeadas. Caso precise, adicione novas prateleiras ou paletes abaixo:</p>
                <form onsubmit="addCustomSlot(event)" class="flex gap-2">
                    <input type="text" id="new-custom-slot-code" required maxlength="6" placeholder="Cód (Ex: P2)" class="w-24 text-xs font-bold uppercase bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0F294A]">
                    <input type="text" id="new-custom-slot-label" required placeholder="Descrição (Ex: Palete Extra)" class="flex-1 text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0F294A]">
                    <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-[#0F294A] hover:bg-[#183B66] text-white">+ Criar</button>
                </form>

                <div id="custom-slots-list" class="space-y-1.5 max-h-40 overflow-y-auto"></div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <button type="button" onclick="confirmResetAllSlots()" class="text-xs font-bold text-rose-600 hover:underline">
                        Esvaziar todas as posições 2D
                    </button>
                    <button type="button" onclick="closeModal('modal-layout-config')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 7: CONFIRMAÇÃO CUSTOMIZADA -->
    <div id="modal-confirm" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-sm w-full shadow-2xl border border-slate-200 p-5 space-y-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-rose-100 text-rose-600">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                </div>
                <div>
                    <h4 id="confirm-title" class="text-sm font-extrabold text-slate-900">Confirmar Ação</h4>
                    <p id="confirm-message" class="text-xs text-slate-600 mt-0.5">Deseja continuar?</p>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('modal-confirm')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700">Cancelar</button>
                <button type="button" id="confirm-action-btn" class="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- MODAL 8: IMPORTAR PLANILHA (EXCEL / CSV) -->
    <div id="modal-import-spreadsheet" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-3xl w-full shadow-2xl border border-slate-200 overflow-hidden max-h-[92vh] flex flex-col">
            <div class="bg-[#0F294A] border-b-2 border-[#E30613] px-5 py-4 text-white flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <i data-lucide="file-spreadsheet" class="w-5 h-5 text-amber-300"></i>
                    <h3 class="text-sm font-extrabold">Importar Planilha Excel / CSV (Categorias, Validades e Lotes)</h3>
                </div>
                <button onclick="closeModal('modal-import-spreadsheet')" class="text-white/80 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form onsubmit="executeSpreadsheetImport(event)" class="p-5 space-y-4 overflow-y-auto">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 text-xs text-slate-600 space-y-2">
                    <p class="font-bold text-slate-800">Importação Inteligente de Planilhas:</p>
                    <p>O sistema identifica automaticamente as <strong>abas/categorias</strong>, <strong>colunas de cabeçalho</strong> (Produto, Validade, Quantidade, Unidade, Observações, Posição) e datas nos formatos <code class="bg-white px-1 rounded border border-slate-200">MM/AAAA</code> (ex: 01/2027) ou <code class="bg-white px-1 rounded border border-slate-200">DD/MM/AAAA</code>.</p>
                    <div class="pt-1 flex flex-wrap items-center gap-3">
                        <label class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-[#0F294A] hover:bg-[#183B66] text-white font-bold cursor-pointer transition-colors shadow-2xs">
                            <i data-lucide="folder-open" class="w-4 h-4 text-amber-300"></i>
                            <span>Selecionar Arquivo (.XLSX, .XLS ou .CSV)</span>
                            <input type="file" id="import-file-input" accept=".xlsx,.xls,.csv,.txt" onchange="handleSpreadsheetFileUpload(event)" class="hidden">
                        </label>
                        <button type="button" onclick="downloadSpreadsheetTemplate()" class="inline-flex items-center gap-1.5 px-3 py-2.5 rounded-lg bg-white hover:bg-slate-100 text-[#0F294A] border border-slate-300 font-bold transition-colors">
                            <i data-lucide="download" class="w-4 h-4 text-[#E30613]"></i>
                            <span>Baixar Modelo (.XLSX)</span>
                        </button>
                        <span id="import-file-name" class="text-xs font-semibold text-slate-500">Nenhum arquivo selecionado</span>
                    </div>
                </div>

                <!-- Categorias Detectadas na Planilha -->
                <div id="import-categories-wrap" class="hidden space-y-1.5">
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500">Categorias Identificadas:</span>
                    <div id="import-categories-badges" class="flex flex-wrap gap-1.5"></div>
                </div>

                <!-- Pré-visualização Estruturada -->
                <div id="import-table-preview-wrap" class="hidden border border-slate-200 rounded-xl overflow-hidden">
                    <div class="bg-slate-100 px-3 py-2 flex items-center justify-between text-[11px] font-bold text-slate-700 border-b border-slate-200">
                        <span>Pré-visualização dos Dados Reconhecidos</span>
                        <span id="import-preview-badge" class="text-[#0F294A]">0 itens identificados</span>
                    </div>
                    <div class="max-h-44 overflow-y-auto">
                        <table class="w-full text-left border-collapse text-[11px]">
                            <thead>
                                <tr class="bg-[#0F294A] text-white">
                                    <th class="py-1.5 px-2.5">Produto</th>
                                    <th class="py-1.5 px-2.5">Categoria</th>
                                    <th class="py-1.5 px-2.5">Posição</th>
                                    <th class="py-1.5 px-2.5">Validade</th>
                                    <th class="py-1.5 px-2.5">Qtd</th>
                                    <th class="py-1.5 px-2.5">Observações</th>
                                </tr>
                            </thead>
                            <tbody id="import-preview-tbody" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-slate-700">Ou cole os dados diretamente do Excel (Ctrl+C / Ctrl+V)</label>
                    </div>
                    <textarea id="import-raw-text" rows="4" oninput="previewSpreadsheetImport()" placeholder="Cole aqui as linhas da planilha ou selecione o arquivo .xlsx acima..." class="w-full text-xs font-mono bg-slate-50 border border-slate-300 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-[#0F294A]"></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <label class="flex items-start gap-2 p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                        <input type="checkbox" id="import-auto-allocate" class="mt-0.5 rounded text-[#0F294A] focus:ring-[#0F294A]">
                        <div>
                            <span class="font-bold text-slate-800 block">Distribuir automaticamente nas 15 posições 2D</span>
                            <span class="text-[11px] text-slate-500">Os demais itens ficam salvos no Estoque Geral prontos para endereçar depois.</span>
                        </div>
                    </label>
                    <div class="p-3 rounded-xl border border-slate-200 bg-slate-50">
                        <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Modo de Importação</label>
                        <select id="import-mode" class="w-full text-xs font-bold bg-white border border-slate-200 rounded-lg px-2.5 py-1.5 text-slate-800">
                            <option value="replace">Substituir estoque atual pela planilha</option>
                            <option value="merge">Adicionar / Mesclar aos itens atuais</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-import-spreadsheet')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700">Cancelar</button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-[#E30613] hover:bg-[#C80510] text-white shadow-sm">Importar Tudo para o Programa</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 9: ALTERAR A PRÓPRIA SENHA -->
    <div id="modal-change-password" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden" role="dialog" aria-modal="true" aria-labelledby="change-password-title">
            <div class="bg-[#0F294A] border-b-2 border-[#E30613] px-5 py-4 text-white flex items-center justify-between">
                <h3 id="change-password-title" class="text-sm font-extrabold">Alterar minha senha</h3>
                <button type="button" onclick="closeModal('modal-change-password')" class="text-white/80 hover:text-white" aria-label="Fechar">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form onsubmit="saveChangePasswordForm(event)" class="p-5 space-y-4" novalidate>
                <div>
                    <label for="cp-current" class="block text-xs font-bold text-slate-700 mb-1">Senha atual</label>
                    <input type="password" id="cp-current" autocomplete="current-password" required class="w-full text-sm bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F294A]">
                </div>
                <div>
                    <label for="cp-new" class="block text-xs font-bold text-slate-700 mb-1">Nova senha</label>
                    <input type="password" id="cp-new" autocomplete="new-password" required minlength="8" class="w-full text-sm bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F294A]">
                    <p class="mt-1 text-xs text-slate-600">Mínimo de 8 caracteres, com letras e números.</p>
                </div>
                <div>
                    <label for="cp-confirm" class="block text-xs font-bold text-slate-700 mb-1">Confirme a nova senha</label>
                    <input type="password" id="cp-confirm" autocomplete="new-password" required minlength="8" class="w-full text-sm bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0F294A]">
                </div>
                <p id="cp-error" class="hidden rounded-xl border border-red-200 bg-red-50 px-3.5 py-2.5 text-xs font-semibold text-red-800" role="alert"></p>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-change-password')" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700">Cancelar</button>
                    <button type="submit" id="cp-submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-[#0F294A] hover:bg-[#183B66] text-white disabled:opacity-60">Salvar nova senha</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 10: GERENCIAR USUÁRIOS (ADMIN) -->
    <div id="modal-users" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-3xl w-full shadow-2xl border border-slate-200 overflow-hidden max-h-[95vh] flex flex-col" role="dialog" aria-modal="true" aria-labelledby="users-modal-title">
            <div class="bg-[#0F294A] border-b-2 border-[#E30613] px-5 py-4 text-white flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <i data-lucide="users" class="w-5 h-5 text-sky-300"></i>
                    <h3 id="users-modal-title" class="text-sm font-extrabold">Gerenciar usuários</h3>
                </div>
                <button type="button" onclick="closeModal('modal-users')" class="text-white/80 hover:text-white" aria-label="Fechar">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-5 space-y-5 overflow-y-auto">
                <!-- Senha temporária gerada (exibida uma única vez) -->
                <div id="users-temp-password" class="hidden rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-900" role="status">
                    <p class="font-bold">Senha temporária de <span id="users-temp-username"></span>:</p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <code id="users-temp-value" class="px-3 py-1.5 rounded-lg bg-white border border-emerald-300 text-base font-extrabold tracking-wider text-slate-900 select-all"></code>
                        <button type="button" onclick="copyTempPassword()" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-700 hover:bg-emerald-800 text-white">Copiar</button>
                    </div>
                    <p class="mt-2 text-xs">Entregue essa senha ao usuário. Ela não será exibida novamente e precisará ser trocada no primeiro acesso.</p>
                </div>

                <!-- Novo usuário -->
                <form onsubmit="saveNewUserForm(event)" class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3" novalidate>
                    <p class="text-xs font-extrabold uppercase tracking-wider text-slate-600">Novo usuário</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label for="nu-name" class="block text-xs font-bold text-slate-700 mb-1">Nome completo</label>
                            <input type="text" id="nu-name" required maxlength="100" autocomplete="off" class="w-full text-sm bg-white border border-slate-300 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0F294A]">
                        </div>
                        <div>
                            <label for="nu-username" class="block text-xs font-bold text-slate-700 mb-1">Usuário (login)</label>
                            <input type="text" id="nu-username" required maxlength="32" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="ex.: maria.souza" class="w-full text-sm bg-white border border-slate-300 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0F294A]">
                        </div>
                        <div>
                            <label for="nu-role" class="block text-xs font-bold text-slate-700 mb-1">Perfil</label>
                            <select id="nu-role" class="w-full text-sm bg-white border border-slate-300 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0F294A]">
                                <option value="operador">Operador</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <p id="nu-error" class="hidden rounded-xl border border-red-200 bg-red-50 px-3.5 py-2.5 text-xs font-semibold text-red-800" role="alert"></p>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <p class="text-xs text-slate-600">Uma senha temporária será gerada automaticamente.</p>
                        <button type="submit" id="nu-submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-[#E30613] hover:bg-[#C80510] text-white disabled:opacity-60">+ Criar usuário</button>
                    </div>
                </form>

                <!-- Lista -->
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-wider text-slate-600 mb-2">Usuários cadastrados</p>
                    <div id="users-list" class="space-y-2" aria-live="polite"></div>
                </div>
                <div class="text-xs text-slate-600 rounded-xl border border-slate-200 p-3 space-y-1">
                    <p><strong>Administrador:</strong> tudo do operador + excluir produtos, posições extras do mapa, limpar histórico/dados, restaurar backup e gerenciar usuários.</p>
                    <p><strong>Operador:</strong> cadastrar/editar produtos, entradas, saídas, transferências, validades, importação (modo mesclar) e exportações.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- TOAST DE NOTIFICAÇÃO -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <!-- =========================================================================
         LÓGICA COMPLETA DO SISTEMA + BANCO DE DADOS PERSISTENTE (FIRESTORE / IDB)
         ========================================================================= -->
    <script>
        let isSyncing = false;
        let opsInFlight = 0;   // operações de escrita em andamento
        let stateVersion = 0;  // incrementa a cada operação, para descartar polling desatualizado

        // Sanitização contra XSS para qualquer conteúdo interpolado em innerHTML
        function escapeHtml(value) {
            if (value === null || value === undefined) return '';
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function sanitizeUrl(url) {
            const trimmed = String(url || '').trim();
            if (!trimmed) return '';
            if (/^https?:\/\//i.test(trimmed)) return trimmed;
            return '';
        }

        /**
         * Argumento seguro para handlers inline (onclick="fn(${jsArg(x)})").
         * JSON.stringify escapa aspas/barras para o JavaScript e escapeHtml
         * protege o atributo HTML — escapeHtml sozinho NÃO basta nesse contexto.
         */
        function jsArg(value) {
            return escapeHtml(JSON.stringify(String(value ?? '')));
        }

        function todayISO() {
            const d = new Date();
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd}`;
        }

        function addDaysISO(days) {
            const d = new Date();
            d.setDate(d.getDate() + days);
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd}`;
        }

        function getProductFallbackImg(name) {
            const initials = encodeURIComponent((name || 'PR').slice(0, 2).toUpperCase());
            return `https://placehold.co/96x96/0F294A/FFFFFF?text=${initials}`;
        }

        // Definição estrutural das 15 posições da planta baixa vista de cima
        const BASE_FLOORPLAN = {
            left: [
                { code: 'M1', label: 'Estante Madeira — Módulo 1' },
                { code: 'M2', label: 'Estante Madeira — Módulo 2' },
                { code: 'M3', label: 'Estante Madeira — Módulo 3' },
                { code: 'G1', label: 'Gaveteiro Baixo' },
                { code: 'E1', label: 'Estante Aço Esquerda — Módulo 1' },
                { code: 'E2', label: 'Estante Aço Esquerda — Módulo 2' }
            ],
            back: [
                { code: 'C1', label: 'Módulo de Canto' },
                { code: 'F1', label: 'Estante Aço Fundo — Módulo 1' },
                { code: 'F2', label: 'Estante Aço Fundo — Módulo 2' }
            ],
            center: [
                { code: 'I1', label: 'Ilha Central (Piso)' },
                { code: 'P1', label: 'Área de Palete (Janela)' }
            ],
            right: [
                { code: 'D1', label: 'Estante Aço Direita — Módulo 1' },
                { code: 'D2', label: 'Estante Aço Direita — Módulo 2' },
                { code: 'D3', label: 'Estante Aço Direita — Módulo 3' },
                { code: 'A1', label: 'Armário Fechado 2 Portas' }
            ]
        };

        const defaultState = {
            products: [],
            slots: {},
            unallocatedLots: [],
            customSlots: [],
            history: []
        };

        let appState = JSON.parse(JSON.stringify(defaultState));
        let selectedSlotCode = 'M1';
        let dashboardSlicerStatus = 'all';
        let dashboardSubView = 'overview';
        let dashboardViewMode = 'table';
        let almoxStockFilter = 'all';
        let pendingConfirmCallback = null;
        let parsedSpreadsheetBuffer = [];

        function updateDbStatusUI(state, text) {
            const badge = document.getElementById('db-status-badge');
            const dot = document.getElementById('db-status-dot');
            const label = document.getElementById('db-status-text');
            if (!badge || !dot || !label) return;

            label.textContent = text;
            if (state === 'connected') {
                badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200';
                dot.className = 'w-2 h-2 rounded-full bg-[#16A34A]';
            } else if (state === 'saving') {
                badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-sky-50 text-sky-800 border border-sky-200';
                dot.className = 'w-2 h-2 rounded-full bg-sky-500 animate-ping';
            } else if (state === 'error') {
                badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-red-50 text-red-800 border border-red-200';
                dot.className = 'w-2 h-2 rounded-full bg-[#DC2626]';
            } else {
                badge.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200';
                dot.className = 'w-2 h-2 rounded-full bg-amber-500 animate-pulse';
            }
        }

        // =========================================================================
        // COMUNICAÇÃO COM O BACKEND PHP / MYSQL
        // =========================================================================

        // =========================================================================
        // SESSÃO / USUÁRIO LOGADO
        // =========================================================================

        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const ROLE_LABELS = { admin: 'Administrador', operador: 'Operador' };

        function isAdminUser() {
            return window.APP_USER?.role === 'admin';
        }

        /** Bloqueia no cliente ações exclusivas do administrador (o servidor também valida). */
        function requireAdminAction() {
            if (isAdminUser()) return true;
            showToast('Ação permitida somente para administradores.', 'error');
            return false;
        }

        class ApiError extends Error {
            constructor(message, status) {
                super(message);
                this.status = status;
            }
        }

        let sessionExpiredHandled = false;
        function handleSessionExpired() {
            if (sessionExpiredHandled) return;
            sessionExpiredHandled = true;
            showToast('Sua sessão expirou. Redirecionando para o login...', 'error');
            setTimeout(() => { window.location.href = 'login.php?expirada=1'; }, 1200);
        }

        /**
         * Chamada padrão à API: envia cookie de sessão + token CSRF e
         * converte respostas de erro em ApiError com a mensagem do servidor.
         */
        async function apiFetch(action, { method = 'GET', body } = {}) {
            const opts = {
                method,
                cache: 'no-store',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            };
            if (method !== 'GET') {
                opts.headers['Content-Type'] = 'application/json';
                opts.headers['X-CSRF-Token'] = CSRF_TOKEN;
                opts.body = JSON.stringify(body || {});
            }

            let response;
            try {
                response = await fetch(`api.php?action=${encodeURIComponent(action)}`, opts);
            } catch (networkErr) {
                throw new ApiError('Sem conexão com o servidor. Verifique a rede.', 0);
            }

            let json = null;
            try { json = await response.json(); } catch (_) { /* resposta não-JSON */ }

            if (response.status === 401) {
                handleSessionExpired();
                throw new ApiError(json?.error || 'Sessão expirada.', 401);
            }
            if (!response.ok || !json || json.success === false) {
                throw new ApiError(json?.error || `Erro inesperado do servidor (HTTP ${response.status}).`, response.status);
            }
            return json;
        }

        function applyServerData(data) {
            appState.products = Array.isArray(data.products) ? data.products : [];
            appState.slots = (data.slots && typeof data.slots === 'object') ? data.slots : {};
            appState.unallocatedLots = Array.isArray(data.unallocatedLots) ? data.unallocatedLots : [];
            appState.customSlots = Array.isArray(data.customSlots) ? data.customSlots : [];
            appState.history = Array.isArray(data.history) ? data.history : [];
        }

        function renderUserHeader() {
            const u = window.APP_USER || {};
            const name = u.displayName || u.username || '—';
            const initials = name.split(/\s+/).filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase() || '--';
            document.getElementById('user-avatar').textContent = initials;
            document.getElementById('user-display-name').textContent = name;
            document.getElementById('user-role-label').textContent = ROLE_LABELS[u.role] || u.role || '';
            document.getElementById('user-menu-name').textContent = name;
            document.getElementById('user-menu-username').textContent = `@${u.username || ''} • ${ROLE_LABELS[u.role] || ''}`;

            // Operador não pode usar o modo "substituir" da importação (excluiria produtos)
            if (!isAdminUser()) {
                document.querySelector('#import-mode option[value="replace"]')?.remove();
            }
        }

        function toggleUserMenu(forceOpen) {
            const menu = document.getElementById('user-menu');
            const btn = document.getElementById('user-menu-btn');
            const open = typeof forceOpen === 'boolean' ? forceOpen : menu.classList.contains('hidden');
            menu.classList.toggle('hidden', !open);
            btn.setAttribute('aria-expanded', String(open));
        }

        document.addEventListener('click', (ev) => {
            const wrap = document.getElementById('user-menu-wrap');
            if (wrap && !wrap.contains(ev.target)) toggleUserMenu(false);
        });
        document.addEventListener('keydown', (ev) => {
            if (ev.key === 'Escape') toggleUserMenu(false);
        });

        // =========================================================================
        // COMUNICAÇÃO COM O BACKEND PHP / MYSQL
        // =========================================================================

        // O servidor é a fonte da verdade: cada ação chama uma operação da API
        // (validada e registrada no histórico lá) e a resposta traz o estado atualizado.

        async function initDatabase() {
            updateDbStatusUI('connecting', 'Conectando ao MySQL...');
            try {
                const json = await apiFetch('get_all');
                applyServerData(json.data || {});
                updateDbStatusUI('connected', 'Banco MySQL Conectado');
                renderAll();

                // Sincronização periódica em segundo plano (a cada 6 segundos)
                setInterval(pollDatabaseUpdates, 6000);
            } catch (err) {
                console.error('Erro ao conectar ao PHP/MySQL:', err);
                if (err.status === 401) return;
                updateDbStatusUI('error', 'Sem conexão MySQL');
                showToast('Não foi possível carregar os dados: ' + err.message, 'error');
                renderAll();
            }
        }

        async function pollDatabaseUpdates({ force = false } = {}) {
            // Não sincroniza enquanto estiver salvando ou com modais de edição abertos
            if (opsInFlight > 0 || isSyncing || sessionExpiredHandled) return;
            if (!force && document.querySelectorAll('div[id^="modal-"]:not(.hidden)').length > 0) return;

            const versionAtStart = stateVersion;
            try {
                isSyncing = true;
                const json = await apiFetch('get_all');
                // Uma operação começou durante a leitura: este retrato já está velho
                if (versionAtStart !== stateVersion || opsInFlight > 0) return;
                applyServerData(json.data || {});
                renderAll();
            } catch (err) {
                // Silencioso no polling (401 já redireciona para o login)
            } finally {
                isSyncing = false;
            }
        }

        /** Desabilita os botões de envio de um formulário enquanto a operação roda. */
        function setFormBusy(form, busy) {
            if (!form) return;
            form.querySelectorAll('button[type="submit"]').forEach(btn => {
                if (busy) {
                    btn.dataset.originalHtml = btn.innerHTML;
                    btn.disabled = true;
                    btn.classList.add('opacity-60', 'cursor-wait');
                    btn.innerHTML = '<span class="inline-block w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full animate-spin align-[-2px] mr-1.5" aria-hidden="true"></span>Salvando...';
                } else {
                    btn.disabled = false;
                    btn.classList.remove('opacity-60', 'cursor-wait');
                    if (btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
                }
            });
        }

        /**
         * Executa uma operação de escrita no servidor.
         * Retorna a resposta em caso de sucesso, ou null em caso de erro (já notificado).
         */
        async function runOperation(action, body = {}, { form = null, successMessage = '', silent = false } = {}) {
            opsInFlight++;
            stateVersion++;
            setFormBusy(form, true);
            updateDbStatusUI('saving', 'Salvando no MySQL...');
            try {
                const json = await apiFetch(action, { method: 'POST', body });
                if (json.data) {
                    applyServerData(json.data);
                    renderAll();
                }
                updateDbStatusUI('connected', 'Banco de Dados Salvo (MySQL)');
                if (!silent) showToast(successMessage || json.message || 'Operação concluída!');
                return json;
            } catch (err) {
                if (err.status === 401) return null;
                const serverDown = err.status === 0 || err.status >= 500;
                updateDbStatusUI(serverDown ? 'error' : 'connected', serverDown ? 'Erro ao salvar no MySQL' : 'Banco MySQL Conectado');
                showToast(err.message, 'error');
                // Dados mudaram em outra aba/usuário: mostra a situação real
                if ([404, 409].includes(err.status)) {
                    opsInFlight--;
                    await pollDatabaseUpdates({ force: true });
                    opsInFlight++;
                }
                return null;
            } finally {
                opsInFlight--;
                setFormBusy(form, false);
            }
        }

        function getAllSlotDefinitions() {
            return [
                ...BASE_FLOORPLAN.left,
                ...BASE_FLOORPLAN.back,
                ...BASE_FLOORPLAN.center,
                ...BASE_FLOORPLAN.right,
                ...appState.customSlots
            ];
        }

        function getDaysUntilExpiry(expiryStr) {
            if (!expiryStr) return 99999;
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const [y, m, d] = expiryStr.split('-').map(Number);
            if (!y || !m || !d) return 99999;
            const exp = new Date(y, m - 1, d);
            exp.setHours(0, 0, 0, 0);
            return Math.round((exp - today) / (1000 * 60 * 60 * 24));
        }

        // Regra de Cores:
        // Sem validade: Cinza | < 0: Vermelho (#DC2626) | 0..15: Laranja (#D9530F) | 16..30: Amarelo (#FACC15) | > 30: Verde (#16A34A)
        function getExpiryStatus(expiryStr) {
            if (!expiryStr) {
                return {
                    key: 'none',
                    days: 99999,
                    label: 'Sem validade',
                    daysLabel: 'Sem validade',
                    shortLabel: 'Sem validade',
                    slotBg: 'bg-slate-600 border-slate-800 hover:bg-slate-700 shadow-sm',
                    slotTextMain: 'text-white',
                    slotTextSub: 'text-slate-200',
                    badgeClass: 'bg-slate-700 text-white shadow-2xs font-bold',
                    textClass: 'text-slate-700 font-bold',
                    rowClass: 'bg-slate-100 hover:bg-slate-200/90 border-l-[6px] border-l-slate-400'
                };
            }
            const days = getDaysUntilExpiry(expiryStr);
            if (days >= 9999) {
                return {
                    key: 'none',
                    days: 99999,
                    label: 'Sem validade',
                    daysLabel: 'Sem validade',
                    shortLabel: 'Sem validade',
                    slotBg: 'bg-slate-600 border-slate-800 hover:bg-slate-700 shadow-sm',
                    slotTextMain: 'text-white',
                    slotTextSub: 'text-slate-200',
                    badgeClass: 'bg-slate-700 text-white shadow-2xs font-bold',
                    textClass: 'text-slate-700 font-bold',
                    rowClass: 'bg-slate-100 hover:bg-slate-200/90 border-l-[6px] border-l-slate-400'
                };
            }
            if (days < 0) {
                return {
                    key: 'expired',
                    days,
                    label: 'Vencido',
                    daysLabel: `Vencido há ${Math.abs(days)}d`,
                    shortLabel: 'Vencido',
                    slotBg: 'bg-[#DC2626] border-red-900 hover:bg-red-700 shadow-sm',
                    slotTextMain: 'text-white',
                    slotTextSub: 'text-red-100',
                    badgeClass: 'bg-[#DC2626] text-white shadow-2xs font-extrabold',
                    textClass: 'text-[#DC2626] font-extrabold',
                    rowClass: 'bg-red-100 hover:bg-red-200/90 border-l-[6px] border-l-[#DC2626]'
                };
            }
            if (days <= 15) {
                return {
                    key: 'urgent',
                    days,
                    label: 'Próx. 15 dias',
                    daysLabel: days === 0 ? 'Vence hoje' : `${days} ${days === 1 ? 'dia' : 'dias'}`,
                    shortLabel: 'Próx. 15 dias',
                    slotBg: 'bg-[#D9530F] border-orange-900 hover:bg-orange-700 shadow-sm',
                    slotTextMain: 'text-white',
                    slotTextSub: 'text-orange-100',
                    badgeClass: 'bg-[#D9530F] text-white shadow-2xs font-extrabold',
                    textClass: 'text-[#C2410C] font-extrabold',
                    rowClass: 'bg-orange-100 hover:bg-orange-200/90 border-l-[6px] border-l-[#D9530F]'
                };
            }
            if (days <= 30) {
                return {
                    key: 'warning',
                    days,
                    label: 'Próx. 30 dias',
                    daysLabel: `${days} dias`,
                    shortLabel: 'Próx. 30 dias',
                    slotBg: 'bg-[#FACC15] border-yellow-600 hover:bg-yellow-500 shadow-sm',
                    slotTextMain: 'text-slate-950',
                    slotTextSub: 'text-slate-800',
                    badgeClass: 'bg-[#FACC15] text-slate-950 shadow-2xs font-extrabold border border-amber-400',
                    textClass: 'text-amber-900 font-extrabold',
                    rowClass: 'bg-amber-100 hover:bg-amber-200/90 border-l-[6px] border-l-[#FACC15]'
                };
            }
            return {
                key: 'ok',
                days,
                label: 'Longe de vencer',
                daysLabel: `${days} dias`,
                shortLabel: 'Longe de vencer',
                slotBg: 'bg-[#16A34A] border-emerald-900 hover:bg-emerald-700 shadow-sm',
                slotTextMain: 'text-white',
                slotTextSub: 'text-emerald-100',
                badgeClass: 'bg-[#16A34A] text-white shadow-2xs font-extrabold',
                textClass: 'text-[#15803D] font-extrabold',
                rowClass: 'bg-emerald-100/90 hover:bg-emerald-200/85 border-l-[6px] border-l-[#16A34A]'
            };
        }

        function formatDateBR(isoStr) {
            if (!isoStr) return '-';
            const [y, m, d] = isoStr.split('-');
            if (!y || !m || !d) return isoStr;
            return `${d}/${m}/${y}`;
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return;
            const toast = document.createElement('div');
            const colors = {
                success: 'bg-slate-900 text-white border-emerald-500',
                error: 'bg-red-600 text-white border-red-800',
                info: 'bg-indigo-600 text-white border-indigo-800'
            };
            toast.className = `pointer-events-auto px-4 py-3 rounded-xl shadow-lg border-l-4 text-xs font-semibold flex items-center gap-2 transition-all ${colors[type] || colors.success}`;
            toast.textContent = message;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 250);
            }, 3000);
        }

        function refreshIcons() {
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        }

        function switchTab(tabName) {
            ['dashboard', 'map', 'catalog', 'history'].forEach(t => {
                const view = document.getElementById(`view-${t}`);
                const btn = document.getElementById(`tab-${t}`);
                if (!view || !btn) return;
                if (t === tabName) {
                    view.classList.remove('hidden');
                    btn.className = 'nav-btn w-full px-3 py-2.5 rounded-lg text-xs sm:text-sm font-bold transition-all flex items-center justify-center gap-2 bg-white text-[#0F294A] shadow-sm whitespace-nowrap';
                } else {
                    view.classList.add('hidden');
                    btn.className = 'nav-btn w-full px-3 py-2.5 rounded-lg text-xs sm:text-sm font-semibold text-slate-300 hover:text-white hover:bg-white/10 transition-all flex items-center justify-center gap-2 whitespace-nowrap';
                }
            });
            renderAll();
        }

        function jumpToMapSlot(slotCode) {
            selectedSlotCode = slotCode;
            switchTab('map');
        }

        function openModal(id) {
            const el = document.getElementById(id);
            if (el) el.classList.remove('hidden');
            refreshIcons();
        }

        function closeModal(id) {
            const el = document.getElementById(id);
            if (el) el.classList.add('hidden');
        }

        function openConfirmDialog(title, message, onConfirm) {
            document.getElementById('confirm-title').textContent = title;
            document.getElementById('confirm-message').textContent = message;
            pendingConfirmCallback = onConfirm;
            const btn = document.getElementById('confirm-action-btn');
            btn.onclick = () => {
                closeModal('modal-confirm');
                if (typeof pendingConfirmCallback === 'function') {
                    pendingConfirmCallback();
                }
            };
            openModal('modal-confirm');
        }

        /** Saldo total do produto (posições do mapa + Estoque Geral). */
        function getProductBalance(productId) {
            const inSlots = Object.values(appState.slots).filter(s => s?.productId === productId).reduce((sum, s) => sum + (s.qty || 0), 0);
            const inLots = (appState.unallocatedLots || []).filter(u => u.productId === productId).reduce((sum, u) => sum + (u.qty || 0), 0);
            return inSlots + inLots;
        }

        /** Ajuste rápido ±1 (a saída segue FEFO no servidor). */
        async function adjustProductQuickQty(productId, delta) {
            if (delta < 0 && getProductBalance(productId) <= 0) {
                showToast('Produto já está com saldo zerado.', 'info');
                return;
            }
            await runOperation('quick_adjust', { productId, delta: delta > 0 ? 1 : -1 });
        }

        // =========================================================================
        // ABA 1: RESUMO GERAL (SEGMENTADOR, KPIs E SUB-MENUS)
        // =========================================================================

        const KPI_FILTER_CONFIG = {
            all: { ring: 'ring-[#0F294A]', text: 'text-[#0F294A]' },
            expired: { ring: 'ring-[#DC2626]', text: 'text-[#DC2626]' },
            urgent: { ring: 'ring-[#D9530F]', text: 'text-[#D9530F]' },
            warning: { ring: 'ring-[#FACC15]', text: 'text-amber-600' },
            ok: { ring: 'ring-[#16A34A]', text: 'text-[#16A34A]' },
            low: { ring: 'ring-[#E30613]', text: 'text-[#E30613]' }
        };

        function syncSlicerUI(statusKey) {
            // 1. Sincroniza botões do segmentador lateral
            document.querySelectorAll('.slicer-status-btn').forEach(btn => {
                btn.classList.remove('border-2', 'border-[#0F294A]', 'bg-[#0F294A]/5', 'text-[#0F294A]', 'font-bold');
                btn.classList.add('border', 'border-slate-200', 'bg-white', 'text-slate-700', 'font-semibold');
            });
            const activeBtn = document.getElementById(`slicer-btn-${statusKey}`);
            if (activeBtn) {
                activeBtn.classList.remove('border', 'border-slate-200', 'bg-white', 'text-slate-700', 'font-semibold');
                activeBtn.classList.add('border-2', 'border-[#0F294A]', 'bg-[#0F294A]/5', 'text-[#0F294A]', 'font-bold');
            }

            // 2. Sincroniza os 6 cards de KPI superiores
            const allRingClasses = [
                'ring-2', 'ring-offset-2', 'scale-[1.02]', 'bg-slate-50/80', 'shadow-md',
                'ring-[#0F294A]', 'ring-[#DC2626]', 'ring-[#D9530F]', 'ring-[#FACC15]', 'ring-[#16A34A]', 'ring-[#E30613]'
            ];

            document.querySelectorAll('.kpi-filter-card').forEach(card => {
                card.classList.remove(...allRingClasses);
                card.setAttribute('aria-pressed', 'false');
                const icon = card.querySelector('[data-lucide="filter"], .lucide-filter');
                if (icon) {
                    icon.classList.remove('text-[#0F294A]', 'text-[#DC2626]', 'text-[#D9530F]', 'text-amber-600', 'text-[#16A34A]', 'text-[#E30613]', 'opacity-100');
                    icon.classList.add('text-slate-300');
                }
            });

            const activeKpi = document.getElementById(`kpi-card-${statusKey}`);
            if (activeKpi && KPI_FILTER_CONFIG[statusKey]) {
                const conf = KPI_FILTER_CONFIG[statusKey];
                activeKpi.classList.add('ring-2', 'ring-offset-2', conf.ring, 'scale-[1.02]', 'bg-slate-50/80', 'shadow-md');
                activeKpi.setAttribute('aria-pressed', 'true');
                const activeIcon = activeKpi.querySelector('[data-lucide="filter"], .lucide-filter');
                if (activeIcon) {
                    activeIcon.classList.remove('text-slate-300');
                    activeIcon.classList.add(conf.text, 'opacity-100');
                }
            }
        }

        function setSlicerStatus(statusKey) {
            dashboardSlicerStatus = statusKey;
            syncSlicerUI(statusKey);
            if (dashboardSubView !== 'overview') {
                setDashboardSubView('overview');
            } else {
                renderDashboard();
            }
        }

        function setDashboardSubView(subKey) {
            dashboardSubView = subKey;
            ['overview', 'almox', 'expiry'].forEach(k => {
                const subEl = document.getElementById(`dash-subview-${k}`);
                const tabEl = document.getElementById(`dash-subtab-${k}`);
                if (!subEl || !tabEl) return;
                if (k === subKey) {
                    subEl.classList.remove('hidden');
                    tabEl.className = 'dash-subtab py-3 text-xs sm:text-sm font-bold border-b-2 border-[#E30613] text-[#0F294A] flex items-center gap-2 transition-all -mb-px';
                } else {
                    subEl.classList.add('hidden');
                    tabEl.className = 'dash-subtab py-3 text-xs sm:text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-[#0F294A] flex items-center gap-2 transition-all -mb-px';
                }
            });
            renderDashboard();
        }

        function setDashboardViewMode(mode) {
            dashboardViewMode = mode;
            const tableWrap = document.getElementById('dash-overview-table-wrap');
            const cardsWrap = document.getElementById('dash-overview-cards-wrap');
            const btnTable = document.getElementById('btn-view-table');
            const btnCards = document.getElementById('btn-view-cards');
            if (mode === 'table') {
                tableWrap.classList.remove('hidden');
                cardsWrap.classList.add('hidden');
                btnTable.className = 'px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 bg-white text-[#0F294A] shadow-2xs';
                btnCards.className = 'px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 text-slate-600 hover:text-slate-900';
            } else {
                tableWrap.classList.add('hidden');
                cardsWrap.classList.remove('hidden');
                btnCards.className = 'px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 bg-white text-[#0F294A] shadow-2xs';
                btnTable.className = 'px-3 py-1.5 rounded-md text-xs font-semibold flex items-center gap-1.5 text-slate-600 hover:text-slate-900';
            }
            renderDashboard();
        }

        function setAlmoxFilter(filterKey) {
            almoxStockFilter = filterKey;
            ['all', 'normal', 'low', 'empty'].forEach(k => {
                const pill = document.getElementById(`almox-pill-${k}`);
                if (!pill) return;
                if (k === filterKey) {
                    pill.className = 'almox-pill px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-900 text-white';
                } else {
                    const baseStyles = {
                        all: 'bg-slate-100 text-slate-700 border border-slate-200',
                        normal: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                        low: 'bg-amber-50 text-amber-800 border border-amber-200',
                        empty: 'bg-red-50 text-red-700 border border-red-200'
                    };
                    pill.className = `almox-pill px-3 py-1.5 rounded-xl text-xs font-semibold ${baseStyles[k]}`;
                }
            });
            renderDashboard();
        }

        function resetDashboardFilters() {
            document.getElementById('slicer-zone').value = 'all';
            document.getElementById('slicer-category').value = 'all';
            document.getElementById('dash-search').value = '';
            const dashSort = document.getElementById('dash-sort');
            if (dashSort) dashSort.value = 'expiry-asc';
            const almoxSearch = document.getElementById('almox-search');
            if (almoxSearch) almoxSearch.value = '';
            const almoxSort = document.getElementById('almox-sort');
            if (almoxSort) almoxSort.value = 'name-asc';
            setSlicerStatus('all');
        }

        function sortProductItems(items, sortMode) {
            return [...items].sort((a, b) => {
                if (sortMode === 'name-desc') {
                    return b.prod.name.localeCompare(a.prod.name, 'pt-BR', { sensitivity: 'base' });
                }
                if (sortMode === 'qty-desc') {
                    return b.totalQty - a.totalQty || a.prod.name.localeCompare(b.prod.name, 'pt-BR');
                }
                if (sortMode === 'qty-asc') {
                    return a.totalQty - b.totalQty || a.prod.name.localeCompare(b.prod.name, 'pt-BR');
                }
                if (sortMode === 'expiry-asc') {
                    const dA = a.effectiveDays ?? 99999;
                    const dB = b.effectiveDays ?? 99999;
                    return dA - dB || a.prod.name.localeCompare(b.prod.name, 'pt-BR');
                }
                if (sortMode === 'expiry-desc') {
                    const rawA = a.effectiveDays ?? 99999;
                    const rawB = b.effectiveDays ?? 99999;
                    const dA = rawA >= 9999 ? -99999 : rawA;
                    const dB = rawB >= 9999 ? -99999 : rawB;
                    return dB - dA || a.prod.name.localeCompare(b.prod.name, 'pt-BR');
                }
                return a.prod.name.localeCompare(b.prod.name, 'pt-BR', { sensitivity: 'base' });
            });
        }

        function renderDashboard() {
            const zoneFilter = document.getElementById('slicer-zone').value;
            const catSelect = document.getElementById('slicer-category');
            const currentCat = catSelect.value;

            const categories = [...new Set(appState.products.map(p => p.category).filter(Boolean))].sort();
            catSelect.innerHTML = '<option value="all">Todas as Categorias</option>' +
                categories.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');
            catSelect.value = [...catSelect.options].some(o => o.value === currentCat) ? currentCat : 'all';
            const catFilter = catSelect.value;

            const searchQuery = (document.getElementById('dash-search').value || '').toLowerCase().trim();
            const allSlotDefs = getAllSlotDefinitions();

            // Coleta todos os lotes alocados no Mapa 2D e lotes do Estoque Geral
            const activeLots = [];
            Object.entries(appState.slots).forEach(([slotCode, slot]) => {
                if (!slot || !slot.productId || slot.qty <= 0) return;
                if (zoneFilter !== 'all' && !slotCode.startsWith(zoneFilter)) return;
                const prod = appState.products.find(p => p.id === slot.productId);
                if (!prod) return;
                if (catFilter !== 'all' && prod.category !== catFilter) return;
                const status = getExpiryStatus(slot.expiryDate);
                activeLots.push({
                    source: 'slot',
                    slotCode,
                    slot,
                    prod,
                    status,
                    days: status.days
                });
            });

            if (zoneFilter === 'all') {
                (appState.unallocatedLots || []).forEach(uLot => {
                    if (!uLot || !uLot.productId || uLot.qty <= 0) return;
                    const prod = appState.products.find(p => p.id === uLot.productId);
                    if (!prod) return;
                    if (catFilter !== 'all' && prod.category !== catFilter) return;
                    const status = getExpiryStatus(uLot.expiryDate);
                    activeLots.push({
                        source: 'unallocated',
                        lotId: uLot.id,
                        slotCode: 'Geral',
                        slot: uLot,
                        prod,
                        status,
                        days: status.days
                    });
                });
            }

            // Consolida por produto
            const consolidated = appState.products
                .filter(p => catFilter === 'all' || p.category === catFilter)
                .map(prod => {
                    const lots = activeLots
                        .filter(l => l.prod.id === prod.id)
                        .sort((a, b) => a.days - b.days);
                    const totalQty = lots.reduce((sum, l) => sum + l.slot.qty, 0);
                    const nearestLot = lots[0] || null;
                    const expiryStatus = nearestLot
                        ? nearestLot.status
                        : getExpiryStatus(prod.defaultExpiry || '');
                    const isEmpty = totalQty === 0;
                    const isLow = totalQty > 0 && totalQty <= (prod.minStock || 0);
                    const effectiveDays = nearestLot
                        ? nearestLot.days
                        : (prod.defaultExpiry ? getDaysUntilExpiry(prod.defaultExpiry) : 99999);

                    return {
                        prod,
                        lots,
                        totalQty,
                        nearestLot,
                        expiryStatus,
                        effectiveDays,
                        isEmpty,
                        isLow
                    };
                })
                .filter(item => zoneFilter === 'all' || item.lots.length > 0);

            // Atualiza contadores do Segmentador
            document.getElementById('slicer-count-all').textContent = consolidated.length;
            document.getElementById('slicer-count-ok').textContent = consolidated.filter(i => i.lots.length > 0 && i.expiryStatus.key === 'ok').length;
            document.getElementById('slicer-count-warning').textContent = consolidated.filter(i => i.lots.length > 0 && i.expiryStatus.key === 'warning').length;
            document.getElementById('slicer-count-urgent').textContent = consolidated.filter(i => i.lots.length > 0 && i.expiryStatus.key === 'urgent').length;
            document.getElementById('slicer-count-expired').textContent = consolidated.filter(i => i.lots.length > 0 && i.expiryStatus.key === 'expired').length;
            document.getElementById('slicer-count-none').textContent = consolidated.filter(i => i.expiryStatus.key === 'none').length;
            document.getElementById('slicer-count-low').textContent = consolidated.filter(i => i.isEmpty || i.isLow).length;

            // Atualiza os 6 KPIs superiores
            const relevantSlotDefs = zoneFilter === 'all'
                ? allSlotDefs
                : allSlotDefs.filter(s => s.code.startsWith(zoneFilter));
            const occupiedSlotsCount = relevantSlotDefs.filter(s => {
                const sl = appState.slots[s.code];
                return sl && sl.productId && sl.qty > 0;
            }).length;
            const occPercent = relevantSlotDefs.length > 0 ? Math.round((occupiedSlotsCount / relevantSlotDefs.length) * 100) : 0;

            const sumUnits = consolidated.reduce((s, i) => s + i.totalQty, 0);
            const lowCount = consolidated.filter(i => i.isLow || i.isEmpty).length;

            document.getElementById('kpi-total-units').textContent = sumUnits;
            document.getElementById('kpi-total-skus-sub').textContent = `${activeLots.length} lotes • ${consolidated.length} produtos`;

            document.getElementById('kpi-expired-count').textContent = activeLots.filter(l => l.status.key === 'expired').length;
            document.getElementById('kpi-urgent-count').textContent = activeLots.filter(l => l.status.key === 'urgent').length;
            document.getElementById('kpi-warning-count').textContent = activeLots.filter(l => l.status.key === 'warning').length;
            document.getElementById('kpi-ok-count').textContent = activeLots.filter(l => l.status.key === 'ok').length;

            document.getElementById('kpi-low-count').textContent = lowCount;
            document.getElementById('kpi-occupancy-sub').textContent = `${occPercent}% mapa (${occupiedSlotsCount}/${relevantSlotDefs.length})`;

            const subtabLowBadge = document.getElementById('subtab-low-badge');
            if (subtabLowBadge) subtabLowBadge.textContent = lowCount;

            // Sincroniza estado ativo nos 6 cards superiores e segmentador lateral
            syncSlicerUI(dashboardSlicerStatus);

            // Filtra e ordena itens para Sub-view 1
            const dashSortMode = (document.getElementById('dash-sort')?.value) || 'expiry-asc';
            const filteredItems = sortProductItems(
                consolidated.filter(item => {
                    if (dashboardSlicerStatus === 'ok' && !(item.lots.length > 0 && item.expiryStatus.key === 'ok')) return false;
                    if (dashboardSlicerStatus === 'warning' && !(item.lots.length > 0 && item.expiryStatus.key === 'warning')) return false;
                    if (dashboardSlicerStatus === 'urgent' && !(item.lots.length > 0 && item.expiryStatus.key === 'urgent')) return false;
                    if (dashboardSlicerStatus === 'expired' && !(item.lots.length > 0 && item.expiryStatus.key === 'expired')) return false;
                    if (dashboardSlicerStatus === 'none' && item.expiryStatus.key !== 'none') return false;
                    if (dashboardSlicerStatus === 'low' && !(item.isEmpty || item.isLow)) return false;

                    if (searchQuery) {
                        const matchName = item.prod.name.toLowerCase().includes(searchQuery);
                        const matchSku = item.prod.sku.toLowerCase().includes(searchQuery);
                        const matchSlot = item.lots.some(l => l.slotCode.toLowerCase().includes(searchQuery));
                        const matchExp = item.nearestLot && formatDateBR(item.nearestLot.slot.expiryDate).includes(searchQuery);
                        const matchObs = (item.prod.observations || '').toLowerCase().includes(searchQuery);
                        if (!matchName && !matchSku && !matchSlot && !matchExp && !matchObs) return false;
                    }
                    return true;
                }),
                dashSortMode
            );

            const tbody = document.getElementById('dash-overview-tbody');
            const cardsWrap = document.getElementById('dash-overview-cards-wrap');

            if (filteredItems.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="py-10 px-4 text-center text-slate-500">
                            Nenhum produto encontrado. Use <strong>+ Produto</strong>, <strong>+ Adicionar Item</strong> ou <strong>Importar Planilha</strong> para começar.
                        </td>
                    </tr>`;
                cardsWrap.innerHTML = `
                    <div class="col-span-full py-10 text-center text-xs text-slate-500">
                        Nenhum produto encontrado com os filtros atuais.
                    </div>`;
            } else {
                tbody.innerHTML = filteredItems.map(item => {
                    const { prod, lots, totalQty, nearestLot, isEmpty, isLow } = item;
                    const activeExpDate = nearestLot ? nearestLot.slot.expiryDate : (prod.defaultExpiry || '');
                    const rowStatus = getExpiryStatus(activeExpDate);
                    const safeProdId = escapeHtml(prod.id);
                    const jsProdId = jsArg(prod.id);
                    const safeProdName = escapeHtml(prod.name);
                    const safeSku = escapeHtml(prod.sku);
                    const safeCat = escapeHtml(prod.category);

                    const locationsHtml = lots.length > 0
                        ? lots.map(l => {
                            const safeCode = escapeHtml(l.slotCode);
                            const jsCode = jsArg(l.slotCode);
                            if (l.source === 'slot') {
                                return `<button onclick="jumpToMapSlot(${jsCode})" class="px-2 py-0.5 rounded text-[11px] font-bold bg-[#0F294A]/10 text-[#0F294A] hover:bg-[#0F294A] hover:text-white transition-colors">${safeCode} (${l.slot.qty})</button>`;
                            }
                            return `<button onclick="openAllocateModal('', ${jsProdId})" title="Clique para endereçar no Mapa 2D" class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-200/80 text-slate-700 hover:bg-[#0F294A] hover:text-white transition-colors">Geral (${l.slot.qty})</button>`;
                        }).join(' ')
                        : '<span class="text-slate-400 italic">Não alocado</span>';

                    const expiryCellHtml = activeExpDate
                        ? `<div class="text-center">
                               <p class="font-extrabold text-slate-900">${escapeHtml(activeExpDate.slice(5, 7))}/${escapeHtml(activeExpDate.slice(0, 4))}</p>
                               <p class="text-[11px] text-slate-500">Ref: ${escapeHtml(formatDateBR(activeExpDate))}</p>
                           </div>`
                        : '<span class="text-slate-400 italic">—</span>';

                    const obsText = escapeHtml((nearestLot && nearestLot.slot.observations) || prod.observations || '—');

                    const stockBadge = isEmpty
                        ? '<span class="ml-1.5 text-[10px] font-bold px-1.5 py-0.5 rounded bg-red-100 text-red-700">Em falta</span>'
                        : (isLow ? '<span class="ml-1.5 text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800">Baixo</span>' : '');

                    return `
                        <tr class="${rowStatus.rowClass} transition-colors border-b border-slate-300">
                            <td class="py-3.5 px-4 align-middle text-left">
                                <p class="font-extrabold text-slate-900 text-xs sm:text-[13px] leading-tight">${safeProdName}${stockBadge}</p>
                                <p class="text-[11px] text-slate-600 font-medium mt-0.5">${safeSku} • ${safeCat}</p>
                            </td>
                            <td class="py-3.5 px-4 align-middle text-left">
                                <div class="flex flex-wrap gap-1 items-center">${locationsHtml}</div>
                            </td>
                            <td class="py-3.5 px-4 align-middle text-center">${expiryCellHtml}</td>
                            <td class="py-3.5 px-4 align-middle text-center ${rowStatus.textClass}">${escapeHtml(rowStatus.daysLabel)}</td>
                            <td class="py-3.5 px-4 align-middle text-center">
                                <span class="inline-block px-2.5 py-1 rounded-md text-[11px] font-bold ${rowStatus.badgeClass}">
                                    ${escapeHtml(rowStatus.label)}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 align-middle text-center">
                                <div class="inline-flex items-center justify-center bg-white border border-slate-300 rounded-lg shadow-2xs overflow-hidden">
                                    <button onclick="adjustProductQuickQty(${jsProdId}, -1)" title="Remover 1 unidade" class="px-2.5 py-1 text-slate-600 hover:bg-slate-100 font-bold text-xs">−</button>
                                    <span class="px-3 py-1 font-extrabold text-slate-900 min-w-[38px] text-center">${totalQty}</span>
                                    <button onclick="adjustProductQuickQty(${jsProdId}, 1)" title="Adicionar 1 unidade" class="px-2.5 py-1 text-slate-600 hover:bg-slate-100 font-bold text-xs">+</button>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 align-middle text-left text-slate-700 font-medium max-w-[180px] truncate" title="${obsText}">${obsText}</td>
                            <td class="py-3.5 px-4 align-middle text-center">
                                <div class="inline-flex items-center justify-center gap-1.5 text-slate-600">
                                    <button onclick="openAllocateModal('', ${jsProdId})" title="Alocar lote no Mapa 2D" class="p-1.5 rounded-lg bg-white/90 hover:bg-white text-slate-700 hover:text-[#0F294A] border border-slate-300 shadow-2xs transition-all">
                                        <i data-lucide="map-pin" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick="openProductModal(${jsProdId})" title="Editar produto" class="p-1.5 rounded-lg bg-white/90 hover:bg-white text-slate-700 hover:text-[#0F294A] border border-slate-300 shadow-2xs transition-all">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick="deleteProduct(${jsProdId})" title="Excluir produto" class="admin-only p-1.5 rounded-lg bg-white/90 hover:bg-white text-[#DC2626] border border-slate-300 shadow-2xs transition-all">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>`;
                }).join('');

                // Modo Cards
                cardsWrap.innerHTML = filteredItems.map(item => {
                    const { prod, lots, totalQty, nearestLot } = item;
                    const activeExpDate = nearestLot ? nearestLot.slot.expiryDate : (prod.defaultExpiry || '');
                    const cardStatus = getExpiryStatus(activeExpDate);
                    const fallbackImg = getProductFallbackImg(prod.name);
                    const imgSrc = sanitizeUrl(prod.photo) || fallbackImg;
                    const safeProdId = escapeHtml(prod.id);
                    const jsProdId = jsArg(prod.id);
                    const safeProdName = escapeHtml(prod.name);
                    const safeSku = escapeHtml(prod.sku);
                    const safeUnit = escapeHtml(prod.unit);
                    const locationsText = lots.length > 0
                        ? escapeHtml(lots.map(l => `${l.slotCode} (${l.slot.qty})`).join(', '))
                        : 'Não alocado';

                    return `
                        <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-2xs flex flex-col justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <img src="${escapeHtml(imgSrc)}" onerror="this.src='${fallbackImg}'" class="w-12 h-12 rounded-lg object-cover border border-slate-200 shrink-0" alt="${safeProdName}">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[10px] font-bold uppercase text-slate-400">${safeSku}</span>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold ${cardStatus.badgeClass}">${escapeHtml(cardStatus.label)}</span>
                                    </div>
                                    <h4 class="text-xs font-extrabold text-slate-900 mt-0.5 truncate">${safeProdName}</h4>
                                    <p class="text-[11px] text-slate-500 truncate">${locationsText}</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-2.5 border-t border-slate-100 text-xs">
                                <div>
                                    <span class="text-[10px] text-slate-400 block">Validade</span>
                                    <span class="font-bold text-slate-800">${activeExpDate ? escapeHtml(formatDateBR(activeExpDate)) : '—'} (${escapeHtml(cardStatus.daysLabel)})</span>
                                </div>
                                <div class="flex items-center gap-1 bg-slate-50 border border-slate-200 rounded-lg">
                                    <button onclick="adjustProductQuickQty(${jsProdId}, -1)" class="px-2 py-1 font-bold text-slate-600 hover:bg-slate-200 rounded-l-lg">−</button>
                                    <span class="px-2 font-extrabold text-slate-900">${totalQty} ${safeUnit}</span>
                                    <button onclick="adjustProductQuickQty(${jsProdId}, 1)" class="px-2 py-1 font-bold text-slate-600 hover:bg-slate-200 rounded-r-lg">+</button>
                                </div>
                            </div>
                        </div>`;
                }).join('');
            }

            // Sub-view 3: Radar FEFO
            const sortedByNearest = [...activeLots].sort((a, b) => a.days - b.days);
            const sortedByFarthest = [...activeLots].sort((a, b) => b.days - a.days);

            const urgentBadge = document.getElementById('fefo-urgent-badge');
            const safeBadge = document.getElementById('fefo-safe-badge');
            if (urgentBadge) urgentBadge.textContent = `${sortedByNearest.length} ${sortedByNearest.length === 1 ? 'lote' : 'lotes'}`;
            if (safeBadge) safeBadge.textContent = `${sortedByFarthest.length} ${sortedByFarthest.length === 1 ? 'lote' : 'lotes'}`;

            const nearestListEl = document.getElementById('fefo-nearest-list');
            const farthestListEl = document.getElementById('fefo-farthest-list');

            const renderFefoRow = (l) => {
                const safeCode = escapeHtml(l.slotCode);
                const jsCode = jsArg(l.slotCode);
                const safeProdId = escapeHtml(l.prod.id);
                const jsProdId = jsArg(l.prod.id);
                const safeName = escapeHtml(l.prod.name);
                const safeUnit = escapeHtml(l.prod.unit);
                const actionAttr = l.source === 'slot'
                    ? `onclick="jumpToMapSlot(${jsCode})"`
                    : `onclick="openAllocateModal('', ${jsProdId})"`;
                return `
                <button type="button" ${actionAttr} class="w-full p-3.5 hover:bg-slate-50 transition-colors flex items-center justify-between gap-3 text-left">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded text-[11px] font-extrabold ${l.source === 'slot' ? 'bg-[#0F294A] text-white' : 'bg-slate-200 text-slate-800'}">${safeCode}</span>
                            <p class="text-xs font-extrabold text-slate-900 truncate">${safeName}</p>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">Qtd: <strong>${l.slot.qty} ${safeUnit}</strong> • Vencimento: <strong>${escapeHtml(formatDateBR(l.slot.expiryDate))}</strong></p>
                    </div>
                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold shrink-0 ${l.status.badgeClass}">${escapeHtml(l.status.daysLabel)}</span>
                </button>`;
            };

            if (nearestListEl) {
                nearestListEl.innerHTML = sortedByNearest.length === 0
                    ? '<p class="p-6 text-center text-xs text-slate-400">Nenhum lote cadastrado no estoque.</p>'
                    : sortedByNearest.map(renderFefoRow).join('');
            }

            if (farthestListEl) {
                farthestListEl.innerHTML = sortedByFarthest.length === 0
                    ? '<p class="p-6 text-center text-xs text-slate-400">Nenhum lote cadastrado no estoque.</p>'
                    : sortedByFarthest.map(renderFefoRow).join('');
            }

            // Sub-view 2: Itens em Falta & Reposição
            const countAllEl = document.getElementById('almox-count-all');
            const countNormEl = document.getElementById('almox-count-normal');
            const countLowEl = document.getElementById('almox-count-low');
            const countEmptyEl = document.getElementById('almox-count-empty');
            if (countAllEl) countAllEl.textContent = consolidated.length;
            if (countNormEl) countNormEl.textContent = consolidated.filter(i => !i.isEmpty && !i.isLow).length;
            if (countLowEl) countLowEl.textContent = consolidated.filter(i => i.isLow).length;
            if (countEmptyEl) countEmptyEl.textContent = consolidated.filter(i => i.isEmpty).length;

            const almoxSearch = (document.getElementById('almox-search')?.value || '').toLowerCase().trim();
            const almoxSort = (document.getElementById('almox-sort')?.value) || 'name-asc';
            const almoxItems = sortProductItems(
                consolidated.filter(item => {
                    if (almoxStockFilter === 'normal' && (item.isEmpty || item.isLow)) return false;
                    if (almoxStockFilter === 'low' && !item.isLow) return false;
                    if (almoxStockFilter === 'empty' && !item.isEmpty) return false;
                    if (almoxSearch && !item.prod.name.toLowerCase().includes(almoxSearch) && !item.prod.sku.toLowerCase().includes(almoxSearch)) return false;
                    return true;
                }),
                almoxSort
            );

            const almoxTbody = document.getElementById('dash-almox-tbody');
            if (almoxTbody) {
                if (almoxItems.length === 0) {
                    almoxTbody.innerHTML = '<tr><td colspan="6" class="py-8 px-4 text-center text-slate-400">Nenhum item encontrado nesta situação.</td></tr>';
                } else {
                    almoxTbody.innerHTML = almoxItems.map(item => {
                        const { prod, lots, totalQty, isEmpty, isLow } = item;
                        const minStock = prod.minStock || 0;
                        const pct = minStock > 0 ? Math.min(100, Math.round((totalQty / minStock) * 100)) : 100;
                        const barColor = isEmpty ? 'bg-[#DC2626]' : (isLow ? 'bg-[#D9530F]' : 'bg-[#16A34A]');
                        const statusPill = isEmpty
                            ? '<span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-[#DC2626] text-white">Em Falta</span>'
                            : (isLow
                                ? '<span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-[#FACC15] text-slate-900">Reposição</span>'
                                : '<span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-[#16A34A] text-white">Regular</span>');

                        const locText = lots.length > 0
                            ? escapeHtml(lots.map(l => `${l.slotCode} (${l.slot.qty})`).join(', '))
                            : 'Sem posição';

                        const safeUnit = escapeHtml(prod.unit);

                        return `
                            <tr class="hover:bg-slate-50">
                                <td class="py-3 px-4">
                                    <p class="font-extrabold text-slate-900">${escapeHtml(prod.name)}</p>
                                    <p class="text-[11px] text-slate-500">${escapeHtml(prod.sku)} • ${escapeHtml(prod.category)}</p>
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-700">${locText}</td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-extrabold text-slate-900">${totalQty} ${safeUnit}</span>
                                        <div class="w-20 h-2 bg-slate-200 rounded-full overflow-hidden">
                                            <div class="h-full ${barColor}" style="width: ${pct}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-600">${minStock} ${safeUnit}</td>
                                <td class="py-3 px-4">${statusPill}</td>
                                <td class="py-3 px-4 text-right">
                                    <button onclick="openAllocateModal('', ${jsArg(prod.id)})" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-[#0F294A] hover:bg-[#183B66] text-white">
                                        + Repor
                                    </button>
                                </td>
                            </tr>`;
                    }).join('');
                }
            }
        }

        // =========================================================================
        // ABA 2: MAPA 2D INTERATIVO (ESTOQUE VIRTUAL VISTA DE CIMA)
        // =========================================================================

        function selectSlot(code) {
            selectedSlotCode = code;
            renderMap();
            renderInspector();
        }

        function resetMapFilters() {
            document.getElementById('map-search').value = '';
            document.getElementById('map-category-filter').value = 'all';
            document.getElementById('map-expiry-filter').value = 'all';
            renderMap();
        }

        function renderSlotBox(code, subtitle, extraClasses = '') {
            const slot = appState.slots[code];
            const prod = slot && slot.productId ? appState.products.find(p => p.id === slot.productId) : null;
            const isSelected = selectedSlotCode === code;
            const searchQuery = (document.getElementById('map-search').value || '').toLowerCase().trim();
            const catFilter = (document.getElementById('map-category-filter')?.value) || 'all';
            const expFilter = (document.getElementById('map-expiry-filter')?.value) || 'all';

            const hasActiveFilter = Boolean(searchQuery || catFilter !== 'all' || expFilter !== 'all');
            const isOccupied = Boolean(prod && slot && slot.qty > 0);
            const status = isOccupied ? getExpiryStatus(slot.expiryDate) : null;

            let isMatch = true;
            if (hasActiveFilter) {
                if (searchQuery) {
                    const matchCode = code.toLowerCase().includes(searchQuery);
                    const matchProd = isOccupied && (prod.name.toLowerCase().includes(searchQuery) || prod.sku.toLowerCase().includes(searchQuery));
                    if (!matchCode && !matchProd) isMatch = false;
                }
                if (catFilter !== 'all') {
                    if (!isOccupied || prod.category !== catFilter) isMatch = false;
                }
                if (expFilter !== 'all') {
                    if (expFilter === 'free') {
                        if (isOccupied) isMatch = false;
                    } else {
                        if (!isOccupied || status.key !== expFilter) isMatch = false;
                    }
                }
            }

            const selectedRing = isSelected ? 'ring-4 ring-indigo-500/70 scale-[1.02] z-10' : '';
            const filterVisualClass = hasActiveFilter
                ? (isMatch ? 'slot-highlight' : 'opacity-40')
                : '';

            const safeCode = escapeHtml(code);

            const jsCode = jsArg(code);
            const safeSubtitle = escapeHtml(subtitle);

            if (!isOccupied) {
                return `
                    <button type="button" onclick="selectSlot(${jsCode})" class="group relative rounded-xl border-2 border-dashed border-slate-300 bg-white hover:border-indigo-400 hover:bg-indigo-50/40 p-2 text-left transition-all flex flex-col justify-between ${extraClasses} ${selectedRing} ${filterVisualClass}">
                        <div class="flex items-center justify-between w-full">
                            <span class="text-xs font-extrabold text-slate-800 bg-slate-100 group-hover:bg-indigo-100 group-hover:text-indigo-700 px-1.5 py-0.5 rounded-md">${safeCode}</span>
                            <span class="text-[10px] font-medium text-slate-400">Livre</span>
                        </div>
                        <span class="text-[10px] text-slate-400 truncate mt-1">${safeSubtitle}</span>
                    </button>`;
            }

            return `
                <button type="button" onclick="selectSlot(${jsCode})" class="group relative rounded-xl border-2 p-2 text-left transition-all flex flex-col justify-between ${status.slotBg} ${extraClasses} ${selectedRing} ${filterVisualClass}">
                    <div class="flex items-center justify-between w-full gap-1">
                        <span class="text-xs font-extrabold px-1.5 py-0.5 rounded-md bg-black/20 ${status.slotTextMain}">${safeCode}</span>
                        <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded-md bg-black/20 ${status.slotTextMain}">${slot.qty} ${escapeHtml(prod.unit)}</span>
                    </div>
                    <div class="mt-1 min-w-0">
                        <p class="text-[11px] font-bold truncate ${status.slotTextMain}">${escapeHtml(prod.name)}</p>
                        <p class="text-[9px] font-semibold truncate ${status.slotTextSub}">${escapeHtml(status.label)}</p>
                    </div>
                </button>`;
        }

        function renderMap() {
            const mapCatSelect = document.getElementById('map-category-filter');
            if (mapCatSelect) {
                const currentMapCat = mapCatSelect.value;
                const categories = [...new Set(appState.products.map(p => p.category).filter(Boolean))].sort();
                mapCatSelect.innerHTML = '<option value="all">Todas Categorias</option>' +
                    categories.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');
                mapCatSelect.value = [...mapCatSelect.options].some(o => o.value === currentMapCat) ? currentMapCat : 'all';
            }

            const hasFilter = Boolean(
                (document.getElementById('map-search')?.value || '').trim() ||
                (document.getElementById('map-category-filter')?.value || 'all') !== 'all' ||
                (document.getElementById('map-expiry-filter')?.value || 'all') !== 'all'
            );
            const resetBtn = document.getElementById('btn-reset-map-filters');
            if (resetBtn) resetBtn.classList.toggle('hidden', !hasFilter);

            const allDefs = getAllSlotDefinitions();
            const occCount = allDefs.filter(d => {
                const s = appState.slots[d.code];
                return s && s.productId && s.qty > 0;
            }).length;
            const pill = document.getElementById('map-occupied-pill');
            if (pill) pill.textContent = `${occCount} / ${allDefs.length}`;

            document.getElementById('zone-left-slots').innerHTML =
                BASE_FLOORPLAN.left.map(s => renderSlotBox(s.code, s.label, 'w-full min-h-[62px]')).join('');

            document.getElementById('zone-back-slots').innerHTML =
                BASE_FLOORPLAN.back.map(s => renderSlotBox(s.code, s.label, 'min-h-[70px]')).join('');

            document.getElementById('zone-center-slots').innerHTML =
                BASE_FLOORPLAN.center.map(s => renderSlotBox(s.code, s.label, 'w-full min-h-[76px]')).join('');

            document.getElementById('zone-right-slots').innerHTML =
                BASE_FLOORPLAN.right.map(s => renderSlotBox(s.code, s.label, 'w-full min-h-[72px]')).join('');

            const customWrapper = document.getElementById('zone-custom-wrapper');
            const customContainer = document.getElementById('zone-custom-slots');
            if (appState.customSlots.length > 0) {
                customWrapper.classList.remove('hidden');
                customContainer.innerHTML = appState.customSlots
                    .map(s => renderSlotBox(s.code, s.label, 'min-h-[68px]'))
                    .join('');
            } else {
                customWrapper.classList.add('hidden');
            }
        }

        function renderInspector() {
            const container = document.getElementById('map-inspector');
            const allDefs = getAllSlotDefinitions();
            const def = allDefs.find(d => d.code === selectedSlotCode) || allDefs[0];
            if (!def) return;
            selectedSlotCode = def.code;

            const safeCode = escapeHtml(def.code);

            const jsCode = jsArg(def.code);
            const safeLabel = escapeHtml(def.label);
            const slot = appState.slots[def.code];
            const prod = slot && slot.productId ? appState.products.find(p => p.id === slot.productId) : null;
            const isOccupied = Boolean(prod && slot && slot.qty > 0);

            if (!isOccupied) {
                container.innerHTML = `
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Posição Selecionada</span>
                                <h3 class="text-lg font-extrabold text-slate-900">${safeCode}</h3>
                                <p class="text-xs text-slate-500">${safeLabel}</p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">Disponível</span>
                        </div>
                        <div class="py-8 text-center space-y-3 bg-slate-50 rounded-2xl border border-dashed border-slate-200 px-4">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 mx-auto flex items-center justify-center">
                                <i data-lucide="package-plus" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">Espaço Livre para Alocação</p>
                                <p class="text-[11px] text-slate-500 mt-1">Aloque um produto cadastrado nesta posição com quantidade, entrada e validade.</p>
                            </div>
                            <button onclick="openAllocateModal(${jsCode})" class="w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-[#0F294A] hover:bg-[#183B66] text-white shadow-sm">
                                + Alocar Produto em ${safeCode}
                            </button>
                        </div>
                    </div>`;
                refreshIcons();
                return;
            }

            const status = getExpiryStatus(slot.expiryDate);
            const fallbackImg = getProductFallbackImg(prod.name);
            const imgSrc = sanitizeUrl(prod.photo) || fallbackImg;
            const safeProdId = escapeHtml(prod.id);
            const jsProdId = jsArg(prod.id);
            const safeProdName = escapeHtml(prod.name);

            container.innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600">Posição Ocupada</span>
                            <h3 class="text-lg font-extrabold text-slate-900">${safeCode}</h3>
                            <p class="text-xs text-slate-500">${safeLabel}</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-md text-xs font-extrabold ${status.badgeClass}">${escapeHtml(status.shortLabel)}</span>
                    </div>

                    <div class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                        <img src="${escapeHtml(imgSrc)}" onerror="this.src='${fallbackImg}'" class="w-16 h-16 rounded-xl object-cover border border-slate-200 shrink-0" alt="${safeProdName}">
                        <div class="min-w-0">
                            <span class="text-[10px] font-bold uppercase text-indigo-600">${escapeHtml(prod.category)}</span>
                            <h4 class="text-sm font-extrabold text-slate-900 leading-snug">${safeProdName}</h4>
                            <p class="text-[11px] text-slate-500">SKU: ${escapeHtml(prod.sku)}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5 text-xs">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Quantidade</span>
                            <span class="text-base font-extrabold text-slate-900">${slot.qty} ${escapeHtml(prod.unit)}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <span class="text-[10px] font-bold uppercase text-slate-400 block">Data Entrada</span>
                            <span class="text-xs font-bold text-slate-800">${escapeHtml(formatDateBR(slot.entryDate))}</span>
                        </div>
                        <div class="col-span-2 p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-bold uppercase text-slate-400 block">Data de Vencimento</span>
                                <span class="text-xs font-extrabold text-slate-900">${escapeHtml(formatDateBR(slot.expiryDate))} <span class="font-semibold text-slate-500">(${escapeHtml(status.label)})</span></span>
                            </div>
                            <button onclick="openEditExpiryModal('slot', ${jsCode})" class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white hover:bg-indigo-50 text-indigo-600 border border-slate-200">
                                Editar
                            </button>
                        </div>
                        ${slot.lot ? `<div class="col-span-2 px-3 py-2 rounded-xl bg-indigo-50/60 border border-indigo-100 text-[11px] text-indigo-900"><strong>Lote / Obs:</strong> ${escapeHtml(slot.lot)}</div>` : ''}
                    </div>

                    <div class="grid grid-cols-2 gap-2 pt-2">
                        <button onclick="openAllocateModal(${jsCode}, ${jsProdId})" class="py-2.5 px-3 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white flex items-center justify-center gap-1.5">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>+ Adicionar</span>
                        </button>
                        <button onclick="openWithdrawModal(${jsCode})" class="py-2.5 px-3 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center gap-1.5">
                            <i data-lucide="minus" class="w-4 h-4"></i>
                            <span>Dar Baixa</span>
                        </button>
                        <button onclick="openTransferModal(${jsCode})" class="py-2.5 px-3 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-800 flex items-center justify-center gap-1.5">
                            <i data-lucide="arrow-right-left" class="w-3.5 h-3.5"></i>
                            <span>Transferir</span>
                        </button>
                        <button onclick="clearSingleSlot(${jsCode})" class="py-2.5 px-3 rounded-xl text-xs font-bold bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 flex items-center justify-center gap-1.5">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            <span>Esvaziar</span>
                        </button>
                    </div>
                </div>`;
            refreshIcons();
        }

        // =========================================================================
        // ABA 3: CATÁLOGO DE PRODUTOS
        // =========================================================================

        function resetCatalogFilters() {
            document.getElementById('catalog-search').value = '';
            document.getElementById('catalog-category-filter').value = 'all';
            document.getElementById('catalog-status-filter').value = 'all';
            document.getElementById('catalog-sort').value = 'name-asc';
            renderCatalog();
        }

        function renderCatalog() {
            const grid = document.getElementById('catalog-grid');
            const search = (document.getElementById('catalog-search').value || '').toLowerCase().trim();
            const catFilter = document.getElementById('catalog-category-filter');
            const statusFilter = (document.getElementById('catalog-status-filter')?.value) || 'all';
            const sortMode = (document.getElementById('catalog-sort')?.value) || 'name-asc';
            const currentCat = catFilter.value;

            const categories = [...new Set(appState.products.map(p => p.category).filter(Boolean))].sort();
            catFilter.innerHTML = '<option value="all">Todas as Categorias</option>' +
                categories.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');
            catFilter.value = [...catFilter.options].some(o => o.value === currentCat) ? currentCat : 'all';

            const enriched = appState.products.map(prod => {
                const slotLots = Object.entries(appState.slots)
                    .filter(([, s]) => s && s.productId === prod.id && s.qty > 0)
                    .map(([code, s]) => ({ code, ...s, days: getDaysUntilExpiry(s.expiryDate) }));

                const generalLots = (appState.unallocatedLots || [])
                    .filter(u => u && u.productId === prod.id && u.qty > 0)
                    .map(u => ({ code: 'Geral', ...u, days: getDaysUntilExpiry(u.expiryDate) }));

                const prodSlots = [...slotLots, ...generalLots].sort((a, b) => a.days - b.days);

                const totalQty = prodSlots.reduce((sum, s) => sum + s.qty, 0);
                const nearestSlot = prodSlots[0] || null;
                const effectiveExpiryDate = nearestSlot ? nearestSlot.expiryDate : (prod.defaultExpiry || '');
                const effectiveDays = effectiveExpiryDate ? getDaysUntilExpiry(effectiveExpiryDate) : 99999;

                return {
                    prod,
                    totalQty,
                    nearestSlot,
                    effectiveExpiryDate,
                    effectiveDays
                };
            });

            const filtered = sortProductItems(
                enriched.filter(item => {
                    const { prod, totalQty, effectiveDays, effectiveExpiryDate } = item;
                    if (catFilter.value !== 'all' && prod.category !== catFilter.value) return false;
                    if (search && !prod.name.toLowerCase().includes(search) && !prod.sku.toLowerCase().includes(search)) return false;

                    if (statusFilter === 'in-stock' && totalQty <= 0) return false;
                    if (statusFilter === 'out-of-stock' && totalQty > 0) return false;
                    if (statusFilter === 'expired' && !(effectiveDays < 0)) return false;
                    if (statusFilter === 'expiring-30' && !(effectiveDays >= 0 && effectiveDays <= 30)) return false;
                    if (statusFilter === 'ok' && !(effectiveDays > 30 && effectiveDays < 9999)) return false;
                    if (statusFilter === 'none' && Boolean(effectiveExpiryDate)) return false;

                    return true;
                }),
                sortMode
            );

            const countEl = document.getElementById('catalog-results-count');
            if (countEl) {
                countEl.textContent = `${filtered.length} ${filtered.length === 1 ? 'produto' : 'produtos'}`;
            }

            if (filtered.length === 0) {
                grid.innerHTML = `
                    <div class="col-span-full bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center space-y-3">
                        <p class="text-sm font-bold text-slate-700">Nenhum produto encontrado</p>
                        <p class="text-xs text-slate-500">Ajuste os filtros acima ou cadastre um novo produto com foto, estoque mínimo e validade.</p>
                        <div class="flex justify-center gap-2">
                            <button onclick="resetCatalogFilters()" class="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700">Limpar Filtros</button>
                            <button onclick="openProductModal()" class="px-4 py-2 rounded-xl text-xs font-bold bg-[#0F294A] text-white">+ Cadastrar Produto</button>
                        </div>
                    </div>`;
                return;
            }

            grid.innerHTML = filtered.map(({ prod, totalQty, nearestSlot }) => {
                const fallbackImg = getProductFallbackImg(prod.name);
                const imgSrc = sanitizeUrl(prod.photo) || fallbackImg;
                const expDateToShow = nearestSlot ? nearestSlot.expiryDate : prod.defaultExpiry;
                const expStatus = expDateToShow ? getExpiryStatus(expDateToShow) : null;
                const safeProdId = escapeHtml(prod.id);
                const jsProdId = jsArg(prod.id);
                const safeProdName = escapeHtml(prod.name);
                const safeUnit = escapeHtml(prod.unit);

                return `
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-2xs hover:shadow-md transition-shadow flex flex-col justify-between gap-3">
                        <div>
                            <div class="flex items-start gap-3">
                                <img src="${escapeHtml(imgSrc)}" onerror="this.src='${fallbackImg}'" class="w-14 h-14 rounded-xl object-cover border border-slate-200 shrink-0" alt="${safeProdName}">
                                <div class="min-w-0 flex-1">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600">${escapeHtml(prod.category)}</span>
                                    <h3 class="text-sm font-bold text-slate-900 leading-snug">${safeProdName}</h3>
                                    <p class="text-[11px] text-slate-500">SKU: ${escapeHtml(prod.sku)} • Mín: ${prod.minStock} ${safeUnit}</p>
                                </div>
                            </div>

                            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                                <div>
                                    <span class="text-[10px] text-slate-400 block">Saldo Total</span>
                                    <span class="font-extrabold text-slate-900">${totalQty} ${safeUnit}</span>
                                </div>
                                <div class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <span class="text-[10px] text-slate-400">${nearestSlot ? `Próx. Venc. (${escapeHtml(nearestSlot.code)})` : 'Validade Padrão'}</span>
                                        <button onclick="openEditExpiryModal('product', ${jsProdId})" title="Editar validade padrão" class="text-indigo-600 hover:text-indigo-800">
                                            <i data-lucide="calendar" class="w-3 h-3"></i>
                                        </button>
                                    </div>
                                    ${expStatus
                                        ? `<span class="inline-block mt-0.5 px-2 py-0.5 rounded-md text-[10px] font-bold ${expStatus.badgeClass}">${escapeHtml(formatDateBR(expDateToShow))}</span>`
                                        : '<span class="text-[11px] text-slate-400">Não definida</span>'}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-1.5 pt-2 border-t border-slate-100">
                            <button onclick="openAllocateModal('', ${jsProdId})" class="flex-1 py-2 px-3 rounded-xl text-xs font-bold bg-[#0F294A] hover:bg-[#183B66] text-white">
                                + Alocar
                            </button>
                            <button onclick="openProductModal(${jsProdId})" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700" title="Editar Produto">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <button onclick="deleteProduct(${jsProdId})" class="admin-only p-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600" title="Excluir Produto">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>`;
            }).join('');
        }

        // =========================================================================
        // ABA 4: HISTÓRICO
        // =========================================================================

        function renderHistory() {
            const tbody = document.getElementById('history-tbody');
            const filter = document.getElementById('history-type-filter').value;
            const list = appState.history.filter(h => filter === 'all' || h.type === filter);

            if (list.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="py-8 px-4 text-center text-slate-400">Nenhuma movimentação registrada.</td></tr>';
                return;
            }

            const badges = {
                ENTRADA: 'bg-emerald-100 text-emerald-800',
                SAIDA: 'bg-rose-100 text-rose-800',
                TRANSFERENCIA: 'bg-sky-100 text-sky-800',
                VALIDADE: 'bg-amber-100 text-amber-800',
                CADASTRO: 'bg-indigo-100 text-indigo-800',
                EDICAO: 'bg-violet-100 text-violet-800',
                EXCLUSAO: 'bg-red-100 text-red-800',
                SISTEMA: 'bg-slate-200 text-slate-800'
            };

            tbody.innerHTML = list.map(h => `
                <tr class="hover:bg-slate-50">
                    <td class="py-3 px-4 text-slate-500 whitespace-nowrap">${escapeHtml(h.timestamp)}</td>
                    <td class="py-3 px-4"><span class="px-2 py-0.5 rounded-md text-[10px] font-bold ${badges[h.type] || 'bg-slate-100 text-slate-700'}">${escapeHtml(h.type)}</span></td>
                    <td class="py-3 px-4 font-bold text-slate-900">${escapeHtml(h.productName)}</td>
                    <td class="py-3 px-4 font-extrabold text-indigo-700">${escapeHtml(h.slotCode)}</td>
                    <td class="py-3 px-4 font-semibold text-slate-700">${escapeHtml(h.qtyText)}</td>
                    <td class="py-3 px-4 text-slate-500">${escapeHtml(h.note)}</td>
                    <td class="py-3 px-4 font-semibold text-slate-700 whitespace-nowrap">${h.user ? '@' + escapeHtml(h.user) : '<span class="text-slate-400">—</span>'}</td>
                </tr>`).join('');
        }

        function clearHistoryLog() {
            if (!requireAdminAction()) return;
            openConfirmDialog('Limpar Histórico', 'Deseja apagar todo o registro de auditoria? A limpeza ficará registrada com o seu usuário.', () => {
                runOperation('clear_history');
            });
        }

        // =========================================================================
        // AÇÕES DE MODAIS E FORMULÁRIOS
        // =========================================================================

        // Sugestões de armazenamento por categoria
        const CATEGORY_STORAGE_HINTS = {
            'Bebidas':                  { storage: '', perecivel: false, hint: 'Sucos e laticínios devem ser refrigerados após abertos.' },
            'Farinhas & Amidos':        { storage: 'Local seco e arejado', perecivel: false, hint: 'Armazenar em local fresco e seco, longe de umidade.' },
            'Doces & Chocolates':       { storage: 'Local seco e arejado', perecivel: false, hint: 'Evitar calor excessivo para não derreter chocolates.' },
            'Grãos & Cereais':          { storage: 'Local seco e arejado', perecivel: false, hint: 'Fechar bem após abrir. Verificar presença de insetos.' },
            'Temperos & Condimentos':   { storage: 'Local seco e arejado', perecivel: false, hint: 'Óleos: proteger da luz solar. Molhos: refrigerar após abrir.' },
            'Laticínios & Ovos':        { storage: 'Refrigerado (2–8 °C)', perecivel: true,  hint: 'Produto perecível. Manter sempre refrigerado. FEFO obrigatório.' },
            'Legumes & Verduras':       { storage: 'Refrigerado (2–8 °C)', perecivel: true,  hint: 'Alta perecibilidade. Priorizar saída dos lotes mais antigos.' },
            'Descartáveis & Embalagens':{ storage: '', perecivel: false, hint: 'Sem validade em geral. Manter em local limpo e seco.' },
            'Higiene & Limpeza':        { storage: 'Local seco e arejado', perecivel: false, hint: 'Álcool e produtos inflamáveis: longe de fontes de calor.' },
            'Material de Escritório':   { storage: '', perecivel: false, hint: 'Papel: proteger de umidade. Sem validade de consumo.' },
        };

        // Classificação automática: palavras-chave (sem acento, minúsculas) → categoria.
        // A ordem importa: a primeira categoria com palavra encontrada vence.
        const CATEGORY_KEYWORDS = [
            ['Laticínios & Ovos', ['leite', 'queijo', 'iogurte', 'manteiga', 'margarina', 'requeijao', 'creme de leite', 'nata', 'ovo', 'ovos', 'mussarela', 'muçarela', 'parmesao', 'ricota', 'coalhada', 'leite condensado']],
            ['Doces & Chocolates', ['chocolate', 'achocolatado', 'cacau', 'doce', 'bala', 'balas', 'bombom', 'goiabada', 'brigadeiro', 'granulado', 'acucar', 'mel', 'geleia', 'biscoito', 'bolacha', 'wafer', 'sobremesa', 'gelatina', 'pudim', 'cobertura', 'confeito', 'paçoca', 'pacoca']],
            ['Bebidas', ['suco', 'refrigerante', 'agua mineral', 'agua', 'cafe', 'cha', 'cerveja', 'vinho', 'refresco', 'energetico', 'isotonico', 'bebida', 'guarana', 'coca', 'nectar', 'xarope']],
            ['Grãos & Cereais', ['feijao', 'arroz', 'lentilha', 'grao de bico', 'grao', 'graos', 'ervilha seca', 'milho', 'pipoca', 'aveia', 'granola', 'cereal', 'cereais', 'soja', 'quinoa', 'linhaca', 'chia', 'cevada', 'trigo em grao', 'canjica', 'amendoim', 'castanha']],
            ['Farinhas & Amidos', ['farinha', 'fuba', 'amido', 'maisena', 'polvilho', 'tapioca', 'goma', 'fecula', 'farelo', 'massa', 'macarrao', 'espaguete', 'lasanha', 'fermento', 'mistura para bolo', 'empanar', 'rosca']],
            ['Temperos & Condimentos', ['sal', 'pimenta', 'oregano', 'tempero', 'caldo', 'cominho', 'colorau', 'canela', 'cravo', 'louro', 'paprica', 'curry', 'açafrao', 'acafrao', 'vinagre', 'oleo', 'azeite', 'molho', 'catchup', 'ketchup', 'maionese', 'mostarda', 'shoyu', 'extrato de tomate', 'condimento', 'alho em po', 'noz moscada']],
            ['Legumes & Verduras', ['tomate', 'cebola', 'alho', 'batata', 'cenoura', 'abobora', 'abobrinha', 'chuchu', 'beterraba', 'pepino', 'pimentao', 'alface', 'couve', 'repolho', 'brocolis', 'espinafre', 'rucula', 'agriao', 'mandioca', 'aipim', 'inhame', 'berinjela', 'quiabo', 'vagem', 'legume', 'legumes', 'verdura', 'verduras', 'salsa', 'cebolinha', 'coentro', 'cheiro verde', 'banana', 'maca', 'laranja', 'limao', 'fruta', 'frutas', 'mamao', 'melancia', 'abacaxi', 'uva', 'morango']],
            ['Higiene & Limpeza', ['sabao', 'sabonete', 'detergente', 'desinfetante', 'agua sanitaria', 'alvejante', 'cloro', 'alcool', 'amaciante', 'esponja', 'shampoo', 'xampu', 'condicionador', 'creme dental', 'pasta de dente', 'escova', 'papel higienico', 'limpador', 'multiuso', 'lustra moveis', 'vassoura', 'rodo', 'pano', 'flanela', 'luva', 'luvas', 'desengordurante', 'higienizador', 'sanitizante', 'limpeza', 'saco de lixo', 'saco lixo']],
            ['Descartáveis & Embalagens', ['descartavel', 'descartaveis', 'copo', 'copos', 'prato', 'pratos', 'talher', 'talheres', 'garfo', 'faca', 'colher', 'guardanapo', 'canudo', 'marmitex', 'embalagem', 'embalagens', 'pote', 'potes', 'filme plastico', 'papel aluminio', 'papel manteiga', 'sacola', 'saco', 'sacos', 'bandeja', 'touca', 'mascara', 'isopor', 'tampa', 'papel toalha']],
            ['Material de Escritório', ['caneta', 'lapis', 'borracha', 'papel a4', 'papel sulfite', 'sulfite', 'caderno', 'grampo', 'grampeador', 'clipe', 'clips', 'pasta', 'envelope', 'fita adesiva', 'durex', 'tesoura', 'cola', 'marca texto', 'pincel', 'post it', 'bloco de notas', 'etiqueta', 'toner', 'cartucho', 'regua', 'calculadora', 'escritorio']],
        ];

        function normalizeForMatch(text) {
            return ' ' + String(text || '').toLowerCase()
                .normalize('NFD').replace(/[̀-ͯ]/g, '')
                .replace(/[^a-z0-9]+/g, ' ').trim() + ' ';
        }

        const CATEGORY_KEYWORDS_NORM = CATEGORY_KEYWORDS.map(([cat, words]) =>
            [cat, words.map(w => normalizeForMatch(w))]);

        // Retorna a categoria sugerida para o nome do produto, ou '' se nenhuma regra casar.
        // Vence a palavra-chave que aparece primeiro no nome (o substantivo principal em pt-BR:
        // "Suco de laranja" → Bebidas, "Farinha de milho" → Farinhas); no empate, a mais longa
        // ("creme dental" antes de "creme").
        function classifyProductCategory(name) {
            const text = normalizeForMatch(name);
            if (text.trim() === '') return '';
            let best = '', bestPos = Infinity, bestLen = 0;
            for (const [cat, words] of CATEGORY_KEYWORDS_NORM) {
                for (const w of words) {
                    // casa palavra inteira, aceitando plural simples ("ovos", "copos")
                    let pos = text.indexOf(w);
                    if (pos < 0) pos = text.indexOf(w.replace(/ $/, 's '));
                    if (pos < 0) continue;
                    if (pos < bestPos || (pos === bestPos && w.length > bestLen)) {
                        best = cat; bestPos = pos; bestLen = w.length;
                    }
                }
            }
            return best;
        }

        // ---------------------------------------------------------------------
        // Aprendizado com o catálogo: usa as categorias que as pessoas já escolheram
        // para produtos parecidos. Produtos em "Geral" não ensinam nada (são os sem categoria).
        // ---------------------------------------------------------------------
        const NAME_STOPWORDS = new Set(['de', 'da', 'do', 'das', 'dos', 'com', 'sem', 'para', 'em', 'e', 'a', 'o', 'tipo',
            'kg', 'g', 'gr', 'mg', 'l', 'lt', 'ml', 'un', 'und', 'unid', 'cx', 'pct', 'pc', 'fd', 'm', 'cm', 'mm']);

        // Palavras relevantes do nome, na ordem: "Feijão Carioca 1kg" → ['feijao', 'carioca']
        function nameTokens(name) {
            return normalizeForMatch(name).trim().split(' ')
                .filter(t => t.length >= 2 && !/^\d/.test(t) && !NAME_STOPWORDS.has(t))
                .map(t => (t.length > 3 && t.endsWith('s')) ? t.slice(0, -1) : t); // plural simples
        }

        function buildCatalogIndex(excludeId = '') {
            const exact = new Map();   // nome completo → categoria
            const head = new Map();    // 1ª palavra → {categoria: [nomes]}
            const any = new Map();     // qualquer palavra → {categoria: quantidade}
            for (const p of appState.products || []) {
                const cat = String(p.category || '').trim();
                if (!cat || cat === 'Geral' || cat === 'Outra' || p.id === excludeId) continue;
                const toks = nameTokens(p.name);
                if (toks.length === 0) continue;
                exact.set(toks.join(' '), { cat, from: p.name });
                const h = head.get(toks[0]) || {};
                (h[cat] = h[cat] || []).push(p.name);
                head.set(toks[0], h);
                for (const t of new Set(toks)) {
                    const a = any.get(t) || {};
                    a[cat] = (a[cat] || 0) + 1;
                    any.set(t, a);
                }
            }
            return { exact, head, any };
        }

        // Categoria majoritária de um contador, se dominar (≥ 70%) — evita palpites em palavras ambíguas.
        function dominantCategory(counts) {
            const entries = Object.entries(counts).map(([c, v]) => [c, Array.isArray(v) ? v.length : v]);
            const total = entries.reduce((s, [, n]) => s + n, 0);
            entries.sort((x, y) => y[1] - x[1]);
            return (entries.length && entries[0][1] / total >= 0.7) ? entries[0][0] : '';
        }

        // Sugere a categoria de um nome. Ordem de confiança:
        //  1. mesmo nome já cadastrado; 2. mesma palavra principal (1ª palavra) no catálogo;
        //  3. lista de palavras-chave; 4. outras palavras em comum com o catálogo; 5. "Geral".
        // Retorna { category, reason } (reason explica a origem para mostrar na tela).
        function suggestCategory(name, { excludeId = '', index = null } = {}) {
            const toks = nameTokens(name);
            if (toks.length === 0) return { category: 'Geral', reason: '' };
            const idx = index || buildCatalogIndex(excludeId);

            const ex = idx.exact.get(toks.join(' '));
            if (ex) return { category: ex.cat, reason: `igual a "${ex.from}" já cadastrado` };

            const h = idx.head.get(toks[0]);
            if (h) {
                const cat = dominantCategory(h);
                if (cat) return { category: cat, reason: `parecido com "${h[cat][0]}" já cadastrado` };
            }

            const kw = classifyProductCategory(name);
            if (kw) return { category: kw, reason: 'pelas palavras do nome' };

            const score = {};
            toks.forEach((t, i) => {
                const a = idx.any.get(t);
                const cat = a && dominantCategory(a);
                if (cat) score[cat] = (score[cat] || 0) + 1 / (i + 1); // palavras do início pesam mais
            });
            const bestLearned = Object.entries(score).sort((x, y) => y[1] - x[1])[0];
            if (bestLearned) return { category: bestLearned[0], reason: 'pelo histórico de produtos parecidos' };

            return { category: 'Geral', reason: 'nenhuma categoria clara' };
        }

        // Ao digitar o nome, escolhe a categoria sozinho — a menos que o usuário já tenha escolhido manualmente.
        function onProductNameInput() {
            const catSelect = document.getElementById('prod-category');
            const autoNote = document.getElementById('prod-category-auto');
            if (catSelect.dataset.userEdited) return;
            const name = document.getElementById('prod-name').value.trim();
            // Campo vazio volta para "Selecione".
            const sug = name ? suggestCategory(name, { excludeId: document.getElementById('prod-id').value })
                             : { category: '', reason: '' };
            if (autoNote) {
                autoNote.textContent = sug.category ? `Categoria automática: ${sug.reason}.` : '';
                autoNote.classList.toggle('hidden', !sug.category);
            }

            const known = Array.from(catSelect.options).some(o => o.value === sug.category && o.value !== 'Outra');
            const customInput = document.getElementById('prod-custom-category');
            if (known || !sug.category) {
                if (catSelect.value === sug.category) return;
                catSelect.value = sug.category;
                onCategoryChange({ auto: true });
            } else {
                // Categoria personalizada aprendida do catálogo (ex.: "Ferragens")
                if (catSelect.value === 'Outra' && customInput.value === sug.category) return;
                catSelect.value = 'Outra';
                onCategoryChange({ auto: true });
                customInput.value = sug.category;
            }
        }

        function onCategoryChange({ auto = false } = {}) {
            const catSelect = document.getElementById('prod-category');
            const customWrap = document.getElementById('prod-custom-category-wrap');
            const customInput = document.getElementById('prod-custom-category');
            const storageEl = document.getElementById('prod-storage');
            const hintWrap = document.getElementById('prod-category-hint');
            const hintText = document.getElementById('prod-category-hint-text');
            const periEl = document.getElementById('prod-flag-perecivel');

            const val = catSelect.value;
            const isCustom = val === 'Outra';
            customWrap.classList.toggle('hidden', !isCustom);
            if (isCustom) {
                customInput.required = true;
                if (!auto) customInput.focus(); // não rouba o foco de quem está digitando o nome
            } else {
                customInput.required = false;
                customInput.value = '';
            }

            const hint = CATEGORY_STORAGE_HINTS[val];
            if (hint) {
                if (storageEl && !storageEl.dataset.userEdited) storageEl.value = hint.storage;
                if (periEl) periEl.checked = hint.perecivel;
                if (hintWrap && hintText) {
                    hintText.textContent = hint.hint;
                    hintWrap.classList.remove('hidden');
                }
            } else {
                if (hintWrap) hintWrap.classList.add('hidden');
            }

            // Atualiza prefixo SKU sugerido
            const skuPrefixes = {
                'Bebidas': 'BEB', 'Farinhas & Amidos': 'FAR', 'Doces & Chocolates': 'DOC',
                'Grãos & Cereais': 'GRA', 'Temperos & Condimentos': 'TMP', 'Laticínios & Ovos': 'LAT',
                'Legumes & Verduras': 'LEG', 'Descartáveis & Embalagens': 'DES',
                'Higiene & Limpeza': 'HIG', 'Material de Escritório': 'ESC'
            };
            const skuEl = document.getElementById('prod-sku');
            if (skuEl && !document.getElementById('prod-id').value) {
                const prefix = skuPrefixes[val] || 'PRD';
                const count = appState.products.filter(p => p.category === val).length + 1;
                skuEl.value = `${prefix}-${String(count).padStart(3, '0')}`;
            }
        }

        function openProductModal(productId = '') {
            const prod = productId ? appState.products.find(p => p.id === productId) : null;
            const isEdit = Boolean(prod);

            document.getElementById('modal-product-title').textContent = isEdit ? 'Editar Produto' : 'Cadastrar Novo Produto';
            document.getElementById('modal-product-subtitle').textContent = isEdit
                ? `Editando: ${prod.name}`
                : 'Preencha as informações do produto abaixo';
            document.getElementById('prod-submit-label').textContent = isEdit ? 'Salvar Alterações' : 'Salvar Produto';
            document.getElementById('prod-id').value = prod ? prod.id : '';
            document.getElementById('prod-name').value = prod ? prod.name : '';
            document.getElementById('prod-sku').value = prod ? prod.sku : '';

            // Categoria
            const catSelect = document.getElementById('prod-category');
            const customWrap = document.getElementById('prod-custom-category-wrap');
            const customInput = document.getElementById('prod-custom-category');
            if (prod) {
                const knownCats = Array.from(catSelect.options).map(o => o.value);
                if (knownCats.includes(prod.category)) {
                    catSelect.value = prod.category;
                    customWrap.classList.add('hidden');
                } else {
                    catSelect.value = 'Outra';
                    customWrap.classList.remove('hidden');
                    customInput.value = prod.category;
                    customInput.required = true;
                }
            } else {
                catSelect.value = '';
                customWrap.classList.add('hidden');
                customInput.value = '';
                customInput.required = false;
            }

            document.getElementById('prod-unit').value = prod ? prod.unit : 'un';
            document.getElementById('prod-min-stock').value = prod ? prod.minStock : 5;
            document.getElementById('prod-max-stock').value = prod ? (prod.maxStock || '') : '';
            document.getElementById('prod-default-expiry').value = prod ? (prod.defaultExpiry || '') : '';
            document.getElementById('prod-storage').value = prod ? (prod.storage || '') : '';
            document.getElementById('prod-storage').dataset.userEdited = prod && prod.storage ? '1' : '';
            document.getElementById('prod-flag-perecivel').checked = prod ? Boolean(prod.flagPerecivel) : false;
            document.getElementById('prod-flag-epi').checked = prod ? Boolean(prod.flagEpi) : false;
            document.getElementById('prod-flag-controlado').checked = prod ? Boolean(prod.flagControlado) : false;
            const obsEl = document.getElementById('prod-observations');
            if (obsEl) obsEl.value = prod ? (prod.observations || '') : '';
            document.getElementById('prod-category-hint').classList.add('hidden');
            const autoNote = document.getElementById('prod-category-auto');
            autoNote.textContent = '';
            autoNote.classList.add('hidden');

            // Preview de validade
            const expiryInput = document.getElementById('prod-default-expiry');
            expiryInput.oninput = () => {
                const st = getExpiryStatus(expiryInput.value);
                const preview = document.getElementById('prod-expiry-preview');
                if (preview) preview.textContent = expiryInput.value ? st.label : '';
            };

            // Marca storage como editado pelo usuário se ele mudar
            const storageEl = document.getElementById('prod-storage');
            storageEl.onchange = () => { storageEl.dataset.userEdited = '1'; };

            // Na edição, a categoria salva é respeitada; em novo produto, ela segue o nome digitado.
            catSelect.dataset.userEdited = prod ? '1' : '';
            if (!prod) {
                // Para novo produto, sugere SKU só depois de escolher categoria
                catSelect.value = '';
            }

            openModal('modal-product');
        }

        async function saveProductForm(e) {
            e.preventDefault();
            const form = e.target;
            const id = document.getElementById('prod-id').value;
            const catSelect = document.getElementById('prod-category');
            const rawCat = catSelect.value;
            const finalCategory = rawCat === 'Outra'
                ? (document.getElementById('prod-custom-category').value.trim() || 'Geral')
                : rawCat;

            // Monta flags como tags de observação extras
            const flagPerecivel = document.getElementById('prod-flag-perecivel').checked;
            const flagEpi = document.getElementById('prod-flag-epi').checked;
            const flagControlado = document.getElementById('prod-flag-controlado').checked;

            const minRaw = document.getElementById('prod-min-stock').value.trim();
            const maxRaw = document.getElementById('prod-max-stock').value.trim();
            const data = {
                id,
                name: document.getElementById('prod-name').value.trim(),
                sku: document.getElementById('prod-sku').value.trim().toUpperCase(),
                category: finalCategory,
                unit: document.getElementById('prod-unit').value,
                minStock: minRaw === '' ? 0 : Number(minRaw),
                maxStock: maxRaw === '' ? null : Number(maxRaw),
                defaultExpiry: document.getElementById('prod-default-expiry').value || '',
                storage: document.getElementById('prod-storage').value || '',
                flagPerecivel,
                flagEpi,
                flagControlado,
                observations: (document.getElementById('prod-observations')?.value || '').trim()
            };

            // Validação imediata (o servidor valida tudo novamente)
            const fail = (msg, fieldId) => {
                showToast(msg, 'error');
                if (fieldId) document.getElementById(fieldId)?.focus();
            };
            if (data.name.length < 2) return fail('Informe o nome do produto (mínimo 2 caracteres).', 'prod-name');
            if (!data.sku) return fail('Informe o SKU do produto.', 'prod-sku');
            if (!/^[A-Z0-9][A-Z0-9._\/-]{0,31}$/.test(data.sku)) return fail('SKU inválido: use letras, números, ponto, hífen, barra ou sublinhado (até 32).', 'prod-sku');
            const skuOwner = appState.products.find(p => p.id !== id && String(p.sku).toUpperCase() === data.sku);
            if (skuOwner) return fail(`O SKU ${data.sku} já está em uso pelo produto "${skuOwner.name}".`, 'prod-sku');
            if (!data.category) return fail('Selecione a categoria do produto.', 'prod-category');
            if (!Number.isInteger(data.minStock) || data.minStock < 0) return fail('O estoque mínimo deve ser um número inteiro maior ou igual a zero.', 'prod-min-stock');
            if (data.maxStock !== null && (!Number.isInteger(data.maxStock) || data.maxStock < 1)) return fail('O estoque máximo deve ser um número inteiro maior que zero (ou deixe em branco).', 'prod-max-stock');
            if (data.maxStock !== null && data.maxStock < data.minStock) return fail('O estoque máximo não pode ser menor que o estoque mínimo.', 'prod-max-stock');

            const ok = await runOperation('product_save', data, { form });
            if (ok) closeModal('modal-product');
        }

        function deleteProduct(productId) {
            if (!requireAdminAction()) return;
            const prod = appState.products.find(p => p.id === productId);
            if (!prod) return;
            const balance = getProductBalance(productId);
            const extra = balance > 0 ? ` O saldo atual de ${balance} ${prod.unit} também será removido.` : '';
            openConfirmDialog('Excluir Produto', `Remover "${prod.name}" do catálogo e do estoque?${extra}`, () => {
                runOperation('product_delete', { id: productId });
            });
        }

        function openAllocateModal(preSlotCode = '', preProductId = '') {
            if (appState.products.length === 0) {
                showToast('Cadastre um produto primeiro antes de alocar.', 'info');
                openProductModal();
                return;
            }

            const prodSelect = document.getElementById('alloc-product');
            prodSelect.innerHTML = appState.products
                .map(p => `<option value="${escapeHtml(p.id)}">${escapeHtml(p.name)} (${escapeHtml(p.sku)})</option>`)
                .join('');
            if (preProductId && appState.products.some(p => p.id === preProductId)) {
                prodSelect.value = preProductId;
            }

            const slotSelect = document.getElementById('alloc-slot');
            slotSelect.innerHTML = getAllSlotDefinitions()
                .map(s => {
                    const occ = appState.slots[s.code];
                    const occProd = occ ? appState.products.find(p => p.id === occ.productId) : null;
                    const suffix = occProd ? ` [Ocupado: ${occProd.name} - ${occ.qty}]` : ' [Livre]';
                    return `<option value="${escapeHtml(s.code)}">${escapeHtml(s.code)} — ${escapeHtml(s.label)}${escapeHtml(suffix)}</option>`;
                })
                .join('');
            if (preSlotCode) slotSelect.value = preSlotCode;

            document.getElementById('alloc-qty').value = 1;
            document.getElementById('alloc-entry').value = todayISO();
            document.getElementById('alloc-lot').value = '';
            onAllocateProductChange();
            openModal('modal-allocate');
        }

        function onAllocateProductChange() {
            const prodId = document.getElementById('alloc-product').value;
            const prod = appState.products.find(p => p.id === prodId);
            const expiryInput = document.getElementById('alloc-expiry');
            if (prod && prod.defaultExpiry) {
                expiryInput.value = prod.defaultExpiry;
            } else if (!expiryInput.value) {
                expiryInput.value = addDaysISO(60);
            }
            updateAllocateExpiryPreview();
        }

        function updateAllocateExpiryPreview() {
            const val = document.getElementById('alloc-expiry').value;
            const el = document.getElementById('alloc-expiry-preview');
            if (!val || !el) return;
            const st = getExpiryStatus(val);
            el.textContent = st.label;
        }

        /** Lê uma quantidade inteira positiva de um campo; retorna null se inválida. */
        function readPositiveInt(inputId) {
            const raw = String(document.getElementById(inputId).value).trim();
            if (!/^\d+$/.test(raw)) return null;
            const n = Number(raw);
            return n >= 1 && n <= 1000000 ? n : null;
        }

        async function commitAllocate(form, payload) {
            const ok = await runOperation('stock_allocate', payload, { form });
            if (ok) {
                selectedSlotCode = payload.slotCode;
                closeModal('modal-allocate');
                renderAll();
            }
        }

        function saveAllocateForm(e) {
            e.preventDefault();
            const form = e.target;
            const productId = document.getElementById('alloc-product').value;
            const slotCode = document.getElementById('alloc-slot').value;
            const qty = readPositiveInt('alloc-qty');
            const entryDate = document.getElementById('alloc-entry').value;
            const expiryDate = document.getElementById('alloc-expiry').value;
            const lot = document.getElementById('alloc-lot').value.trim();

            if (!productId || !slotCode) return showToast('Selecione o produto e a posição.', 'error');
            if (qty === null) return showToast('Informe uma quantidade inteira entre 1 e 1.000.000.', 'error');
            if (expiryDate && entryDate && expiryDate < entryDate) {
                showToast('Atenção: a validade informada é anterior à data de entrada.', 'info');
            }

            const payload = { productId, slotCode, qty, entryDate, expiryDate, lot, displace: false };
            const existing = appState.slots[slotCode];
            if (existing && existing.productId && existing.productId !== productId && existing.qty > 0) {
                const occProd = appState.products.find(p => p.id === existing.productId);
                const occName = occProd ? occProd.name : 'outro item';
                openConfirmDialog(
                    'Posição Ocupada',
                    `A posição ${slotCode} já está ocupada por "${occName}" (${existing.qty} ${occProd?.unit || 'un'}). Deseja movê-lo para o Estoque Geral e alocar o novo item aqui?`,
                    () => commitAllocate(form, { ...payload, displace: true })
                );
                return;
            }

            commitAllocate(form, payload);
        }

        function openWithdrawModal(slotCode) {
            const slot = appState.slots[slotCode];
            const prod = slot ? appState.products.find(p => p.id === slot.productId) : null;
            if (!slot || !prod) return;

            document.getElementById('withdraw-slot').value = slotCode;
            document.getElementById('withdraw-info-title').textContent = `${slotCode} — ${prod.name}`;
            document.getElementById('withdraw-info-sub').textContent = `Saldo atual nesta posição: ${slot.qty} ${prod.unit}`;
            const qtyInput = document.getElementById('withdraw-qty');
            qtyInput.max = slot.qty;
            qtyInput.value = 1;
            document.getElementById('withdraw-note').value = '';
            openModal('modal-withdraw');
        }

        async function saveWithdrawForm(e) {
            e.preventDefault();
            const slotCode = document.getElementById('withdraw-slot').value;
            const qty = readPositiveInt('withdraw-qty');
            const note = document.getElementById('withdraw-note').value.trim();

            const slot = appState.slots[slotCode];
            const prod = slot ? appState.products.find(p => p.id === slot.productId) : null;
            if (!slot || !prod) {
                showToast(`A posição ${slotCode} já está vazia.`, 'error');
                closeModal('modal-withdraw');
                return;
            }
            if (qty === null) return showToast('Informe uma quantidade inteira maior que zero.', 'error');
            if (qty > slot.qty) {
                showToast(`Quantidade maior que o saldo da posição (${slot.qty} ${prod.unit}).`, 'error');
                document.getElementById('withdraw-qty').focus();
                return;
            }

            const ok = await runOperation('stock_withdraw', { slotCode, qty, note }, { form: e.target });
            if (ok) closeModal('modal-withdraw');
        }

        function openTransferModal(fromSlotCode) {
            const slot = appState.slots[fromSlotCode];
            const prod = slot ? appState.products.find(p => p.id === slot.productId) : null;
            if (!slot || !prod) return;

            document.getElementById('transfer-from-slot').value = fromSlotCode;
            document.getElementById('transfer-info-title').textContent = `Origem: ${fromSlotCode} — ${prod.name}`;
            document.getElementById('transfer-info-sub').textContent = `Disponível: ${slot.qty} ${prod.unit}`;

            const toSelect = document.getElementById('transfer-to-slot');
            toSelect.innerHTML = getAllSlotDefinitions()
                .filter(d => d.code !== fromSlotCode)
                .map(d => {
                    const occ = appState.slots[d.code];
                    return `<option value="${escapeHtml(d.code)}">${escapeHtml(d.code)} — ${escapeHtml(d.label)}${occ ? ' [Ocupado]' : ' [Livre]'}</option>`;
                })
                .join('');

            const qtyInput = document.getElementById('transfer-qty');
            qtyInput.max = slot.qty;
            qtyInput.value = slot.qty;
            openModal('modal-transfer');
        }

        async function commitTransfer(form, payload) {
            const ok = await runOperation('stock_transfer', payload, { form });
            if (ok) {
                selectedSlotCode = payload.toCode;
                closeModal('modal-transfer');
                renderAll();
            }
        }

        function saveTransferForm(e) {
            e.preventDefault();
            const form = e.target;
            const fromCode = document.getElementById('transfer-from-slot').value;
            const toCode = document.getElementById('transfer-to-slot').value;
            const qty = readPositiveInt('transfer-qty');

            const src = appState.slots[fromCode];
            const prod = src ? appState.products.find(p => p.id === src.productId) : null;
            if (!src || !prod) {
                showToast(`A posição ${fromCode} já está vazia.`, 'error');
                closeModal('modal-transfer');
                return;
            }
            if (!toCode || toCode === fromCode) return showToast('Escolha uma posição de destino diferente da origem.', 'error');
            if (qty === null) return showToast('Informe uma quantidade inteira maior que zero.', 'error');
            if (qty > src.qty) {
                showToast(`Quantidade maior que o disponível na origem (${src.qty} ${prod.unit}).`, 'error');
                document.getElementById('transfer-qty').focus();
                return;
            }

            const payload = { fromCode, toCode, qty, displace: false };
            const dest = appState.slots[toCode];
            if (dest && dest.productId && dest.productId !== src.productId && dest.qty > 0) {
                const occProd = appState.products.find(p => p.id === dest.productId);
                const occName = occProd ? occProd.name : 'outro item';
                openConfirmDialog(
                    'Destino Ocupado',
                    `A posição ${toCode} já contém "${occName}" (${dest.qty} ${occProd?.unit || 'un'}). Deseja movê-lo para o Estoque Geral e concluir a transferência?`,
                    () => commitTransfer(form, { ...payload, displace: true })
                );
                return;
            }

            commitTransfer(form, payload);
        }

        function clearSingleSlot(slotCode) {
            const slot = appState.slots[slotCode];
            const prod = slot ? appState.products.find(p => p.id === slot.productId) : null;
            if (!slot) return showToast(`A posição ${slotCode} já está vazia.`, 'info');
            const detail = prod ? ` (${slot.qty} ${prod.unit} de "${prod.name}")` : '';
            openConfirmDialog('Esvaziar Posição', `Dar baixa em todo o saldo da posição ${slotCode}${detail}?`, () => {
                runOperation('slot_clear', { slotCode });
            });
        }

        function openEditExpiryModal(targetType, targetId) {
            document.getElementById('edit-expiry-target-type').value = targetType;
            document.getElementById('edit-expiry-target-id').value = targetId;
            const dateInput = document.getElementById('edit-expiry-date');
            const label = document.getElementById('edit-expiry-label');

            if (targetType === 'slot') {
                const slot = appState.slots[targetId];
                label.textContent = `Nova data de vencimento para a posição ${targetId}:`;
                dateInput.value = slot?.expiryDate || todayISO();
            } else {
                const prod = appState.products.find(p => p.id === targetId);
                label.textContent = `Validade padrão para "${prod?.name || 'Produto'}":`;
                dateInput.value = prod?.defaultExpiry || todayISO();
            }
            openModal('modal-edit-expiry');
        }

        async function saveEditExpiryForm(e) {
            e.preventDefault();
            const targetType = document.getElementById('edit-expiry-target-type').value;
            const id = document.getElementById('edit-expiry-target-id').value;
            const date = document.getElementById('edit-expiry-date').value;

            if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) return showToast('Informe uma data de validade válida.', 'error');

            const ok = await runOperation('expiry_update', { targetType, id, date }, { form: e.target });
            if (ok) closeModal('modal-edit-expiry');
        }

        function openLayoutConfigModal() {
            if (!requireAdminAction()) return;
            renderCustomSlotsList();
            openModal('modal-layout-config');
        }

        function renderCustomSlotsList() {
            const listEl = document.getElementById('custom-slots-list');
            if (!listEl) return;
            if (appState.customSlots.length === 0) {
                listEl.innerHTML = '<p class="text-xs text-slate-400 italic py-2">Nenhuma posição extra adicionada.</p>';
                return;
            }
            listEl.innerHTML = appState.customSlots.map(s => `
                <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                    <span><strong>${escapeHtml(s.code)}</strong> — ${escapeHtml(s.label)}</span>
                    <button onclick="removeCustomSlot(${jsArg(s.code)})" class="text-rose-600 hover:underline font-semibold">Remover</button>
                </div>`).join('');
        }

        async function addCustomSlot(e) {
            e.preventDefault();
            if (!requireAdminAction()) return;
            const rawCode = document.getElementById('new-custom-slot-code').value.trim().toUpperCase();
            const label = document.getElementById('new-custom-slot-label').value.trim();
            if (!/^[A-Z0-9_-]{1,6}$/.test(rawCode)) {
                return showToast('Código inválido: use até 6 letras, números, hífen ou sublinhado.', 'error');
            }
            if (!label) return showToast('Informe a descrição da posição.', 'error');
            if (getAllSlotDefinitions().some(d => d.code === rawCode)) {
                return showToast('Já existe uma posição com esse código.', 'error');
            }

            const ok = await runOperation('custom_slot_add', { code: rawCode, label }, { form: e.target });
            if (ok) {
                document.getElementById('new-custom-slot-code').value = '';
                document.getElementById('new-custom-slot-label').value = '';
                renderCustomSlotsList();
            }
        }

        function removeCustomSlot(code) {
            if (!requireAdminAction()) return;
            const run = async () => {
                const ok = await runOperation('custom_slot_remove', { code });
                if (ok) {
                    renderCustomSlotsList();
                    openModal('modal-layout-config');
                }
            };
            const slot = appState.slots[code];
            if (slot && slot.qty > 0) {
                const prod = appState.products.find(p => p.id === slot.productId);
                closeModal('modal-layout-config');
                openConfirmDialog('Remover Posição', `A posição ${code} contém ${slot.qty} ${prod?.unit || 'un'} de "${prod?.name || 'produto'}". O saldo será movido para o Estoque Geral. Continuar?`, run);
                return;
            }
            run();
        }

        function confirmResetAllSlots() {
            if (!requireAdminAction()) return;
            closeModal('modal-layout-config');
            openConfirmDialog('Esvaziar Mapa 2D', 'Isso dará baixa em TODOS os itens alocados nas prateleiras (os produtos cadastrados e o Estoque Geral são mantidos). Continuar?', () => {
                runOperation('slots_reset_all');
            });
        }

        function confirmClearAllData() {
            if (!requireAdminAction()) return;
            openConfirmDialog(
                'Limpar Todos os Dados',
                'Deseja apagar TODOS os produtos, posições do Mapa 2D, lotes e histórico salvos no banco de dados? O programa ficará completamente zerado. (Os usuários são mantidos.)',
                async () => {
                    const ok = await runOperation('reset_all');
                    if (ok) selectedSlotCode = 'M1';
                }
            );
        }

        // =========================================================================
        // IMPORTADOR DE PLANILHAS EXCEL (.XLSX / .XLS / .CSV)
        // =========================================================================

        function normalizeDateValue(rawVal) {
            if (rawVal === undefined || rawVal === null || rawVal === '') return '';
            if (typeof rawVal === 'number' && rawVal > 20000 && rawVal < 90000) {
                const utcDays = Math.floor(rawVal - 25569);
                const d = new Date(utcDays * 86400 * 1000);
                if (!isNaN(d.getTime())) {
                    return d.toISOString().slice(0, 10);
                }
            }
            const str = String(rawVal).trim();
            if (!str || /sem\s*validade|indeterminad|n\/a|^-+$/i.test(str)) return '';

            const isoMatch = str.match(/^(\d{4})[-/](\d{1,2})[-/](\d{1,2})/);
            if (isoMatch) {
                return `${isoMatch[1]}-${String(isoMatch[2]).padStart(2, '0')}-${String(isoMatch[3]).padStart(2, '0')}`;
            }

            const brFullMatch = str.match(/^(\d{1,2})[-/](\d{1,2})[-/](\d{2,4})/);
            if (brFullMatch) {
                const day = String(brFullMatch[1]).padStart(2, '0');
                const month = String(brFullMatch[2]).padStart(2, '0');
                const year = brFullMatch[3].length === 2 ? `20${brFullMatch[3]}` : brFullMatch[3];
                return `${year}-${month}-${day}`;
            }

            const monthYearMatch = str.match(/^(\d{1,2})[-/](\d{4})$/);
            if (monthYearMatch) {
                const month = parseInt(monthYearMatch[1], 10);
                const year = parseInt(monthYearMatch[2], 10);
                if (month >= 1 && month <= 12) {
                    const lastDay = new Date(year, month, 0).getDate();
                    return `${year}-${String(month).padStart(2, '0')}-${String(lastDay).padStart(2, '0')}`;
                }
            }

            return '';
        }

        function parseRowsMatrixToItems(rowsMatrix, defaultCategory = 'Geral') {
            const items = [];
            if (!Array.isArray(rowsMatrix) || rowsMatrix.length === 0) return items;
            const catalogIndex = buildCatalogIndex(); // montado uma vez para a planilha inteira

            let colMap = { name: 0, category: -1, expiry: -1, qty: -1, unit: -1, slot: -1, obs: -1, sku: -1 };
            let headerRowIndex = -1;

            for (let r = 0; r < Math.min(rowsMatrix.length, 15); r++) {
                const row = (rowsMatrix[r] || []).map(c => String(c ?? '').toLowerCase().trim());
                const joined = row.join(' ');
                if (joined.includes('produto') || joined.includes('item') || joined.includes('descri') || joined.includes('validade')) {
                    headerRowIndex = r;
                    row.forEach((cell, idx) => {
                        if (!cell) return;
                        if (cell.includes('produto') || cell.includes('item') || cell.includes('descri') || cell.includes('insumo') || cell.includes('material')) colMap.name = idx;
                        else if (cell.includes('categ') || cell.includes('grupo') || cell.includes('setor')) colMap.category = idx;
                        else if (cell.includes('valid') || cell.includes('venc')) colMap.expiry = idx;
                        else if (cell.includes('qtd') || cell.includes('quant') || cell.includes('saldo')) colMap.qty = idx;
                        else if (cell.includes('unid') || cell === 'un') colMap.unit = idx;
                        else if (cell.includes('posi') || cell.includes('local') || cell.includes('prat')) colMap.slot = idx;
                        else if (cell.includes('obs') || cell.includes('nota') || cell.includes('lote')) colMap.obs = idx;
                        else if (cell.includes('sku') || cell.includes('cod') || cell.includes('cód')) colMap.sku = idx;
                    });
                    break;
                }
            }

            let activeGroupCategory = defaultCategory;
            const startRow = headerRowIndex >= 0 ? headerRowIndex + 1 : 0;

            for (let r = startRow; r < rowsMatrix.length; r++) {
                const rawRow = rowsMatrix[r] || [];
                const cells = rawRow.map(c => (c === undefined || c === null ? '' : String(c).trim()));
                const nonEmptyCells = cells.filter(Boolean);
                if (nonEmptyCells.length === 0) continue;

                if (nonEmptyCells.length === 1 && nonEmptyCells[0].length >= 3 && !/^\d+$/.test(nonEmptyCells[0])) {
                    const candidateCat = nonEmptyCells[0].replace(/:$/, '').trim();
                    if (!/total|resumo|legenda/i.test(candidateCat)) {
                        activeGroupCategory = candidateCat;
                    }
                    continue;
                }

                const nameVal = cells[colMap.name] || nonEmptyCells[0] || '';
                if (!nameVal || /^(produto|item|descrição|total|subtotal)$/i.test(nameVal)) continue;

                let expiryDate = colMap.expiry >= 0 ? normalizeDateValue(rawRow[colMap.expiry]) : '';
                let qtyVal = colMap.qty >= 0 ? parseInt(String(cells[colMap.qty]).replace(/[^\d-]/g, ''), 10) : NaN;

                if (headerRowIndex < 0) {
                    for (let c = 1; c < cells.length; c++) {
                        if (!expiryDate) {
                            const maybeDate = normalizeDateValue(rawRow[c]);
                            if (maybeDate) { expiryDate = maybeDate; continue; }
                        }
                        if (isNaN(qtyVal) && /^\d+$/.test(cells[c])) {
                            qtyVal = parseInt(cells[c], 10);
                        }
                    }
                }

                const catVal = (colMap.category >= 0 && cells[colMap.category]) ? cells[colMap.category] : activeGroupCategory;
                const unitVal = (colMap.unit >= 0 && cells[colMap.unit]) ? cells[colMap.unit].toLowerCase() : 'un';
                const slotVal = (colMap.slot >= 0 && cells[colMap.slot]) ? cells[colMap.slot].toUpperCase() : '';
                const obsVal = (colMap.obs >= 0 && cells[colMap.obs]) ? cells[colMap.obs] : '';
                const skuVal = (colMap.sku >= 0 && cells[colMap.sku]) ? cells[colMap.sku].toUpperCase() : '';

                items.push({
                    name: nameVal,
                    sku: skuVal,
                    // Sem categoria explícita (coluna vazia ou aba genérica): classifica pelo nome
                    category: (catVal && catVal !== 'Geral') ? catVal : suggestCategory(nameVal, { index: catalogIndex }).category,
                    expiryDate,
                    qty: !isNaN(qtyVal) && qtyVal >= 0 ? qtyVal : 1,
                    unit: unitVal || 'un',
                    slotCode: slotVal,
                    observations: obsVal
                });
            }

            return items;
        }

        // =========================================================================
        // GERADOR DE MODELO EXCEL (.XLSX) PARA DOWNLOAD
        // =========================================================================

        function downloadSpreadsheetTemplate() {
            if (typeof XLSX === 'undefined') {
                showToast('Biblioteca SheetJS não carregou. Tente recarregar a página.', 'error');
                return;
            }

            const wb = XLSX.utils.book_new();
            const HDR = ['Produto', 'SKU', 'Categoria', 'Quantidade', 'Unidade', 'Validade (DD/MM/AAAA)', 'Observações'];
            const COLS = [{ wch: 36 }, { wch: 12 }, { wch: 24 }, { wch: 12 }, { wch: 10 }, { wch: 22 }, { wch: 32 }];

            const sheetDefs = [
                {
                    name: 'Bebidas',
                    rows: [
                        ['Refrigerante Cola 2L', 'BEB-001', 'Bebidas', 24, 'un', '30/06/2027', 'Manter fora do sol'],
                        ['Suco de Laranja Integral 1L', 'BEB-002', 'Bebidas', 12, 'un', '15/03/2027', 'Refrigerar após aberto'],
                        ['Água Mineral s/ Gás 500ml (pct 12)', 'BEB-003', 'Bebidas', 10, 'pct', '12/2028', ''],
                        ['Achocolatado em Pó 400g', 'BEB-004', 'Bebidas', 8, 'un', '08/2027', ''],
                        ['Café Solúvel Granulado 200g', 'BEB-005', 'Bebidas', 15, 'un', '31/12/2026', 'Manter em local seco'],
                        ['Chá de Ervas (cx 25 sachês)', 'BEB-006', 'Bebidas', 6, 'cx', '01/2028', ''],
                    ]
                },
                {
                    name: 'Farinhas & Amidos',
                    rows: [
                        ['Farinha de Trigo Especial 1kg', 'FAR-001', 'Farinhas & Amidos', 20, 'kg', '30/09/2026', 'Armazenar em local fresco e seco'],
                        ['Fubá Mimoso 500g', 'FAR-002', 'Farinhas & Amidos', 10, 'pct', '28/02/2027', ''],
                        ['Amido de Milho (Maisena) 500g', 'FAR-003', 'Farinhas & Amidos', 8, 'pct', '31/07/2027', ''],
                        ['Farinha de Mandioca Fina 500g', 'FAR-004', 'Farinhas & Amidos', 6, 'pct', '31/05/2027', ''],
                        ['Aveia em Flocos Finos 500g', 'FAR-005', 'Farinhas & Amidos', 5, 'pct', '30/04/2027', ''],
                    ]
                },
                {
                    name: 'Doces & Chocolates',
                    rows: [
                        ['Chocolate em Pó 50% Cacau 200g', 'DOC-001', 'Doces & Chocolates', 10, 'un', '31/08/2027', ''],
                        ['Açúcar Refinado 1kg', 'DOC-002', 'Doces & Chocolates', 15, 'pct', '30/06/2027', ''],
                        ['Adoçante Líquido Stevita 65ml', 'DOC-003', 'Doces & Chocolates', 12, 'un', '15/09/2026', ''],
                        ['Leite Condensado Moça 395g', 'DOC-004', 'Doces & Chocolates', 8, 'un', '28/02/2027', ''],
                        ['Creme de Leite UHT 200ml', 'DOC-005', 'Doces & Chocolates', 10, 'un', '31/01/2027', ''],
                        ['Granulado Misto (Confeito) 500g', 'DOC-006', 'Doces & Chocolates', 4, 'pct', '31/10/2027', ''],
                    ]
                },
                {
                    name: 'Grãos & Cereais',
                    rows: [
                        ['Arroz Branco Polido Tipo 1 — 5kg', 'GRA-001', 'Grãos & Cereais', 10, 'pct', '31/12/2026', ''],
                        ['Feijão Carioca Tipo 1 — 1kg', 'GRA-002', 'Grãos & Cereais', 20, 'pct', '30/09/2026', ''],
                        ['Macarrão Espaguete 500g', 'GRA-003', 'Grãos & Cereais', 15, 'pct', '28/02/2028', ''],
                        ['Lentilha 500g', 'GRA-004', 'Grãos & Cereais', 6, 'pct', '31/05/2027', ''],
                        ['Ervilha em Conserva 170g', 'GRA-005', 'Grãos & Cereais', 10, 'un', '15/08/2027', ''],
                    ]
                },
                {
                    name: 'Temperos & Condimentos',
                    rows: [
                        ['Sal Refinado Iodado 1kg', 'TMP-001', 'Temperos & Condimentos', 10, 'pct', '31/12/2027', ''],
                        ['Óleo de Soja 900ml', 'TMP-002', 'Temperos & Condimentos', 12, 'un', '30/06/2026', 'FEFO — verificar lote'],
                        ['Vinagre de Álcool 750ml', 'TMP-003', 'Temperos & Condimentos', 6, 'un', '31/03/2028', ''],
                        ['Alho Granulado Desidratado 100g', 'TMP-004', 'Temperos & Condimentos', 8, 'un', '28/02/2027', ''],
                        ['Molho de Tomate 520g', 'TMP-005', 'Temperos & Condimentos', 15, 'un', '31/10/2026', ''],
                        ['Pimenta-do-Reino Moída 50g', 'TMP-006', 'Temperos & Condimentos', 5, 'un', '30/11/2027', ''],
                    ]
                },
                {
                    name: 'Laticínios & Ovos',
                    rows: [
                        ['Leite Integral UHT 1L (cx 12)', 'LAT-001', 'Laticínios & Ovos', 5, 'cx', '31/01/2027', 'Refrigerar após aberto'],
                        ['Queijo Mussarela Fatiado 150g', 'LAT-002', 'Laticínios & Ovos', 6, 'un', '10/12/2026', 'Refrigerado'],
                        ['Manteiga com Sal 200g', 'LAT-003', 'Laticínios & Ovos', 4, 'un', '28/02/2027', 'Refrigerado'],
                        ['Ovos Brancos (bandeja 30)', 'LAT-004', 'Laticínios & Ovos', 3, 'un', '15/12/2026', 'Refrigerado'],
                        ['Iogurte Natural Integral 170g', 'LAT-005', 'Laticínios & Ovos', 10, 'un', '05/12/2026', 'Refrigerado'],
                    ]
                },
                {
                    name: 'Descartáveis & Embalagens',
                    rows: [
                        ['Copo Descartável 180ml (pct 100)', 'DES-001', 'Descartáveis & Embalagens', 10, 'pct', '', ''],
                        ['Prato Descartável Fundo 500ml (pct 10)', 'DES-002', 'Descartáveis & Embalagens', 20, 'pct', '', ''],
                        ['Garfo Descartável (pct 50)', 'DES-003', 'Descartáveis & Embalagens', 15, 'pct', '', ''],
                        ['Sacola Plástica 30x40cm (pct 100)', 'DES-004', 'Descartáveis & Embalagens', 8, 'pct', '', ''],
                        ['Papel Alumínio 30cm × 7,5m', 'DES-005', 'Descartáveis & Embalagens', 5, 'un', '', ''],
                        ['Filme PVC Esticável 30cm × 30m', 'DES-006', 'Descartáveis & Embalagens', 4, 'un', '', ''],
                    ]
                },
                {
                    name: 'Higiene & Limpeza',
                    rows: [
                        ['Papel Higiênico Folha Dupla (Fardo 64)', 'HIG-001', 'Higiene & Limpeza', 3, 'fd', '', 'Estoque no palete'],
                        ['Sabonete Líquido 500ml', 'HIG-002', 'Higiene & Limpeza', 6, 'un', '05/2028', ''],
                        ['Álcool 70% 1L', 'HIG-003', 'Higiene & Limpeza', 10, 'un', '12/2026', 'Inflamável'],
                        ['Detergente Neutro 500ml', 'HIG-004', 'Higiene & Limpeza', 8, 'un', '08/2027', ''],
                        ['Desinfetante Pinho 1L', 'HIG-005', 'Higiene & Limpeza', 5, 'un', '03/2027', ''],
                        ['Luva Descartável (cx 100)', 'HIG-006', 'Higiene & Limpeza', 4, 'cx', '01/2029', 'EPI'],
                    ]
                },
                {
                    name: 'Material de Escritório',
                    rows: [
                        ['Resma de Papel A4 (500fls)', 'ESC-001', 'Material de Escritório', 10, 'un', '', '75g/m²'],
                        ['Caneta Esferográfica Azul (cx 50)', 'ESC-002', 'Material de Escritório', 2, 'cx', '', ''],
                        ['Pasta Arquivo AZ', 'ESC-003', 'Material de Escritório', 15, 'un', '', ''],
                        ['Grampeador de Mesa', 'ESC-004', 'Material de Escritório', 5, 'un', '', ''],
                        ['Grampo 26/6 (cx 5000)', 'ESC-005', 'Material de Escritório', 8, 'cx', '', ''],
                    ]
                },
                {
                    name: 'Legumes & Verduras',
                    rows: [
                        ['Tomate Italiano (kg)', 'LEG-001', 'Legumes & Verduras', 5, 'kg', '05/12/2026', 'Refrigerado'],
                        ['Cebola Branca (kg)', 'LEG-002', 'Legumes & Verduras', 4, 'kg', '20/12/2026', 'Local arejado e seco'],
                        ['Alho Cabeça (kg)', 'LEG-003', 'Legumes & Verduras', 2, 'kg', '31/01/2027', 'Local arejado e seco'],
                        ['Batata Inglesa (kg)', 'LEG-004', 'Legumes & Verduras', 6, 'kg', '15/12/2026', 'Escuro e seco'],
                        ['Cenoura (kg)', 'LEG-005', 'Legumes & Verduras', 3, 'kg', '10/12/2026', 'Refrigerado'],
                        ['Alface Crespa (maço)', 'LEG-006', 'Legumes & Verduras', 4, 'un', '03/12/2026', 'Refrigerado — consumo rápido'],
                        ['Brócolis (maço)', 'LEG-007', 'Legumes & Verduras', 2, 'un', '04/12/2026', 'Refrigerado'],
                        ['Abobrinha Verde (kg)', 'LEG-008', 'Legumes & Verduras', 3, 'kg', '07/12/2026', 'Refrigerado'],
                    ]
                },
            ];

            sheetDefs.forEach(def => {
                const wsData = [HDR, ...def.rows];
                const ws = XLSX.utils.aoa_to_sheet(wsData);
                ws['!cols'] = COLS;
                XLSX.utils.book_append_sheet(wb, ws, def.name);
            });

            // --- Aba INSTRUÇÕES ---
            const instrData = [
                ['INSTRUÇÕES DE PREENCHIMENTO DO MODELO DE IMPORTAÇÃO — SENAI 2D'],
                [''],
                ['COLUNA', 'OBRIGATÓRIO?', 'FORMATO / EXEMPLOS', 'OBSERVAÇÕES'],
                ['Produto', 'SIM', 'Texto livre', 'Nome do item. Ex: Arroz Branco 5kg'],
                ['SKU', 'Não', 'Texto maiúsculo', 'Código interno. Gerado automaticamente se vazio.'],
                ['Categoria', 'Não', 'Texto livre', 'Se vazio, usa o nome da aba como categoria.'],
                ['Quantidade', 'Não', 'Número inteiro', 'Ex: 10. Padrão: 1 se não informado.'],
                ['Unidade', 'Não', 'un / cx / fd / pct / kg / lt', 'Padrão: "un" se não informado.'],
                ['Validade (DD/MM/AAAA)', 'Não', 'DD/MM/AAAA  ou  MM/AAAA  ou  AAAA-MM-DD', 'Deixe vazio para "Sem validade".'],
                ['Observações', 'Não', 'Texto livre', 'Notas de lote, condições de armazenamento, etc.'],
                [''],
                ['CATEGORIAS DISPONÍVEIS NO MODELO'],
                ['Bebidas', 'Refrigerantes, sucos, água, café, chá', '', ''],
                ['Farinhas & Amidos', 'Farinha de trigo, fubá, amido, aveia', '', ''],
                ['Doces & Chocolates', 'Açúcar, chocolate em pó, leite condensado', '', ''],
                ['Grãos & Cereais', 'Arroz, feijão, macarrão, lentilha', '', ''],
                ['Temperos & Condimentos', 'Sal, óleo, vinagre, molho de tomate', '', ''],
                ['Laticínios & Ovos', 'Leite, queijo, manteiga, ovos', '', ''],
                ['Descartáveis & Embalagens', 'Copos, pratos, garfos, sacolas, filmes', '', ''],
                ['Higiene & Limpeza', 'Papel higiênico, sabonete, álcool, luvas', '', ''],
                ['Material de Escritório', 'Papel A4, canetas, pastas', '', ''],
                ['Legumes & Verduras', 'Tomate, cebola, batata, cenoura, alface', '', ''],
                [''],
                ['DICAS'],
                ['• Cada aba representa uma categoria — não altere o nome das abas se quiser manter a categoria.'],
                ['• Você pode apagar as linhas de exemplo e inserir seus próprios produtos.'],
                ['• A linha de cabeçalho é detectada automaticamente.'],
                ['• Datas no formato serial do Excel também são reconhecidas.'],
                ['• Use o modo "Substituir" para zerar o estoque atual, ou "Mesclar" para somar aos itens existentes.'],
            ];
            const wsInstr = XLSX.utils.aoa_to_sheet(instrData);
            wsInstr['!cols'] = [{ wch: 30 }, { wch: 36 }, { wch: 45 }, { wch: 20 }];
            XLSX.utils.book_append_sheet(wb, wsInstr, 'INSTRUÇÕES');

            const wbout = XLSX.write(wb, { bookType: 'xlsx', type: 'array' });
            const blob = new Blob([wbout], { type: 'application/octet-stream' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'modelo_importacao_senai_estoque.xlsx';
            a.click();
            URL.revokeObjectURL(url);
            showToast('Modelo Excel baixado! Preencha e importe de volta.');
        }

        // =========================================================================
        // PARSER E RENDERIZAÇÃO DE PRÉ-VISUALIZAÇÃO
        // =========================================================================

        function renderImportPreviewUI(items) {
            parsedSpreadsheetBuffer = items;
            const catWrap = document.getElementById('import-categories-wrap');
            const catBadges = document.getElementById('import-categories-badges');
            const tableWrap = document.getElementById('import-table-preview-wrap');
            const badge = document.getElementById('import-preview-badge');
            const tbody = document.getElementById('import-preview-tbody');

            if (!items || items.length === 0) {
                catWrap.classList.add('hidden');
                tableWrap.classList.add('hidden');
                return;
            }

            const uniqueCats = [...new Set(items.map(i => i.category))];
            catWrap.classList.remove('hidden');
            catBadges.innerHTML = uniqueCats.map(c =>
                `<span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#0F294A]/10 text-[#0F294A]">${escapeHtml(c)}</span>`
            ).join('');

            tableWrap.classList.remove('hidden');
            const shownCount = Math.min(items.length, 50);
            badge.textContent = `${items.length} ${items.length === 1 ? 'item identificado' : 'itens identificados'}${items.length > 50 ? ' (exibindo primeiros 50)' : ''}`;

            tbody.innerHTML = items.slice(0, 50).map(it => {
                const statusObj = getExpiryStatus(it.expiryDate);
                const badgeStyle = it.expiryDate ? statusObj.badgeClass : 'bg-slate-200 text-slate-600';
                const dateLabel = it.expiryDate ? escapeHtml(formatDateBR(it.expiryDate)) : '—';
                return `
                <tr class="hover:bg-slate-50">
                    <td class="py-1.5 px-2.5 font-bold text-slate-900">${escapeHtml(it.name)}</td>
                    <td class="py-1.5 px-2.5 text-slate-600">${escapeHtml(it.category)}</td>
                    <td class="py-1.5 px-2.5">
                        ${it.slotCode
                            ? `<span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#0F294A] text-white">${escapeHtml(it.slotCode)}</span>`
                            : '<span class="text-slate-400">Auto</span>'
                        }
                    </td>
                    <td class="py-1.5 px-2.5">
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold ${badgeStyle}">${dateLabel}</span>
                    </td>
                    <td class="py-1.5 px-2.5 font-bold text-[#0F294A]">${it.qty} <span class="font-normal text-slate-500">${escapeHtml(it.unit)}</span></td>
                    <td class="py-1.5 px-2.5 text-slate-500 max-w-[140px] truncate" title="${escapeHtml(it.observations || '')}">${escapeHtml(it.observations || '—')}</td>
                </tr>`;
            }).join('');
        }

        function openImportSpreadsheetModal() {
            parsedSpreadsheetBuffer = [];
            const fileInput = document.getElementById('import-file-input');
            if (fileInput) fileInput.value = '';
            document.getElementById('import-file-name').textContent = 'Nenhum arquivo selecionado';
            document.getElementById('import-raw-text').value = '';
            document.getElementById('import-categories-wrap').classList.add('hidden');
            document.getElementById('import-table-preview-wrap').classList.add('hidden');
            openModal('modal-import-spreadsheet');
        }

        function handleSpreadsheetFileUpload(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            document.getElementById('import-file-name').textContent = file.name;

            if (typeof XLSX === 'undefined') {
                showToast('Biblioteca SheetJS não carregou. Recarregue a página com internet ativa.', 'error');
                return;
            }

            const isCsv = /\.(csv|txt)$/i.test(file.name);
            const reader = new FileReader();

            if (isCsv) {
                reader.onload = (e) => {
                    try {
                        const text = e.target.result;
                        // detecta separador: tab, ponto-e-vírgula ou vírgula
                        const sep = text.includes('\t') ? '\t' : text.includes(';') ? ';' : ',';
                        const lines = text.split(/\r?\n/).map(l => l.split(sep));
                        const items = parseRowsMatrixToItems(lines, 'Geral');
                        renderImportPreviewUI(items);
                        if (items.length > 0) {
                            showToast(`${items.length} itens lidos de "${file.name}"!`);
                        } else {
                            showToast('Nenhuma linha válida encontrada no arquivo.', 'error');
                        }
                    } catch (err) {
                        console.error('Erro CSV:', err);
                        showToast('Erro ao processar arquivo CSV.', 'error');
                    }
                };
                reader.readAsText(file, 'UTF-8');
            } else {
                reader.onload = (e) => {
                    try {
                        const data = new Uint8Array(e.target.result);
                        const wb = XLSX.read(data, { type: 'array', cellDates: false });
                        let collected = [];

                        wb.SheetNames.forEach(sheetName => {
                            // pula aba de instruções
                            if (/instru[çc]/i.test(sheetName)) return;
                            const ws = wb.Sheets[sheetName];
                            if (!ws) return;
                            const matrix = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '', raw: true });
                            const isGenericSheet = /^(planilha|sheet|folha)\s*\d*$/i.test(sheetName.trim());
                            const defaultCat = isGenericSheet ? 'Geral' : sheetName.trim();
                            collected = collected.concat(parseRowsMatrixToItems(matrix, defaultCat));
                        });

                        renderImportPreviewUI(collected);
                        if (collected.length > 0) {
                            showToast(`${collected.length} itens lidos de "${file.name}"!`);
                        } else {
                            showToast('Nenhuma linha válida encontrada na planilha. Verifique se a aba tem cabeçalhos (Produto, Validade, etc.).', 'error');
                        }
                    } catch (err) {
                        console.error('Erro Excel:', err);
                        showToast('Erro ao processar arquivo Excel. Certifique-se que é .xlsx ou .xls válido.', 'error');
                    }
                };
                reader.readAsArrayBuffer(file);
            }
        }

        function previewSpreadsheetImport() {
            const raw = document.getElementById('import-raw-text').value.trim();
            if (!raw) {
                renderImportPreviewUI([]);
                return;
            }
            const lines = raw.split(/\r?\n/).map(line => line.split(/\t|;/));
            const items = parseRowsMatrixToItems(lines, 'Geral');
            renderImportPreviewUI(items);
        }

        async function executeSpreadsheetImport(e) {
            e.preventDefault();
            const form = e.target;
            if (!parsedSpreadsheetBuffer || parsedSpreadsheetBuffer.length === 0) {
                previewSpreadsheetImport();
            }
            if (!parsedSpreadsheetBuffer || parsedSpreadsheetBuffer.length === 0) {
                showToast('Selecione um arquivo .xlsx/.csv ou cole os dados primeiro.', 'error');
                return;
            }

            const mode = document.getElementById('import-mode').value;
            const autoAllocate = document.getElementById('import-auto-allocate').checked;
            if (mode === 'replace' && !requireAdminAction()) return;

            // Toda a lógica de criação de produtos e distribuição roda no servidor (validada e atômica)
            const items = parsedSpreadsheetBuffer.map(item => ({
                name: item.name,
                sku: item.sku || '',
                category: item.category || 'Geral',
                unit: item.unit || 'un',
                qty: Number.isInteger(item.qty) && item.qty >= 0 ? item.qty : 0,
                expiryDate: item.expiryDate || '',
                slotCode: item.slotCode || '',
                observations: item.observations || ''
            }));

            const run = async () => {
                const ok = await runOperation('import_items', { items, mode, autoAllocate }, { form });
                if (ok) closeModal('modal-import-spreadsheet');
            };

            if (mode === 'replace') {
                openConfirmDialog('Substituir Estoque', 'O modo "Substituir" apaga TODOS os produtos, posições e lotes atuais antes de importar a planilha. Continuar?', run);
                return;
            }
            run();
        }

        // =========================================================================
        // EXPORTAÇÃO CSV E BACKUP JSON
        // =========================================================================

        function exportCSV() {
            const headers = ['Posicao', 'SKU', 'Produto', 'Categoria', 'Quantidade', 'Unidade', 'Validade', 'Status', 'Observacoes'];
            const rows = [headers.join(';')];

            Object.entries(appState.slots).forEach(([code, slot]) => {
                if (!slot || !slot.productId || slot.qty <= 0) return;
                const prod = appState.products.find(p => p.id === slot.productId);
                if (!prod) return;
                const st = getExpiryStatus(slot.expiryDate);
                rows.push([
                    code,
                    prod.sku,
                    `"${prod.name.replace(/"/g, '""')}"`,
                    `"${prod.category.replace(/"/g, '""')}"`,
                    slot.qty,
                    prod.unit,
                    formatDateBR(slot.expiryDate),
                    st.label,
                    `"${(slot.observations || prod.observations || '').replace(/"/g, '""')}"`
                ].join(';'));
            });

            (appState.unallocatedLots || []).forEach(u => {
                if (!u || !u.productId || u.qty <= 0) return;
                const prod = appState.products.find(p => p.id === u.productId);
                if (!prod) return;
                const st = getExpiryStatus(u.expiryDate);
                rows.push([
                    'Geral',
                    prod.sku,
                    `"${prod.name.replace(/"/g, '""')}"`,
                    `"${prod.category.replace(/"/g, '""')}"`,
                    u.qty,
                    prod.unit,
                    formatDateBR(u.expiryDate),
                    st.label,
                    `"${(u.observations || prod.observations || '').replace(/"/g, '""')}"`
                ].join(';'));
            });

            const blob = new Blob(['\uFEFF' + rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `estoque_senai_${todayISO()}.csv`;
            a.click();
            URL.revokeObjectURL(url);
            showToast('Planilha CSV exportada!');
        }

        function exportJSON() {
            const blob = new Blob([JSON.stringify(appState, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `backup_estoque_senai_${todayISO()}.json`;
            a.click();
            URL.revokeObjectURL(url);
            showToast('Backup JSON salvo!');
        }

        function importJSON(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            if (!requireAdminAction()) { event.target.value = ''; return; }
            const reader = new FileReader();
            reader.onload = (e) => {
                let parsed;
                try {
                    parsed = JSON.parse(e.target.result);
                } catch (err) {
                    showToast('Erro ao ler arquivo JSON.', 'error');
                    return;
                }
                if (!parsed || !Array.isArray(parsed.products)) {
                    showToast('Arquivo JSON inválido: não é um backup deste sistema.', 'error');
                    return;
                }
                const counts = `${parsed.products.length} produtos`;
                openConfirmDialog(
                    'Restaurar Backup',
                    `Substituir TODOS os produtos, posições e lotes atuais pelo backup "${file.name}" (${counts})? O histórico atual é mantido.`,
                    () => runOperation('backup_restore', { backup: parsed })
                );
            };
            reader.readAsText(file);
            event.target.value = '';
        }

        function renderAll() {
            const refEl = document.getElementById('header-ref-date');
            if (refEl) {
                refEl.textContent = new Date().toLocaleDateString('pt-BR', {
                    weekday: 'short',
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
            }
            renderDashboard();
            renderMap();
            renderInspector();
            renderCatalog();
            renderHistory();
            refreshIcons();
        }

        // =========================================================================
        // CONTA DO USUÁRIO: ALTERAR SENHA
        // =========================================================================

        function setFormError(elId, message) {
            const el = document.getElementById(elId);
            if (!el) return;
            el.textContent = message || '';
            el.classList.toggle('hidden', !message);
        }

        function setButtonBusy(btnId, busy, busyText) {
            const btn = document.getElementById(btnId);
            if (!btn) return;
            if (busy) {
                btn.dataset.originalText = btn.textContent;
                btn.textContent = busyText || 'Aguarde...';
                btn.disabled = true;
            } else {
                btn.textContent = btn.dataset.originalText || btn.textContent;
                btn.disabled = false;
            }
        }

        function validateNewPasswordClient(pwd) {
            if (pwd.length < 8) return 'A nova senha deve ter pelo menos 8 caracteres.';
            if (!/[A-Za-z]/.test(pwd) || !/\d/.test(pwd)) return 'A nova senha deve conter letras e números.';
            return '';
        }

        function openChangePasswordModal() {
            toggleUserMenu(false);
            ['cp-current', 'cp-new', 'cp-confirm'].forEach(id => { document.getElementById(id).value = ''; });
            setFormError('cp-error', '');
            openModal('modal-change-password');
            document.getElementById('cp-current').focus();
        }

        async function saveChangePasswordForm(e) {
            e.preventDefault();
            const currentPassword = document.getElementById('cp-current').value;
            const newPassword = document.getElementById('cp-new').value;
            const confirmPassword = document.getElementById('cp-confirm').value;

            if (!currentPassword) return setFormError('cp-error', 'Informe a senha atual.');
            const pwdError = validateNewPasswordClient(newPassword);
            if (pwdError) return setFormError('cp-error', pwdError);
            if (newPassword !== confirmPassword) return setFormError('cp-error', 'As novas senhas não conferem.');
            if (newPassword === currentPassword) return setFormError('cp-error', 'A nova senha deve ser diferente da atual.');

            setFormError('cp-error', '');
            setButtonBusy('cp-submit', true, 'Salvando...');
            try {
                await apiFetch('change_password', { method: 'POST', body: { currentPassword, newPassword } });
                closeModal('modal-change-password');
                showToast('Senha alterada com sucesso!');
            } catch (err) {
                if (err.status !== 401) setFormError('cp-error', err.message);
            } finally {
                setButtonBusy('cp-submit', false);
            }
        }

        // =========================================================================
        // GERENCIAR USUÁRIOS (ADMIN)
        // =========================================================================

        let usersCache = [];

        async function openUsersModal() {
            toggleUserMenu(false);
            if (!requireAdminAction()) return;
            document.getElementById('users-temp-password').classList.add('hidden');
            setFormError('nu-error', '');
            openModal('modal-users');
            await loadUsers();
        }

        async function loadUsers() {
            const listEl = document.getElementById('users-list');
            listEl.innerHTML = Array.from({ length: 3 }).map(() =>
                '<div class="h-16 rounded-xl bg-slate-100 animate-pulse"></div>'
            ).join('');
            try {
                const json = await apiFetch('list_users');
                usersCache = Array.isArray(json.users) ? json.users : [];
                renderUsersList();
            } catch (err) {
                if (err.status === 401) return;
                listEl.innerHTML = `<p class="text-sm text-red-700 font-semibold py-3">Não foi possível carregar os usuários: ${escapeHtml(err.message)}</p>`;
            }
        }

        function formatDateTimeBR(sqlDateTime) {
            if (!sqlDateTime) return 'nunca';
            const d = new Date(sqlDateTime.replace(' ', 'T'));
            return isNaN(d) ? sqlDateTime : d.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
        }

        function renderUsersList() {
            const listEl = document.getElementById('users-list');
            if (usersCache.length === 0) {
                listEl.innerHTML = '<p class="text-sm text-slate-600 py-3">Nenhum usuário cadastrado.</p>';
                return;
            }
            const meId = window.APP_USER?.id;
            listEl.innerHTML = usersCache.map(u => {
                const isMe = u.id === meId;
                const statusBadges = [
                    u.active
                        ? '<span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-100 text-emerald-800">Ativo</span>'
                        : '<span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-200 text-slate-700">Desativado</span>',
                    u.mustChangePassword ? '<span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-100 text-amber-900">Senha temporária</span>' : '',
                    u.locked ? '<span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-red-100 text-red-800">Bloqueado</span>' : '',
                    isMe ? '<span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-sky-100 text-sky-800">Você</span>' : ''
                ].join(' ');

                return `
                <div class="rounded-xl border border-slate-200 p-3 flex flex-col md:flex-row md:items-center gap-3 ${u.active ? 'bg-white' : 'bg-slate-50'}" data-user-id="${u.id}">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-900 truncate">${escapeHtml(u.displayName)} <span class="font-medium text-slate-600">@${escapeHtml(u.username)}</span></p>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">${statusBadges}</div>
                        <p class="mt-1 text-xs text-slate-600">Último acesso: ${escapeHtml(formatDateTimeBR(u.lastLoginAt))}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="sr-only" for="role-${u.id}">Perfil de ${escapeHtml(u.username)}</label>
                        <select id="role-${u.id}" data-user-action="role" ${isMe ? 'disabled title="Você não pode alterar o próprio perfil"' : ''}
                                class="text-xs font-semibold bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 disabled:opacity-60">
                            <option value="operador" ${u.role === 'operador' ? 'selected' : ''}>Operador</option>
                            <option value="admin" ${u.role === 'admin' ? 'selected' : ''}>Administrador</option>
                        </select>
                        <button type="button" data-user-action="reset" ${isMe ? 'disabled' : ''}
                                class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-800 disabled:opacity-50">Redefinir senha</button>
                        <button type="button" data-user-action="toggle" ${isMe ? 'disabled' : ''}
                                class="px-2.5 py-1.5 rounded-lg text-xs font-bold disabled:opacity-50 ${u.active ? 'bg-rose-50 hover:bg-rose-100 text-rose-700' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800'}">
                            ${u.active ? 'Desativar' : 'Reativar'}
                        </button>
                    </div>
                </div>`;
            }).join('');
        }

        function showTempPassword(username, tempPassword) {
            document.getElementById('users-temp-username').textContent = '@' + username;
            document.getElementById('users-temp-value').textContent = tempPassword;
            const box = document.getElementById('users-temp-password');
            box.classList.remove('hidden');
            box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        async function copyTempPassword() {
            const value = document.getElementById('users-temp-value').textContent;
            try {
                await navigator.clipboard.writeText(value);
                showToast('Senha copiada!');
            } catch (_) {
                showToast('Não foi possível copiar. Selecione e copie manualmente.', 'info');
            }
        }

        async function saveNewUserForm(e) {
            e.preventDefault();
            const displayName = document.getElementById('nu-name').value.trim();
            const username = document.getElementById('nu-username').value.trim().toLowerCase();
            const role = document.getElementById('nu-role').value;

            if (displayName.length < 2) return setFormError('nu-error', 'Informe o nome completo.');
            if (!/^[a-z0-9._-]{3,32}$/.test(username)) {
                return setFormError('nu-error', 'Usuário inválido: use de 3 a 32 letras minúsculas, números, ponto, hífen ou sublinhado.');
            }

            setFormError('nu-error', '');
            setButtonBusy('nu-submit', true, 'Criando...');
            try {
                const json = await apiFetch('create_user', { method: 'POST', body: { displayName, username, role } });
                document.getElementById('nu-name').value = '';
                document.getElementById('nu-username').value = '';
                document.getElementById('nu-role').value = 'operador';
                showTempPassword(json.username, json.tempPassword);
                showToast(`Usuário @${json.username} criado!`);
                await loadUsers();
            } catch (err) {
                if (err.status !== 401) setFormError('nu-error', err.message);
            } finally {
                setButtonBusy('nu-submit', false);
            }
        }

        async function updateUser(id, changes, successMsg) {
            try {
                await apiFetch('update_user', { method: 'POST', body: { id, ...changes } });
                showToast(successMsg);
            } catch (err) {
                if (err.status !== 401) showToast(err.message, 'error');
            }
            await loadUsers();
        }

        // Delegação de eventos na lista de usuários (sem onclick com dados interpolados)
        document.getElementById('users-list').addEventListener('change', (ev) => {
            const select = ev.target.closest('[data-user-action="role"]');
            if (!select) return;
            const id = Number(select.closest('[data-user-id]').dataset.userId);
            const u = usersCache.find(x => x.id === id);
            if (!u) return;
            updateUser(id, { role: select.value }, `Perfil de @${u.username} alterado para ${ROLE_LABELS[select.value]}.`);
        });

        document.getElementById('users-list').addEventListener('click', (ev) => {
            const btn = ev.target.closest('button[data-user-action]');
            if (!btn || btn.disabled) return;
            const id = Number(btn.closest('[data-user-id]').dataset.userId);
            const u = usersCache.find(x => x.id === id);
            if (!u) return;

            if (btn.dataset.userAction === 'toggle') {
                const deactivate = u.active;
                const run = () => updateUser(id, { active: !u.active }, deactivate ? `@${u.username} foi desativado.` : `@${u.username} foi reativado.`);
                if (deactivate) {
                    closeModal('modal-users');
                    openConfirmDialog('Desativar usuário', `@${u.username} perderá o acesso ao sistema imediatamente. O histórico dele é mantido. Continuar?`, async () => {
                        openModal('modal-users');
                        await run();
                    });
                } else {
                    run();
                }
            }

            if (btn.dataset.userAction === 'reset') {
                closeModal('modal-users');
                openConfirmDialog('Redefinir senha', `Gerar uma nova senha temporária para @${u.username}? A senha atual deixará de funcionar.`, async () => {
                    openModal('modal-users');
                    try {
                        const json = await apiFetch('reset_password', { method: 'POST', body: { id } });
                        showTempPassword(json.username, json.tempPassword);
                        showToast(`Senha de @${json.username} redefinida.`);
                    } catch (err) {
                        if (err.status !== 401) showToast(err.message, 'error');
                    }
                    await loadUsers();
                });
            }
        });

        Object.assign(window, {
            toggleUserMenu,
            openChangePasswordModal,
            saveChangePasswordForm,
            openUsersModal,
            saveNewUserForm,
            copyTempPassword,
            switchTab,
            jumpToMapSlot,
            openModal,
            closeModal,
            setSlicerStatus,
            setDashboardSubView,
            setDashboardViewMode,
            setAlmoxFilter,
            resetDashboardFilters,
            renderDashboard,
            adjustProductQuickQty,
            selectSlot,
            resetMapFilters,
            renderMap,
            resetCatalogFilters,
            renderCatalog,
            renderHistory,
            clearHistoryLog,
            openProductModal,
            onCategoryChange,
            saveProductForm,
            deleteProduct,
            openAllocateModal,
            onAllocateProductChange,
            updateAllocateExpiryPreview,
            saveAllocateForm,
            openWithdrawModal,
            saveWithdrawForm,
            openTransferModal,
            saveTransferForm,
            clearSingleSlot,
            openEditExpiryModal,
            saveEditExpiryForm,
            openLayoutConfigModal,
            addCustomSlot,
            removeCustomSlot,
            confirmResetAllSlots,
            confirmClearAllData,
            openImportSpreadsheetModal,
            handleSpreadsheetFileUpload,
            previewSpreadsheetImport,
            executeSpreadsheetImport,
            downloadSpreadsheetTemplate,
            exportCSV,
            exportJSON,
            importJSON
        });

        window.onload = function () {
            renderUserHeader();
            initDatabase();
        };
    </script>
</body>
</html>
