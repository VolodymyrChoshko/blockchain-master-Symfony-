import fs from 'fs';
import path from 'path';
import YAML from 'yaml';

export interface Config {
    pdo: {
        adapter: 'mysql';
        port: number;
        host: string;
        name: string;
        username: string;
        password: string;
    };
    aws: {
        version: string;
        region: string;
        credentials: {
            key: string;
            secret: string;
        }
    };
    sqs: {
        queues: {
            layoutsUpgrade: {
                url: string;
            };
            libraryThumbnails: {
                url: string;
            };
            notifications: {
                url: string;
            };
        }
    };
    socket: {
        url: string;
        path: string;
    };
    redis: {
        host: string;
        port: number;
    }
}

const configFile = path.resolve(`${__dirname}/../../config/config.yaml`);
const file   = fs.readFileSync(configFile, 'utf8');
const config = YAML.parse(file);
const parameters = config.parameters as Config;

export default parameters;
