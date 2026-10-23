<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Member } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

defineProps<{
    action: RouteFormDefinition<'post'>;
    member?: Member;
    submitLabel: string;
}>();

const textareaClass = 'field w-full py-2.5';
</script>

<template>
    <Form v-bind="action" class="space-y-6" v-slot="{ errors, processing }">
        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input
                id="name"
                name="name"
                :default-value="member?.name"
                required
                autofocus
                placeholder="Full name"
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    :default-value="member?.email ?? ''"
                    placeholder="member@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="phone">Phone</Label>
                <Input
                    id="phone"
                    type="tel"
                    name="phone"
                    :default-value="member?.phone ?? ''"
                    placeholder="Phone number"
                />
                <InputError :message="errors.phone" />
            </div>

            <div class="grid gap-2">
                <Label for="date_of_birth">Date of birth</Label>
                <Input
                    id="date_of_birth"
                    type="date"
                    name="date_of_birth"
                    :default-value="member?.date_of_birth ?? ''"
                />
                <InputError :message="errors.date_of_birth" />
            </div>

            <div class="grid gap-2">
                <Label for="gender">Gender</Label>
                <select id="gender" name="gender" class="h-11 w-full field">
                    <option value="" :selected="!member?.gender">
                        Not specified
                    </option>
                    <option value="male" :selected="member?.gender === 'male'">
                        Male
                    </option>
                    <option
                        value="female"
                        :selected="member?.gender === 'female'"
                    >
                        Female
                    </option>
                    <option
                        value="other"
                        :selected="member?.gender === 'other'"
                    >
                        Other
                    </option>
                </select>
                <InputError :message="errors.gender" />
            </div>

            <div class="grid gap-2">
                <Label for="height_cm">Height (cm)</Label>
                <Input
                    id="height_cm"
                    type="number"
                    step="0.1"
                    inputmode="decimal"
                    name="height_cm"
                    :default-value="member?.height_cm ?? ''"
                />
                <InputError :message="errors.height_cm" />
            </div>

            <div class="grid gap-2">
                <Label for="weight_kg">Body weight (kg)</Label>
                <Input
                    id="weight_kg"
                    type="number"
                    step="0.1"
                    inputmode="decimal"
                    name="weight_kg"
                    :default-value="member?.weight_kg ?? ''"
                />
                <InputError :message="errors.weight_kg" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="goals">Goals</Label>
            <textarea
                id="goals"
                name="goals"
                rows="3"
                :class="textareaClass"
                placeholder="e.g. Lose 5 kg, squat 100 kg by June"
                >{{ member?.goals ?? '' }}</textarea>
            <InputError :message="errors.goals" />
        </div>

        <div class="grid gap-2">
            <Label for="notes">Notes</Label>
            <textarea
                id="notes"
                name="notes"
                rows="3"
                :class="textareaClass"
                placeholder="Injuries, limitations, preferences…"
                >{{ member?.notes ?? '' }}</textarea>
            <InputError :message="errors.notes" />
        </div>

        <Button type="submit" :disabled="processing">{{ submitLabel }}</Button>
    </Form>
</template>
