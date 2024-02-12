import { useEffect } from 'react';
import sizeRepo from 'dashboard/store/sizeRepo';

const useSize = (id, type, ref) => {
  const mount = document.getElementById('mount');

  /**
   *
   */
  useEffect(() => {
    if (ref.current) {
      /**
       *
       */
      const handleResize = () => {
        const rect = ref.current.getBoundingClientRect();
        sizeRepo.setSize(id, {
          x:      rect.x,
          y:      rect.y,
          width:  rect.width,
          height: rect.height,
          type
        });
      };

      let observer;
      if (window.ResizeObserver !== undefined) {
        observer = new ResizeObserver(handleResize);
        observer.observe(ref.current);
      }

      mount.addEventListener('scroll', handleResize, false);
      window.addEventListener('be.dropped', handleResize, false);

      return () => {
        if (observer) {
          observer.disconnect();
        }
        mount.removeEventListener('scroll', handleResize);
        window.removeEventListener('be.dropped', handleResize);
      };
    }

    return () => {};
  }, [ref, id]);
};

export default useSize;
