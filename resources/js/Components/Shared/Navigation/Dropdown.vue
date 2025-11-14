<script setup>
import { computed, onMounted, onUnmounted, ref, nextTick, watch } from 'vue';
import { computePosition, autoUpdate, offset, flip, shift, size } from '@floating-ui/dom';

const props = defineProps({
    align: {
        type: String,
        default: 'right',
    },
    placement: {
        type: String,
        default: 'bottom-end',
    },
    width: {
        type: String,
        default: '48',
    },
    contentClasses: {
        type: String,
        default: 'py-1 bg-white',
    },
});

const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const widthClass = computed(() => {
    return {
        32: 'w-32',
        48: 'w-48',
    }[props.width.toString()];
});

// Map align prop to placement for backward compatibility
const effectivePlacement = computed(() => {
    if (props.placement !== 'bottom-end') {
        return props.placement;
    }
    
    const alignMap = {
        'left': 'bottom-start',
        'right': 'bottom-end',
        'top': 'top-end',
    };
    
    return alignMap[props.align] || 'bottom-end';
});

const open = ref(false);
const triggerRef = ref(null);
const floatingRef = ref(null);
let cleanup = null;

const updatePosition = async () => {
    if (!triggerRef.value || !floatingRef.value) return;
    
    const { x, y, middlewareData } = await computePosition(triggerRef.value, floatingRef.value, {
        placement: effectivePlacement.value,
        strategy: 'fixed',
        middleware: [
            offset(4),
            flip(),
            shift({ padding: 8 }),
            size({
                apply({ availableHeight, elements }) {
                    elements.floating.style.maxHeight = `${Math.min(availableHeight, window.innerHeight * 0.6)}px`;
                },
            }),
        ],
    });
    
    Object.assign(floatingRef.value.style, {
        left: `${x}px`,
        top: `${y}px`,
    });
};

watch(open, async (isOpen) => {
    if (isOpen) {
        await nextTick();
        updatePosition();
        
        if (triggerRef.value && floatingRef.value) {
            cleanup = autoUpdate(triggerRef.value, floatingRef.value, updatePosition);
        }
    } else {
        if (cleanup) {
            cleanup();
            cleanup = null;
        }
    }
});

onUnmounted(() => {
    if (cleanup) {
        cleanup();
    }
});
</script>

<template>
    <div class="relative">
        <div ref="triggerRef" @click="open = !open">
            <slot name="trigger" />
        </div>

        <!-- Full Screen Dropdown Overlay -->
        <div
            v-show="open"
            class="fixed inset-0 z-40"
            @click="open = false"
        ></div>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-show="open"
                ref="floatingRef"
                class="fixed z-50 rounded-md shadow-lg overflow-y-auto"
                :class="[widthClass, contentClasses]"
                style="display: none"
                @click="open = false"
            >
                <div
                    class="rounded-md ring-1 ring-black ring-opacity-5"
                    :class="contentClasses"
                >
                    <slot name="content" />
                </div>
            </div>
        </Transition>
    </div>
</template>
