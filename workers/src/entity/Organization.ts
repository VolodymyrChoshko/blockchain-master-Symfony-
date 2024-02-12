import { Entity, Column, PrimaryGeneratedColumn } from 'typeorm';

@Entity({ name: 'org_organization' })
export class Organization {
    @PrimaryGeneratedColumn({ name: 'org_id', type: 'int' })
    id: number;

    @Column({
        name: 'org_name',
    })
    name: string;

    @Column({
        name: 'org_token',
    })
    token: string;
}
