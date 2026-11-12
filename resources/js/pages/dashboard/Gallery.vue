<script setup lang="ts">
import FlashMessages from '@/components/FlashMessages.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useLocale } from '@/composables/useLocale';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem, GalleryPhoto } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ImageIcon, Images, LoaderCircle, Save, Star, Trash2, ZoomIn } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

const props = defineProps<{
    business: { id: number; name: string; slug: string };
    photos: GalleryPhoto[];
    max_photos: number;
}>();

const { t, num } = useLocale();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('panel.dashboard.title'), href: '/dashboard' },
    { title: t('panel.gallery.title'), href: '/gallery' },
]);

const fileInput = ref<HTMLInputElement | null>(null);
const uploading = ref(false);
const savingCaption = ref<number | null>(null);
const lightbox = ref<number | null>(null);

const captions = reactive<Record<number, string>>(
    Object.fromEntries(props.photos.map((photo) => [photo.id, photo.caption ?? ''])),
);

const canUpload = computed(() => props.photos.length < props.max_photos);
const remaining = computed(() => props.max_photos - props.photos.length);

const lightboxPhoto = computed<GalleryPhoto | null>(() =>
    lightbox.value === null ? null : props.photos[lightbox.value] ?? null,
);

function chooseFiles(): void {
    fileInput.value?.click();
}

function upload(event: Event): void {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []).slice(0, Math.max(0, remaining.value));

    if (files.length === 0) return;

    uploading.value = true;

    const form = new FormData();
    files.forEach((file) => form.append('photos[]', file));

    router.post(route('gallery.store'), form, {
        preserveScroll: true,
        onFinish: () => {
            uploading.value = false;
            if (fileInput.value) fileInput.value.value = '';
        },
    });
}

function saveCaption(photo: GalleryPhoto): void {
    savingCaption.value = photo.id;

    router.put(route('gallery.update', photo.id), { caption: captions[photo.id] ?? null }, { preserveScroll: true, onFinish: () => (savingCaption.value = null) });
}

function makeCover(photo: GalleryPhoto): void {
    router.patch(route('gallery.cover', photo.id), {}, { preserveScroll: true });
}

function destroy(photo: GalleryPhoto): void {
    if (!confirm(t('panel.common.confirmDeleteName', { name: photo.caption ?? '—' }))) return;

    router.delete(route('gallery.destroy', photo.id), { preserveScroll: true });
}

function openLightbox(index: number): void {
    lightbox.value = index;
}

function closeLightbox(): void {
    lightbox.value = null;
}

function stepLightbox(step: number): void {
    if (lightbox.value === null || props.photos.length === 0) return;

    lightbox.value = (lightbox.value + step + props.photos.length) % props.photos.length;
}
</script>

<template>
    <Head :title="t('panel.gallery.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex-1 space-y-6 p-4 md:p-6">
            <FlashMessages />

            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="flex items-center gap-2 text-xl font-bold">
                        <Images class="size-5 text-primary" />
                        {{ t('panel.gallery.title') }}
                    </h1>
                    <p class="text-sm text-muted-foreground">{{ t('panel.gallery.subtitle', { name: business.name }) }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs text-muted-foreground">
                        {{ t('panel.gallery.counter', { count: num(photos.length), max: num(max_photos) }) }}
                    </span>
                    <Button size="sm" :disabled="!canUpload || uploading" @click="chooseFiles">
                        <LoaderCircle v-if="uploading" class="ml-1 size-4 animate-spin" />
                        <ImageIcon v-else class="ml-1 size-4" />
                        {{ uploading ? t('panel.gallery.adding') : t('panel.gallery.add') }}
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <Link :href="route('businesses.show', { business: business.slug })" target="_blank">
                            {{ t('panel.gallery.publicLink') }}
                        </Link>
                    </Button>
                </div>

                <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" multiple class="hidden" @change="upload" />
            </div>

            <p class="text-xs text-muted-foreground">{{ t('panel.gallery.hint') }}</p>

            <div v-if="photos.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="(photo, index) in photos" :key="photo.id" class="group overflow-hidden rounded-xl border bg-background">
                    <div class="relative aspect-video overflow-hidden">
                        <img :src="photo.url" :alt="photo.caption ?? ''" class="size-full object-cover" loading="lazy" />

                        <Badge v-if="photo.is_cover" class="absolute top-2 start-2 gap-1">
                            <Star class="size-3" />
                            {{ t('panel.gallery.coverBadge') }}
                        </Badge>

                        <button
                            type="button"
                            class="absolute inset-0 flex items-center justify-center bg-black/0 opacity-0 transition-all hover:bg-black/30 hover:opacity-100"
                            :aria-label="t('panel.gallery.publicLink')"
                            @click="openLightbox(index)"
                        >
                            <ZoomIn class="size-6 text-white" />
                        </button>
                    </div>

                    <div class="space-y-2 p-3">
                        <Input v-model="captions[photo.id]" :placeholder="t('panel.gallery.captionPlaceholder')" class="h-8 text-xs" />
                        <div class="flex items-center gap-1">
                            <Button size="sm" variant="outline" class="h-7 gap-1 text-xs" :disabled="savingCaption === photo.id" @click="saveCaption(photo)">
                                <Save class="size-3" />
                                {{ t('panel.gallery.saveCaption') }}
                            </Button>
                            <Button v-if="!photo.is_cover" size="sm" variant="ghost" class="h-7 gap-1 text-xs" @click="makeCover(photo)">
                                <Star class="size-3" />
                                {{ t('panel.gallery.makeCover') }}
                            </Button>
                            <Button size="sm" variant="ghost" class="ms-auto h-7 text-destructive" @click="destroy(photo)">
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="rounded-xl border border-dashed px-4 py-12 text-center">
                <Images class="mx-auto mb-3 size-8 text-muted-foreground" />
                <p class="font-medium">{{ t('panel.gallery.emptyTitle') }}</p>
                <p class="mt-1 text-sm text-muted-foreground">{{ t('panel.gallery.emptyHint') }}</p>
            </div>

            <Dialog :open="lightbox !== null" @update:open="(open: boolean) => (open ? null : closeLightbox())">
                <DialogContent class="max-w-3xl border-none bg-transparent p-2 shadow-none sm:max-w-3xl">
                    <DialogHeader>
                        <DialogTitle class="text-sm text-white">{{ lightboxPhoto?.caption ?? '' }}</DialogTitle>
                    </DialogHeader>

                    <div v-if="lightboxPhoto" class="space-y-3">
                        <img :src="lightboxPhoto.url" :alt="lightboxPhoto.caption ?? ''" class="max-h-[70vh] w-full rounded-lg object-contain" />

                        <div class="flex items-center justify-center gap-2">
                            <Button size="sm" variant="secondary" @click="stepLightbox(-1)">{{ t('panel.gallery.prev') }}</Button>
                            <Button size="sm" variant="secondary" @click="closeLightbox()">{{ t('panel.gallery.close') }}</Button>
                            <Button size="sm" variant="secondary" @click="stepLightbox(1)">{{ t('panel.gallery.next') }}</Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
