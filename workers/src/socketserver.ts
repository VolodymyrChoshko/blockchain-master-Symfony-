import express, { Request, Response } from 'express';
import http from 'http';
import { Server } from 'socket.io';
import { createClient } from 'redis';
import cors from 'cors';
import config from '@src/utils/config';

const app = express();
app.use(cors());

app.get('/', (req: Request, res: Response) => {
    console.log('Got health check');
    res.send('ok');
});

const server = http.createServer(app);
const io = new Server(server, {
    path: config.socket.path,
    cors: {
        origin: '*'
    }
});

type RoomState = 'editing' | 'watching';

interface User {
    email: string;
    state: RoomState;
}

interface Room {
    html: string;
    users: User[];
}

class RoomUsers {
    protected readonly rooms: Record<string, Room> = {}

    /**
     * @param {string} name
     * @returns {*}
     */
    get = (name: string): Room | undefined => {
        return this.rooms[name];
    }

    /**
     * @param {string} name
     * @returns {boolean}
     */
    exists = (name: string): boolean => {
        return typeof this.rooms[name] !== 'undefined';
    }

    /**
     * @param {string} name
     * @param {string} html
     */
    create = (name: string, html: string): void => {
        this.rooms[name] = {
            html,
            users: []
        };
    }

    /**
     * @param {string} oldName
     * @param {string} newName
     * @param {string} html
     */
    clone = (oldName: string, newName: string, html: string): void => {
        this.rooms[newName] = Object.assign({}, this.rooms[oldName]);
        this.rooms[newName].html = html;
    }

    /**
     * @param {string} name
     * @returns {[]}
     */
    getUsers = (name: string): User[] => {
        if (!this.exists(name)) {
            return [];
        }
        return this.rooms[name].users;
    }

    /**
     * @param {string} name
     * @param {*} user
     */
    addUser = (name: string, user: User) => {
        this.rooms[name].users.push(user);
    }

    /**
     * @param {string} name
     * @param {*} user
     */
    removeUser = (name: string, user: User) => {
        const index = this.rooms[name].users.findIndex((u) => u.email === user.email);
        if (index !== -1) {
            this.rooms[name].users.splice(index, 1);
        }
        if (this.rooms[name].users.length === 0) {
            delete this.rooms[name];
        }
    }

    /**
     * @param {string} name
     * @param {*} user
     * @param {string} state
     * @returns {boolean}
     */
    updateUserState = (name: string, user: User, state: RoomState): boolean => {
        const index = this.rooms[name].users.findIndex((u) => u.email === user.email);
        if (index !== -1) {
            this.rooms[name].users[index].state = state;
            return true;
        }

        return false;
    }

    /**
     * @param {string} name
     * @returns {string}
     */
    getHTML = (name: string): string => {
        return this.rooms[name].html;
    }

    /**
     * @param {string} name
     * @param {string} html
     */
    updateHTML = (name: string, html: string): void => {
        this.rooms[name].html = html;
    }
}

const rooms = new RoomUsers();

io.on('connection', (socket) => {
    /**
     *
     */
    socket.on('listUsers', (room) => {
        socket.emit('listUsers', {
            room,
            users: rooms.getUsers(room)
        });
    });

    /**
     *
     */
    socket.on('kick', (room) => {
        if (io.sockets.adapter.rooms.get(room)) {
            const socketRooms = io.sockets.adapter.rooms.get(room);
            if (socketRooms) {
                socketRooms.forEach((socketID) => {
                    io.to(socketID).emit('kick');
                });
            }
        }
    });

    /**
     *
     */
    socket.on('join', (m) => {
        const { user, html } = m;
        let { room } = m;

        if (!user) {
            return;
        }

        user.state = 'watching';
        if (!rooms.exists(room)) {
            rooms.create(room, html);
            rooms.addUser(room, user);
        } else {
            rooms.addUser(room, user);
            // socket.emit('html', rooms.getHTML(room));
        }

        socket.join(room);
        socket.emit('joined', rooms.get(room));
        socket.in(room).emit('joined', rooms.get(room));

        /**
         *
         */
        socket.on('updateState', (state) => {
            if (rooms.updateUserState(room, user, state)) {
                socket.in(room).emit('updateUsers', rooms.getUsers(room));
            }
        });

        /**
         *
         */
        socket.on('html', (h) => {
            rooms.updateHTML(room, h);
            socket.in(room).emit('html', h);
        });

        /**
         *
         */
        socket.on('sendComment', (comment) => {
            socket.in(room).emit('sendComment', comment);
        });

        /**
         *
         */
        socket.on('updateComment', (comment) => {
            socket.in(room).emit('updateComment', comment);
        });

        /**
         *
         */
        socket.on('deleteComment', (id) => {
            socket.in(room).emit('deleteComment', id);
        });

        /**
         *
         */
        socket.on('updateSkinTone', (data) => {
            socket.in(room).emit('updateSkinTone', data);
        });

        /**
         *
         */
        socket.on('switchRoom', (msg) => {
            rooms.clone(room, msg.room, msg.html);
            socket.in(room).emit('switchRoom', msg);
            socket.leave(room);
            socket.join(msg.room);

            rooms.removeUser(room, user);
            ({ room } = msg);
        });

        /**
         *
         */
        socket.on('disconnect', () => {
            rooms.removeUser(room, user);
            if (rooms.exists(room)) {
                socket.in(room).emit('left', rooms.getUsers(room));
            }
        });
    });

    /**
     *
     */
    socket.on('subNotifications', (uid) => {
        socket.data.uid = uid;
    });
});

const redisClient = createClient({
    url: `redis://${config.redis.host}:${config.redis.port}`,
});

redisClient.on('error', (error: any) => {
    console.error(error);
    process.exit(1);
});

redisClient.on('connect', async () => {
    await redisClient.subscribe('notifications', (message) => {
        const notification = JSON.parse(message);

        for (let s of io.of('/').sockets) {
            let socket = s[1];
            if (socket.data.uid && socket.data.uid === notification.to.id) {
                socket.emit('notification', notification);
            }
        }
    });

    await redisClient.subscribe('notification-delete', (message) => {
        const details = JSON.parse(message);

        for (let s of io.of('/').sockets) {
            let socket = s[1];
            if (socket.data.uid && socket.data.uid === details.to) {
                socket.emit('notification-delete', details.id);
            }
        }
    });
});

(async () => {
    await redisClient.connect();
    const serverURL = new URL(config.socket.url);
    server.listen(parseInt(serverURL.port, 10), '0.0.0.0', () => {
        console.log(`Listening on 0.0.0.0:${serverURL.port}`);
    });
})();

export {};
