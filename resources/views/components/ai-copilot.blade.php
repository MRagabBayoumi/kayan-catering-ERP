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

<style>
/* AI Copilot Styling matching Kayan ERP */
.ai-copilot-container {
    position: fixed;
    bottom: 24px;
    left: 24px;
    z-index: 9999;
    font-family: inherit;
}

.ai-launcher-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #1e293b, #0f172a);
    color: #f8fafc;
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 10px 18px;
    border-radius: 9999px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 0 15px rgba(59, 130, 246, 0.3);
    cursor: pointer;
    transition: all 0.25s ease;
}

.ai-launcher-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.4), 0 0 20px rgba(59, 130, 246, 0.5);
    border-color: #3b82f6;
}

.ai-btn-icon {
    display: flex;
    align-items: center;
    color: #60a5fa;
}

.ai-btn-label {
    font-weight: 600;
    font-size: 0.875rem;
}

.ai-pulse-dot {
    width: 8px;
    height: 8px;
    background-color: #10b981;
    border-radius: 50%;
    box-shadow: 0 0 8px #10b981;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.2); }
}

.ai-chat-drawer {
    position: fixed;
    bottom: 80px;
    left: 24px;
    width: 440px;
    max-width: calc(100vw - 32px);
    height: 580px;
    max-height: calc(100vh - 120px);
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
    border: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

[data-theme="dark"] .ai-chat-drawer {
    background: #0f172a;
    border-color: #1e293b;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
}

.ai-chat-drawer.hidden {
    display: none;
}

.ai-drawer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

[data-theme="dark"] .ai-drawer-header {
    background: #1e293b;
    border-color: #334155;
}

.ai-header-title {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ai-avatar {
    font-size: 20px;
    background: #e0e7ff;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.ai-close-btn {
    background: transparent;
    border: none;
    font-size: 24px;
    color: #64748b;
    cursor: pointer;
    line-height: 1;
}

.ai-close-btn:hover {
    color: #ef4444;
}

.ai-quick-prompts {
    padding: 10px 14px;
    background: #f1f5f9;
    border-bottom: 1px solid #e2e8f0;
}

[data-theme="dark"] .ai-quick-prompts {
    background: #1e293b;
    border-color: #334155;
}

.ai-pills-row {
    display: flex;
    gap: 6px;
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: thin;
}

.ai-pill-btn {
    white-space: nowrap;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155;
    font-size: 0.725rem;
    padding: 4px 10px;
    border-radius: 9999px;
    cursor: pointer;
    transition: all 0.15s;
}

[data-theme="dark"] .ai-pill-btn {
    background: #0f172a;
    border-color: #334155;
    color: #cbd5e1;
}

.ai-pill-btn:hover {
    background: #3b82f6;
    color: #ffffff;
    border-color: #3b82f6;
}

.ai-messages-box {
    flex: 1;
    padding: 16px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.ai-msg {
    display: flex;
    width: 100%;
}

.ai-msg-user {
    justify-content: flex-end;
}

.ai-msg-bot {
    justify-content: flex-start;
}

.ai-bubble {
    max-width: 90%;
    padding: 12px 14px;
    border-radius: 14px;
    font-size: 0.825rem;
    line-height: 1.5;
}

.ai-msg-user .ai-bubble {
    background: #2563eb;
    color: #ffffff;
    border-bottom-left-radius: 2px;
}

.ai-msg-bot .ai-bubble {
    background: #f1f5f9;
    color: #1e293b;
    border-bottom-right-radius: 2px;
    border: 1px solid #e2e8f0;
}

[data-theme="dark"] .ai-msg-bot .ai-bubble {
    background: #1e293b;
    color: #f8fafc;
    border-color: #334155;
}

.ai-drawer-footer {
    padding: 10px 14px;
    border-top: 1px solid #e2e8f0;
    background: #ffffff;
}

[data-theme="dark"] .ai-drawer-footer {
    background: #0f172a;
    border-color: #1e293b;
}

.ai-input-form {
    display: flex;
    gap: 8px;
}

.ai-input-field {
    flex: 1;
    padding: 9px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.85rem;
    background: #f8fafc;
    color: inherit;
    outline: none;
}

[data-theme="dark"] .ai-input-field {
    background: #1e293b;
    border-color: #334155;
}

.ai-input-field:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.ai-send-btn {
    background: #2563eb;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0 14px;
    cursor: pointer;
    transition: background 0.2s;
}

.ai-send-btn:hover {
    background: #1d4ed8;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const launcher = document.getElementById('aiLauncherBtn');
    const drawer = document.getElementById('aiChatDrawer');
    const closeBtn = document.getElementById('aiCloseBtn');
    const form = document.getElementById('aiPromptForm');
    const input = document.getElementById('aiPromptInput');
    const box = document.getElementById('aiMessagesBox');
    const pills = document.querySelectorAll('.ai-pill-btn');

    if (!launcher || !drawer) return;

    // Toggle drawer
    launcher.addEventListener('click', () => {
        drawer.classList.toggle('hidden');
        if (!drawer.classList.contains('hidden')) {
            input.focus();
        }
    });

    closeBtn.addEventListener('click', () => {
        drawer.classList.add('hidden');
    });

    // Pill click
    pills.forEach(pill => {
        pill.addEventListener('click', function () {
            const query = this.getAttribute('data-query');
            if (query) {
                input.value = query;
                form.dispatchEvent(new Event('submit'));
            }
        });
    });

    // Form submit
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;

        // Append user bubble
        appendMessage(text, 'user');
        input.value = '';

        // Append loading indicator
        const loadingId = 'loading-' + Date.now();
        appendMessage('⏳ جاري استدعاء البيانات والتحليل المالي...', 'bot', loadingId);

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch('/ai/ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ prompt: text })
            });

            const data = await res.json();
            const loadingEl = document.getElementById(loadingId);
            if (loadingEl) loadingEl.remove();

            if (data.success && data.response) {
                let formattedHtml = `<div class="font-bold text-blue-600 dark:text-blue-400 mb-1">${escapeHtml(data.response.headline || '')}</div>`;
                formattedHtml += `<div class="whitespace-pre-line text-xs">${formatMarkdown(data.response.message || '')}</div>`;

                if (data.response.recommendations && data.response.recommendations.length > 0) {
                    formattedHtml += `<div class="mt-2 pt-2 border-t border-slate-200 dark:border-slate-700 text-[11px] text-amber-600 dark:text-amber-400 font-semibold">💡 توصيات: ${data.response.recommendations.map(r => `<div>• ${escapeHtml(r)}</div>`).join('')}</div>`;
                }

                appendMessage(formattedHtml, 'bot', null, true);
            } else {
                appendMessage('عذراً، حدث خطأ أثناء معالجة السؤال.', 'bot');
            }
        } catch (err) {
            const loadingEl = document.getElementById(loadingId);
            if (loadingEl) loadingEl.remove();
            appendMessage('تعذر الاتصال بالخادم، يرجى المحاولة لاحقاً.', 'bot');
        }
    });

    function appendMessage(content, sender, id = null, isHtml = false) {
        const row = document.createElement('div');
        row.className = `ai-msg ai-msg-${sender}`;
        if (id) row.id = id;

        const bubble = document.createElement('div');
        bubble.className = 'ai-bubble';

        if (isHtml) {
            bubble.innerHTML = content;
        } else {
            bubble.textContent = content;
        }

        row.appendChild(bubble);
        box.appendChild(row);
        box.scrollTop = box.scrollHeight;
    }

    function escapeHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function formatMarkdown(text) {
        return text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/_(.*?)_/g, '<em>$1</em>');
    }
});
</script>
