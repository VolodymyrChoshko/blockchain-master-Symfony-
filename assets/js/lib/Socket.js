import io from 'socket.io-client';

/**
 *
 */
export class Socket {
  /**
   * @param config
   */
  constructor(config) {
    this.config = config;
    this.listeners = [];
    this.socket = io(this.config.url, {
      path: this.config.path,
      autoConnect: false,
    });
  }

  /**
   * @return {null}
   */
  connect = () => {
    if (!this.socket.connected) {
      this.socket.on('connect', () => {
        for (let i = 0; i < this.listeners.length; i++) {
          const cb = this.listeners[i];
          if (cb) {
            cb(this);
          }
        }
      });

      this.socket.connect();
    }

    return this.socket;
  }

  /**
   *
   */
  disconnect = () => {
    if (this.socket && this.socket.connected) {
      this.socket.disconnect();
      this.socket = null;
    }
  }

  /**
   * @param event
   * @param cb
   */
  on = (event, cb) => {
    if (!this.socket || !this.socket.connected) {
      console.error(`Cannot subscribe to socket event ${event} because socket connected is not open.`);
      return;
    }
    this.socket.on(event, cb);
  }

  /**
   * @param event
   * @param data
   */
  emit = (event, data) => {
    if (!this.socket || !this.socket.connected) {
      console.error(`Cannot emit socket event ${event} because socket connected is not open.`);
      return;
    }
    this.socket.emit(event, data);
  }

  /**
   * @param cb
   */
  onConnect = (cb) => {
    this.listeners.push(cb);
    if (this.socket && this.socket.connected) {
      cb(this);
    }
  }
}
