import { Entity, Column, PrimaryGeneratedColumn, ManyToOne, JoinColumn } from 'typeorm';
import { User } from '@src/entity/User';
import { Comment } from '@src/entity/Comment';

@Entity({ name: 'men_mentions' })
export class Mention {
    @PrimaryGeneratedColumn({ name: 'men_id', type: 'int' })
    id: number;

    @Column({ name: 'men_uuid' })
    uuid: string;

    @ManyToOne(() => User, user => user.id, {
        eager: true,
    })
    @JoinColumn({
        name: 'men_usr_id',
    })
    user: User;

    @ManyToOne(() => Comment, comment => comment.id, {
        eager: true,
    })
    @JoinColumn({
        name: 'men_comment_id',
    })
    comment: Comment;
}
