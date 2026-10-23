<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import MemberForm from '@/components/members/MemberForm.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { destroy, edit, index, show, update } from '@/routes/members';
import type { Member } from '@/types';

const props = defineProps<{
    member: Member;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Members', href: index() },
        { title: props.member.name, href: show(props.member.id) },
        { title: 'Edit', href: edit(props.member.id) },
    ],
});
</script>

<template>
    <Head :title="`Edit ${member.name}`" />

    <div class="mx-auto w-full max-w-2xl space-y-10 p-4 md:p-6">
        <div>
            <Heading title="Edit member" :description="member.name" />

            <MemberForm
                :action="update.form(member.id)"
                :member="member"
                submit-label="Save changes"
            />
        </div>

        <div
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
        >
            <div class="space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">Delete member</p>
                <p class="text-sm">
                    This permanently removes {{ member.name }} and all of their
                    workout records.
                </p>
            </div>

            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive">Delete member</Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete {{ member.name }}?</DialogTitle>
                        <DialogDescription>
                            All of their workout records will be deleted too.
                            This cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">Cancel</Button>
                        </DialogClose>
                        <Form
                            v-bind="destroy.form(member.id)"
                            v-slot="{ processing }"
                        >
                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing"
                            >
                                Delete member
                            </Button>
                        </Form>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
