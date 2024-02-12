import { trimLeft } from 'utils';
import routes from './routes.json';
import site from './site.json';

/**
 * Site routes are defined in /config/routes. The command `bin/console app.routes.generate` creates
 * the routes.json file. This class generates URLs using the routes defined in that file.
 */
class Router {
  /**
   * Generates and returns the url for the given name
   *
   * @param {string} name
   * @param {*} params
   * @param {string} type
   * @param {number} oid
   * @returns {string}
   */
  generate = (name, params = [], type = 'relative', oid = null) => {
    const route = routes[name];
    if (!route) {
      throw new Error(`Router: route "${name}" not found.`);
    }

    let { path } = route;
    Object.keys(route.keys).forEach((key) => {
      if (params[key] === undefined) {
        throw new Error(`Router: missing key "${key}" in route "${name}".`);
      }
      path = path.replace(`{${key}}`, params[key]);
    });

    if (type === 'absolute') {
      if (oid) {
        const matches = site.url.match(/(https?):\/\/(.*)/);
        const schema  = matches[1] || 'https';
        const host    = matches[2] || 'app.blocksedit.com';
        return `${schema}://${oid}.${host}${path}`;
      }
      return `${site.url}${path}`;
    }

    return path;
  };

  /**
   * @returns {string}
   */
  getSiteUrl = () => {
    return site.url;
  };

  /**
   * @param {string} path
   * @returns {`{{ assetsUri }}/${string}`}
   */
  asset = (path) => {
    if (window.assetManifest[path] !== undefined) {
      return window.assetManifest[path];
    }

    path = trimLeft(path, '/');
    if (path.indexOf('?') === -1) {
      path = `${path}?v=${site.assets_version}`;
    } else {
      path = `${path}&v=${site.assets_version}`;
    }

    return `${site.assets_url}/${path}`;
  };
}

export default new Router();
