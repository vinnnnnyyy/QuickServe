<!-- 6bf4e8d2-2c6f-462c-a09d-e730002ef243 da9f82be-87bd-4814-8077-f546b35c36cf -->
# Realtime Order Animations via SSE + Lottie

## Assumptions

- Order status stored with `app/Services/JsonStorageService.php` in a JSON file.
- We’ll create per-order streams at `/orders/{orderId}/events`.
- Inertia + Vue 3 frontend; Lottie assets placed in `resources/js/Data/lottie/`.
- Minimal staff UI exists or will be stubbed with buttons to change status.

## Backend

### 1) Routes (`routes/web.php`)

```php
// SSE stream per order
Route::get('/orders/{order}/events', [OrderEventsController::class, 'stream']);

// Staff actions
Route::post('/orders/{order}/process', [OrderStatusController::class, 'markProcessing']);
Route::post('/orders/{order}/start-prep', [OrderStatusController::class, 'startPreparation']);
Route::post('/orders/{order}/ready', [OrderStatusController::class, 'markReady']);
```

### 2) SSE stream controller (`app/Http/Controllers/OrderEventsController.php`)

- Long-lived response with headers: `text/event-stream`, `Cache-Control: no-cache`, `X-Accel-Buffering: no`.
- Poll JSON storage every 1s; send event only when status changes.
```php
return response()->stream(function () use ($orderId) {
    @ob_end_flush(); @ob_implicit_flush(1); ignore_user_abort(true);
    $last = null;
    while (!connection_aborted()) {
        $status = $this->orders->getStatus($orderId); // JsonStorageService
        if ($status !== $last) {
            $payload = json_encode(['status' => $status]);
            echo "data: {$payload}\n\n"; // SSE default event
            @ob_flush(); flush();
            $last = $status;
        }
        sleep(1);
    }
}, 200, [
    'Content-Type' => 'text/event-stream',
    'Cache-Control' => 'no-cache, no-transform',
    'X-Accel-Buffering' => 'no',
]);
```


### 3) Status update controller (`app/Http/Controllers/OrderStatusController.php`)

```php
public function markProcessing(string $orderId) { $this->orders->setStatus($orderId, 'processing'); return response()->noContent(); }
public function startPreparation(string $orderId) { $this->orders->setStatus($orderId, 'preparing'); return response()->noContent(); }
public function markReady(string $orderId) { $this->orders->setStatus($orderId, 'ready'); return response()->noContent(); }
```

### 4) Extend JSON storage (`app/Services/JsonStorageService.php`)

- Add helpers `getStatus($orderId)` and `setStatus($orderId, $status)` operating on `storage/app/data/orders.json`.

## Frontend

### 5) Install Lottie and add assets

- `npm i lottie-web`
- Place files in `resources/js/Data/lottie/processing.json`, `preparing.json`, `ready.json` (placeholders from LottieFiles).

### 6) Animation component (`resources/js/Components/OrderStatusAnimation.vue`)

- Prop: `status`.
- Load animation via lottie-web; swap animation when `status` changes.
```vue
<template><div ref="container" style="width:280px;height:280px" /></template>
<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import lottie from 'lottie-web';
import processing from '@/Data/lottie/processing.json';
import preparing from '@/Data/lottie/preparing.json';
import ready from '@/Data/lottie/ready.json';
const props = defineProps({ status: { type: String, required: true } });
const container = ref();
let anim;
const map = { processing, preparing, ready };
const load = () => {
  if (anim) anim.destroy();
  anim = lottie.loadAnimation({ container: container.value, renderer: 'svg', loop: true, autoplay: true, animationData: map[props.status] || processing });
};
watch(() => props.status, load);
onMounted(load);
onBeforeUnmount(() => anim?.destroy());
</script>
```


### 7) SSE composable (`resources/js/composables/useOrderEvents.js`)

```js
import { ref, onBeforeUnmount } from 'vue';
export function useOrderEvents(orderId) {
  const status = ref('processing');
  const es = new EventSource(`/orders/${orderId}/events`);
  es.onmessage = (e) => { try { status.value = JSON.parse(e.data).status; } catch {} };
  es.onerror = () => {/* browser auto-reconnects */};
  onBeforeUnmount(() => es.close());
  return { status };
}
```

### 8) Customer page with toggle (e.g., `resources/js/Pages/Orders/Track.vue`)

```vue
<template>
  <div class="grid place-items-center p-6">
    <AnimationToggle v-model="showAnimation" />

    <OrderStatusMiniIcon v-if="!showAnimation" :status="status" />
    <OrderStatusAnimation v-else :status="status" />

    <p class="mt-4 text-xs text-gray-500">Status: {{ status }}</p>
  </div>
</template>
<script setup>
import { ref, watch, onMounted } from 'vue';
import { useOrderEvents } from '@/composables/useOrderEvents';
import OrderStatusAnimation from '@/Components/OrderStatusAnimation.vue';
import OrderStatusMiniIcon from '@/Components/OrderStatusMiniIcon.vue';
import AnimationToggle from '@/Components/AnimationToggle.vue';
const props = defineProps({ orderId: { type: String, required: true } });
const { status } = useOrderEvents(props.orderId);
const showAnimation = ref(true);
// persist preference
onMounted(() => {
  const saved = localStorage.getItem('showAnimation');
  if (saved !== null) showAnimation.value = saved === 'true';
});
watch(showAnimation, v => localStorage.setItem('showAnimation', String(v)));
</script>
```

### 9) Staff UI buttons (admin/barista page)

- Add POST actions to update status.
```vue
<script setup>
const props = defineProps({ orderId: String });
const act = (url) => fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.head.querySelector('meta[name=csrf-token]').content } });
</script>
<template>
  <div class="flex gap-2">
    <button @click="act(`/orders/${orderId}/process`)" class="btn">Process</button>
    <button @click="act(`/orders/${orderId}/start-prep`)" class="btn">Start Preparation</button>
    <button @click="act(`/orders/${orderId}/ready`)" class="btn">Mark Ready</button>
  </div>
</template>
```


## Assets (Lottie placeholders)

- Processing: `https://lottiefiles.com/` search “loading coffee” / “processing”.
- Preparing: search “coffee brewing” animation.
- Ready: search “checkmark” or “order ready”.

## Notes

- Consider signed SSE URLs so only the customer with the link can subscribe.
- If concurrency grows, consider Laravel Octane to avoid tying PHP-FPM workers.

### To-dos

- [ ] Add SSE and status update routes in routes/web.php
- [ ] Create OrderEventsController with SSE stream
- [ ] Create OrderStatusController for processing/preparing/ready
- [ ] Extend JsonStorageService with get/set order status
- [ ] Install lottie-web and add 3 placeholder animations
- [ ] Create OrderStatusAnimation.vue to render Lottie by status
- [ ] Create useOrderEvents composable to open EventSource
- [ ] Add customer Track.vue to display animation/status
- [ ] Add staff buttons to trigger status changes
- [ ] Optional: secure streams with signed URLs