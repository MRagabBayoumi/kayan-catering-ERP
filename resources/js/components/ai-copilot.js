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
            toggleDrawer();
        });
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            drawer.classList.add('hidden');
        });
    }

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !drawer.classList.contains('hidden')) {
            drawer.classList.add('hidden');
        }
    });

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
                    let formattedHtml = `<div class="ai-response-headline">${escapeHtml(data.response.headline || '')}</div>`;
                    formattedHtml += `<div class="ai-response-message">${formatMarkdown(data.response.message || '')}</div>`;

                    if (data.response.recommendations && data.response.recommendations.length > 0) {
                        formattedHtml += `<div class="ai-response-recommendations">
                            <div class="ai-rec-title">💡 توصيات تشغيلية:</div>
                            <div class="ai-rec-list">${data.response.recommendations.map(r => `<div class="ai-rec-item">• ${escapeHtml(r)}</div>`).join('')}</div>
                        </div>`;
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
        if (!text) return '';
        return text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/_(.*?)_/g, '<em>$1</em>')
            .replace(/\[(BATCH-[^\]]+)\]/g, '<span class="ai-batch-tag">[$1]</span>');
    }
}

document.addEventListener('DOMContentLoaded', initAiCopilot);
