import { Entity, Column, PrimaryGeneratedColumn } from 'typeorm';

@Entity({ name: 'usr_users' })
export class User {
    @PrimaryGeneratedColumn({ name: 'usr_id', type: 'int' })
    id: number;

    @Column({
        name: 'usr_email',
    })
    email: string;

    @Column({
        name: 'usr_name',
    })
    name: string;

    @Column({
        name: 'usr_job',
    })
    job: string;

    @Column({
        name: 'usr_organization',
    })
    organization: string;

    @Column({
        name: 'usr_avatar',
    })
    avatar: string;

    @Column({
        name: 'usr_avatar_60',
    })
    avatar60: string;

    @Column({
        name: 'usr_avatar_120',
    })
    avatar120: string;

    @Column({
        name: 'usr_avatar_240',
    })
    avatar240: string;

    @Column({
        name: 'usr_is_site_admin',
    })
    isSiteAdmin: boolean;

    @Column({
        name: 'usr_timezone',
    })
    timezone: string;

    @Column({
        name: 'usr_is_notifications_enabled',
    })
    isNotificationsEnabled: boolean;

    @Column({
        name: 'usr_is_emails_enabled',
    })
    isEmailsEnabled: boolean;

    @Column({
        name: 'usr_web_push_subscription',
    })
    webPushSubscription: string;
}
