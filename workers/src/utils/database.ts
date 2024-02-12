import { DataSource } from 'typeorm';
import { Notification } from '@src/entity/Notification';
import { User } from '@src/entity/User';
import { Comment } from '@src/entity/Comment';
import { Email } from '@src/entity/Email';
import { Template } from '@src/entity/Template';
import { Organization } from '@src/entity/Organization';
import { Mention } from '@src/entity/Mention';
import config from '@src/utils/config';

const entities = [
    User,
    Organization,
    Template,
    Email,
    Comment,
    Mention,
    Notification,
];

export const database = new DataSource({
    type: config.pdo.adapter,
    host: config.pdo.host,
    port: config.pdo.port,
    username: config.pdo.username,
    password: config.pdo.password,
    database: config.pdo.name,
    synchronize: false,
    logging: false,
    subscribers: [],
    migrations: [],
    entities,
}) as DataSource;
