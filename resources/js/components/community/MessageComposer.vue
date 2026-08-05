<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Color, TextStyle } from '@tiptap/extension-text-style';
import Underline from '@tiptap/extension-underline';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { useForm } from '@inertiajs/vue3';
import { Bold, Code, Italic, List, ListOrdered, Paperclip, Quote, Send, Strikethrough, Underline as UnderlineIcon, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    channelId: number;
    canSendTaskCard?: boolean;
}>();

const emit = defineEmits<{ sent: [] }>();

const isTaskMode = ref(false);
const taskTitle = ref('');
const attachmentInput = ref<HTMLInputElement | null>(null);
const attachments = ref<File[]>([]);
const sending = ref(false);

const COLORS = ['#111827', '#dc2626', '#2563eb', '#16a34a', '#d97706'];

function send() {
    if (sending.value) {
        return;
    }

    const html = editor.value?.getHTML() ?? '';
    const isEmpty = editor.value?.isEmpty ?? true;

    if (isTaskMode.value && !taskTitle.value.trim()) {
        return;
    }

    if (!isTaskMode.value && isEmpty) {
        return;
    }

    sending.value = true;

    useForm({
        type: isTaskMode.value ? 'task_card' : 'text',
        body: isEmpty ? null : html,
        meta: isTaskMode.value ? { title: taskTitle.value } : null,
        attachments: attachments.value,
    }).post(route('community.messages.store', props.channelId), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            editor.value?.commands.clearContent();
            taskTitle.value = '';
            isTaskMode.value = false;
            attachments.value = [];
            emit('sent');
        },
        onFinish: () => {
            sending.value = false;
        },
    });
}

const editor = useEditor({
    extensions: [StarterKit, Underline, TextStyle, Color],
    editorProps: {
        attributes: { class: 'tiptap-content min-h-[2.5rem] max-h-40 overflow-y-auto' },
        handleKeyDown: (_view, event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                send();

                return true;
            }

            return false;
        },
    },
});

function toggleTaskMode() {
    isTaskMode.value = !isTaskMode.value;

    if (!isTaskMode.value) {
        taskTitle.value = '';
    }
}

function pickAttachments() {
    attachmentInput.value?.click();
}

function onAttachmentsChosen(event: Event) {
    const files = (event.target as HTMLInputElement).files;

    if (files) {
        attachments.value.push(...Array.from(files));
    }

    (event.target as HTMLInputElement).value = '';
}

function removeAttachment(index: number) {
    attachments.value.splice(index, 1);
}
</script>

<template>
    <div class="border-t border-sidebar-border/70 p-3 dark:border-sidebar-border">
        <div v-if="isTaskMode" class="mb-2 flex items-center gap-2 rounded-md border border-border bg-muted/50 p-2">
            <span class="text-xs font-medium text-muted-foreground">Task title</span>
            <Input v-model="taskTitle" placeholder="e.g. Reminder: for next time" class="h-8" />
            <Button variant="ghost" size="icon" class="size-8 shrink-0" @click="toggleTaskMode">
                <X class="size-4" />
            </Button>
        </div>

        <div v-if="attachments.length" class="mb-2 flex flex-wrap gap-2">
            <div v-for="(file, index) in attachments" :key="index" class="flex items-center gap-1 rounded-md bg-muted px-2 py-1 text-xs">
                <span class="max-w-40 truncate">{{ file.name }}</span>
                <button type="button" class="text-muted-foreground hover:text-foreground" @click="removeAttachment(index)">
                    <X class="size-3" />
                </button>
            </div>
        </div>

        <div class="rounded-md border border-input bg-background">
            <div class="flex flex-wrap items-center gap-0.5 border-b border-border p-1">
                <Button variant="ghost" size="icon" class="size-7" :class="{ 'bg-accent': editor?.isActive('bold') }" @click="editor?.chain().focus().toggleBold().run()">
                    <Bold class="size-3.5" />
                </Button>
                <Button variant="ghost" size="icon" class="size-7" :class="{ 'bg-accent': editor?.isActive('italic') }" @click="editor?.chain().focus().toggleItalic().run()">
                    <Italic class="size-3.5" />
                </Button>
                <Button variant="ghost" size="icon" class="size-7" :class="{ 'bg-accent': editor?.isActive('underline') }" @click="editor?.chain().focus().toggleUnderline().run()">
                    <UnderlineIcon class="size-3.5" />
                </Button>
                <Button variant="ghost" size="icon" class="size-7" :class="{ 'bg-accent': editor?.isActive('strike') }" @click="editor?.chain().focus().toggleStrike().run()">
                    <Strikethrough class="size-3.5" />
                </Button>
                <Button variant="ghost" size="icon" class="size-7" :class="{ 'bg-accent': editor?.isActive('blockquote') }" @click="editor?.chain().focus().toggleBlockquote().run()">
                    <Quote class="size-3.5" />
                </Button>
                <Button variant="ghost" size="icon" class="size-7" :class="{ 'bg-accent': editor?.isActive('codeBlock') }" @click="editor?.chain().focus().toggleCodeBlock().run()">
                    <Code class="size-3.5" />
                </Button>
                <Button variant="ghost" size="icon" class="size-7" :class="{ 'bg-accent': editor?.isActive('bulletList') }" @click="editor?.chain().focus().toggleBulletList().run()">
                    <List class="size-3.5" />
                </Button>
                <Button variant="ghost" size="icon" class="size-7" :class="{ 'bg-accent': editor?.isActive('orderedList') }" @click="editor?.chain().focus().toggleOrderedList().run()">
                    <ListOrdered class="size-3.5" />
                </Button>
                <div class="mx-1 flex items-center gap-0.5">
                    <button
                        v-for="color in COLORS"
                        :key="color"
                        type="button"
                        class="size-4 rounded-full ring-offset-1 hover:ring-1 hover:ring-ring"
                        :style="{ backgroundColor: color }"
                        @click="editor?.chain().focus().setColor(color).run()"
                    />
                </div>

                <div class="ml-auto flex items-center gap-0.5">
                    <Button v-if="canSendTaskCard" variant="ghost" size="sm" class="h-7 text-xs" :class="{ 'bg-accent': isTaskMode }" @click="toggleTaskMode">
                        + Task
                    </Button>
                    <Button variant="ghost" size="icon" class="size-7" @click="pickAttachments">
                        <Paperclip class="size-3.5" />
                    </Button>
                    <input ref="attachmentInput" type="file" multiple class="hidden" @change="onAttachmentsChosen" />
                </div>
            </div>

            <EditorContent :editor="editor" class="p-2" />
        </div>

        <div class="mt-1 flex items-center justify-between">
            <p class="text-xs text-muted-foreground">Press Enter to send, Shift+Enter for a new line</p>
            <Button size="sm" :disabled="sending" @click="send">
                <Send class="mr-1 size-3.5" />
                Send
            </Button>
        </div>
    </div>
</template>
