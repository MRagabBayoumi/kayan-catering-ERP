/**
 * Kayan ERP — AI Copilot Drawer & Assistant
 */
export function initAiCopilot() {
    const launcher = document.getElementById('aiLauncherBtn');
    const drawer = document.getElementById('aiChatDrawer');
    const closeBtn = document.getElementById('aiCloseBtn');
    const form = document.getElementById('aiPromptForm');
    const input = document.getElementById('aiPromptInput');
    const box = document.getElementById('aiMessagesBox');
    const pills = document.querySelectorAll('.ai-pill-btn');
    const sidebarAiTriggers = document.querySelectorAll('[data-action="open-ai-copilot"]');

    if (!drawer) return;

    function openDrawer() {
        drawer.classList.remove('hidden');
        if (input) {
            input.focus();
        }
    }

    function toggleDrawer() {
        drawer.classList.toggle('hidden');
        if (!drawer.classList.contains('hidden') && input) {
            input.focus();
        }
    }

    if (launcher) {
        launcher.addEventListener('click', toggleDrawer);
    }

    sidebarAiTriggers.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openDrawer();
        });
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            drawer.classList.add('hidden');
        });
    }

    // Pill click
    pills.forEach(pill => {
        pill.addEventListener('click', function () {
            const query = this.getAttribute('data-query');
            if (query && input && form) {
                input.value = query;
                form.dispatchEvent(new Event('submit'));
            }
        });
    });

    // Form submit
    if (form && input && box) {
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
    }

    function appendMessage(content, sender, id = null, isHtml = false) {
        if (!box) return;
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
}

document.addEventListener('DOMContentLoaded', initAiCopilot);
