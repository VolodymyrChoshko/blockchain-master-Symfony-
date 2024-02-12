/**
 *
 */
class EventDispatcher {
  /**
   * @param {*} base
   */
  static mixin = (base) => {
    base.eventDispatcher = new EventDispatcher();
    base.trigger = base.eventDispatcher.trigger.bind(base);
    base.on = base.eventDispatcher.on.bind(base);
    base.off = base.eventDispatcher.off.bind(base);
  };

  /**
   * Constructor
   */
  constructor() {
    this.listeners = {};
  }

  /**
   * @param {string} eventName
   * @param {Function} cb
   * @returns {Function}
   */
  on = (eventName, cb) => {
    if (!this.listeners[eventName]) {
      this.listeners[eventName] = [];
    }

    this.listeners[eventName].push(cb);

    return () => {
      this.off(eventName, cb);
    };
  };

  /**
   * @param {string} eventName
   * @param {Function} cb
   * @returns {boolean}
   */
  off = (eventName, cb) => {
    if (this.listeners[eventName]) {
      const index = this.listeners[eventName].indexOf(cb);
      if (index !== -1) {
        this.listeners[eventName].splice(index, 1);
        return true;
      }
    }

    return false;
  };

  /**
   * @param {string} eventName
   * @param {*} e
   */
  trigger = (eventName, ...e) => {
    if (this.listeners[eventName]) {
      for(let i = 0; i < this.listeners[eventName].length; i++) {
        const cb = this.listeners[eventName][i];
        if (cb(...e) === false) {
          break;
        }
      }
    }
  };
}

export default EventDispatcher;
