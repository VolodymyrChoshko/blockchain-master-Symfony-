class Log {
  /**
   *
   */
  start = () => {
    this.oError = console.error;
    console.error = this.error;
  };

  /**
   * @param args
   */
  error = (...args) => {
    this.oError(...args);
    this.sendBeacon(args);
  };

  /**
   * @param {array} args
   */
  sendBeacon = (args) => {
    let stack;
    if (args[0] && args[0] instanceof Error) {
      // eslint-disable-next-line prefer-destructuring
      ({ stack } = args[0]);
      args[0]    = args[0].toString();
    } else {
      const e    = new Error();
      ({ stack } = e);
    }

    const data = new FormData();
    data.append('level', 'error');
    data.append('location', 'builder');
    data.append('stack', stack);
    data.append('message', JSON.stringify(args));

    if (navigator.sendBeacon) {
      navigator.sendBeacon('/ajax/logs', data);
    } else {
      const xhr = new XMLHttpRequest();
      xhr.open('POST', '/ajax/logs');
      xhr.send(data);
    }
  };
}

export default Log;
