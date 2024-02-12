import router from '@assets/lib/router';
import config from '@src/utils/config';
import NotificationRepo from '@src/repo/NotificationRepo';
import { database } from '@src/utils/database';
import { Comment } from '@src/entity/Comment';
import { SqsConsumer, Message } from '@src/utils/SqsConsumer';
import { sendNotification, Subscription } from '@src/utils/webpush';

/**
 * @param comment
 * @param oid
 */
const getActivityUrl = (comment: Comment, oid: number): string => {
    return router.generate('build_comments_redirect', {
        id: comment.id,
    }, 'absolute', oid);
};

/**
 * @param message
 * @param id
 */
const handleMessage = async (message: Message, id: number): Promise<void> => {
    const notification = await NotificationRepo.findById(id);
    if (!notification) {
        console.error(`Notification ${message.Body} not found`);
        return;
    }

    if (notification.to.webPushSubscription) {
        const webPushSubscription = JSON.parse(notification.to.webPushSubscription) as Subscription;
        if (!webPushSubscription || Array.isArray(webPushSubscription)) {
            console.error('Malformed webPushSubscription', notification.to);
            return;
        }

        if (notification.action === 'mention' && notification.mention) {
            const mention = notification.mention;
            const comment = mention.comment;
            const name = comment.user.name;
            const msg = `${name} mentioned you in a comment.`;
            const url = getActivityUrl(comment, webPushSubscription.oid);
            const resp = await sendNotification(webPushSubscription, msg, url);
            if (resp.statusCode > 299) {
                console.error(`Received status code ${resp.statusCode}`);
            }
        } else if (notification.action === 'reply' && notification.comment) {
            const comment = notification.comment;
            const name = comment.user.name;
            const msg = `${name} replied to your comment.`;
            const url = getActivityUrl(comment, webPushSubscription.oid);
            const resp = await sendNotification(webPushSubscription, msg, url);
            if (resp.statusCode > 299) {
                console.error(`Received status code ${resp.statusCode}`);
            }
        }
    }
};

(async () => {
    await database.initialize();
    const consumer = new SqsConsumer<number>(config.sqs.queues.notifications.url);
    consumer.expectsNumber = true;
    consumer.receiveMessages(handleMessage);
    console.log(`Notification service is listening to ${config.sqs.queues.notifications.url}`);
})();
