import { Entity, PrimaryGeneratedColumn, CreateDateColumn, ManyToOne, JoinColumn, Column } from 'typeorm';
import { User } from '@src/entity/User';
import { Comment } from '@src/entity/Comment';
import { Mention } from '@src/entity/Mention';

@Entity({ name: 'not_notifications' })
export class Notification {
    @PrimaryGeneratedColumn({ name: 'not_id', type: 'bigint' })
    id: number;

    @Column({ name: 'not_action' })
    action: 'mention' | 'reply';

    @Column({ name: 'not_message' })
    message: string;

    @Column({ name: 'not_status' })
    status: 'read' | 'unread';

    @ManyToOne(() => User, user => user.id, {
        eager: true,
    })
    @JoinColumn({
        name: 'not_to_id',
    })
    to: User;

    @ManyToOne(() => User, user => user.id, {
        eager: true,
    })
    @JoinColumn({
        name: 'not_from_id',
    })
    from: User|null;

    @ManyToOne(() => Comment, comment => comment.id, {
        eager: true,
    })
    @JoinColumn({
        name: 'not_comment_id',
    })
    comment: Comment|null;

    @ManyToOne(() => Mention, mention => mention.id, {
        eager: true,
    })
    @JoinColumn({
        name: 'not_mention_id',
    })
    mention: Mention|null;

    @CreateDateColumn({
        name: 'not_date_created',
    })
    dateCreated: Date = new Date();
}
