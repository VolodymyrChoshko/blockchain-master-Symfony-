import { Entity, Column, PrimaryGeneratedColumn, ManyToOne, JoinColumn } from 'typeorm';
import { User } from '@src/entity/User';
import { Organization } from '@src/entity/Organization';

@Entity({ name: 'tmp_templates' })
export class Template {
    @PrimaryGeneratedColumn({ name: 'tmp_id', type: 'int' })
    id: number;

    @Column({
        name: 'tmp_title',
    })
    title: string;

    @Column({
        name: 'tmp_version',
    })
    version: number;

    @ManyToOne(() => User, user => user.id, {
        eager: true,
    })
    @JoinColumn({
        name: 'tmp_usr_id',
    })
    user: User;

    @ManyToOne(() => Organization, organization => organization.id, {
        eager: true,
    })
    @JoinColumn({
        name: 'tmp_org_id',
    })
    organization: Organization;
}
