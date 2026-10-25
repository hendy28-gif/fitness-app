export type Gender = 'male' | 'female' | 'other';

export type Member = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    date_of_birth: string | null;
    gender: Gender | null;
    height_cm: string | null;
    weight_kg: string | null;
    goals: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
};

export type MemberListItem = Member & {
    workouts_count: number;
    last_workout_on: string | null;
};

export type Exercise = {
    id: number;
    name: string;
};

export type LibraryExercise = Exercise & {
    category: string | null;
    workout_entries_count: number;
};

export type WorkoutSet = {
    id: number;
    workout_entry_id: number;
    position: number;
    reps: number;
    weight_kg: string;
};

export type WorkoutEntry = {
    id: number;
    workout_id: number;
    exercise_id: number;
    position: number;
    exercise: Exercise;
    sets: WorkoutSet[];
};

export type Workout = {
    id: number;
    member_id: number;
    performed_on: string;
    notes: string | null;
    entries: WorkoutEntry[];
    member?: Pick<Member, 'id' | 'name'>;
};

export type ExerciseOptionGroup = {
    category: string;
    names: string[];
};

export type WorkoutFormSet = {
    reps: number | string;
    weight_kg: number | string;
};

export type WorkoutFormEntry = {
    exercise: string;
    sets: WorkoutFormSet[];
};

export type SetSummary = {
    reps: number;
    weight_kg: string;
};

export type ExerciseSummary = {
    exercise_id: number;
    exercise: string;
    sessions: number;
    last_performed_on: string;
    last_sets: SetSummary[];
    best_weight_kg: string;
};

export type ProgressSession = {
    workout_id: number;
    performed_on: string;
    sets: SetSummary[];
    top_weight_kg: number;
    total_reps: number;
    volume_kg: number;
    estimated_one_rep_max_kg: number;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
};
