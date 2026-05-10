const FLOWFORM_AI_MODEL = 'deepseek/deepseek-chat';

const FLOWFORM_AI_SYSTEM_PROMPT = `You are FlowForm AI assistant.
When user describes a form return ONLY JSON:
{
  "sequences": [
    {
      "name": "Requested By",
      "assigned_employee_names": ["Employee Name"]
    }
  ],
  "fields": [
    {
      "label": "Field Name",
      "type": "text|date|number|dropdown|file|checkbox|user_dropdown",
      "required": true,
      "placeholder": "placeholder text",
      "options": ["opt1", "opt2"],
      "sequence": "Requested By",
      "assigned_employee_name": "Employee Name"
    }
  ],
  "css": {
    "primaryColor": "#hexcode",
    "backgroundColor": "#hexcode",
    "borderRadius": "8px",
    "fontFamily": "Inter"
  },
  "message": "Human readable response"
}
If the user names employees, use exact names from the provided employee list. Put fields under the sequence owned by that employee. Use sequence names like "Requested By", "Received By", "Approved By", or a clear workflow step from the prompt.
Return ONLY valid JSON. Nothing else.`;

function appendAiMessage(text, className = 'ai-bubble') {
    const container = document.getElementById('ai-messages');
    if (!container) {
        return null;
    }

    const bubble = document.createElement('div');
    bubble.className = className;
    bubble.textContent = text;
    container.appendChild(bubble);
    container.scrollTop = container.scrollHeight;
    return bubble;
}

function parseAiJson(rawText) {
    try {
        return JSON.parse(rawText);
    } catch (error) {
        const match = rawText.match(/\{[\s\S]*\}/);
        if (!match) {
            throw new Error('AI did not return valid JSON.');
        }
        return JSON.parse(match[0]);
    }
}

async function sendToAI() {
    const input = document.getElementById('ai-input');
    if (!input) {
        return;
    }

    const styleImage = document.getElementById('theme-style-image');
    const styleFile = styleImage && styleImage.files && styleImage.files[0] ? styleImage.files[0] : null;
    const prompt = input.value.trim();
    if (!prompt) {
        appendAiMessage('Describe the form first so the builder has something to generate.', 'ai-bubble-error');
        return;
    }

    appendAiMessage(styleFile ? `${prompt}\n\nStyle image: ${styleFile.name}` : prompt, 'ai-bubble-user');
    input.value = '';

    const pendingBubble = appendAiMessage('Generating fields and theme suggestions...', 'ai-bubble');

    if (typeof puter === 'undefined' || !puter.ai || typeof puter.ai.chat !== 'function') {
        pendingBubble.textContent = 'Puter.js is not available in this browser session.';
        pendingBubble.className = 'ai-bubble-error';
        return;
    }

    try {
        const employeeContext = window.FlowFormBuilder && typeof window.FlowFormBuilder.employeePromptContext === 'function'
            ? window.FlowFormBuilder.employeePromptContext()
            : '';

        const response = await puter.ai.chat([
            { role: 'system', content: FLOWFORM_AI_SYSTEM_PROMPT },
            {
                role: 'user',
                content: styleFile
                    ? `${prompt}\n\nAvailable employees:\n${employeeContext}\n\nA style reference image named "${styleFile.name}" will be attached to the form. Suggest matching colors, border radius, and font choices based on a clean internal business form style.`
                    : `${prompt}\n\nAvailable employees:\n${employeeContext}`,
            },
        ], {
            model: FLOWFORM_AI_MODEL,
            temperature: 0.2,
        });

        const rawContent = response && response.message && response.message.content ? response.message.content : '';
        const payload = parseAiJson(String(rawContent || ''));

        if (!window.FlowFormBuilder || typeof window.FlowFormBuilder.applyAiSuggestion !== 'function') {
            throw new Error('Builder runtime is not ready.');
        }

        window.FlowFormBuilder.applyAiSuggestion(payload);

        pendingBubble.textContent = payload.message || 'Form suggestions applied to the builder.';
        pendingBubble.className = 'ai-bubble';
    } catch (error) {
        pendingBubble.textContent = error.message || 'AI generation failed.';
        pendingBubble.className = 'ai-bubble-error';
    }
}
