import { Entity, Column, PrimaryGeneratedColumn, ManyToOne, JoinColumn } from 'typeorm';
import { Email } from '@src/entity/Email';
import { User } from '@src/entity/User';

@Entity({ name: 'cmt_comments' })
export class Comment {
    @PrimaryGeneratedColumn({ name: 'cmt_id', type: 'int' })
    id: number;

    @ManyToOne(() => Email, email => email.id, {
        eager: true,
    })
    @JoinColumn({
        name: 'cmt_ema_id',
    })
    email: Email;

    @ManyToOne(() => User, user => user.id, {
        eager: true,
    })
    @JoinColumn({
        name: 'cmt_usr_id',
    })
    user: User;

    @ManyToOne(() => Comment, comment => comment.id, {
        eager: false,
    })
    @JoinColumn({
        name: 'cmt_parent_id',
    })
    parent: Comment|null;

    @Column({
        name: 'cmt_content',
    })
    content: string;

    @Column({
        name: 'cmt_status',
    })
    status: string;

    @Column({
        name: 'cmt_block_id',
        type: 'smallint'
    })
    blockId: number;
}
