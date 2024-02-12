import axios, { CancelToken } from 'axios';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const postBusy = [];

/**
 * @param {string} url
 * @returns {Promise<any> | Promise | Promise}
 */
const get = (url) => {
  return axios.get(url)
    .then(resp => resp.data);
};

/**
 * @param {string} url
 * @param {*} body
 * @param {*} config
 * @param {*} cancelToken
 * @param {string} name
 * @returns {Promise<any> | Promise | Promise}
 */
const post = (url, body = {}, config = {}, cancelToken = null, name = '') => {
  const options = Object.assign({}, config);
  if (cancelToken) {
    options.cancelToken = cancelToken.token;
  }

  postBusy.push(name || url);

  return axios.post(url, body, options)
    .then((resp) => {
      const i = postBusy.indexOf(name || url);
      if (i !== -1) {
        postBusy.splice(i, 1);
      }
      return resp.data;
    })
    .catch((error) => {
      const i = postBusy.indexOf(name || url);
      if (i !== -1) {
        postBusy.splice(i, 1);
      }
      if (!axios.isCancel(error)) {
        throw error;
      }
    });
};

/**
 * @param {string} url
 * @param {*} body
 * @param {*} config
 * @returns {Promise<any> | Promise | Promise}
 */
const put = (url, body = {}, config = {}) => {
  return axios.put(url, body, config)
    .then(resp => resp.data);
};

/**
 * @param {string} method
 * @param {string} url
 * @param {*} data
 * @returns {Promise<any> | Promise | Promise}
 */
const req = (method, url, data = {}) => {
  return axios.request({
    url,
    data,
    method
  }).then(resp => resp.data);
};

/**
 * @returns {*}
 */
const getCancelToken = () => {
  return CancelToken.source();
};

/**
 * @param name
 * @returns {boolean}
 */
const isPostBusy = (name) => {
  return postBusy.indexOf(name) !== -1;
};

export default {
  get,
  req,
  post,
  put,
  isPostBusy,
  getCancelToken
};
