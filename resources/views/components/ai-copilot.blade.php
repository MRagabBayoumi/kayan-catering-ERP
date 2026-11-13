<!-- Kayan Catering AI Copilot Floating Widget -->
<div id="aiCopilotWidget" class="ai-copilot-container" dir="rtl">
    <!-- Floating Launcher Trigger Button -->
    <button id="aiLauncherBtn" class="ai-launcher-btn" type="button" title="مساعد كيان الذكي (AI Copilot)">
        <span class="ai-btn-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2a10 10 0 0 1 10 10c0 5.523-4.477 10-10 10S2 17.523 2 12A10 10 0 0 1 12 2z"></path>
                <path d="m9 12 2 2 4-4"></path>
                <path d="M12 6v2"></path>
                <path d="M12 16v2"></path>
            </svg>
        </span>
        <span class="ai-btn-label">مساعد كيان الذكي</span>
        <span class="ai-pulse-dot"></span>
    </button>

    <!-- Slide-over AI Chat Panel -->
    <div id="aiChatDrawer" class="ai-chat-drawer hidden">
        <!-- Drawer Header -->
        <div class="ai-drawer-header">
            <div class="ai-header-title">
                <div class="ai-avatar">🤖</div>
                <div>
                    <h3 class="font-bold text-sm">Kayan AI Copilot</h3>
                    <p class="text-xs text-slate-400">مساعدك الذكي لإدارة المخازن والمقننات والتكاليف</p>
                </div>
            </div>
            <button id="aiCloseBtn" type="button" class="ai-close-btn" title="إغلاق">&times;</button>
        </div>

        <!-- Quick Prompt Suggestions (Pills) -->
        <div class="ai-quick-prompts">
            <span class="text-xs text-slate-400 block mb-1">أسئلة سريعة:</span>
            <div class="ai-pills-row">
                <button type="button" class="ai-pill-btn" data-query="عندي قد ايه لحمه">🥩 عندي قد ايه لحمة؟</button>
                <button type="button" class="ai-pill-btn" data-query="المناقصه مثلا بتاع الجيزه وجبة الغداء كانت بكام السنه الي فاتت">🎯 مناقصة الجيزة وجبة الغداء بكام؟</button>
                <button type="button" class="ai-pill-btn" data-query="تكلفة مستشفي امبابه كام و معجزه ولا مهدره في البضاعه و صافي ربحها بعد نسبة المطبخ و ضرائب و دمعات و تامينات و عمولة شريك">🏥 تكلفة وأرباح مستشفى إمبابة؟</button>
                <button type="button" class="ai-pill-btn" data-query="انا اكلت كام وجبه النهارده">🍽️ أكلت كام وجبة النهاردة؟</button>
                <button type="button" class="ai-pill-btn" data-query="حساب مورد فلان كام اخر مره واخد دفعه امتي">🤝 كشف حساب الموردين وآخر دفعة؟</button>
            </div>
        </div>

        <!-- Chat Stream Messages Box -->
        <div id="aiMessagesBox" class="ai-messages-box">
            <!-- Welcome message from AI -->
            <div class="ai-msg ai-msg-bot">
                <div class="ai-bubble">
                    <p class="font-semibold text-primary mb-1">أهلاً بك يا فندم! 🌸</p>
                    <p class="text-xs leading-relaxed text-slate-700 dark:text-slate-200">
                        أنا مساعدك اللحظي المتصل مباشرة بقاعدة بيانات وتشغيلات الكيترينج. اسألني عن أرصدة الباتشات، تكاليف المستشفيات، المقننات والهادر، أو المناقصات وأسعار المنافسين السابقة.
                    </p>
                </div>
            </div>
        </div>

        <!-- Chat Input Footer -->
        <div class="ai-drawer-footer">
            <form id="aiPromptForm" class="ai-input-form">
                <input 
                    type="text" 
                    id="aiPromptInput" 
                    class="ai-input-field" 
                    placeholder="اكتب سؤالك بالعامية أو الفصحى (مثلاً: عندي قد ايه لحمة؟)..." 
                    autocomplete="off" 
                    required
                />
                <button type="submit" id="aiSendBtn" class="ai-send-btn" title="إرسال">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>
