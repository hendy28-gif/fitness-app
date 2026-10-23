<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import MemberForm from '@/components/members/MemberForm.vue';
import { create, index, store } from '@/routes/members';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Members', href: index() },
            { title: 'New member', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New member" />

    <div class="mx-auto w-full max-w-2xl p-4 md:p-6">
        <Heading
            title="New member"
            description="Add a member you're training"
        />

        <MemberForm :action="store.form()" submit-label="Add member" />
    </div>
</template>
