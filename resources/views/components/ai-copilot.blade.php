<!-- Kayan Catering AI Copilot Widget -->
<div id="aiCopilotWidget" class="ai-copilot-container" dir="rtl">
    <!-- Slide-over AI Chat Panel -->
    <div id="aiChatDrawer" class="ai-chat-drawer hidden">
        <!-- Drawer Header -->
        <div class="ai-drawer-header">
            <div class="ai-header-title">
                <div class="ai-avatar">
                    <x-icon name="sparkles" size="18" />
                </div>
                <div>
                    <h3 class="ai-header-name">Kayan AI Copilot</h3>
                    <p class="ai-header-subtitle">مساعدك الذكي لإدارة المخازن والمقننات والتكاليف</p>
                </div>
            </div>
            <button id="aiCloseBtn" type="button" class="ai-close-btn" title="إغلاق" aria-label="إغلاق المساعد الذكي">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Quick Prompt Suggestions (Pills) -->
        <div class="ai-quick-prompts">
            <div class="ai-quick-title">أسئلة سريعة ومقترحة:</div>
            <div class="ai-pills-row">
                <button type="button" class="ai-pill-btn" data-query="عندي قد ايه لحمه">
                    <x-icon name="package" size="14" class="text-primary-600" />
                    <span>رصيد اللحوم بالمخزن</span>
                </button>
                <button type="button" class="ai-pill-btn" data-query="المناقصه مثلا بتاع الجيزه وجبة الغداء كانت بكام السنه الي فاتت">
                    <x-icon name="file-text" size="14" class="text-amber-600" />
                    <span>مناقصة الجيزة وجبة الغداء بكام؟</span>
                </button>
                <button type="button" class="ai-pill-btn" data-query="تكلفة مستشفي امبابه كام و معجزه ولا مهدره في البضاعه و صافي ربحها بعد نسبة المطبخ و ضرائب و دمعات و تامينات و عمولة شريك">
                    <x-icon name="hospital" size="14" class="text-rose-600" />
                    <span>تكلفة وأرباح مستشفى إمبابة</span>
                </button>
                <button type="button" class="ai-pill-btn" data-query="انا اكلت كام وجبه النهارده">
                    <x-icon name="utensils" size="14" class="text-emerald-600" />
                    <span>عدد الوجبات المنفذة اليوم</span>
                </button>
                <button type="button" class="ai-pill-btn" data-query="حساب مورد فلان كام اخر مره واخد دفعه امتي">
                    <x-icon name="users" size="14" class="text-cyan-600" />
                    <span>كشوفات الموردين والدفعات</span>
                </button>
            </div>
        </div>

        <!-- Chat Stream Messages Box -->
        <div id="aiMessagesBox" class="ai-messages-box">
            <!-- Welcome message from AI -->
            <div class="ai-msg ai-msg-bot">
                <div class="ai-bubble">
                    <div class="ai-welcome-header">
                        <span class="ai-welcome-icon flex items-center justify-center text-primary-600">
                            <x-icon name="sparkles" size="16" />
                        </span>
                        <span class="ai-welcome-title">أهلاً بك يا فندم!</span>
                    </div>
                    <p class="ai-welcome-body">
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
                <button type="submit" id="aiSendBtn" class="ai-send-btn" title="إرسال" aria-label="إرسال السؤال">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>
