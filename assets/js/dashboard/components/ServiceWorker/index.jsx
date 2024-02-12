import { useEffect } from 'react';
import { registerServiceWorker } from 'utils/serviceWorkers';

const ServiceWorker = () => {
  /**
   *
   */
  useEffect(() => {
    registerServiceWorker();
  }, []);

  return null;
};

export default ServiceWorker;
