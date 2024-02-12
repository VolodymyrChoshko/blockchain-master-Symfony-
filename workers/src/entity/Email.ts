import { Entity, Column, PrimaryGeneratedColumn, ManyToOne, JoinColumn } from 'typeorm';
import { Template } from '@src/entity/Template';

@Entity({ name: 'ema_emails' })
export class Email {
    @PrimaryGeneratedColumn({ name: 'ema_id', type: 'int' })
    id: number;

    @Column({
        name: 'ema_title',
    })
    title: string;

    @ManyToOne(() => Template, template => template.id, {
        eager: true,
    })
    @JoinColumn({
        name: 'ema_tmp_id',
    })
    template: Template;
}
