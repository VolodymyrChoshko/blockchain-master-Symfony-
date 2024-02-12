import { SqsConsumer, Message } from '@src/utils/SqsConsumer';
import config from '@src/utils/config';
import path from 'path';
import { spawn } from 'child_process';

(async () => {
    interface MessageData {
        desktop: number;
        mobile: number;
    }

    const bin = path.resolve(__dirname, '../../bin/console');

    /**
     * @param message
     * @param data
     */
    const handleMessage = async (message: Message, data: MessageData): Promise<void> => {
        return new Promise((resolve, reject) => {
            const process = spawn(bin, [
                'section:library:thumbnails',
                `-desktop=${data.desktop}`,
                `-mobile=${data.mobile}`
            ]);
            process.stdout.on('data', (data) => {
                console.log(data.toString());
            });
            process.stderr.on('data', (data) => {
                console.error(data.toString());
            });
            process.on('error', (error) => {
                console.error(error);
                reject(error);
            });
            process.on('close', (code) => {
                if (code === 1) {
                    reject(new Error('System error.'));
                } else {
                    resolve();
                }
            });
        });
    };

    const consumer = new SqsConsumer<MessageData>(config.sqs.queues.libraryThumbnails.url);
    consumer.receiveMessages(handleMessage);
    console.log(`Pin thumbnail service is listening to ${config.sqs.queues.libraryThumbnails.url}`);
})();
