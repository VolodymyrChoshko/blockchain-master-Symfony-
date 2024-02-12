import AWS from 'aws-sdk';
import https from 'https';
import { pLimit } from 'plimit-lit';
import config from '@src/utils/config';

AWS.config.update({
    region: config.aws.region,
    credentials: {
        accessKeyId: config.aws.credentials.key,
        secretAccessKey: config.aws.credentials.secret,
    }
});

export type Message = AWS.SQS.Types.Message;
export type MessageHandler<T> = (m: Message, data: T) => Promise<void>;

/**
 *
 */
export class SqsConsumer<T> {
    protected readonly sqs: AWS.SQS;
    protected readonly maxMessagesPLimit = 3;
    protected readonly maxNumberOfMessages = 10;
    protected readonly waitTimeSeconds = 20;
    protected readonly pollingDelay = 5000;
    public expectsNumber: boolean = false;

    /**
     * @param queueUrl
     */
    constructor(protected readonly queueUrl: string) {
        this.sqs = new AWS.SQS({
            apiVersion: config.aws.version,
            httpOptions: {
                agent: new https.Agent({
                    keepAlive: true
                }),
            }
        });
    }

    /**
     * @param handler
     */
    public receiveMessages = (handler: MessageHandler<T>) => {
        const limit = pLimit(this.maxMessagesPLimit);
        const params = {
            MaxNumberOfMessages: this.maxNumberOfMessages,
            WaitTimeSeconds: this.waitTimeSeconds,
            QueueUrl: this.queueUrl
        };

        /**
         *
         */
        const poll = () => {
            this.sqs.receiveMessage(params, async (err, data) => {
                if (err) {
                    console.error(err);
                    setTimeout(poll, this.pollingDelay);
                    return;
                }

                if (data.Messages) {
                    const promises = [];
                    for (let i = 0; i < data.Messages.length; i++) {
                        const message = data.Messages[i];
                        promises.push(limit(() => this.handleMessage(handler, message)));
                    }

                    Promise.all(promises)
                        .catch((error) => {
                            console.error(error);
                        });
                }

                setTimeout(poll, this.pollingDelay);
            });
        };

        poll();
    }

    /**
     * @param handler
     * @param message
     */
    protected handleMessage = (handler: MessageHandler<T>, message: Message): Promise<void> => {
        return new Promise((resolve, reject) => {
            console.log(`Processing message ${message.MessageId}`);

            let data: any;
            try {
                data = JSON.parse(message.Body || '');
            } catch (error) {
                reject(new Error(`Failed to decode message body: ${message.Body}`));
                return;
            }
            if (this.expectsNumber) {
                data = parseInt(data, 10);
                if (isNaN(data)) {
                    reject(new Error(`Expected a number from message body: ${message.Body}`));
                    return;
                }
            }

            handler(message, data)
                .then(() => {
                    if (message.ReceiptHandle) {
                        this.sqs.deleteMessage({
                            QueueUrl: this.queueUrl,
                            ReceiptHandle: message.ReceiptHandle,
                        }, (err) => {
                            if (err) {
                                console.error('Error deleting message ', err);
                            }

                            resolve();
                        });
                    } else {
                        resolve();
                    }
                })
                .catch((error) => {
                    reject(error);
                });
        });
    }
}
