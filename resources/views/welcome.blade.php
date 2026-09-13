<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MenuAI - 日本飲食店向け AI商品写真補正 & メニュー作成</title>
    <!-- Tailwind CSS CDN, Lucide Icons, FingerprintJS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://openfpcdn.io/fingerprintjs/v4/i.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+JP:wght@400;500;700&display=swap');
        body { font-family: 'Plus Jakarta Sans', 'Noto Sans JP', sans-serif; }
        .glass-panel {
            background: rgba(23, 23, 23, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #27272a; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #3f3f46; }
    </style>
</head>
<body class="bg-[#0f0f11] text-zinc-100 min-h-screen flex flex-col antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Top Navigation -->
    <header class="border-b border-zinc-800/80 bg-[#0f0f11]/80 backdrop-blur-md sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <i data-lucide="sparkles" class="w-5 h-5 text-white"></i>
                </div>
                <span class="text-xl font-bold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white via-zinc-200 to-zinc-400">
                    Menu<span class="text-indigo-400">AI</span>
                </span>
                <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">JP v1.0</span>
            </div>

            <div class="flex items-center gap-4">
                <!-- User State: Logged In -->
                <div id="userLoggedInView" class="hidden items-center gap-3">
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-zinc-900 border border-zinc-800 text-sm">
                        <i data-lucide="zap" class="w-4 h-4 text-amber-400 fill-amber-400"></i>
                        <span class="text-zinc-400">残クレジット:</span>
                        <span class="font-bold text-white" id="creditDisplay">0</span>
                    </div>
                    <span class="text-xs text-zinc-400 hidden sm:inline" id="userNameDisplay"></span>
                    <button id="logoutBtn" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 transition border border-zinc-700">
                        ログアウト
                    </button>
                </div>

                <!-- User State: Logged Out -->
                <div id="userLoggedOutView" class="flex items-center gap-3">
                    <button id="openAuthModalBtn" class="px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-lg shadow-indigo-500/20">
                        ログイン / 新規登録
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Workspace -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-6 py-8 grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Left Controls / Preset Settings (5 Cols) -->
        <div class="lg:col-span-5 flex flex-col gap-6">

            <!-- Compliance Badge -->
            <div class="p-4 rounded-xl bg-indigo-950/30 border border-indigo-500/20 flex gap-3 items-start">
                <i data-lucide="shield-check" class="w-5 h-5 text-indigo-400 shrink-0 mt-0.5"></i>
                <div class="text-xs text-zinc-300 leading-relaxed">
                    <strong class="text-indigo-300 font-medium">景品表示法対応:</strong>
                    お料理そのものは生成・加工せず、背景・影・色味だけを整えます。
                </div>
            </div>

            <!-- 1. Upload Area -->
            <div class="glass-panel rounded-2xl p-6 flex flex-col gap-4">
                <label class="text-sm font-semibold text-zinc-200 flex items-center justify-between">
                    <span>1. 写真をアップロード</span>
                    <span class="text-xs text-zinc-500">JPG, PNG, WebP (最大 10MB)</span>
                </label>

                <div id="dropZone" class="border-2 border-dashed border-zinc-700 hover:border-indigo-500/60 rounded-xl p-8 text-center cursor-pointer transition bg-zinc-900/40 hover:bg-zinc-900/80 group flex flex-col items-center justify-center gap-3">
                    <input type="file" id="imageInput" accept="image/*" class="hidden">
                    <div class="w-12 h-12 rounded-full bg-zinc-800 group-hover:scale-110 transition flex items-center justify-center text-zinc-400 group-hover:text-indigo-400">
                        <i data-lucide="upload-cloud" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-300">クリックまたは画像をドロップ</p>
                        <p class="text-xs text-zinc-500 mt-1" id="fileStatus">歪みを防ぐため 1.5x〜2x ズーム撮影推奨</p>
                    </div>
                </div>
            </div>

            <!-- 2. Style Presets -->
            <div class="glass-panel rounded-2xl p-6 flex flex-col gap-4">
                <label class="text-sm font-semibold text-zinc-200">2. 背景プリセットを選択</label>

                <div class="grid grid-cols-3 gap-3" id="presetContainer">
                    <button type="button" data-prompt="izakaya" class="preset-btn active p-3 rounded-xl border border-indigo-500 bg-indigo-500/10 flex flex-col items-center gap-2 text-center transition">
                        <span class="text-2xl">🏮</span>
                        <div class="text-xs font-semibold text-zinc-200">居酒屋・和食</div>
                        <div class="text-[10px] text-zinc-400">銘木・温かい照明</div>
                    </button>

                    <button type="button" data-prompt="cafe" class="preset-btn p-3 rounded-xl border border-zinc-800 bg-zinc-900/50 hover:border-zinc-700 flex flex-col items-center gap-2 text-center transition">
                        <span class="text-2xl">☕</span>
                        <div class="text-xs font-semibold text-zinc-200">カフェ・洋食</div>
                        <div class="text-[10px] text-zinc-400">白大理石・自然光</div>
                    </button>

                    <button type="button" data-prompt="ramen" class="preset-btn p-3 rounded-xl border border-zinc-800 bg-zinc-900/50 hover:border-zinc-700 flex flex-col items-center gap-2 text-center transition">
                        <span class="text-2xl">🍜</span>
                        <div class="text-xs font-semibold text-zinc-200">ラーメン・中華</div>
                        <div class="text-[10px] text-zinc-400">黒石板・スポット光</div>
                    </button>
                </div>
            </div>

            <!-- 3. Angle Selection -->
            <div class="glass-panel rounded-2xl p-6 flex flex-col gap-4">
                <label class="text-sm font-semibold text-zinc-200 flex items-center justify-between">
                    <span>3. 撮影アングルを選択</span>
                    <span class="text-xs text-zinc-500">写真の角度に合わせる</span>
                </label>

                <div class="grid grid-cols-3 gap-3" id="angleContainer">
                    <button type="button" data-angle="front" class="angle-btn p-3 rounded-xl border border-zinc-800 bg-zinc-900/50 hover:border-zinc-700 flex flex-col items-center gap-1.5 text-center transition">
                        <span class="text-2xl">🍔</span>
                        <div class="text-xs font-semibold text-zinc-200">正面 (水平)</div>
                        <div class="text-[10px] text-zinc-400">バーガー・高さ</div>
                    </button>

                    <button type="button" data-angle="45" class="angle-btn active p-3 rounded-xl border border-indigo-500 bg-indigo-500/10 flex flex-col items-center gap-1.5 text-center transition">
                        <span class="text-2xl">🍝</span>
                        <div class="text-xs font-semibold text-zinc-200">標準 (45°)</div>
                        <div class="text-[10px] text-zinc-400">一般的な皿料理</div>
                    </button>

                    <button type="button" data-angle="top" class="angle-btn p-3 rounded-xl border border-zinc-800 bg-zinc-900/50 hover:border-zinc-700 flex flex-col items-center gap-1.5 text-center transition">
                        <span class="text-2xl">🍕</span>
                        <div class="text-xs font-semibold text-zinc-200">真上 (俯瞰 90°)</div>
                        <div class="text-[10px] text-zinc-400">ピザ・定食</div>
                    </button>
                </div>
            </div>

            <!-- Action Button -->
            <button id="generateBtn" class="w-full py-4 rounded-xl bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white font-bold text-base shadow-lg shadow-indigo-500/25 hover:opacity-95 active:scale-[0.99] transition flex items-center justify-center gap-2">
                <i data-lucide="wand-2" class="w-5 h-5"></i>
                <span>1クリックでAI補正を実行 (1 クレジット)</span>
            </button>

        </div>

        <!-- Right Preview, Export Station & History Gallery (7 Cols) -->
        <div class="lg:col-span-7 flex flex-col gap-6">

            <!-- Canvas View Area -->
            <div class="glass-panel rounded-2xl p-6 flex flex-col items-center justify-center min-h-[400px] relative overflow-hidden">
                
                <div id="previewEmpty" class="flex flex-col items-center gap-3 text-zinc-600">
                    <i data-lucide="image" class="w-16 h-16 stroke-1"></i>
                    <p class="text-sm font-medium">左側から写真をアップロードしてください</p>
                </div>

                <div id="previewActive" class="hidden w-full h-full flex flex-col items-center justify-center relative">
                    <img id="resultImage" src="" alt="Result" class="max-h-[380px] max-w-full rounded-xl object-contain shadow-2xl border border-zinc-800 transition-all duration-300">
                    <div id="processingOverlay" class="absolute inset-0 bg-black/70 backdrop-blur-sm rounded-xl hidden flex-col items-center justify-center gap-4">
                        <div class="w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-sm font-medium text-zinc-300 animate-pulse">AIが料理の輪郭を抽出し背景を補正中...</p>
                    </div>
                </div>

            </div>

            <!-- Review notice: shown when the pipeline marked the result REVIEW -->
            <div id="reviewNotice" style="display: none" class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 gap-3 items-start" role="status">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-400 shrink-0 mt-0.5"></i>
                <div>
                    <p class="text-sm font-semibold text-amber-300">確認が必要な画像です</p>
                    <p id="reviewNoticeText" class="text-xs text-amber-100/80 mt-0.5 leading-relaxed"></p>
                </div>
            </div>

            <!-- Download Single High-Res Button -->
            <div id="downloadContainer" class="hidden transition-all duration-500 transform translate-y-2 opacity-0">
                <a id="downloadBtn" href="#" download="menu-ai-processed.jpg" target="_blank"
                   class="w-full py-3.5 px-6 rounded-xl bg-gradient-to-r from-emerald-500/90 to-teal-600/90 hover:from-emerald-500 hover:to-teal-600 border border-emerald-400/30 text-white font-bold flex items-center justify-center gap-2 shadow-lg shadow-emerald-950/40 backdrop-blur-md transition duration-200">
                    <i data-lucide="download" class="w-5 h-5"></i>
                    <span>高解像度画像をダウンロード (JPEG)</span>
                </a>
            </div>

            <!-- History Gallery Section -->
            <div id="historySection" class="glass-panel rounded-2xl p-5 flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm font-semibold text-zinc-200">
                        <i data-lucide="history" class="w-4 h-4 text-indigo-400"></i>
                        <span>生成履歴 (直近20件)</span>
                    </div>
                    <button id="refreshHistoryBtn" class="text-xs text-zinc-400 hover:text-white flex items-center gap-1 transition">
                        <i data-lucide="rotate-cw" class="w-3.5 h-3.5"></i>
                        <span>更新</span>
                    </button>
                </div>

                <div id="historyGrid" class="flex gap-3 overflow-x-auto pb-2 min-h-[96px] items-center">
                    <p class="text-xs text-zinc-500 py-4 w-full text-center" id="historyEmptyText">ログインすると過去に生成した画像が表示されます</p>
                </div>
            </div>

            <!-- Export Options -->
            <div class="glass-panel rounded-2xl p-5 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-sm text-zinc-400">
                    <i data-lucide="layers" class="w-4 h-4 text-zinc-400"></i>
                    <span class="font-medium text-zinc-200">一括書き出し:</span>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <button class="px-3 py-1.5 rounded-lg bg-zinc-900 border border-zinc-800 hover:border-zinc-700 text-xs font-medium text-zinc-300 transition flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        Uber Eats (16:9)
                    </button>
                    <button class="px-3 py-1.5 rounded-lg bg-zinc-900 border border-zinc-800 hover:border-zinc-700 text-xs font-medium text-zinc-300 transition flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-red-400"></span>
                        出前館 (1:1)
                    </button>
                    <button class="px-3 py-1.5 rounded-lg bg-zinc-900 border border-zinc-800 hover:border-zinc-700 text-xs font-medium text-zinc-300 transition flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-orange-400"></span>
                        食べログ (4:3)
                    </button>
                    <a href="/api/menu-boards/pdf" target="_blank" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-xs font-semibold text-white transition flex items-center gap-1.5 ml-2">
                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                        A4メニュー表 PDF
                    </a>
                </div>
            </div>

        </div>

    </main>

    <!-- Auth Modal (Login / Register) -->
    <div id="authModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="glass-panel max-w-md w-full rounded-2xl p-6 border border-zinc-800 relative shadow-2xl">
            <button id="closeAuthModalBtn" class="absolute top-4 right-4 text-zinc-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <div class="flex items-center gap-2 mb-6">
                <button id="tabLogin" class="flex-1 pb-2 border-b-2 border-indigo-500 font-bold text-sm text-white text-center">ログイン</button>
                <button id="tabRegister" class="flex-1 pb-2 border-b-2 border-transparent font-medium text-sm text-zinc-400 hover:text-zinc-200 text-center">新規登録 (無料3クレジット)</button>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="flex flex-col gap-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1">メールアドレス</label>
                    <input type="email" id="loginEmail" required class="w-full px-3 py-2 rounded-lg bg-zinc-900 border border-zinc-700 text-sm text-white focus:border-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1">パスワード</label>
                    <input type="password" id="loginPassword" required class="w-full px-3 py-2 rounded-lg bg-zinc-900 border border-zinc-700 text-sm text-white focus:border-indigo-500 focus:outline-none">
                </div>
                <button type="submit" class="w-full py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm transition mt-2">
                    ログインする
                </button>
            </form>

            <!-- Register Form -->
            <form id="registerForm" class="hidden flex flex-col gap-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1">お名前・店舗名</label>
                    <input type="text" id="regName" required class="w-full px-3 py-2 rounded-lg bg-zinc-900 border border-zinc-700 text-sm text-white focus:border-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1">メールアドレス (使い捨て不可)</label>
                    <input type="email" id="regEmail" required class="w-full px-3 py-2 rounded-lg bg-zinc-900 border border-zinc-700 text-sm text-white focus:border-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1">パスワード (6文字以上)</label>
                    <input type="password" id="regPassword" required minlength="6" class="w-full px-3 py-2 rounded-lg bg-zinc-900 border border-zinc-700 text-sm text-white focus:border-indigo-500 focus:outline-none">
                </div>
                <button type="submit" class="w-full py-2.5 rounded-lg bg-gradient-to-r from-indigo-500 to-pink-500 text-white font-semibold text-sm transition mt-2">
                    アカウント作成して 3クレジット獲得
                </button>
            </form>
        </div>
    </div>

    <!-- Client Script -->
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        lucide.createIcons();

        let currentUser = null;
        let deviceFingerprint = 'browser_' + Math.random().toString(36).substring(2, 15);
        let selectedFile = null;
        let selectedPrompt = 'izakaya';
        let selectedAngle = '45';

        // Load FingerprintJS asynchronously
        if (window.FingerprintJS) {
            FingerprintJS.load()
                .then(fp => fp.get())
                .then(result => { deviceFingerprint = result.visitorId; })
                .catch(() => {});
        }

        // DOM Elements
        const authModal = document.getElementById('authModal');
        const openAuthModalBtn = document.getElementById('openAuthModalBtn');
        const closeAuthModalBtn = document.getElementById('closeAuthModalBtn');
        const tabLogin = document.getElementById('tabLogin');
        const tabRegister = document.getElementById('tabRegister');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const userLoggedInView = document.getElementById('userLoggedInView');
        const userLoggedOutView = document.getElementById('userLoggedOutView');
        const creditDisplay = document.getElementById('creditDisplay');
        const userNameDisplay = document.getElementById('userNameDisplay');
        const logoutBtn = document.getElementById('logoutBtn');

        const dropZone = document.getElementById('dropZone');
        const imageInput = document.getElementById('imageInput');
        const fileStatus = document.getElementById('fileStatus');
        const generateBtn = document.getElementById('generateBtn');
        const previewEmpty = document.getElementById('previewEmpty');
        const previewActive = document.getElementById('previewActive');
        const resultImage = document.getElementById('resultImage');
        const processingOverlay = document.getElementById('processingOverlay');
        const presetBtns = document.querySelectorAll('.preset-btn');
        const angleBtns = document.querySelectorAll('.angle-btn');
        const downloadContainer = document.getElementById('downloadContainer');
        const downloadBtn = document.getElementById('downloadBtn');
        const historyGrid = document.getElementById('historyGrid');
        const historyEmptyText = document.getElementById('historyEmptyText');
        const refreshHistoryBtn = document.getElementById('refreshHistoryBtn');
        const reviewNotice = document.getElementById('reviewNotice');
        const reviewNoticeText = document.getElementById('reviewNoticeText');
        const REVIEW_FALLBACK_MESSAGE = '仕上がりの確認をおすすめします。気になる点がないかご確認ください。';

        function showReviewNotice(message) {
            reviewNoticeText.innerText = message || REVIEW_FALLBACK_MESSAGE;
            reviewNotice.style.display = 'flex';
        }

        function hideReviewNotice() {
            reviewNotice.style.display = 'none';
        }

        // Modal Open / Close
        openAuthModalBtn.addEventListener('click', (e) => {
            e.preventDefault();
            authModal.classList.remove('hidden');
            authModal.style.display = 'flex';
        });

        closeAuthModalBtn.addEventListener('click', (e) => {
            e.preventDefault();
            authModal.classList.add('hidden');
            authModal.style.display = 'none';
        });

        authModal.addEventListener('click', (e) => {
            if (e.target === authModal) {
                authModal.classList.add('hidden');
                authModal.style.display = 'none';
            }
        });

        // Tabs
        tabLogin.addEventListener('click', () => {
            tabLogin.className = "flex-1 pb-2 border-b-2 border-indigo-500 font-bold text-sm text-white text-center";
            tabRegister.className = "flex-1 pb-2 border-b-2 border-transparent font-medium text-sm text-zinc-400 hover:text-zinc-200 text-center";
            loginForm.classList.remove('hidden');
            loginForm.style.display = 'flex';
            registerForm.classList.add('hidden');
            registerForm.style.display = 'none';
        });

        tabRegister.addEventListener('click', () => {
            tabRegister.className = "flex-1 pb-2 border-b-2 border-indigo-500 font-bold text-sm text-white text-center";
            tabLogin.className = "flex-1 pb-2 border-b-2 border-transparent font-medium text-sm text-zinc-400 hover:text-zinc-200 text-center";
            registerForm.classList.remove('hidden');
            registerForm.style.display = 'flex';
            loginForm.classList.add('hidden');
            loginForm.style.display = 'none';
        });

        // Check Auth Status
        async function checkAuth() {
            try {
                const res = await fetch('/api/auth/me');
                const data = await res.json();
                if (data.logged_in) {
                    setAuthState(data.user);
                    loadUserHistory();
                } else {
                    setAuthState(null);
                }
            } catch(e) {
                setAuthState(null);
            }
        }
        checkAuth();

        function setAuthState(user) {
            currentUser = user;
            if (user) {
                userLoggedInView.classList.remove('hidden');
                userLoggedInView.style.display = 'flex';
                userLoggedOutView.classList.add('hidden');
                userLoggedOutView.style.display = 'none';
                creditDisplay.innerText = user.credits;
                userNameDisplay.innerText = user.name;
            } else {
                userLoggedInView.classList.add('hidden');
                userLoggedInView.style.display = 'none';
                userLoggedOutView.classList.remove('hidden');
                userLoggedOutView.style.display = 'flex';
                historyGrid.innerHTML = '<p class="text-xs text-zinc-500 py-4 w-full text-center">ログインすると過去に生成した画像が表示されます</p>';
            }
        }

        // Fetch User Generated Images History
        async function loadUserHistory() {
            if (!currentUser) return;
            try {
                const res = await fetch('/api/images');
                const data = await res.json();
                if (res.ok && data.images) {
                    if (data.images.length === 0) {
                        historyGrid.innerHTML = '<p class="text-xs text-zinc-500 py-4 w-full text-center">生成履歴がまだありません</p>';
                        return;
                    }
                    historyGrid.innerHTML = '';
                    data.images.forEach(img => {
                        const targetUrl = img.processed_url || img.original_url;
                        const item = document.createElement('div');
                        item.className = "w-20 h-20 shrink-0 rounded-xl bg-zinc-900 border border-zinc-800 hover:border-indigo-500 overflow-hidden cursor-pointer relative group transition-all duration-200 hover:scale-105";
                        item.innerHTML = `
                            <img src="${targetUrl}" class="w-full h-full object-cover">
                            ${img.review_required ? '<span class="absolute top-1 left-1 px-1.5 py-0.5 rounded-md bg-amber-400 text-[9px] font-bold text-black">要確認</span>' : ''}
                            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 flex items-center justify-center transition">
                                <i data-lucide="eye" class="w-4 h-4 text-white"></i>
                            </div>
                        `;
                        item.addEventListener('click', () => {
                            previewEmpty.classList.add('hidden');
                            previewEmpty.style.display = 'none';
                            previewActive.classList.remove('hidden');
                            previewActive.style.display = 'flex';
                            resultImage.src = targetUrl;
                            img.review_required ? showReviewNotice(null) : hideReviewNotice();

                            if (img.processed_url) {
                                downloadBtn.href = img.processed_url;
                                downloadBtn.download = `menu_ai_${img.id}.jpg`;
                                downloadContainer.classList.remove('hidden', 'opacity-0', 'translate-y-2');
                                downloadContainer.classList.add('opacity-100', 'translate-y-0');
                            }
                        });
                        historyGrid.appendChild(item);
                    });
                    lucide.createIcons();
                }
            } catch(e) {}
        }

        refreshHistoryBtn.addEventListener('click', loadUserHistory);

        // Register Submission
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const res = await fetch('/api/auth/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({
                    name: document.getElementById('regName').value,
                    email: document.getElementById('regEmail').value,
                    password: document.getElementById('regPassword').value,
                    device_fingerprint: deviceFingerprint
                })
            });
            const data = await res.json();
            if (res.ok) {
                authModal.classList.add('hidden');
                authModal.style.display = 'none';
                setAuthState(data.user);
                loadUserHistory();
                alert('登録が完了しました！3クレジットが付与されました。');
            } else {
                alert(data.message || '登録に失敗しました。');
            }
        });

        // Login Submission
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const res = await fetch('/api/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({
                    email: document.getElementById('loginEmail').value,
                    password: document.getElementById('loginPassword').value,
                })
            });
            const data = await res.json();
            if (res.ok) {
                authModal.classList.add('hidden');
                authModal.style.display = 'none';
                setAuthState(data.user);
                loadUserHistory();
            } else {
                alert(data.message || 'ログインに失敗しました。');
            }
        });

        // Logout
        logoutBtn.addEventListener('click', async () => {
            await fetch('/api/auth/logout', { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } });
            setAuthState(null);
            location.reload();
        });

        // File Selection & Drag Drop
        dropZone.addEventListener('click', () => imageInput.click());
        imageInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) handleFile(e.target.files[0]);
        });
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('border-indigo-500'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('border-indigo-500'));
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-indigo-500');
            if (e.dataTransfer.files.length > 0) handleFile(e.dataTransfer.files[0]);
        });

        function handleFile(file) {
            selectedFile = file;
            fileStatus.innerText = file.name;
            fileStatus.classList.add('text-indigo-400', 'font-medium');
            hideReviewNotice();

            downloadContainer.classList.add('hidden', 'opacity-0', 'translate-y-2');
            downloadContainer.classList.remove('opacity-100', 'translate-y-0');

            const reader = new FileReader();
            reader.onload = (e) => {
                previewEmpty.classList.add('hidden');
                previewEmpty.style.display = 'none';
                previewActive.classList.remove('hidden');
                previewActive.style.display = 'flex';
                resultImage.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        // Preset Switching
        presetBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                presetBtns.forEach(b => {
                    b.classList.remove('active', 'border-indigo-500', 'bg-indigo-500/10');
                    b.classList.add('border-zinc-800', 'bg-zinc-900/50');
                });
                btn.classList.add('active', 'border-indigo-500', 'bg-indigo-500/10');
                btn.classList.remove('border-zinc-800', 'bg-zinc-900/50');
                selectedPrompt = btn.getAttribute('data-prompt');
            });
        });

        // Angle Switching
        angleBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                angleBtns.forEach(b => {
                    b.classList.remove('active', 'border-indigo-500', 'bg-indigo-500/10');
                    b.classList.add('border-zinc-800', 'bg-zinc-900/50');
                });
                btn.classList.add('active', 'border-indigo-500', 'bg-indigo-500/10');
                btn.classList.remove('border-zinc-800', 'bg-zinc-900/50');
                selectedAngle = btn.getAttribute('data-angle');
            });
        });

        // Generate AI Background with Polling
        generateBtn.addEventListener('click', async () => {
            if (!currentUser) {
                alert('AI補正を実行するにはログインが必要です。');
                openAuthModalBtn.click();
                return;
            }
            if (currentUser.credits < 1) {
                alert('クレジットが不足しています。プランを購入してください。');
                return;
            }
            if (!selectedFile) {
                alert('写真をアップロードしてください。');
                return;
            }

            downloadContainer.classList.add('hidden', 'opacity-0', 'translate-y-2');
            downloadContainer.classList.remove('opacity-100', 'translate-y-0');
            hideReviewNotice();

            processingOverlay.classList.remove('hidden');
            processingOverlay.style.display = 'flex';

            const formData = new FormData();
            formData.append('image', selectedFile);
            formData.append('prompt', selectedPrompt);
            formData.append('angle', selectedAngle); // not used by the Standard pipeline yet

            try {
                const response = await fetch('/api/images/upload', {
                    method: 'POST',
                    headers: { 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                });
                const result = await response.json();

                if (response.ok && result.data) {
                    const imageId = result.data.id;
                    currentUser.credits = result.data.remaining_credits;
                    creditDisplay.innerText = currentUser.credits;

                    let attempts = 0;
                    const maxAttempts = 180;
                    const interval = setInterval(async () => {
                        attempts++;
                        try {
                            const statusRes = await fetch(`/api/images/${imageId}/status`, {
                                headers: { 'Accept': 'application/json' }
                            });
                            const statusData = await statusRes.json();

                            if (statusData.status === 'completed' && statusData.processed_url) {
                                clearInterval(interval);

                                const finalUrl = statusData.processed_url + '?t=' + new Date().getTime();
                                resultImage.src = finalUrl;

                                downloadBtn.href = finalUrl;
                                downloadBtn.download = `menu_ai_${imageId}.jpg`;
                                statusData.review_required ? showReviewNotice(statusData.message) : hideReviewNotice();

                                processingOverlay.classList.add('hidden');
                                processingOverlay.style.display = 'none';

                                downloadContainer.classList.remove('hidden');
                                setTimeout(() => {
                                    downloadContainer.classList.remove('opacity-0', 'translate-y-2');
                                    downloadContainer.classList.add('opacity-100', 'translate-y-0');
                                    lucide.createIcons();
                                }, 50);

                                loadUserHistory();

                            } else if (statusData.status === 'failed' || attempts >= maxAttempts) {
                                clearInterval(interval);
                                processingOverlay.classList.add('hidden');
                                processingOverlay.style.display = 'none';
                                alert(statusData.message || '画像の補正処理に失敗しました。');
                            }
                        } catch (e) {
                            clearInterval(interval);
                            processingOverlay.classList.add('hidden');
                            processingOverlay.style.display = 'none';
                        }
                    }, 1000);

                } else {
                    processingOverlay.classList.add('hidden');
                    processingOverlay.style.display = 'none';
                    alert(result.message || 'アップロードに失敗しました。');
                }
            } catch (err) {
                processingOverlay.classList.add('hidden');
                processingOverlay.style.display = 'none';
                alert('通信エラーが発生しました: ' + err.message);
            }
        });
    </script>
</body>
</html>