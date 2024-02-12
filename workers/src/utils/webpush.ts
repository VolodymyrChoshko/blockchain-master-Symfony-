import webpush, { SendResult } from 'web-push';
import fs from 'fs';

export interface Subscription {
    endpoint: string;
    expirationTime: number|null;
    keys: {
        p256dh: string;
        auth: string;
    };
    oid: number;
}

const publicKey = fs.readFileSync(`${__dirname}/../../config/certs/vapid.pub`);
const privateKey = fs.readFileSync(`${__dirname}/../../config/certs/vapid.priv`);
webpush.setVapidDetails(
    'https://blocksedit.com',
    publicKey.toString().trim(),
    privateKey.toString().trim(),
);

/**
 * @param webPushSubscription
 * @param body
 * @param url
 * @param icon
 */
export const sendNotification = (
    webPushSubscription: Subscription,
    body: string,
    url: string,
    icon: string = '/assets/images/appicon.png'
): Promise<SendResult> => {
    console.log('Sending notification', { body, url, icon });

    return webpush.sendNotification(webPushSubscription, JSON.stringify({
        body,
        url,
        icon,
    }));
};
