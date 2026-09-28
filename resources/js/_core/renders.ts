export const renders = {
    mode_list: 'list',
    mode_create: 'create',
    mode_edit: 'edit',
    mode_consultation: 'consultation',
} as const;

export type ModeVue = (typeof renders)[keyof typeof renders];
