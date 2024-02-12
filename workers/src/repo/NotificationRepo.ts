import { DataSource, Repository, EntityTarget } from 'typeorm';
import { database } from '@src/utils/database';
import { Notification } from '@src/entity/Notification';

export { Notification };

/**
 *
 */
class NotificationRepo {
    private repo: Repository<Notification>;

    constructor(connection: DataSource) {
        this.repo = connection.getRepository<Notification>(Notification);
    }

    /**
     * @param id
     */
    findById = async (id: number): Promise<Notification|null> => {
        return await this.repo.findOneBy({ id });
    }
}

export default new NotificationRepo(database);
