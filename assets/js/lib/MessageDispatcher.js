import EventDispatcher from 'lib/EventDispatcher';

/**
 * @property {Function} on
 * @property {Function} off
 * @property {Function} trigger
 */
class MessageDispatcher {
    /**
     * @param {array} wins
     * @param {boolean} debug
     */
    constructor(wins = [], debug = false) {
        EventDispatcher.mixin(this);

        this.windows      = wins;
        this.debug        = debug;
        this.targetOrigin = '*';

        window.addEventListener('message', this.handleMessage, false);
    }

    /**
     * @param {string} event
     * @param {*} args
     */
    send = (event, ...args) => {
        const data = {
            event,
            args
        };
        const message = '[app]' + JSON.stringify(data);

        this.windows.forEach((win) => {
            if (this.debug) {
                const who = win.frameElement ? 'frame' : 'parent';
                console.log(`MessageDispatcher: sending to ${who}`, data);
            }
            win.postMessage(message, this.targetOrigin);
        });
    };

    /**
     * @private
     * @param {MessageEvent} message
     */
    handleMessage = (message) => {
        if (typeof message.data === 'string' && message.data.indexOf('[app]{') === 0) {
            try {
                const data = JSON.parse(message.data.substr(5));
                if (this.debug) {
                    const who = window.frameElement ? 'frame' : 'parent';
                    console.log(`MessageDispatcher: received by ${who}`, data);
                }
                this.trigger(data.event, ...data.args);
            } catch (e) {
                console.error(e);
            }
        }
    }
}

export default MessageDispatcher;
